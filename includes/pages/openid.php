<?php
// 获取openid结果页面
if (!defined('IN_CRONLITE')) exit();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="initial-scale=1, maximum-scale=1, user-scalable=no, width=device-width">
<meta name="renderer" content="webkit" />
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<title>获取<?php echo $openid_name?></title>
<link href="/assets/css/checkout.css?v=1" rel="stylesheet" media="screen" />
</head>
<body class="epay-page">
<div class="epay-card">
  <div class="epay-status epay-status--success">
    <div class="epay-status__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg></div>
    <h1 class="epay-status__title">获取<?php echo htmlspecialchars($openid_name, ENT_QUOTES, 'UTF-8') ?>成功</h1>
    <p class="epay-status__desc">如未自动填写，请手动复制下方<?php echo htmlspecialchars($openid_name, ENT_QUOTES, 'UTF-8') ?>：</p>
  </div>
  <textarea class="epay-textarea-copy" rows="2" readonly><?php echo htmlspecialchars($openid_content, ENT_QUOTES, 'UTF-8') ?></textarea>
  <div class="epay-status__actions">
    <a role="button" class="epay-btn epay-btn--primary epay-btn--block copy-btn" href="javascript:" data-clipboard-text="<?php echo htmlspecialchars($openid_content, ENT_QUOTES, 'UTF-8') ?>">点击复制</a>
    <a href="javascript:;" class="epay-btn epay-btn--ghost epay-btn--block" id="Close">关闭</a>
  </div>
  <p class="epay-captcha-note">Copyright © <?php echo date("Y") ?> <?php echo htmlspecialchars($conf['sitename'], ENT_QUOTES, 'UTF-8') ?></p>
</div><script src="<?php echo $cdnpublic ?>jquery/1.12.4/jquery.min.js"></script>
<script src="<?php echo $cdnpublic ?>layer/3.1.1/layer.js"></script><script src="<?php echo $cdnpublic ?>clipboard.js/1.7.1/clipboard.min.js"></script>
<script>
document.body.addEventListener('touchmove', function (event) {
	event.preventDefault();
},{ passive: false });
$(document).ready(function(){
	var clipboard = new Clipboard('.copy-btn');
	clipboard.on('success', function (e) {
		layer.msg('复制成功！', {icon: 1});
	});
	clipboard.on('error', function (e) {
		layer.msg('复制失败，请长按链接后手动复制', {icon: 2});
	});
});
if(navigator.userAgent.indexOf("AlipayClient/") > -1){
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
}else if(navigator.userAgent.indexOf("MicroMessenger/") > -1){
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
}else if(navigator.userAgent.indexOf("QQ/") > -1){
    $('#Close').hide();
}else {
    $('#Close').click(function() {
        window.opener=null;window.close();
    });
}
</script>
</body>
</html>