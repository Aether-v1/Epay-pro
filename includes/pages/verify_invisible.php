<?php
// 支付安全验证（极验无感）
if (!defined('IN_CRONLITE')) exit();

$html = '<form id="dopay" action="'.$siteurl.'submit.php" method="post">';
foreach ($query_arr as $k=>$v) {
    $nameEsc = htmlspecialchars((string)$k, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $valueEsc = htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $html.= '<input type="hidden" name="'.$nameEsc.'" value="'.$valueEsc.'"/>';
}
$html .= '<input type="submit" value="Loading"></form>';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="initial-scale=1, maximum-scale=1, user-scalable=no, width=device-width">
<meta name="renderer" content="webkit" />
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<title>正在进行支付安全验证，请稍候...</title>
<link href="/assets/css/checkout.css?v=1" rel="stylesheet" media="screen" />
</head>
<body class="epay-page">
<div class="epay-card">  <div class="epay-status epay-status--waiting">
    <div class="epay-status__icon"><span class="epay-spinner epay-spinner--lg" aria-hidden="true"></span></div>
    <h1 class="epay-status__title" id="waiting">正在进行支付安全验证，请稍候...</h1>
    <p class="epay-status__desc">验证通过后将自动进入支付流程</p>
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
    captchaId: "99b142aaece96330d0f3ffb565ffb3ef",
    product: 'bind',
    protocol: 'https://',
    riskType: 'ai',
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