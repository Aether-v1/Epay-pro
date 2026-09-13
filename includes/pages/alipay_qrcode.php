<?php
// 支付宝扫码支付页面（Phase 2-C 现代化重构：仅替换 View 层，业务逻辑/接口/轮询/拉起行为保持不变）

if(!defined('IN_PLUGIN'))exit();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta name="viewport" content="initial-scale=1, maximum-scale=1, user-scalable=no, width=device-width">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta http-equiv="Content-Language" content="zh-cn">
<meta name="renderer" content="webkit">
<title>支付宝扫码支付</title>
<link href="/assets/css/checkout.css?v=1" rel="stylesheet" media="screen">
</head>
<body class="epay-page epay-qr-page epay-channel--alipay">
<img src="/assets/img/guide1.png" alt="引导图" class="top-guide" style="max-width:100%;margin:auto;display:none">
<div class="guide" style="display:none"><img src="/assets/img/guide2.png" alt="引导图" style="width:96%"></div>
<div class="epay-card">
  <div class="epay-head">
    <span class="epay-head__brand">
      <svg class="epay-head__logo" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2l8 4v6c0 5-3.5 8.5-8 10-4.5-1.5-8-5-8-10V6l8-4z"/></svg>
      <span>安全支付</span>
    </span>
    <span class="epay-head__secure"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>订单信息已加密</span>
  </div>

  <h1 class="epay-qr-page__title"><span class="epay-channel-ico" aria-hidden="true"><svg viewBox="0 0 24 24" fill="#ffffff"><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10 10-4.5 10-10S17.5 2 12 2zm5.9 7.2l-1.8 8.5c-.1.6-.5.8-.9.5l-2.6-1.9-1.2 1.2c-.1.1-.3.2-.5.2l.2-2.6 4.8-4.3c.2-.2 0-.3-.3-.1l-5.9 3.7-2.5-.8c-.6-.2-.6-.6.1-.8l9.7-3.7c.5-.2.9.1.9.7z"/></svg></span>支付宝扫码支付</h1>
  <p class="epay-qr-page__subtitle">使用支付宝 App 扫一扫完成付款</p>

  <div class="epay-summary">
    <p class="epay-summary__name"><?php echo htmlspecialchars($order['name'], ENT_QUOTES, 'UTF-8')?></p>
    <p class="epay-summary__merchant"><?php echo htmlspecialchars($sitename, ENT_QUOTES, 'UTF-8')?></p>
  </div>

  <div class="epay-amount">
    <span class="epay-amount__currency">¥</span><span class="epay-amount__value"><?php echo htmlspecialchars($order['realmoney'], ENT_QUOTES, 'UTF-8')?></span>
  </div>

  <div class="epay-qr">
    <div class="epay-qr__box" id="qrcode"></div>
    <div class="epay-qr__status" id="payStatus"><span class="epay-dot"></span><span id="statusText">正在等待付款</span></div>
    <div class="epay-qr__help">请使用支付宝扫描二维码完成支付</div>
  </div>

  <div class="open_app" style="display:none">
    <a class="btn-open-app">打开支付宝APP继续付款</a>
    <a onclick="checkresult()" class="btn-check">我已付款，返回查看订单</a>
  </div>

  <div class="epay-order-meta">
    <div class="epay-order-meta__row"><span class="epay-order-meta__label">订单号</span><span class="epay-order-meta__value"><b><?php echo htmlspecialchars($order['trade_no'], ENT_QUOTES, 'UTF-8')?></b></span></div>
    <div class="epay-order-meta__row"><span class="epay-order-meta__label">创建时间</span><span class="epay-order-meta__value"><?php echo htmlspecialchars($order['addtime'], ENT_QUOTES, 'UTF-8')?></span></div>
    <div class="epay-order-meta__row"><span class="epay-order-meta__label">有效期</span><span class="epay-order-meta__value">订单将在有效期内保持可支付</span></div>
  </div>

  <div class="detail" id="orderDetail">
    <dl class="detail-ct" style="display:none">
      <dt>购买物品</dt><dd id="productName"><?php echo htmlspecialchars($order['name'], ENT_QUOTES, 'UTF-8')?></dd>
      <dt>商户订单号</dt><dd id="billId"><?php echo htmlspecialchars($order['trade_no'], ENT_QUOTES, 'UTF-8')?></dd>
      <dt>创建时间</dt><dd id="createTime"><?php echo htmlspecialchars($order['addtime'], ENT_QUOTES, 'UTF-8')?></dd>
    </dl>
    <a href="javascript:void(0)" class="arrow"><i class="ico-arrow"></i>订单详情</a>
  </div>

  <div class="epay-trust">
    <span class="epay-trust__item epay-trust__item--lock"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>安全支付</span>
    <span class="epay-trust__item epay-trust__item--shield"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2l8 4v6c0 5-3.5 8.5-8 10-4.5-1.5-8-5-8-10V6l8-4z"/></svg>订单信息已加密传输</span>
  </div>
