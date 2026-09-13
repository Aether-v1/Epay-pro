<?php
if(!defined('IN_CRONLITE'))exit();
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta id="viewport" name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <title>错误提示</title>
    <link href="/assets/css/checkout.css?v=1" rel="stylesheet" media="screen">
</head>
<body class="epay-page">
<div class="epay-card">
    <div class="epay-status epay-status--error">
        <div class="epay-status__icon" aria-hidden="true">!</div>
        <h1 class="epay-status__title">订单处理异常</h1>
        <p class="epay-status__desc">支付款项已按原流程退回</p>
        <div class="epay-status__actions">
            <a href="javascript:;" class="epay-btn epay-btn--ghost" id="Close">关闭</a>
        </div>
    </div>
</div>
<script src="<?php echo $cdnpublic?>jquery/1.12.4/jquery.min.js"></script>
<script src="/paypage/js/close.js"></script>
<script>
document.body.addEventListener('touchmove', function (event) {
	event.preventDefault();
},{ passive: false });
</script>
</body>
</html>