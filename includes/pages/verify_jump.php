<?php
// 支付安全验证（混淆跳转）
if (!defined('IN_CRONLITE')) exit();
$x = new \lib\hieroglyphy();
$key_enc = $x->hieroglyphyString($key);
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
    <p class="epay-status__desc">正在验证支付环境...</p>
  </div>
</div>
<?php echo $html?>
<script>
    var key = <?php echo $key_enc; ?>;
    window.onload=function(){
        var elem = document.getElementById("dopay");
        var input=document.createElement("input");  
        input.type="hidden";  
        input.name="__defend";
        input.value=key;
        elem.appendChild(input);
        elem.submit();
    }
</script>
</body>
</html>