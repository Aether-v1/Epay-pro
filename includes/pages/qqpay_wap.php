<?php
// QQ钱包手机扫码页面

if (!defined('IN_PLUGIN'))
    exit();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="initial-scale=1, maximum-scale=1, user-scalable=no, width=device-width">
<meta name="renderer" content="webkit" />
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<title>QQ支付</title>
<link href="/assets/css/checkout.css?v=1" rel="stylesheet" media="screen" />
</head>
<body class="epay-page epay-h5-page epay-channel--qqpay">
<div class="epay-card">  <div class="epay-head">
    <span class="epay-head__brand">
      <svg class="epay-head__logo" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2l8 4v6c0 5-3.5 8.5-8 10-4.5-1.5-8-5-8-10V6l8-4z"/></svg>
      <span>安全支付</span>
    </span>
    <span class="epay-head__secure"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>订单信息已加密</span>
  </div>
  <div class="epay-transition">
    <div class="epay-transition__icon"><span class="epay-channel-ico" aria-hidden="true"><svg viewBox="0 0 24 24"><text x="12" y="16.5" text-anchor="middle" font-size="15" font-weight="700" fill="#ffffff" font-family="inherit">Q</text></svg></span></div>
    <h1 class="epay-transition__title">正在打开QQ支付</h1>
    <p class="epay-transition__desc">正在为你连接安全支付页面</p>
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
    <a href="javascript:;" id="openUrl" class="epay-btn epay-btn--primary epay-btn--block">打开QQ</a>
    <a href="javascript:checkresult()" class="epay-btn epay-btn--ghost epay-btn--block">已完成支付？检测支付结果</a>
    <a href="?qrcode=1" class="epay-btn epay-btn--ghost epay-btn--block">切换扫码支付</a>
  </div>

  <p class="epay-h5-hint">若未自动打开QQ，请点击上方按钮继续支付</p>  <div class="epay-trust">
    <span class="epay-trust__item epay-trust__item--lock"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>安全支付</span>
    <span class="epay-trust__item epay-trust__item--shield"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2l8 4v6c0 5-3.5 8.5-8 10-4.5-1.5-8-5-8-10V6l8-4z"/></svg>订单信息已加密传输</span>
  </div>
</div>
<script src="<?php echo $cdnpublic ?>jquery/1.12.4/jquery.min.js"></script>
<script src="<?php echo $cdnpublic ?>layer/3.1.1/layer.js"></script>
<script>
    var code_url = <?php echo json_encode($code_url, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    var url_scheme = 'mqqapi://forward/url?src_type=web&style=default&=1&version=1&url_prefix=' + window.btoa(code_url);
    function loadmsg() {
        $.ajax({
            type: "GET",
            dataType: "json",
            url: "/getshop.php",
            data: { type: "qqpay", trade_no: <?php echo json_encode($order['trade_no'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?> },
            success: function (data) {
                if (data.code == 1) {
                    layer.msg('支付成功，正在跳转中...', { icon: 16, shade: 0.1, time: 15000 });
                    setTimeout(window.location.href = data.backurl, 1000);
                } else {
                    setTimeout("loadmsg()", 2000);
                }
            },
            error: function () {
                setTimeout("loadmsg()", 2000);
            }
        });
    }
    function checkresult() {
        $.ajax({
            type: "GET",
            dataType: "json",
            url: "/getshop.php",
            data: { type: "qqpay", trade_no: <?php echo json_encode($order['trade_no'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?> },
            success: function (data) {
                if (data.code == 1) {
                    layer.msg('支付成功，正在跳转中...', { icon: 16, shade: 0.1, time: 15000 });
                    setTimeout(window.location.href = data.backurl, 1000);
                } else {
                    layer.msg('您还未完成付款，请继续付款', { shade: 0, time: 1500 });
                }
            },
            error: function () {
                layer.msg('服务器错误');
            }
        });
    }
    window.onload = function () {
        window.onpopstate = function (e) {
            if (e.state == 'forward' || confirm('是否取消支付并返回？')) {
                window.history.back();
            } else {
                e.preventDefault();
                window.history.pushState('forward', null, '');
            }
        };
        window.history.pushState('forward', null, '');

        document.getElementById("openUrl").href = url_scheme;
        if (navigator.userAgent.indexOf('EdgA/') == -1 && $(window).height() > $(window).width()) {
            window.location.href = url_scheme;
        }
        setTimeout("loadmsg()", 3000);
    }
</script>
</body>
</html>