<?php
$is_defend = true;
$nosession = true;
require './includes/common.php';

@header('Content-Type: text/html; charset=UTF-8');

$other = isset($_GET['other']);
$trade_no = daddslashes(isset($_GET['trade_no']) && is_string($_GET['trade_no']) ? $_GET['trade_no'] : '');
$sitename = isset($_GET['sitename']) && is_string($_GET['sitename']) ? base64_decode(daddslashes($_GET['sitename'])) : false;
$row = $DB->getRow("SELECT * FROM pre_order WHERE trade_no='{$trade_no}' limit 1");
if (!$row) sysmsg('该订单号不存在，请返回来源地重新发起请求！');
if ($row['status'] == 1) sysmsg('该订单已完成支付，请勿重复支付');
$gid = $DB->getColumn("SELECT gid FROM pre_user WHERE uid='{$row['uid']}' limit 1");
$paytype = \lib\Channel::getTypes($row['uid'], $gid);

if (checkwechat()) {
    $paytype = array_values($paytype);
    foreach ($paytype as $i => $s) {
        if ($s['name'] == 'wxpay') {
            $temp = $paytype[$i];
            $paytype[$i] = $paytype[0];
            $paytype[0] = $temp;
        }
    }
}

if (!function_exists('epay_esc')) {
    function epay_esc($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
if (!function_exists('epay_cashier_icon_path')) {
    function epay_cashier_icon_path($name) {
        $name = (string)$name;
        if (!preg_match('/\A[a-zA-Z0-9][a-zA-Z0-9._-]*\z/', $name) || strpos($name, '..') !== false) return null;
        $icons = [
            'alipay' => '/assets/pay/icon/alipay.svg',
            'wxpay' => '/assets/pay/icon/wxpay.svg',
            'qqpay' => '/assets/pay/icon/qqpay.svg',
            'jdpay' => '/assets/pay/icon/jdpay.svg',
            'bank' => '/assets/pay/icon/unionpay.svg',
            'unionpay' => '/assets/pay/icon/unionpay.svg',
        ];
        $key = strtolower($name);
        if (isset($icons[$key]) && is_file(__DIR__ . $icons[$key])) return $icons[$key];
        $file = __DIR__ . '/assets/icon/' . $name . '.ico';
        return is_file($file) ? '/assets/icon/' . $name . '.ico' : null;
    }
}

$merchantName = $sitename ? $sitename : $conf['sitename'];
$payamount = $row['realmoney'] ? $row['realmoney'] : $row['money'];
$has_fee = $row['realmoney'] && $row['realmoney'] != $row['money'];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#f5f5f7">
<title>安全支付 | <?php echo epay_esc($merchantName) ?></title>
<link rel="stylesheet" href="/assets/css/checkout.css?v=3">
</head>
<body class="checkout-page checkout-cashier">
<main class="checkout-shell">
  <div class="checkout-brand"><img src="/assets/img/logo.png" alt=""><?php echo epay_esc($merchantName) ?></div>
  <form id="checkout-form" class="checkout-card" action="./submit2.php" method="get">
    <input type="hidden" name="trade_no" value="<?php echo epay_esc($trade_no) ?>">
    <p class="checkout-eyebrow">安全支付 · 等待付款</p>
    <strong class="checkout-amount"><span class="currency">¥</span> <?php echo epay_esc($payamount) ?></strong>
    <dl class="checkout-summary">
      <div class="checkout-row"><dt>商品信息</dt><dd><?php echo epay_esc($row['name']) ?></dd></div>
      <div class="checkout-row"><dt>订单号</dt><dd><?php echo epay_esc($trade_no) ?></dd></div>
      <div class="checkout-row"><dt>创建时间</dt><dd><?php echo epay_esc($row['addtime']) ?></dd></div>
      <?php if ($has_fee) { ?>
      <div class="checkout-row"><dt>订单金额</dt><dd>¥<?php echo epay_esc($row['money']) ?></dd></div>
      <div class="checkout-row"><dt>支付手续费</dt><dd>¥<?php echo epay_esc($row['realmoney'] - $row['money']) ?></dd></div>
      <?php } ?>
    </dl>
    <?php if ($other) { ?>
    <div class="checkout-notice" role="status">
      当前支付方式暂时关闭维护，请更换其他方式支付。
      <?php if (in_array('qqpay', array_column($paytype, 'name'))) { ?>
      <p>如需微信支付，可先将微信余额转到 QQ，再选择 QQ 钱包支付。
        <a href="./wx.html">查看操作教程</a>
      </p>
      <?php } ?>
    </div>
    <?php } ?>

    <h2 class="checkout-section-title" id="method-heading">选择支付方式</h2>
    <div class="checkout-methods" role="radiogroup" aria-labelledby="method-heading">
      <?php if (empty($paytype)) { ?>
      <p class="checkout-empty" role="status">当前没有可用的支付方式，请返回商户重试。</p>
      <?php } else { ?>
      <?php $firstMethod = true; foreach ($paytype as $rows) {
          $iconPath = epay_cashier_icon_path($rows['name']);
      ?>
      <label class="checkout-method">
        <input type="radio" name="typeid" value="<?php echo epay_esc($rows['id']) ?>"<?php echo $firstMethod ? ' checked' : '' ?> required>
        <span class="checkout-method__surface">
          <span class="checkout-method__icon" aria-hidden="true">
            <span class="checkout-method__fallback">¥</span>
            <?php if ($iconPath) { ?><img src="<?php echo epay_esc($iconPath) ?>" alt="" onerror="this.remove()"><?php } ?>
          </span>
          <span class="checkout-method__name"><?php echo epay_esc($rows['showname']) ?></span>
          <span class="check" aria-hidden="true">✓</span>
        </span>
      </label>
      <?php $firstMethod = false; } ?>
      <?php } ?>
    </div>

    <div class="checkout-actions">
      <button type="submit" id="pay-button" class="checkout-button"<?php echo empty($paytype) ? ' disabled' : '' ?>>立即支付 ¥<?php echo epay_esc($payamount) ?></button>
      <p class="checkout-error" id="payment-error" role="alert" hidden></p>
      <p class="checkout-note">支付过程由对应支付渠道安全处理</p>
    </div>
  </form>
  <p class="checkout-footer"><?php echo epay_esc($merchantName) ?></p>
</main>
<script src="/assets/js/checkout.js?v=3" defer></script>
</body>
</html>
