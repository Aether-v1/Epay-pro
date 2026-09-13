<?php
if(!defined('IN_CRONLITE'))exit();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta id="viewport" name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <title>错误提示</title>
    <link href="/assets/css/checkout.css?v=1" rel="stylesheet" media="screen">
</head>
<body class="epay-page">
<div class="epay-card epay-status-card">
    <div class="epay-status epay-status--error">
        <svg viewBox="0 0 24 24" width="44" height="44" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
        <h1>支付未完成</h1>
        <p>请查看下方提示信息</p>
    </div>
    <div class="epay-error-msg"><?php echo $msg?></div>
    <a href="javascript:;" class="epay-btn epay-btn--primary epay-btn--block" id="Close">关闭</a>
</div>
<script src="<?php echo $cdnpublic?>jquery/1.12.4/jquery.min.js"></script>
<script src="js/close.js"></script>
<script>
document.body.addEventListener('touchmove', function (event) {
	event.preventDefault();
},{ passive: false });
</script>
</body>
</html>