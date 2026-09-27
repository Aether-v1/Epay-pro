# R9 payment navigation and status pages

This inventory follows the first-party payment path on `audit/r9-unify-modern-ui`. The styling change uses `assets/css/checkout.css?v=5`; payment requests, callbacks, polling, provider URLs, form payloads, and redirect scripts are unchanged.

| Entry / view | Purpose | R9 treatment |
| --- | --- | --- |
| `includes/functions.php::submitTemplate()` | Auto-submit or redirect to a payment provider | Compact cashier-style waiting card (previous feature commit) |
| `includes/functions.php::returnTemplate()` | Payment result, automatic return to merchant | Cashier-style success or abnormal-order card, with the original automatic redirect and return button handler retained |
| `includes/functions.php::sysmsg()` | Generic first-party error or notice | Neutral interstitial card |
| `includes/pages/{alipay,wxpay,douyinpay,qqpay}_{h5,jspay,wap}.php` where present | Open provider app, show payment instructions, poll result | Neutral card and buttons; original channel icons and order details remain |
| `includes/pages/{jump,wxopen,return}.php` | Open in browser, wait for result | Neutral card; existing deep link and polling code retained |
| `includes/pages/{verify_jump,verify_invisible,verify_slide}.php` | Payment safety verification | Neutral card; verification and form code retained |
| `includes/pages/{ok,error,certok,pay_warning}.php` | Payment result or safety notice | Neutral card |
| `paypage/{success,error,red_success,wxtrans_success}.php` | Paypage result | Neutral card |

The 24 changed `includes/pages` and `paypage` PHP views differ from their previous versions only in the stylesheet version and a body class. The two helper pages in `includes/functions.php` changed as described above. `cashier.php` now requests stylesheet version 5 so the mobile centering fix is loaded after deployment.

`Payment::processOrder()` already sent abnormal or blocked orders to `/payerr.html`, but the shared return page previously said “支付成功” first. That branch now passes `false` to `returnTemplate()` and displays “订单未完成” before following the same `/payerr.html` URL.

Other navigation paths found:

- `includes/lib/Payment.php` has `jump`, `html`, `page`, `qrcode`, `scheme`, and `return` result types. `jump` and `html` use `submitTemplate()`; `return` uses `returnTemplate()`. Provider-hosted pages and redirects without a rendered local document cannot be styled here.
- `includes/pages/*_qrcode.php` and `alipay_qrcodepc.php` are active QR payment screens. They retain their existing layout; their paid-state redirects still use the channel response `backurl`.
- `paypage/red_confirm.php`, `red_confirmwx.php`, and `wxtrans_confirm.php` require user payment or transfer confirmation, so they retain their existing layout and behavior.
- `paypage/index.php` is the amount-entry page. `includes/pages/openid.php` is an OAuth result page. Neither is a cashier transition screen.
- `paypage/inc.php` and some channel or plugin paths use HTTP `Location` or JavaScript redirects directly; these produce no local interstitial page to restyle.

Verification: `php -l` passed for all changed PHP files; `git diff --check` passed. Isolated PHP fixtures confirmed merchant HTML is escaped, both success and abnormal-order titles are correct, and the original return destination is preserved. Headless Chrome reached a local destination through both return variants' automatic redirects. Screenshots checked the merchant return, abnormal-order return, result waiting, Alipay H5, browser-open, and verification views. CDP measured no horizontal overflow at 390px for the return, Alipay H5, and browser-open views after the box-sizing fix. At 320×568, Alipay H5 has a 288px card and a scrollable 773px document; the cashier has a 288px card and a scrollable 880px document. A short return card is vertically centered at 390×500 (top 104px, bottom 388px). Real payment channels, callbacks, and the VPS deployment still need live verification.
