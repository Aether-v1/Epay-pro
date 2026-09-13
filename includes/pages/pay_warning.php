<?php
/**
 * 支付环境异常提示页面
**/
if(!defined('IN_CRONLITE'))exit();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta id="viewport" name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <title>支付提示页面</title>
    <link href="/assets/css/checkout.css?v=1" rel="stylesheet" media="screen">
</head>
<body class="epay-page">
<div class="epay-card">
    <div class="epay-status epay-status--warning">
        <div class="epay-status__icon" aria-hidden="true">!</div>
        <h1 class="epay-status__title">支付安全提示</h1>
        <p class="epay-status__desc">您当前支付的商品为 <span class="epay-warn-name"><?php echo htmlspecialchars($order['name'], ENT_QUOTES, 'UTF-8')?></span> ，请勿用他人发过来的二维码或链接进行支付，以防资金损失！</p>
        <div class="epay-status__actions">
            <a href="<?php echo htmlspecialchars($order['payurl'], ENT_QUOTES, 'UTF-8')?>" class="epay-btn epay-btn--primary">继续支付</a>
            <a href="javascript:;" class="epay-btn epay-btn--ghost" id="Close">关闭</a>
        </div>
        <p class="epay-status__meta">Copyright © <?php echo date("Y")?> <?php echo htmlspecialchars($conf['sitename'], ENT_QUOTES, 'UTF-8')?></p>
    </div>
</div>
<script src="<?php echo $cdnpublic?>jquery/1.12.4/jquery.min.js"></script>
<script>
document.body.addEventListener('touchmove', function (event) {
	event.preventDefault();
},{ passive: false });

if(navigator.userAgent.indexOf("AlipayClient") > -1){
    function Alipayready(callback) {
        if (window.AlipayJSBridge) {
            callback && callback();
        } else {
            document.addEventListener('AlipayJSBridgeReady', callback, false);
        }
    }
    Alipayready(function(){
        $('#Close').click(function() {
            AlipayJSBridge.call('popWindow');
        });
    })
}else if(navigator.userAgent.indexOf("MicroMessenger") > -1){
    if (typeof WeixinJSBridge == "undefined") {
        if (document.addEventListener) {
            document.addEventListener('WeixinJSBridgeReady', jsApiCall, false);
        } else if (document.attachEvent) {
            document.attachEvent('WeixinJSBridgeReady', jsApiCall);
            document.attachEvent('onWeixinJSBridgeReady', jsApiCall);
        }
    } else {
        jsApiCall();
    }
    function jsApiCall() {
        $('#Close').click(function() {
            WeixinJSBridge.call('closeWindow');
        });
    }
}else{
    $('#Close').click(function() {
        window.opener=null;window.close();
    });
}
</script>
</body>
</html>