<?php
// 微信支付浏览器外提示页面
if (!defined('IN_PLUGIN')) exit();
$useragent = strtolower($_SERVER['HTTP_USER_AGENT']);
if(strpos($useragent, 'iphone')!==false || strpos($useragent, 'ipod')!==false){
	$background_img = '/assets/img/ios.png';
}else{
	$background_img = '/assets/img/android.png';
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="utf-8" />
<meta name="apple-mobile-web-app-capable" content="yes"/>
<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
<meta name="format-detection" content="telephone=no"/>
<meta name="format-detection" content="email=no"/>
<meta name="viewport" content="initial-scale=1, maximum-scale=1, user-scalable=no, width=device-width">
<meta name="renderer" content="webkit" />
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<title>支付提示</title>
<link href="/assets/css/checkout.css?v=1" rel="stylesheet" media="screen" />
</head>
<body class="epay-page">
<div class="epay-card">
  <div class="epay-status epay-status--warning">
    <div class="epay-status__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg></div>
    <h1 class="epay-status__title">请在浏览器中打开</h1>
    <p class="epay-status__desc">请点击右上角菜单，选择在浏览器中打开，以完成支付</p>
  </div>
  <img class="epay-wxopen-img" src="<?php echo htmlspecialchars($background_img, ENT_QUOTES, 'UTF-8') ?>" alt="打开方式提示" />
  <div class="epay-qr__status"><span class="epay-dot"></span><span>正在等待付款</span></div>
</div><script src="<?php echo $cdnpublic ?>jquery/1.12.4/jquery.min.js"></script>
<script src="<?php echo $cdnpublic ?>layer/3.1.1/layer.js"></script><script>
    function loadmsg() {
        $.ajax({
            type: "GET",
            dataType: "json",
            url: "/getshop.php",
            data: {type: "alipay", trade_no: <?php echo json_encode($order['trade_no'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>},
            success: function (data) {
                if (data.code == 1) {
					layer.msg('支付成功，正在跳转中...', {icon: 16,shade: 0.1,time: 15000});
					setTimeout(window.location.href=data.backurl, 1000);
                }else{
                    setTimeout("loadmsg()", 2000);
                }
            },
            error: function () {
                setTimeout("loadmsg()", 2000);
            }
        });
    }
    window.onload = function(){
		setTimeout("loadmsg()", 5000);
	}
</script>
</body>
</html>