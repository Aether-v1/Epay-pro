<?php
// 支付环境安全验证（极验滑动）
if (!defined('IN_CRONLITE')) exit();

$html = '<form id="dopay" action="'.$siteurl.'submit.php" method="post">';
foreach ($query_arr as $k=>$v) {
    $nameEsc = htmlspecialchars((string)$k, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $valueEsc = htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $html.= '<input type="hidden" name="'.$nameEsc.'" value="'.$valueEsc.'"/>';
}
$html .= '<input type="submit" value="Loading" style="display:none"></form>';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="initial-scale=1, maximum-scale=1, user-scalable=no, width=device-width">
<meta name="renderer" content="webkit" />
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<title>支付环境安全验证</title>
<link href="/assets/css/checkout.css?v=1" rel="stylesheet" media="screen" />
</head>
<body class="epay-page">
<div class="epay-card">  <div class="epay-status epay-status--warning">
    <div class="epay-status__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg></div>
    <h1 class="epay-status__title" id="waiting">支付环境安全验证</h1>
    <p class="epay-status__desc">当前支付人数过多，请完成<strong>“滑动验证”</strong>后继续支付</p>
  </div>
</div>
<?php echo $html?>
<script src="<?php echo $cdnpublic ?>jquery/1.12.4/jquery.min.js"></script>
<script src="https://static.geetest.com/v4/gt4.js"></script>
<script>
window.appendChildOrg = Element.prototype.appendChild;
Element.prototype.appendChild = function() {
    if(arguments[0].tagName == 'SCRIPT'){
        arguments[0].setAttribute('referrerpolicy', 'no-referrer');
    }
    return window.appendChildOrg.apply(this, arguments);
};
initGeetest4({
    captchaId: "54088bb07d2df3c46b79f80300b0abbe",
    product: 'bind',
    protocol: 'https://',
    riskType: 'slide',
    hideSuccess: true
},function (captcha) {
    captcha.onReady(function(){
        captcha.showCaptcha();
    }).onSuccess(function(){
        var result = captcha.getValidate();
        result.pid = <?php echo json_encode($query_arr['pid'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        result.trade_no = <?php echo json_encode($query_arr['out_trade_no'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        $.ajax({
            url: 'getshop.php?act=captcha_verify',
            type: 'post',
            dataType: 'json',
            data: result,
            cache: false,
            success: function (data) {
                if(data.code == 0){
                    var elem = document.getElementById("dopay");
                    var input = document.createElement("input");  
                    input.type="hidden";  
                    input.name="__defend";
                    input.value=data.key;
                    elem.appendChild(input);
                    elem.submit();
                }else{
                    alert(data.msg);
                }
            },
            error: function () {
                alert('服务器错误');
            }
        });
    }).onError(function(){
        alert('验证码加载失败，请刷新页面重试');
    })
});
</script>
</body>
</html>