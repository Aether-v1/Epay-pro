<?php
// 微信扫码支付页面（Phase 2-C 现代化重构：仅替换 View 层，微信特殊逻辑/接口/轮询/复制/防风控行为保持不变）

if(!defined('IN_PLUGIN'))exit();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta name="viewport" content="initial-scale=1, maximum-scale=1, user-scalable=no, width=device-width">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta http-equiv="Content-Language" content="zh-cn">
<meta name="renderer" content="webkit">
<title>微信扫码支付</title>
<link href="/assets/css/checkout.css?v=1" rel="stylesheet" media="screen">
</head>
<body class="epay-page epay-qr-page epay-channel--wechat">
<div class="epay-card">
  <div class="epay-head">
    <span class="epay-head__brand">
      <svg class="epay-head__logo" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2l8 4v6c0 5-3.5 8.5-8 10-4.5-1.5-8-5-8-10V6l8-4z"/></svg>
      <span>安全支付</span>
    </span>
    <span class="epay-head__secure"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>订单信息已加密</span>
  </div>

  <h1 class="epay-qr-page__title"><span class="epay-channel-ico" aria-hidden="true"><svg viewBox="0 0 24 24" fill="#ffffff"><path d="M8.7 3C5 3 2 5.6 2 8.9c0 1.9 1 3.6 2.6 4.7l-.6 1.9 2.2-1.3c.5.1 1 .2 1.5.2h.4C7.7 13.4 7 12 7 10.5 7 6.4 9.8 3 13.3 3c-.4-0-.7 0-1 0h-.1zM22 15.1c0-2.8-2.5-5-5.6-5s-5.6 2.2-5.6 5 2.5 5 5.6 5c.6 0 1.1-.1 1.6-.2l1.9 1.1-.5-1.7c1.5-1 2.6-2.5 2.6-4.2zm-7.6-1.8c-.5 0-.9-.4-.9-.9s.4-.9.9-.9.9.4.9.9-.4.9-.9.9zm4.1 0c-.5 0-.9-.4-.9-.9s.4-.9.9-.9.9.4.9.9-.4.9-.9.9z"/></svg></span>微信扫码支付</h1>
  <p class="epay-qr-page__subtitle">使用微信扫一扫完成付款</p>

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
    <div class="epay-qr__help">请使用微信扫描二维码完成支付</div>
  </div>

  <div class="mobile-btn" style="display:none">
    <div class="mobile-tip">提示：二维码会风控，请复制下方链接支付</div>
    <div class="mobile-tip">操作流程：复制链接→打开微信搜索自己微信名→打开聊天对话框→粘贴链接→发送→点击发送出来的蓝色链接→进入付款页面→完成付款</div>
    <a class="btn-copy-link" id="copy-btn" data-clipboard-text="<?php echo htmlspecialchars($code_url, ENT_QUOTES, 'UTF-8')?>">点我复制链接</a>
  </div>

  <div class="epay-order-meta">
    <div class="epay-order-meta__row"><span class="epay-order-meta__label">订单号</span><span class="epay-order-meta__value"><b><?php echo htmlspecialchars($order['trade_no'], ENT_QUOTES, 'UTF-8')?></b></span></div>
    <div class="epay-order-meta__row"><span class="epay-order-meta__label">创建时间</span><span class="epay-order-meta__value"><?php echo htmlspecialchars($order['addtime'], ENT_QUOTES, 'UTF-8')?></span></div>
    <div class="epay-order-meta__row"><span class="epay-order-meta__label">有效期</span><span class="epay-order-meta__value">订单将在有效期内保持可支付</span></div>
  </div>

  <div class="detail" id="orderDetail">
    <dl class="detail-ct" style="display:none">
      <dt>商家</dt><dd id="storeName"><?php echo htmlspecialchars($sitename, ENT_QUOTES, 'UTF-8')?></dd>
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
<div class="foot">
  <div class="inner">
    <p>手机用户可保存上方二维码到手机中</p>
    <p>在微信扫一扫中选择“相册”即可</p>
  </div>
</div>
<script src="<?php echo $cdnpublic?>jquery/1.12.4/jquery.min.js"></script>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.js"></script>
<script src="<?php echo $cdnpublic?>jquery.qrcode/1.0/jquery.qrcode.min.js"></script>
<script src="<?php echo $cdnpublic?>clipboard.js/1.7.1/clipboard.min.js"></script>
<script>
    var clipboard = new Clipboard('#copy-btn');
    clipboard.on('success', function(e) {
        layer.msg('复制成功，请到微信里面粘贴');
    });
    clipboard.on('error', function(e) {
        layer.msg('复制失败');
    });
    var code_url = <?php echo json_encode($code_url, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)?>;
    var code_type = code_url.indexOf('data:image/')>-1?1:0;
    if(code_type == 0){
        $('#qrcode').qrcode({
            text: code_url,
            width: 230,
            height: 230,
            foreground: "#000000",
            background: "#ffffff",
            typeNumber: -1
        });
        if(navigator.userAgent.indexOf('MicroMessage/')>0){
            const canvas = $('#qrcode canvas')[0];
            const img = new Image();
            img.src = canvas.toDataURL('image/png');
            $('#qrcode').empty().append(img);
        }
    }else{
        $('#qrcode').empty().append($('<img>', {src: code_url, alt: '微信收款二维码'}));
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
    var isMobile = function (){
        var ua = navigator.userAgent;
        var ipad = ua.match(/(iPad).*OS\s([\d_]+)/),
        isIphone =!ipad && ua.match(/(iPhone\sOS)\s([\d_]+)/),
        isAndroid = ua.match(/(Android)\s+([\d.]+)/);
        return isIphone || isAndroid;
    }
    window.onload = function(){
        if(isMobile()){
            $('.mobile-btn').show();
            $('.mobile-tip').show();
        }
        setTimeout("loadmsg()", 2000);
    }
</script>
</body>
</html>