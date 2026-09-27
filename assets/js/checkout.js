(function () {
  'use strict';

  var form = document.getElementById('checkout-form');
  var payButton = document.getElementById('pay-button');
  var error = document.getElementById('payment-error');
  if (!form || !payButton || !error) return;

  var submitting = false;
  var originalLabel = payButton.textContent;

  form.addEventListener('submit', function (event) {
    event.preventDefault();
    if (submitting) return;

    var selected = form.querySelector('input[name="typeid"]:checked');
    var tradeNo = form.querySelector('input[name="trade_no"]');
    if (!selected || !selected.value) {
      error.textContent = '请选择支付方式后继续';
      error.hidden = false;
      return;
    }
    if (!tradeNo || !tradeNo.value) {
      error.textContent = '订单信息不完整，请返回商户重新发起支付';
      error.hidden = false;
      return;
    }

    error.hidden = true;
    submitting = true;
    payButton.disabled = true;
    payButton.textContent = '正在跳转支付…';
    window.location.assign('./submit2.php?typeid=' + encodeURIComponent(selected.value) +
      '&trade_no=' + encodeURIComponent(tradeNo.value));
  });

  form.addEventListener('change', function () {
    error.hidden = true;
  });

  window.addEventListener('pageshow', function () {
    if (submitting) {
      submitting = false;
      payButton.disabled = false;
      payButton.textContent = originalLabel;
    }
  });
}());
