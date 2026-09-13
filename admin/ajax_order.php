<?php
include("../includes/common.php");
// Batch C Phase2: 订单管理权限检查
require_permission('order.view');
// A3: SQL 注入防护 - column 白名单和安全 WHERE 构建
$a3_allowed_columns = ['uid','type','channel','subchannel','trade_no','out_trade_no','api_trade_no','name','money','realmoney','getmoney','profitmoney','refundmoney','status','settle','addtime','endtime','domain','ip','buyer'];

function a3_build_order_where($post, &$params){
    global $a3_allowed_columns;
    $sql = " 1=1";
    $params = [];
    
    if(!empty($post['uid'])){
        $sql .= " AND A.uid=:uid";
        $params[':uid'] = intval($post['uid']);
    }
    if(!empty($post['type'])){
        $sql .= " AND A.type=:type";
        $params[':type'] = intval($post['type']);
    }elseif(!empty($post['channel'])){
        $sql .= " AND A.channel=:channel";
        $params[':channel'] = intval($post['channel']);
    }elseif(!empty($post['subchannel'])){
        $sql .= " AND A.subchannel=:subchannel";
        $params[':subchannel'] = intval($post['subchannel']);
    }elseif(!empty($post['applyid'])){
        $sql .= " AND A.subchannel IN (SELECT id FROM pre_subchannel WHERE apply_id=:applyid)";
        $params[':applyid'] = intval($post['applyid']);
    }
    
    if(!empty($post['dstatus'])){
        if(substr($post['dstatus'], 0, 6) == 'settle'){
            $sql .= " AND A.settle=:dstatus";
            $params[':dstatus'] = intval(substr($post['dstatus'], 7));
        }else{
            $sql .= " AND A.status=:dstatus";
            $params[':dstatus'] = intval($post['dstatus']);
        }
    }
    
    if(!empty($post['starttime'])){
        $sql .= " AND A.addtime>=:starttime";
        $params[':starttime'] = $post['starttime'] . ' 00:00:00';
    }
    if(!empty($post['endtime'])){
        $sql .= " AND A.addtime<=:endtime";
        $params[':endtime'] = $post['endtime'] . ' 23:59:59';
    }
    
    if(!empty($post['value']) && !empty($post['column'])){
        $col = $post['column'];
        if(!in_array($col, $a3_allowed_columns)){
            // 非法字段名，拒绝查询
            return false;
        }
        $val = $post['value'];
        if($col == 'name' || $col == 'out_trade_no' || $col == 'trade_no' || $col == 'api_trade_no' || $col == 'domain' || $col == 'ip' || $col == 'buyer'){
            $sql .= " AND A.`{$col}` LIKE :searchval";
            $params[':searchval'] = '%' . $val . '%';
        }elseif(in_array($col, ['money','realmoney','getmoney','profitmoney','refundmoney']) && strpos($val, '-') !== false){
            $money = explode('-', $val);
            if(count($money) == 2 && is_numeric($money[0]) && is_numeric($money[1])){
                $sql .= " AND A.`{$col}`>=:moneymin AND A.`{$col}`<=:moneymax";
                $params[':moneymin'] = floatval($money[0]);
                $params[':moneymax'] = floatval($money[1]);
            }
        }else{
            $sql .= " AND A.`{$col}`=:exactval";
            $params[':exactval'] = $val;
        }
    }
    
    return $sql;
}

if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
$act=isset($_GET['act'])?daddslashes($_GET['act']):null;

if(!checkRefererHost())exit('{"code":403}');
if($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_verify())exit('{"code":403,"msg":"CSRF Token Error"}');

@header('Content-Type: application/json; charset=UTF-8');

