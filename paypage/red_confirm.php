<?php
if(!defined('IN_CRONLITE'))exit();
$jsBizNo = json_encode($biz_no, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$jsTime = json_encode($time, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$jsSign = json_encode($sign, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$jsOpenid = json_encode($openid, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta id="viewport" name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <title>红包领取确认</title>
    <link href="/assets/css/checkout.css?v=1" rel="stylesheet" media="screen">
</head>
<body class="epay-page">
<div class="epay-card epay-status-card">
    <div class="epay-status epay-status--waiting">
        <svg viewBox="0 0 24 24" width="44" height="44" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
        <h1>待你收款</h1>
    </div>
    <div class="epay-amount">
        <span class="epay-amount__currency">¥</span><span class="epay-amount__value"><?php echo htmlspecialchars($trans['money'], ENT_QUOTES, 'UTF-8'); ?></span>
    </div>
    <div class="epay-order-meta">
        <div class="epay-order-meta__row"><span class="epay-order-meta__label">创建时间</span><span class="epay-order-meta__value"><?php echo htmlspecialchars($trans['addtime'], ENT_QUOTES, 'UTF-8'); ?></span></div>
    </div>
    <a href="javascript:;" class="epay-btn epay-btn--primary epay-btn--block" id="Confirm">收款</a>
    <p class="epay-status__tip">请在24小时内确认</p>
    <div class="epay-footer">Copyright © <?php echo date("Y")?> <?php echo htmlspecialchars($conf['sitename'], ENT_QUOTES, 'UTF-8'); ?></div>
</div>
    <div role="alert" id="loadingToast" class="epay-toast" style="display: none;">
        <div class="epay-toast__box">
            <span class="epay-toast__spinner"></span>
            <p class="epay-toast__text">正在加载</p>
        </div>
    </div>
    <div class="epay-dialog" role="dialog" aria-hidden="true" aria-modal="true" aria-labelledby="dialog_title" id="iosDialog" style="display: none;">
        <div class="epay-dialog__mask"></div>
        <div class="epay-dialog__box">
            <div class="epay-dialog__title" id="dialog_title">提示</div>
            <div class="epay-dialog__bd" id="dialog_content"></div>
            <div class="epay-dialog__ft">
                <a role="button" href="javascript:" id="dialogClose" class="epay-dialog__btn">关闭</a>
            </div>
        </div>
    </div>
<script src="<?php echo $cdnpublic?>jquery/1.12.4/jquery.min.js"></script>
<script>
document.body.addEventListener('touchmove', function (event) {
	event.preventDefault();
},{ passive: false });
function showDialog(title, content) {
    $('#dialog_title').text(title);
    $('#dialog_content').text(content);
    $('#iosDialog').fadeIn(100);
}
$(document).ready(function(){
  $("#dialogClose").click(function(){
    $('#iosDialog').fadeOut(100);
  });
  $("#Confirm").click(function(){
    $('#loadingToast').fadeIn(100);
    $.ajax({
      type: "POST",
      url: "./red_ajax.php",
      data: {n: <?php echo $jsBizNo; ?>, t: <?php echo $jsTime; ?>, s: <?php echo $jsSign; ?>, openid: <?php echo $jsOpenid; ?>},
      dataType: "json",
      success: function(response) {
        $('#loadingToast').fadeOut(100);
        if(response.code == 0) {
          window.location.href = response.redirect_url;
        } else {
          showDialog('错误提示', response.msg);
        }
      },
      error: function() {
        $('#loadingToast').fadeOut(100);
        showDialog('错误提示', '网络异常，请稍后再试！');
      }
    });
  });
})
</script>
</body>
</html>