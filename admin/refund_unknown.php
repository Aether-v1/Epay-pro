<?php
/**
 * UNKNOWN 退款管理后台
 * 仅管理员可访问，支持人工确认 UNKNOWN 状态退款
 */
include("../includes/common.php");
// Batch D Phase1: RBAC permission check
require_permission('order.refund');
if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");

// CSRF Token
if(empty($_SESSION['csrf_token'])){
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// 处理人工确认
if(isset($_POST['action']) && $_POST['action'] == 'confirm'){
    // CSRF 校验
    if(!isset($_POST['csrf_token']) || !hash_equals($csrf_token, $_POST['csrf_token'])){
        exit(json_encode(['code'=>-1,'msg'=>'CSRF Token 错误']));
    }
    
    $refund_no = trim($_POST['refund_no']);
    $confirm_status = (int)$_POST['confirm_status']; // 30=SUCCESS, 40=REJECTED
    $reason = trim($_POST['reason']);
    $evidence = trim($_POST['evidence']);
    
    if(empty($refund_no) || empty($reason) || empty($evidence)){
        exit(json_encode(['code'=>-1,'msg'=>'退款单号、原因和证据不能为空']));
    }
    if(!in_array($confirm_status, [30, 40])){
        exit(json_encode(['code'=>-1,'msg'=>'无效的确认状态']));
    }
    
    // 调用 Order::confirmUnknown
    $result = \lib\Order::confirmUnknown($refund_no, $confirm_status, $reason, $evidence, $conf['admin_user']);
    
    // 记录审计日志
    $DB->insert('log', [
        'uid'=>0,
        'type'=>'UNKNOWN退款确认',
        'date'=>'NOW()',
        'ip'=>$clientip,
        'city'=>'',
    ]);
    
    exit(json_encode($result));
}

// 获取 UNKNOWN 退款列表
$list = $DB->getAll("SELECT r.*, u.user as username, o.name as order_name, o.realmoney as order_money 
    FROM pre_refundorder r 
    LEFT JOIN pre_user u ON r.uid=u.uid 
    LEFT JOIN pre_order o ON r.trade_no=o.trade_no 
    WHERE r.status=50 
    ORDER BY r.addtime DESC 
    LIMIT 100");

$title='UNKNOWN退款管理';
include './head.php';
?>
<div class="container">
    <div class="panel panel-primary">
        <div class="panel-heading">
            <h3 class="panel-title">UNKNOWN 退款管理（结果未知，禁止再次退款）</h3>
        </div>
        <div class="panel-body">
            <div class="alert alert-warning">
                <strong>警告：</strong>以下退款请求结果未知，可能已在上游完成退款。在确认前请务必与上游对账，禁止盲目确认或再次发起退款。
            </div>
            <?php if(empty($list)): ?>
            <p class="text-center text-muted">暂无 UNKNOWN 状态退款记录</p>
            <?php else: ?>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>退款单号</th>
                        <th>商户订单号</th>
                        <th>商户</th>
                        <th>退款金额</th>
                        <th>冻结金额</th>
                        <th>错误信息</th>
                        <th>创建时间</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($list as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['refund_no']); ?></td>
                        <td><?php echo htmlspecialchars($row['trade_no']); ?></td>
                        <td><?php echo htmlspecialchars($row['username'] ?: $row['uid']); ?></td>
                        <td><?php echo $row['money']; ?> 元</td>
                        <td><?php echo $row['reserved_money']; ?> 元</td>
                        <td><?php echo htmlspecialchars($row['error_msg'] ?: '-'); ?></td>
                        <td><?php echo $row['addtime']; ?></td>
                        <td>
                            <button class="btn btn-success btn-xs" onclick="showConfirm('<?php echo $row['refund_no']; ?>', 30)">确认成功</button>
                            <button class="btn btn-danger btn-xs" onclick="showConfirm('<?php echo $row['refund_no']; ?>', 40)">确认拒绝</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- 确认弹窗 -->
<div id="confirmModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title">人工确认 UNKNOWN 退款</h4>
            </div>
            <div class="modal-body">
                <input type="hidden" id="confirm_refund_no">
                <input type="hidden" id="confirm_status">
                <input type="hidden" id="csrf_token" value="<?php echo $csrf_token; ?>">
                <div class="form-group">
                    <label>处置原因 *</label>
                    <textarea id="confirm_reason" class="form-control" rows="2" placeholder="请填写处置原因，如：上游对账确认退款成功"></textarea>
                </div>
                <div class="form-group">
                    <label>上游对账证据 *</label>
                    <textarea id="confirm_evidence" class="form-control" rows="2" placeholder="请填写上游退款单号或对账证据"></textarea>
                </div>
                <div class="alert alert-info" id="confirm_hint"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" onclick="submitConfirm()">确认提交</button>
            </div>
        </div>
    </div>
</div>

<script>
function showConfirm(refund_no, status){
    $('#confirm_refund_no').val(refund_no);
    $('#confirm_status').val(status);
    $('#confirm_reason').val('');
    $('#confirm_evidence').val('');
    if(status == 30){
        $('#confirm_hint').html('<strong>确认成功：</strong>将完成资金扣减，增加累计退款金额。请确保上游确实已退款。');
    } else {
        $('#confirm_hint').html('<strong>确认拒绝：</strong>将释放冻结资金和退款额度。请确保上游确实未退款。');
    }
    $('#confirmModal').modal('show');
}
function submitConfirm(){
    var reason = $('#confirm_reason').val().trim();
    var evidence = $('#confirm_evidence').val().trim();
    if(!reason || !evidence){ alert('原因和证据不能为空'); return; }
    $.post('./refund_unknown.php', {
        action: 'confirm',
        refund_no: $('#confirm_refund_no').val(),
        confirm_status: $('#confirm_status').val(),
        reason: reason,
        evidence: evidence,
        csrf_token: $('#csrf_token').val()
    }, function(data){
        if(data.code == 0 || data.code == 30 || data.code == 40){
            alert('确认成功');
            location.reload();
        } else {
            alert('确认失败：' + (data.msg || '未知错误'));
        }
    }, 'json');
}
</script>
<?php include './foot.php'; ?>
