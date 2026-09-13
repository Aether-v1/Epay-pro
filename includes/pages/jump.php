<?php
if(!defined('IN_PLUGIN'))exit();
$useragent = strtolower($_SERVER['HTTP_USER_AGENT']);
$guideText = '浏览器打开';
if(strpos($useragent, 'iphone')!==false || strpos($useragent, 'ipod')!==false){
	$guideText = 'Safari打开';
}elseif(strpos($useragent, 'micromessenger')!==false){
	$guideText = '浏览器打开';
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<title>请使用浏览器打开</title>
<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport"/>
<meta content="yes" name="apple-mobile-web-app-capable"/>
<meta content="black" name="apple-mobile-web-app-status-bar-style"/>
<meta name="format-detection" content="telephone=no"/>
<meta content="false" name="twcClient" id="twcClient"/>
<meta name="aplus-touch" content="1"/>
<link href="/assets/css/checkout.css?v=1" rel="stylesheet" media="screen"/>
</head>
<body class="epay-page epay-jump-page">
<div class="epay-jump-card">
  <div class="epay-jump-box">
    <div class="epay-jump-icon">
      <svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2l8 4v6c0 5-3.5 8.5-8 10-4.5-1.5-8-5-8-10V6l8-4z"/></svg>
    </div>
    <h1 class="epay-jump-title">请使用浏览器打开</h1>
    <p class="epay-jump-desc">点击右上角 <b><?php echo htmlspecialchars($guideText, ENT_QUOTES, 'UTF-8'); ?></b>，可以继续浏览本站哦~</p>
    <p class="epay-jump-desc">您也可以复制本站网址，到其它浏览器打开</p>
    <a class="epay-jump-btn" id="J_BtnDowanloadApp">点此继续访问</a>
  </div>
</div>
<a style="display: none;" href="" id="vurl" rel="noreferrer"></a>
<script src="<?php echo $cdnpublic?>jquery/1.12.4/jquery.min.js"></script>
<script>
function openu(u){
	document.getElementById("vurl").href = u;
	document.getElementById("vurl").click();
}
var url = window.location.href;
document.querySelector('body').addEventListener('touchmove', function (event) {
	event.preventDefault();
});
if(navigator.userAgent.indexOf("QQ/") > -1){
	openu("ucbrowser://"+url);
	openu("mttbrowser://url="+url);
	openu("googlechrome://"+url);
	$("html").on("click",function(){
		openu("ucbrowser://"+url);
		openu("mttbrowser://url="+url);
		openu("googlechrome://"+url);
	});
}
</script>
</body>
</html>