<?php
if(!defined('IN_CRONLITE'))exit();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta id="viewport" name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <title>红包领取成功</title>
    <link href="/assets/css/checkout.css?v=1" rel="stylesheet" media="screen">
</head>
<body class="epay-page">
<div class="epay-card epay-status-card">
    <div class="epay-status epay-status--success">
        <svg viewBox="0 0 24 24" width="44" height="44" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6L9 17l-5-5"/></svg>
        <h1>你已收款，资金<?php echo htmlspecialchars($receive_action.$receive_name, ENT_QUOTES, 'UTF-8'); ?></h1>
    </div>
    <div class="epay-amount">
        <span class="epay-amount__currency">¥</span><span class="epay-amount__value"><?php echo htmlspecialchars($trans['money'], ENT_QUOTES, 'UTF-8'); ?></span>
    </div>
    <div class="epay-order-meta">
        <div class="epay-order-meta__row"><span class="epay-order-meta__label">创建时间</span><span class="epay-order-meta__value"><?php echo htmlspecialchars($trans['addtime'], ENT_QUOTES, 'UTF-8'); ?></span></div>
        <div class="epay-order-meta__row"><span class="epay-order-meta__label">收款时间</span><span class="epay-order-meta__value"><?php echo htmlspecialchars($trans['paytime'], ENT_QUOTES, 'UTF-8'); ?></span></div>
    </div>
    <a href="javascript:;" class="epay-btn epay-btn--primary epay-btn--block" id="Close">关闭</a>
    <div class="epay-footer">Copyright © <?php echo date("Y")?> <?php echo htmlspecialchars($conf['sitename'], ENT_QUOTES, 'UTF-8'); ?></div>
</div>
<script src="<?php echo $cdnpublic?>jquery/1.12.4/jquery.min.js"></script>
<script src="js/close.js"></script>
<script>
document.body.addEventListener('touchmove', function (event) {
	event.preventDefault();
},{ passive: false });
</script>
</body>
</html>