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

if(!function_exists('epay_esc')){
	function epay_esc($s){
		return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
	}
}
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
$has_fee = $row['realmoney'] && $row['realmoney'] != $row['money'];
?>
<!DOCTYPE html>
<html lang="zh-CN"><head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0, user-scalable=0">
<meta name="theme-color" content="#f0f4fa">
<title>安全支付 | <?php echo epay_esc($sitename?$sitename:$conf['sitename'])?></title>
<link href="/assets/css/checkout.css?v=2" rel="stylesheet" type="text/css">
</head>
<body class="epay-body--cashier">
<div class="epay-bg">
	<div class="epay-bg__circle epay-bg__circle--1"></div>
	<div class="epay-bg__circle epay-bg__circle--2"></div>
	<div class="epay-bg__circle epay-bg__circle--3"></div>
</div>

<div class="epay-cashier">
	<div class="epay-cashier__card">
		<input type="hidden" name="trade_no" value="<?php echo epay_esc($trade_no)?>"/>

		<!-- 顶部品牌栏 -->
		<header class="epay-cashier__header">
			<div class="epay-cashier__brand">
				<div class="epay-cashier__logo">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
				</div>
				<div class="epay-cashier__brand-text">
					<span class="epay-cashier__brand-name"><?php echo epay_esc($sitename?$sitename:$conf['sitename'])?></span>
					<span class="epay-cashier__brand-sub">安全支付结算</span>
				</div>
			</div>
			<div class="epay-cashier__secure">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
				<span>SSL 加密</span>
			</div>
		</header>

<?php if($other){?>
		<div class="epay-cashier__notice epay-cashier__notice--warning">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
			<span>当前支付方式暂时关闭维护，请更换其他方式支付</span>
		</div>
<?php if(in_array('qqpay',array_column($paytype,'name'))){?>
		<div class="epay-cashier__notice epay-cashier__notice--info">
			<p>如果您需要微信支付请将微信余额转到QQ再选择QQ钱包支付！</p>
			<p><a href="./wx.html" class="epay-cashier__link">点击查看微信余额转到QQ钱包教程</a></p>
		</div>
<?php }}else{?>
		<!-- 金额区 -->
		<div class="epay-cashier__amount">
			<div class="epay-cashier__amount-label">支付金额</div>
			<div class="epay-cashier__amount-value">
				<span class="epay-cashier__amount-currency">¥</span>
				<span class="epay-cashier__amount-num"><?php echo epay_esc($payamount)?></span>
			</div>
			<div class="epay-cashier__amount-goods"><?php echo epay_esc($row['name'])?></div>
		</div>

		<!-- 订单信息 -->
		<div class="epay-cashier__order">
			<div class="epay-cashier__order-row">
				<span class="epay-cashier__order-label">订单号</span>
				<span class="epay-cashier__order-value epay-cashier__order-value--mono"><?php echo epay_esc($trade_no)?></span>
			</div>
			<div class="epay-cashier__order-row">
				<span class="epay-cashier__order-label">商品名称</span>
				<span class="epay-cashier__order-value"><?php echo epay_esc($row['name'])?></span>
			</div>
			<div class="epay-cashier__order-row">
				<span class="epay-cashier__order-label">创建时间</span>
				<span class="epay-cashier__order-value"><?php echo epay_esc($row['addtime'])?></span>
			</div>
			<div class="epay-cashier__order-row">
				<span class="epay-cashier__order-label">订单金额</span>
				<span class="epay-cashier__order-value">¥ <?php echo epay_esc($row['money'])?></span>
			</div>
<?php if($has_fee){?>
			<div class="epay-cashier__order-row epay-cashier__order-row--highlight">
				<span class="epay-cashier__order-label">实付金额</span>
				<span class="epay-cashier__order-value epay-cashier__order-value--strong">¥ <?php echo epay_esc($row['realmoney'])?> <span class="epay-cashier__fee">(含手续费 ¥<?php echo epay_esc($row['realmoney']-$row['money'])?>)</span></span>
			</div>
<?php }?>
		</div>
<?php }?>

		<!-- 支付方式 -->
		<div class="epay-cashier__methods">
			<div class="epay-cashier__methods-title">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
				选择支付方式
			</div>
			<ul class="epay-cashier__method-list types">
<?php foreach($paytype as $rows){?>
				<li class="epay-cashier__method pay_li" value="<?php echo epay_esc($rows['id'])?>">
					<div class="epay-cashier__method-icon epay-cashier__method-icon--<?php echo epay_icon_cls($rows['name'])?>">
						<img src="/assets/icon/<?php echo epay_esc($rows['name'])?>.ico" alt="<?php echo epay_esc($rows['showname'])?>" onerror="this.style.display='none';this.parentNode.classList.add('is-fallback')">
					</div>
					<div class="epay-cashier__method-info">
						<span class="epay-cashier__method-name"><?php echo epay_esc($rows['showname'])?></span>
					</div>
					<div class="epay-cashier__method-radio">
						<div class="epay-cashier__method-radio-dot"></div>
					</div>
				</li>
<?php }?>
			</ul>
		</div>

		<!-- 立即支付 -->
		<div class="epay-cashier__action">
			<button type="button" class="epay-cashier__pay-btn immediate_pay">
				<span>立即支付</span>
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
			</button>
		</div>

		<!-- 信任条 -->
		<div class="epay-cashier__trust">
			<div class="epay-cashier__trust-item">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
				<span>安全支付</span>
			</div>
			<div class="epay-cashier__trust-divider"></div>
			<div class="epay-cashier__trust-item">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
				<span>信息加密传输</span>
			</div>
			<div class="epay-cashier__trust-divider"></div>
			<div class="epay-cashier__trust-item">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
				<span>支付通道认证</span>
			</div>
		</div>
	</div>
</div>

<!-- 提示层（保留原结构与 selector） -->
<div class="mt_agree" style="display:none">
	<div class="mt_agree_main">
		<h2>提示信息</h2>
		<p id="errorContent" style="text-align:center;line-height:36px;"></p>
		<a class="close_btn">确定</a>
	</div>
</div>

<script src="<?php echo $cdnpublic?>jquery/1.12.4/jquery.min.js"></script>
<script type="text/javascript">
$(document).ready(function(){
	$(".types li").click(function(){
		$(".types li").each(function(){
			$(this).attr('class','epay-cashier__method pay_li');
		});
		$(this).attr('class','epay-cashier__method pay_li is-active');
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
