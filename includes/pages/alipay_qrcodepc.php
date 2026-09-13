<?php
// 支付宝扫码支付页面（PC 端 iframe 版）
if(!defined('IN_PLUGIN'))exit();
$jsTradeNo = json_encode($order['trade_no'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="initial-scale=1, maximum-scale=1, user-scalable=no, width=device-width">
<meta name="renderer" content="webkit" />
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<title>支付宝扫码支付</title>
<link href="/assets/css/checkout.css?v=1" rel="stylesheet" media="screen" />
</head>
<body class="epay-page epay-qr-page epay-qrpc-page epay-channel--alipay">
  <div class="epay-card">
    <div class="epay-head">
      <span class="epay-head__brand">
        <span class="epay-channel-ico" aria-hidden="true"><svg viewBox="0 0 24 24"><text x="12" y="16.5" text-anchor="middle" font-size="15" font-weight="700" fill="#ffffff" font-family="inherit">支</text></svg></span>
        <span>安全支付</span>
      </span>
      <span class="epay-head__secure"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>订单信息已加密</span>
    </div>
    <div class="epay-transition">
      <div class="epay-transition__icon"><span class="epay-channel-ico" aria-hidden="true"><svg viewBox="0 0 24 24"><text x="12" y="16.5" text-anchor="middle" font-size="15" font-weight="700" fill="#ffffff" font-family="inherit">支</text></svg></span></div>
      <h1 class="epay-transition__title">支付宝扫码支付</h1>
      <p class="epay-transition__desc">请使用支付宝扫描二维码完成支付</p>
    </div>
    <div class="epay-summary">
      <p class="epay-summary__name"><?php echo htmlspecialchars($order['name'], ENT_QUOTES, 'UTF-8'); ?></p>
    </div>
    <div class="epay-amount">
      <span class="epay-amount__currency">¥</span><span class="epay-amount__value"><?php echo htmlspecialchars($order['realmoney'], ENT_QUOTES, 'UTF-8'); ?></span>
    </div>
    <div class="epay-qr">
      <div class="epay-qr__box epay-qr__box--pc" id="qrcode">
        <iframe src="<?php echo htmlspecialchars($code_url, ENT_QUOTES, 'UTF-8'); ?>" width="230px" height="230px" frameborder="0" scrolling="no" seamless></iframe>
      </div>
      <p class="epay-qr__status" id="pay-status"><span class="epay-dot" aria-hidden="true"></span>正在等待付款</p>
    </div>
    <div class="epay-order-meta" id="orderDetail">
      <div class="epay-order-meta__row"><span class="epay-order-meta__label">订单号</span><span class="epay-order-meta__value"><b><?php echo htmlspecialchars($order['trade_no'], ENT_QUOTES, 'UTF-8'); ?></b></span></div>
      <div class="epay-order-meta__row"><span class="epay-order-meta__label">创建时间</span><span class="epay-order-meta__value"><?php echo htmlspecialchars($order['addtime'], ENT_QUOTES, 'UTF-8'); ?></span></div>
    </div>
    <p class="epay-qr__help">订单将在有效期内保持可支付</p>
    <div class="epay-trust">
      <span class="epay-trust__item epay-trust__item--lock"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>安全支付</span>
      <span class="epay-trust__item epay-trust__item--shield"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2l8 4v6c0 5-3.5 8.5-8 10-4.5-1.5-8-5-8-10V6l8-4z"/></svg>页面会自动检测付款状态</span>
    </div>
  </div>
<script src="<?php echo $cdnpublic?>jquery/1.12.4/jquery.min.js"></script>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.js"></script>
<script>
    function loadmsg() {
        $.ajax({
            type: "GET",
            dataType: "json",
            url: "/getshop.php",
            data: {type: "alipay", trade_no: <?php echo $jsTradeNo; ?>},
            success: function (data) {
                if (data.code == 1) {
                    var st = document.getElementById('pay-status');
                    if (st) {
                        st.className = 'epay-qr__status epay-qr__status--ok';
                        st.innerHTML = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6L9 17l-5-5"/></svg>支付成功，正在跳转...';
                    }
                    layer.msg('支付成功，正在跳转中...', {icon: 16, shade: 0.1, time: 15000});
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
		setTimeout("loadmsg()", 2000);
	}
</script>
</body>
</html>