</div>
<script src="<?php echo $cdnpublic?>jquery/1.12.4/jquery.min.js"></script>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.js"></script>
<script src="<?php echo $cdnpublic?>jquery.qrcode/1.0/jquery.qrcode.min.js"></script>
<script>
    var code_url = <?php echo json_encode($code_url, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)?>;
    var code_type = code_url.indexOf('data:image/')>-1?1:0;
    if(code_type == 0){
        var url_scheme = 'alipays://platformapi/startapp?appId=20000067&url=' + encodeURIComponent(code_url);
        $('#qrcode').qrcode({
            text: code_url,
            width: 230,
            height: 230,
            foreground: "#000000",
            background: "#ffffff",
            typeNumber: -1
        });
    }else{
        $('#qrcode').empty().append($('<img>', {src: code_url, alt: '支付宝收款二维码'}));
    }
    // 订单详情
    $('#orderDetail .arrow').click(function (event) {
        if ($('#orderDetail').hasClass('detail-open')) {
            $('#orderDetail .detail-ct').slideUp(500, function () {
                $('#orderDetail').removeClass('detail-open');
            });
        } else {
            $('#orderDetail .detail-ct').slideDown(500, function () {
                $('#orderDetail').addClass('detail-open');
            });
        }
    });
    var trade_no = <?php echo json_encode($order['trade_no'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)?>;
    function setPayStatus(paid){
        var $st = $('#payStatus');
        if(!$st.length) return;
        $st.toggleClass('is-paid', !!paid);
        $('#statusText').text(paid ? '支付成功' : '正在等待付款');
    }
    function loadmsg() {
        $.ajax({
            type: "GET",
            dataType: "json",
            url: "/getshop.php",
            data: {type: "alipay", trade_no: trade_no},
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
    function checkresult() {
        $.ajax({
            type: "GET",
            dataType: "json",
            url: "/getshop.php",
            data: {type: "alipay", trade_no: trade_no},
            success: function (data) {
                if (data.code == 1) {
                    setPayStatus(true);
                    layer.msg('支付成功，正在跳转中...', {icon: 16,shade: 0.1,time: 15000});
                    window.location.href = data.backurl;
                }else{
                    layer.msg('您还未完成付款，请继续付款', {shade: 0,time: 1500});
                }
            },
            error: function () {
                layer.msg('服务器错误');
            }
        });
    }
    var isMobile = function (){
        var ua = navigator.userAgent;
        var ipad = ua.match(/(iPad).*OS\s([\d_]+)/),
        isIphone =!ipad && ua.match(/(iPhone\sOS)\s([\d_]+)/),
        isAndroid = ua.match(/(Android)\s+([\d.]+)/);
        return isIphone || isAndroid;
    }
    function wx_open(){
        $(".guide").show();
    }
    window.onload = function(){
        if(isMobile()){
            window.onpopstate=function (e) {
                if(e.state=='forward' || confirm('是否取消支付并返回？')){
                    window.history.back();
                }else{
                    e.preventDefault();
                    window.history.pushState('forward', null, '');
                }
            };
            window.history.pushState('forward', null, '');
        }
        if(isMobile() && code_type==0){
            $('.open_app').show();
            if(navigator.userAgent.indexOf('MicroMessenger/')>0){
                $(".top-guide").show();
                $('.btn-open-app').attr('href', 'javascript:wx_open()');
            }else{
                $('.btn-open-app').attr('href', url_scheme)
                if(navigator.userAgent.indexOf('EdgA/')==-1 && $(window).height() > $(window).width()){
                    setTimeout(window.location.href = url_scheme, 1000);
                }
            }
        }
        $("body").click(function(){
            if (!$(".guide").is(":hidden")) {
                $(".guide").hide();
            }
        });
        setTimeout("loadmsg()", 2000);
    }
</script>
</body>
</html>