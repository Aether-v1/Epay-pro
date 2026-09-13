<?php
// 实名认证成功页面
if (!defined('IN_CRONLITE')) exit();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="initial-scale=1, maximum-scale=1, user-scalable=no, width=device-width">
<meta name="renderer" content="webkit" />
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<title>实名认证成功</title>
<link href="/assets/css/checkout.css?v=1" rel="stylesheet" media="screen" />
</head>
<body class="epay-page">
<div class="epay-card">
  <div class="epay-status epay-status--success">
    <div class="epay-status__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg></div>
    <h1 class="epay-status__title">实名认证成功</h1>
    <p class="epay-status__desc">请返回浏览器查看结果</p>
    <div class="epay-status__actions">
      <a href="javascript:;" class="epay-btn epay-btn--primary epay-btn--block" id="Close">关闭</a>
    </div>
  </div>
</div><script src="<?php echo $cdnpublic ?>jquery/1.12.4/jquery.min.js"></script><script>
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