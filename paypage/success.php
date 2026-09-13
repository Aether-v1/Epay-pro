<?php
$is_defend = true;
include("./inc.php");
@header('Content-Type: text/html; charset=UTF-8');
$trade_no=daddslashes($_GET['trade_no']);
$row=$DB->getRow("SELECT * FROM pre_order WHERE trade_no='{$trade_no}' limit 1");
if(!$row)showerror('订单号不存在');
if($row['status']!=1)showerror('订单未完成支付');
if(!isset($_SESSION['paypage_trade_no']) || $_SESSION['paypage_trade_no']!=$trade_no)showerror('订单校验失败');
$userrow=$DB->getRow("select codename,username from pre_user where uid='{$row['uid']}' limit 1");
$codename = !empty($userrow['codename'])?$userrow['codename']:$userrow['username'];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta id="viewport" name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <title>支付成功页面</title>
    <link href="/assets/css/checkout.css?v=1" rel="stylesheet" media="screen">
</head>
<body class="epay-page">
<div class="epay-card epay-status-card">
    <div class="epay-status epay-status--success">
        <svg viewBox="0 0 24 24" width="44" height="44" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6L9 17l-5-5"/></svg>
        <h1>支付成功</h1>
        <p>付款已完成</p>
    </div>
    <div class="epay-amount">
        <span class="epay-amount__currency">¥</span><span class="epay-amount__value"><?php echo htmlspecialchars($row['money'], ENT_QUOTES, 'UTF-8'); ?></span>
    </div>
    <div class="epay-order-meta">
        <div class="epay-order-meta__row"><span class="epay-order-meta__label">收款方</span><span class="epay-order-meta__value"><strong><?php echo htmlspecialchars($codename, ENT_QUOTES, 'UTF-8'); ?></strong></span></div>
        <div class="epay-order-meta__row"><span class="epay-order-meta__label">完成时间</span><span class="epay-order-meta__value"><?php echo htmlspecialchars($row['endtime'], ENT_QUOTES, 'UTF-8'); ?></span></div>
        <div class="epay-order-meta__row"><span class="epay-order-meta__label">订单号</span><span class="epay-order-meta__value"><?php echo htmlspecialchars($trade_no, ENT_QUOTES, 'UTF-8'); ?></span></div>
    </div>
    <a href="javascript:;" class="epay-btn epay-btn--primary epay-btn--block" id="Close">关闭</a>
    <div class="epay-trust">
        <span class="epay-trust__item epay-trust__item--lock"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>安全支付</span>
    </div>
    <div class="epay-footer">Copyright © <?php echo date("Y")?> <?php echo htmlspecialchars($conf['sitename'], ENT_QUOTES, 'UTF-8'); ?></div>
</div>
<script src="<?php echo $cdnpublic?>jquery/1.12.4/jquery.min.js"></script>
<script src="//open.mobile.qq.com/sdk/qqapi.js?_bid=152"></script>
<script src="js/close.js"></script>
<script>
document.body.addEventListener('touchmove', function (event) {
	event.preventDefault();
},{ passive: false });
</script>
</body>
</html>