switch($act){
case 'orderList':
	// A3: 使用参数化查询构建 WHERE 子句
	$a3_params = [];
	$sql = a3_build_order_where($_POST, $a3_params);
	if($sql === false){
		exit(json_encode(['total'=>0, 'rows'=>[], 'msg'=>'非法查询字段']));
	}
	// A3: 分页参数严格转整数并限制范围
	$offset = max(0, intval($_POST['offset'] ?? 0));
	$limit = min(100, max(1, intval($_POST['limit'] ?? 10)));
	$total = $DB->getColumn("SELECT count(*) from pre_order A WHERE{$sql}", $a3_params);
	$list = $DB->getAll("SELECT A.*,B.plugin,B.name channelname FROM pre_order A LEFT JOIN pre_channel B ON A.channel=B.id WHERE{$sql} order by trade_no desc limit {$offset},{$limit}", $a3_params);
	$list2 = [];
	foreach($list as $row){
		$row['typename'] = $paytypes[$row['type']];
		$row['typeshowname'] = $paytype[$row['type']];
		$list2[] = $row;
	}

	exit(json_encode(['total'=>$total, 'rows'=>$list2]));
break;
case 'statistics':
    // A3: 使用参数化查询
    $a3_params = [];
    $sql = a3_build_order_where($_POST, $a3_params);
    if($sql === false){
        exit(json_encode(['code'=>-1,'msg'=>'非法查询字段']));
    }
    $totalMoney = $DB->getColumn("SELECT SUM(A.money) FROM pre_order A LEFT JOIN pre_channel B ON A.channel=B.id WHERE {$sql}", $a3_params);
    $successMoney = $DB->getColumn("SELECT SUM(CASE WHEN A.status = 1 THEN money ELSE 0 END) AS successMoney FROM pre_order A LEFT JOIN pre_channel B ON A.channel=B.id WHERE {$sql}", $a3_params);
    $unpaidMoney = $DB->getColumn("SELECT SUM(CASE WHEN A.status = 0 THEN money ELSE 0 END) AS unpaidMoney FROM pre_order A LEFT JOIN pre_channel B ON A.channel=B.id WHERE {$sql}", $a3_params);
    $refundMoney = $DB->getColumn("SELECT SUM(CASE WHEN A.status = 2 THEN refundmoney ELSE 0 END) AS refundMoney FROM pre_order A LEFT JOIN pre_channel B ON A.channel=B.id WHERE {$sql}", $a3_params);
    $totalCount = $DB->getColumn("SELECT COUNT(*) FROM pre_order A LEFT JOIN pre_channel B ON A.channel=B.id WHERE {$sql}", $a3_params);
    $successCount = $DB->getColumn("SELECT SUM(CASE WHEN A.status = 1 THEN 1 ELSE 0 END) AS successCount FROM pre_order A LEFT JOIN pre_channel B ON A.channel=B.id WHERE {$sql}", $a3_params);
    $unpaidCount = $DB->getColumn("SELECT SUM(CASE WHEN A.status = 0 THEN 1 ELSE 0 END) AS unpaidCount FROM pre_order A LEFT JOIN pre_channel B ON A.channel=B.id WHERE {$sql}", $a3_params);
    $refundCount = $DB->getColumn("SELECT SUM(CASE WHEN A.status = 2 THEN 1 ELSE 0 END) AS refundCount FROM pre_order A LEFT JOIN pre_channel B ON A.channel=B.id WHERE {$sql}", $a3_params);
    $platformProfit = $DB->getColumn("SELECT SUM(A.profitmoney) FROM pre_order A LEFT JOIN pre_channel B ON A.channel=B.id WHERE {$sql} AND status = 1", $a3_params);
    exit(json_encode(['code'=>0,'totalMoney'=>$totalMoney,'successMoney'=>$successMoney,'unpaidMoney'=>$unpaidMoney,'refundMoney'=>$refundMoney,'totalCount'=>$totalCount,'successCount'=>$successCount,'unpaidCount'=>$unpaidCount,'refundCount'=>$refundCount,'platformProfit'=>$platformProfit]));
break;
case 'riskList':
	$sql=" 1=1";
	if(isset($_POST['value']) && !empty($_POST['value'])) {
		// A3: 参数化查询
		$risk_col = $_POST['column'];
		$risk_allowed = ['id','uid','type','content','addtime','ip'];
		if(in_array($risk_col, $risk_allowed)){
			$sql.=" AND `{$risk_col}`=:riskval";
			$risk_params[':riskval'] = $_POST['value'];
		}
	}
	if(isset($_POST['type']) && $_POST['type']>-1) {
		$type = intval($_POST['type']);
		$sql.=" AND `type`={$type}";
	}
	$offset = max(0, intval($_POST['offset'] ?? 0));
	$limit = min(100, max(1, intval($_POST['limit'] ?? 10)));
	$total = $DB->getColumn("SELECT count(*) from pre_risk WHERE{$sql}", $risk_params);
	$list = $DB->getAll("SELECT * FROM pre_risk WHERE{$sql} order by id desc limit {$offset},{$limit}", $risk_params);

	exit(json_encode(['total'=>$total, 'rows'=>$list]));
break;

case 'setStatus':
	// Batch C Phase2: 修改订单状态权限检查和审计
	require_permission('order.edit');
	admin_audit_log('修改订单状态', json_encode(['trade_no'=>$_POST['trade_no'] ?? '', 'status'=>$_POST['status'] ?? ''])); //改变订单状态
	// A3: 参数化查询
	$trade_no=trim($_GET['trade_no']);
	$status=is_numeric($_GET['status'])?intval($_GET['status']):exit('{"code":200}');
	if($status==5){
		if($DB->exec("DELETE FROM pre_order WHERE trade_no=:tn", [':tn'=>$trade_no]))
			exit('{"code":200}');
		else
			exit('{"code":400,"msg":"删除订单失败"}');
	}else{
		if($DB->exec("update pre_order set status=:st where trade_no=:tn", [':st'=>$status, ':tn'=>$trade_no])!==false)
			exit('{"code":200}');
		else
			exit('{"code":400,"msg":"修改订单失败"}');
	}
break;
case 'order': //订单详情
	// A3: 参数化查询
	$trade_no=trim($_GET['trade_no']);
	$row=$DB->getRow("select A.*,B.showname typename,C.name channelname from pre_order A,pre_type B,pre_channel C where trade_no=:tn and A.type=B.id and A.channel=C.id limit 1", [':tn'=>$trade_no]);
	if(!$row)
		exit('{"code":-1,"msg":"当前订单不存在或未成功选择支付通道！"}');
	$row['subchannelname'] = $row['subchannel'] > 0 ? $DB->findColumn('subchannel', 'name', ['id'=>$row['subchannel']]) : '';
	if($row['status']==2){
		$row['refundtime'] = $DB->findColumn('refundorder', 'addtime', ['trade_no'=>$trade_no], 'refund_no DESC');
	}
	$result=array("code"=>0,"msg"=>"succ","data"=>$row);
	exit(json_encode($result));
break;
case 'subOrders':
	$trade_no=trim($_GET['trade_no']);
	$list = \lib\Payment::getSubOrders($trade_no);
	exit(json_encode(['code'=>0, 'data'=>$list, 'settle'=>$DB->findColumn('order', 'settle', ['trade_no'=>$trade_no])]));
break;
case 'operation': //批量操作订单
	$status=is_numeric($_POST['status'])?intval($_POST['status']):exit('{"code":-1,"msg":"请选择操作"}');
	$checkbox=$_POST['checkbox'];
	$i=0;
	foreach($checkbox as $trade_no){
		if($status==4)$DB->exec("DELETE FROM pre_order WHERE trade_no='$trade_no'");
		elseif($status==3){
			\lib\Order::unfreeze($trade_no);
		}
		elseif($status==2){
			\lib\Order::freeze($trade_no);
		}
		else $DB->exec("update pre_order set status='$status' where trade_no='$trade_no' limit 1");
		$i++;
	}
	exit('{"code":0,"msg":"成功改变'.$i.'条订单状态"}');
break;
case 'getmoney': //退款查询
	if(!$conf['admin_paypwd'])exit('{"code":-1,"msg":"你还未设置支付密码"}');
	$trade_no=trim($_POST['trade_no']);
	$api=isset($_POST['api'])?intval($_POST['api']):0;
	$result = \lib\Order::refund_info($trade_no, $api);
	exit(json_encode($result));
break;
case 'refund': //退款操作
	$trade_no=trim($_POST['trade_no']);
	$money = trim($_POST['money']);
	if(!is_numeric($money) || !preg_match('/^[0-9.]+$/', $money))exit('{"code":-1,"msg":"金额输入错误"}');

	$refund_no = date("YmdHis").rand(11111,99999);
	$result = \lib\Order::refund($refund_no, $trade_no, $money);
	if($result['code'] == 0){
		$result['msg'] = '已成功从UID:'.$result['uid'].'扣除'.$result['reducemoney'].'元余额';
	}
	exit(json_encode($result));
break;
case 'apirefund':
	// Batch C Phase2: 手动退款权限检查和审计
	require_permission('order.refund');
	admin_audit_log('手动退款', json_encode(['trade_no'=>$_POST['trade_no'] ?? ''])); //API退款操作
	$trade_no=trim($_POST['trade_no']);
	$paypwd=trim($_POST['paypwd']);
	$money = trim($_POST['money']);
	if(!is_numeric($money) || !preg_match('/^[0-9.]+$/', $money))exit('{"code":-1,"msg":"金额输入错误"}');
	if($paypwd!=$conf['admin_paypwd'])
		exit('{"code":-1,"msg":"支付密码输入错误！"}');
	
	$refund_no = date("YmdHis").rand(11111,99999);
	$result = \lib\Order::refund($refund_no, $trade_no, $money, 1);
	if($result['code'] == 0){
		$result['msg'] = '退款成功！退款金额¥'.$result['money'];
		if($result['reducemoney']>0){
			$result['msg'] .= '，并成功从UID:'.$result['uid'].'扣除'.$result['reducemoney'].'元余额';
		}
	}
	exit(json_encode($result));
break;
case 'freeze': //冻结订单
	$trade_no=trim($_POST['trade_no']);
	$result = \lib\Order::freeze($trade_no);
	exit(json_encode($result));
break;
case 'unfreeze': //解冻订单
	$trade_no=trim($_POST['trade_no']);
	$result = \lib\Order::unfreeze($trade_no);
	exit(json_encode($result));
break;
case 'notify': //获取回调地址
	$trade_no=trim($_POST['trade_no']);
	$row=$DB->getRow("select * from pre_order where trade_no='$trade_no' limit 1");
	if(!$row)
		exit('{"code":-1,"msg":"当前订单不存在！"}');
	$url=creat_callback($row);
	if($_POST['isget'] == 1){
		if(do_notify($url['notify'])){
			$DB->exec("UPDATE pre_order SET notify=0 WHERE trade_no='$trade_no'");
			exit('{"code":0}');
		}
		exit('{"code":-1}');
	}
	if($row['notify']>0)
		$DB->exec("update pre_order set notify=0,notifytime=NULL where trade_no='$trade_no'");
	exit('{"code":0,"url":"'.($_POST['isreturn']==1?$url['return']:$url['notify']).'"}');
break;
case 'fillorder':
	// Batch C Phase2: 手动补单权限检查和审计
	require_permission('order.edit');
	admin_audit_log('手动补单', json_encode(['trade_no'=>$_POST['trade_no'] ?? ''])); //手动补单
	$trade_no=trim($_POST['trade_no']);
	$row=$DB->getRow("SELECT A.*,B.name typename,B.showname typeshowname FROM pre_order A left join pre_type B on A.type=B.id WHERE trade_no=:trade_no limit 1", [':trade_no'=>$trade_no]);
	if(!$row)
		exit('{"code":-1,"msg":"当前订单不存在！"}');
	if($row['status']>0)exit('{"code":-1,"msg":"当前订单不是未完成状态！"}');
	if($DB->exec("update `pre_order` set `status` ='1' where `trade_no`='$trade_no'")){
		$DB->exec("update `pre_order` set `endtime` ='$date',`date` =NOW() where `trade_no`='$trade_no'");
		$channel=\lib\Channel::get($row['channel']);
		processOrder($row);
	}
	exit('{"code":0,"msg":"补单成功"}');
break;
case 'alipaydSettle': //支付宝直付通确认结算
	$trade_no=trim($_POST['trade_no']);
	$row=$DB->getRow("select * from pre_order where trade_no='$trade_no' limit 1");
	if(!$row)
		exit('{"code":-1,"msg":"当前订单不存在！"}');
	if($row['status']==0)exit('{"code":-1,"msg":"当前订单状态是未支付"}');
	$channel = $row['subchannel'] > 0 ? \lib\Channel::getSub($row['subchannel']) : \lib\Channel::get($row['channel'], $DB->findColumn('user', 'channelinfo', ['uid'=>$row['uid']]));
	if(!$channel){
		exit('{"code":-1,"msg":"当前支付通道信息不存在"}');
	}
	try{
		if($channel['plugin'] == 'alipayd'){
			\lib\Payment::alipaydSettle($channel, $row);
		}elseif($channel['plugin'] == 'wxpaynp'){
			\lib\Payment::wxpaynpSettle($channel, $row);
		}else{
			exit('{"code":-1,"msg":"支付插件不支持该操作"}');
		}
		$DB->exec("update `pre_order` set `settle`=2 where `trade_no`='$trade_no'");
		exit('{"code":0,"msg":"结算成功！"}');
	}catch(Exception $e){
		$DB->exec("update `pre_order` set `settle`=3 where `trade_no`='$trade_no'");
		safe_error_log('exception', $e->getMessage());
	exit('{"code":-1,"msg":"结算失败,操作失败，请稍后重试"}');
	}
break;
case 'alipayPreAuthPay': //支付宝授权资金支付
	$trade_no=trim($_POST['trade_no']);
	$order=$DB->getRow("select * from pre_order where trade_no='$trade_no' limit 1");
	if(!$order)
		exit('{"code":-1,"msg":"当前订单不存在！"}');
	$channel = $order['subchannel'] > 0 ? \lib\Channel::getSub($order['subchannel']) : \lib\Channel::get($order['channel'], $DB->findColumn('user', 'channelinfo', ['uid'=>$row['uid']]));
	if(!$channel){
		exit('{"code":-1,"msg":"当前支付通道信息不存在"}');
	}
	try{
		$result = \lib\Payment::alipayPreAuthPay($channel, $order);

		$api_trade_no = $result['trade_no'];
		$buyer_id = $result['buyer_user_id'];
		$total_amount = $result['total_amount'];
		processNotify($order, $api_trade_no, $buyer_id);

		exit('{"code":0,"msg":"授权资金支付成功！"}');
	}catch(Exception $e){
		$errmsg = $e->getMessage();
		exit('{"code":-1,"msg":"授权资金支付失败,'.$errmsg.'"}');
	}
break;
case 'alipayUnfreeze': //支付宝授权资金解冻
	$trade_no=trim($_POST['trade_no']);
	$order=$DB->getRow("select * from pre_order where trade_no='$trade_no' limit 1");
	if(!$order)
		exit('{"code":-1,"msg":"当前订单不存在！"}');
	$channel = $order['subchannel'] > 0 ? \lib\Channel::getSub($order['subchannel']) : \lib\Channel::get($order['channel'], $DB->findColumn('user', 'channelinfo', ['uid'=>$row['uid']]));
	if(!$channel){
		exit('{"code":-1,"msg":"当前支付通道信息不存在"}');
	}
	try{
		\lib\Payment::alipayUnfreeze($channel, $order);
		$DB->exec("update `pre_order` set `status`=0 where `trade_no`='$trade_no'");
		exit('{"code":0,"msg":"授权资金解冻成功！"}');
	}catch(Exception $e){
		$errmsg = $e->getMessage();
		exit('{"code":-1,"msg":"授权资金解冻失败,'.$errmsg.'"}');
	}
break;
case 'alipayRedPacketTansfer': //支付宝红包转账重试
	$trade_no=trim($_POST['trade_no']);
	$order=$DB->getRow("select * from pre_order where trade_no='$trade_no' limit 1");
	if(!$order)
		exit('{"code":-1,"msg":"当前订单不存在！"}');
	$channel = $order['subchannel'] > 0 ? \lib\Channel::getSub($order['subchannel']) : \lib\Channel::get($order['channel'], $DB->findColumn('user', 'channelinfo', ['uid'=>$row['uid']]));
	if(!$channel){
		exit('{"code":-1,"msg":"当前支付通道信息不存在"}');
	}
	if(!empty($channel['appmchid'])) $payee_user_id = $channel['appmchid'];
	else $payee_user_id = $DB->findColumn('user', 'alipay_uid', ['uid'=>$order['uid']]);
	if(!$payee_user_id) exit('{"code":-1,"msg":"当前商户未绑定支付宝账号"}');
	try{
		\lib\Payment::alipayRedPacketTransfer($channel, $payee_user_id, $order['money'], $order['api_trade_no']);
		$DB->exec("update `pre_order` set `settle`=2 where `trade_no`='$trade_no'");
		exit('{"code":0,"msg":"红包打款成功！"}');
	}catch(Exception $e){
		$errmsg = $e->getMessage();
		exit('{"code":-1,"msg":"红包打款失败,'.$errmsg.'"}');
	}
break;
default:
	exit('{"code":-4,"msg":"No Act"}');
break;
}