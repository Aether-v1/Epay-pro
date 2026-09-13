<?php
// 支付返回页面

if(!defined('IN_PLUGIN'))exit();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="initial-scale=1, maximum-scale=1, user-scalable=no, width=device-width">
    <title>支付结果</title>
    <link href="/assets/css/checkout.css?v=1" rel="stylesheet" media="screen">
</head>
<body class="epay-page">
    <div class="epay-card">
        <div class="epay-status epay-status--waiting">
            <div class="epay-status__icon" aria-hidden="true"><span class="epay-spinner" role="status" aria-label="正在检测付款"></span></div>
            <h1 class="epay-status__title">正在确认付款</h1>
            <p class="epay-status__desc">系统正在获取支付结果，请不要重复付款</p>
            <div class="epay-status__meta" id="payStatus"><span class="epay-dot"></span><span id="statusText">正在检测订单状态</span></div>
        </div>
    </div>
<script src="<?php echo $cdnpublic?>jquery/1.12.4/jquery.min.js"></script>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.js"></script>
<script>
	document.body.addEventListener('touchmove', function (event) {
		event.preventDefault();
	},{ passive: false });
	var trade_no = <?php echo json_encode($order['trade_no'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)?>;
	function setPayStatus(paid){
		var $st = $('#payStatus');
		if(!$st.length) return;
		$st.toggleClass('is-paid', !!paid);
		$('#statusText').text(paid ? '支付成功' : '正在检测订单状态');
	}
	function loadmsg() {
        $.ajax({
            type: "GET",
            dataType: "json",
            url: "/getshop.php",
            data: {type: "wxpay", trade_no: trade_no},
            success: function (data) {
                if (data.code == 1) {
					setPayStatus(true);
					layer.msg('支付成功，正在跳转中...', {icon: 16,shade: 0.1,time: 15000});
					window.location.href = data.backurl;
                }else{
                    setTimeout("loadmsg()", 2000);
                }
            },
            error: function () {
                setTimeout("loadmsg()", 2000);
            }
        });
    }
    window.onload = loadmsg();
</script>
</body>
</html>