<?php
$is_defend = true;
$nosession = true;
require './includes/common.php';

@header('Content-Type: text/html; charset=UTF-8');

$other=isset($_GET['other'])?true:false;
$trade_no=daddslashes($_GET['trade_no']);
$sitename=base64_decode(daddslashes($_GET['sitename']));
$row=$DB->getRow("SELECT * FROM pre_order WHERE trade_no='{$trade_no}' limit 1");
if(!$row)sysmsg('该订单号不存在，请返回来源地重新发起请求！');
if($row['status']==1)sysmsg('该订单已完成支付，请勿重复支付');
$gid = $DB->getColumn("SELECT gid FROM pre_user WHERE uid='{$row['uid']}' limit 1");
$paytype = \lib\Channel::getTypes($row['uid'], $gid);

if(checkwechat()){
	$paytype = array_values($paytype);
	foreach($paytype as $i=>$s){
		if($s['name']=='wxpay'){
			$temp = $paytype[$i];
			$paytype[$i] = $paytype[0];
			$paytype[0] = $temp;
		}
	}
}

// ---- View 层输出转义（HTML Context Escape），仅用于本页展示，不改变任何业务数据 ----
if(!function_exists('epay_esc')){
	function epay_esc($s){
		return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
	}
}
// 渠道图标底色归类（纯视觉映射，不改动支付方式数据与提交逻辑）
if(!function_exists('epay_icon_cls')){
	function epay_icon_cls($name){
		$n = strtolower((string)$name);
		if(strpos($n,'wxpay')!==false || strpos($n,'wechat')!==false || strpos($n,'weixin')!==false) return 'wechat';
		if(strpos($n,'alipay')!==false) return 'alipay';
		if(strpos($n,'qq')!==false) return 'qq';
		if(strpos($n,'union')!==false || strpos($n,'bank')!==false || strpos($n,'jd')!==false) return 'bank';
		if(strpos($n,'usdt')!==false) return 'usdt';
		return 'default';
	}
}
$payamount = $row['realmoney'] ? $row['realmoney'] : $row['money'];
?>
<!DOCTYPE html>
<html lang="zh-CN"><head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0, user-scalable=0">
<title>安全支付 | <?php echo epay_esc($sitename?$sitename:$conf['sitename'])?></title>
<link href="/assets/css/checkout.css" rel="stylesheet" type="text/css">
</head>
<body>
<div class="epay-page">
	<div class="epay-card">
		<input type="hidden" name="trade_no" value="<?php echo epay_esc($trade_no)?>"/>
		<!-- 头部 -->
		<header class="epay-head">
			<div class="epay-head__brand">
				<img class="epay-head__logo" src="/assets/img/logo.png" alt="logo">
				<span>安全支付</span>
			</div>
			<span class="epay-head__secure">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
				订单信息已加密
			</span>
		</header>

<?php if($other){?>
		<!-- 支付通道维护提示 -->
		<div class="epay-alert epay-alert--warning">当前支付方式暂时关闭维护，请更换其他方式支付</div>
<?php if(in_array('qqpay',array_column($paytype,'name'))){?>
		<div class="epay-alert epay-alert--info">
			<div>
				<p>如果您需要微信支付请将微信余额转到QQ再选择QQ钱包支付！</p>
				<p><a class="epay-link-btn" href="./wx.html">点击查看微信余额转到QQ钱包教程</a></p>
			</div>
		</div>
<?php }}else{?>
		<!-- 订单摘要 -->
		<div class="epay-summary">
			<p class="epay-summary__name"><?php echo epay_esc($row['name'])?></p>
			<p class="epay-summary__merchant"><?php echo epay_esc($sitename?$sitename:$conf['sitename'])?></p>
		</div>
		<div class="epay-amount">
			<span class="epay-amount__currency">¥</span>
			<span class="epay-amount__value"><?php echo epay_esc($payamount)?></span>
		</div>
		<dl class="epay-order-meta">
			<div class="epay-order-meta__row"><dt class="epay-order-meta__label">订单号</dt><dd class="epay-order-meta__value"><?php echo epay_esc($trade_no)?></dd></div>
			<div class="epay-order-meta__row"><dt class="epay-order-meta__label">商品名称</dt><dd class="epay-order-meta__value"><b><?php echo epay_esc($row['name'])?></b></dd></div>
			<div class="epay-order-meta__row"><dt class="epay-order-meta__label">创建时间</dt><dd class="epay-order-meta__value"><?php echo epay_esc($row['addtime'])?></dd></div>
			<div class="epay-order-meta__row"><dt class="epay-order-meta__label">订单金额</dt><dd class="epay-order-meta__value"><b><?php echo epay_esc($row['money'])?></b> 元</dd></div>
<?php if($row['realmoney'] && $row['realmoney']!=$row['money']){?>
			<div class="epay-order-meta__row"><dt class="epay-order-meta__label">实付金额</dt><dd class="epay-order-meta__value"><b><?php echo epay_esc($row['realmoney'])?></b> 元 <span class="epay-fee">(含<?php echo epay_esc($row['realmoney']-$row['money'])?>元手续费)</span></dd></div>
<?php }?>
		</dl>
		<hr class="epay-divider">
<?php }?>

		<!-- 支付方式 -->
		<section class="epay-section">
			<h2 class="epay-section__title">选择支付方式</h2>
			<ul class="epay-methods types">
<?php foreach($paytype as $rows){?>
				<li class="epay-method pay_li" value="<?php echo epay_esc($rows['id'])?>">
					<span class="epay-method__icon epay-method__icon--<?php echo epay_icon_cls($rows['name'])?>"><img src="/assets/icon/<?php echo epay_esc($rows['name'])?>.ico" alt="<?php echo epay_esc($rows['showname'])?>"></span>
					<span class="epay-method__body">
						<span class="epay-method__name"><?php echo epay_esc($rows['showname'])?></span>
					</span>
					<span class="epay-method__radio"></span>
				</li>
<?php }?>
			</ul>
		</section>

		<!-- 立即支付 -->
		<div style="margin-top:24px">
			<button type="button" class="epay-btn epay-btn--primary epay-btn--block immediate_pay">立即支付</button>
		</div>

		<!-- 信任条 -->
		<div class="epay-trust">
			<span class="epay-trust__item epay-trust__item--lock">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
				安全支付 · 订单信息已加密传输
			</span>
		</div>
	</div>

	<!-- 提示层（保留原结构与 errorContent/close_btn，供兼容；默认隐藏） -->
	<div class="mt_agree" style="display:none">
		<div class="mt_agree_main">
			<h2>提示信息</h2>
			<p id="errorContent" style="text-align:center;line-height:36px;"></p>
			<a class="close_btn">确定</a>
		</div>
	</div>
</div>

<script src="<?php echo $cdnpublic?>jquery/1.12.4/jquery.min.js"></script>
<script type="text/javascript">
$(document).ready(function(){
	$(".types li").click(function(){
		$(".types li").each(function(){
			$(this).attr('class','epay-method pay_li');
		});
		$(this).attr('class','epay-method pay_li is-active');
	});
	$(document).on("click", ".immediate_pay", function () {
		var value = $(".types").find('.is-active').attr('value');
		var trade_no = $("input[name='trade_no']").val();
		window.location.href='./submit2.php?typeid='+value+'&trade_no='+trade_no;
	});
	$(".types li:first").click();
})
</script>
</body>
</html>