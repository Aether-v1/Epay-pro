<?php
// 支付宝JS支付页面
if (!defined('IN_PLUGIN')) exit();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="initial-scale=1, maximum-scale=1, user-scalable=no, width=device-width">
<meta name="renderer" content="webkit" />
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<title>支付宝支付</title>
<link href="/assets/css/checkout.css?v=1" rel="stylesheet" media="screen" />
</head>
<body class="epay-page epay-h5-page epay-channel--alipay">
<div class="epay-card">  <div class="epay-head">
    <span class="epay-head__brand">
      <svg class="epay-head__logo" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2l8 4v6c0 5-3.5 8.5-8 10-4.5-1.5-8-5-8-10V6l8-4z"/></svg>
      <span>安全支付</span>
    </span>
    <span class="epay-head__secure"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>订单信息已加密</span>
  </div>
  <div class="epay-transition">
    <div class="epay-transition__icon"><span class="epay-channel-ico" aria-hidden="true"><svg viewBox="0 0 24 24"><text x="12" y="16.5" text-anchor="middle" font-size="15" font-weight="700" fill="#ffffff" font-family="inherit">支</text></svg></span></div>
    <h1 class="epay-transition__title">正在调起支付</h1>
    <p class="epay-transition__desc">订单正在处理中，请稍候</p>
    <span class="epay-spinner epay-jspay-spinner" aria-hidden="true"></span>
  </div>

  <div class="epay-summary">
    <p class="epay-summary__name"><?php echo htmlspecialchars($order['name'], ENT_QUOTES, 'UTF-8') ?></p>
    <p class="epay-summary__merchant"><?php echo htmlspecialchars($sitename, ENT_QUOTES, 'UTF-8') ?></p>
  </div>

  <div class="epay-amount">
    <span class="epay-amount__currency">¥</span><span class="epay-amount__value"><?php echo htmlspecialchars($order['realmoney'], ENT_QUOTES, 'UTF-8') ?></span>
  </div>

  <div class="epay-order-meta">
    <div class="epay-order-meta__row"><span class="epay-order-meta__label">订单号</span><span class="epay-order-meta__value"><b><?php echo htmlspecialchars($order['trade_no'], ENT_QUOTES, 'UTF-8') ?></b></span></div>
    <div class="epay-order-meta__row"><span class="epay-order-meta__label">创建时间</span><span class="epay-order-meta__value"><?php echo htmlspecialchars($order['addtime'], ENT_QUOTES, 'UTF-8') ?></span></div>
  </div>

  <div class="epay-h5-actions">
    <a href="javascript:;" onclick="callpay()" class="epay-btn epay-btn--primary epay-btn--block">立即支付</a>
  </div>

  <p class="epay-h5-hint">支付将在当前应用中完成</p>  <div class="epay-trust">
    <span class="epay-trust__item epay-trust__item--lock"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>安全支付</span>
    <span class="epay-trust__item epay-trust__item--shield"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2l8 4v6c0 5-3.5 8.5-8 10-4.5-1.5-8-5-8-10V6l8-4z"/></svg>订单信息已加密传输</span>
  </div>
</div>
<script src="<?php echo $cdnpublic ?>jquery/1.12.4/jquery.min.js"></script>
<script src="<?php echo $cdnpublic ?>layer/3.1.1/layer.js"></script>
<script>
document.body.addEventListener('touchmove', function (event) {
	event.preventDefault();
},{ passive: false });
var tradeNO = <?php echo json_encode($alipay_trade_no, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

function Alipayready(callback) {
    if (window.AlipayJSBridge) {
        callback && callback();
    } else {
        document.addEventListener('AlipayJSBridgeReady', callback, false);
    }
}
function AlipayJsPay() {
	Alipayready(function(){
		AlipayJSBridge.call("tradePay",{
			tradeNO: tradeNO
		}, function(result){
			var msg = "";
			if(result.resultCode == "9000"){
				loadmsg();
			}else if(result.resultCode == "8000"){
				msg = "正在处理中";
			}else if(result.resultCode == "4000"){
				msg = "订单支付失败";
			}else if(result.resultCode == "6002"){
				msg = "网络连接出错";
			}
			if (msg!="") {
				layer.msg(msg);
			}
		});
	});
}
function loadmsg() {
	$.ajax({
		type: "GET",
		dataType: "json",
		url: "/getshop.php",
		data: {type: "wxpay", trade_no: <?php echo json_encode(TRADE_NO, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>},
		success: function (data) {
			if (data.code == 1) {
				layer.msg('支付成功，正在跳转中...', {icon: 16,shade: 0.01,time: 15000});
				window.location.href=<?php echo $redirect_url?>;
			}else{
				setTimeout("loadmsg()", 2000);
			}
		},
		error: function () {
			setTimeout("loadmsg()", 2000);
		}
	});
}
window.onload = AlipayJsPay();
</script>
</body>
</html>