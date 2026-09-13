<?php
if(!defined('IN_CRONLITE'))exit();
$jsUrlDone = json_encode($url.'&do=success', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta id="viewport" name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <title>确认收款页面</title>
    <link href="/assets/css/checkout.css?v=1" rel="stylesheet" media="screen">
</head>
<body class="epay-page">
<div class="epay-card epay-status-card">
    <div class="epay-status epay-status--waiting">
        <svg viewBox="0 0 24 24" width="44" height="44" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
        <h1>待你收款</h1>
    </div>
    <div class="epay-amount">
        <span class="epay-amount__currency">¥</span><span class="epay-amount__value"><?php echo htmlspecialchars($money, ENT_QUOTES, 'UTF-8'); ?></span>
    </div>
    <div class="epay-order-meta">
        <div class="epay-order-meta__row"><span class="epay-order-meta__label">转账时间</span><span class="epay-order-meta__value"><?php echo htmlspecialchars($addtime, ENT_QUOTES, 'UTF-8'); ?></span></div>
    </div>
    <a href="javascript:;" class="epay-btn epay-btn--primary epay-btn--block" id="Confirm" disabled>收款</a>
    <p class="epay-status__tip">1天内未确认，将退还给商家</p>
    <div class="epay-footer">Copyright © <?php echo date("Y")?> <?php echo htmlspecialchars($conf['sitename'], ENT_QUOTES, 'UTF-8'); ?></div>
</div>
    <div role="alert" id="loadingToast" class="epay-toast" style="display: none;">
        <div class="epay-toast__box">
            <span class="epay-toast__spinner"></span>
            <p class="epay-toast__text">正在加载</p>
        </div>
    </div>
<script src="<?php echo $cdnpublic?>jquery/1.12.4/jquery.min.js"></script>
<script src="//res.wx.qq.com/open/js/jweixin-1.6.0.js"></script>
<script>
document.body.addEventListener('touchmove', function (event) {
	event.preventDefault();
},{ passive: false });
wx.config(<?php echo $wxconfig?>);
wx.ready(function () {
  $('#loadingToast').fadeOut(100);
  wx.checkJsApi({
    jsApiList: ['requestMerchantTransfer'],
    success: function (res) {
      if (res.checkResult['requestMerchantTransfer']) {
        jsApiCall();
      } else {
        alert('你的微信版本过低，请更新至最新版本。');
      }
    }
  });
});
wx.error(function(res){
});
function jsApiCall(){
    $('#Confirm').removeAttr('disabled');
    $('#Confirm').click(function() {
        WeixinJSBridge.invoke('requestMerchantTransfer', <?php echo $wxtransfer?>,
          function (res) {
            if (res.err_msg === 'requestMerchantTransfer:ok') {
              window.location.href=<?php echo $jsUrlDone; ?>;
            }
          }
        );
    });
}
</script>
</body>
</html>