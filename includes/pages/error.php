<?php
/*
 * 支付失败提示页面
*/
if(!defined('IN_PLUGIN'))exit();
?>
<!DOCTYPE html>
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
        <h1 class="epay-status__title">支付未完成</h1>
        <p class="epay-status__desc">支付失败或支付超时，请返回重新发起支付</p>
        <div class="epay-status__actions">
            <a href="javascript:history.back(-1)" class="epay-btn epay-btn--primary">返回重新支付</a>
        </div>
    </div>
</div>
<script>
document.body.addEventListener('touchmove', function (event) {
	event.preventDefault();
},{ passive: false });
</script>
</body>
</html>