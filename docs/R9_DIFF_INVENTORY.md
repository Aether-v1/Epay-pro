# R9 dual-tree difference inventory

## Scope and baseline

- Outer reference: E:/Users/orang/Downloads/Compressed/epay, main a28edfa.
- Canonical: nested Aether-v1/Epay-pro, master 22ac78d before R9, working branch audit/r9-unify-modern-ui.
- Compared recursively by relative path, size and SHA256 after the initial V3 edit. The inner-only count therefore includes the newly added assets/js/checkout.js; before implementation it was 13. Excluded Git metadata, nested repository from the outer traversal, cache/logs/runtime/sessions, uploads, node_modules, zip and IDE swap files. Third-party files were compared; identical files are omitted.
- This is a path inventory, not permission to copy or delete each difference. The inner branch contains later business and security changes.

## Counts

| Identical | Outer only | Inner only | Different |
| ---: | ---: | ---: | ---: |
| 744 | 5 | 14 | 521 |

## Decision

- KEEP canonical: inner business, security, configuration, database, admin, user and vendor code. Outer has not been proven to supersede these later changes.
- MERGE cashier: follow the outer `docs/checkout-preview.html?v=3` appearance and method selection, retain the inner safe server-side order and channel logic, and add an icon fallback and encoded redirect. The premium gradient appearance was rejected after visual review.
- Canonical CSS: assets/css/checkout.css. The outer assets/pay/css/checkout.css remains reference material and is not copied wholesale.
- Canonical cashier JS: assets/js/checkout.js. Outer assets/pay/js/checkout.js is a source reference.
- Payment frontend pages: inner implementation already uses the canonical CSS in includes/pages, paypage and template/default. Outer versions require per-page verification before any further migration.
- Outer-only docs/checkout-* are reference material. Outer README.md is missing from its working tree; inner README.md is retained.
- Unknown and third-party differences remain REVIEW. No dependency or production data was removed by this inventory.

## Complete differing-path manifest

| Relative path | State | Classification |
| --- | --- | --- |
| .gitignore | only inner | configuration or unknown / REVIEW |
| .htaccess | only inner | configuration or unknown / REVIEW |
| admin/ajax_order.php | different | business or security / KEEP canonical |
| admin/ajax_pay.php | different | business or security / KEEP canonical |
| admin/ajax_profitsharing.php | different | business or security / KEEP canonical |
| admin/ajax_settle.php | different | business or security / KEEP canonical |
| admin/ajax_transfer.php | different | business or security / KEEP canonical |
| admin/ajax_user.php | different | business or security / KEEP canonical |
| admin/ajax.php | different | business or security / KEEP canonical |
| admin/blacklist.php | different | business or security / KEEP canonical |
| admin/buyerstat.php | different | business or security / KEEP canonical |
| admin/clean.php | different | business or security / KEEP canonical |
| admin/code.php | different | business or security / KEEP canonical |
| admin/domain.php | different | business or security / KEEP canonical |
| admin/download.php | different | business or security / KEEP canonical |
| admin/export.php | different | business or security / KEEP canonical |
| admin/gedit.php | different | business or security / KEEP canonical |
| admin/gettoken.php | different | business or security / KEEP canonical |
| admin/glist.php | different | business or security / KEEP canonical |
| admin/gonggao.php | different | business or security / KEEP canonical |
| admin/group.php | different | business or security / KEEP canonical |
| admin/head.php | different | business or security / KEEP canonical |
| admin/invitecode.php | different | business or security / KEEP canonical |
| admin/log.php | different | business or security / KEEP canonical |
| admin/login.php | different | business or security / KEEP canonical |
| admin/order.php | different | business or security / KEEP canonical |
| admin/pay_channel.php | different | business or security / KEEP canonical |
| admin/pay_plugin.php | different | business or security / KEEP canonical |
| admin/pay_roll.php | different | business or security / KEEP canonical |
| admin/pay_type.php | different | business or security / KEEP canonical |
| admin/pay_weixin.php | different | business or security / KEEP canonical |
| admin/pay_wework.php | different | business or security / KEEP canonical |
| admin/plugin_page.php | different | business or security / KEEP canonical |
| admin/ps_order.php | different | business or security / KEEP canonical |
| admin/ps_receiver.php | different | business or security / KEEP canonical |
| admin/record_export.php | different | business or security / KEEP canonical |
| admin/record.php | different | business or security / KEEP canonical |
| admin/refund_unknown.php | only inner | business or security / KEEP canonical |
| admin/risk.php | different | business or security / KEEP canonical |
| admin/set_totp.php | different | business or security / KEEP canonical |
| admin/set_wxkf.php | different | business or security / KEEP canonical |
| admin/set.php | different | business or security / KEEP canonical |
| admin/settle_batch.php | different | business or security / KEEP canonical |
| admin/settle.php | different | business or security / KEEP canonical |
| admin/slist.php | different | business or security / KEEP canonical |
| admin/sso.php | different | business or security / KEEP canonical |
| admin/testsubmit.php | different | business or security / KEEP canonical |
| admin/transfer_add.php | different | business or security / KEEP canonical |
| admin/transfer_batch.php | different | business or security / KEEP canonical |
| admin/transfer_export.php | different | business or security / KEEP canonical |
| admin/transfer_red.php | different | business or security / KEEP canonical |
| admin/transfer_stat.php | different | business or security / KEEP canonical |
| admin/transfer.php | different | business or security / KEEP canonical |
| admin/ulist.php | different | business or security / KEEP canonical |
| admin/uset.php | different | business or security / KEEP canonical |
| admin/ustat.php | different | business or security / KEEP canonical |
| assets/css/bootstrap.min.css | different | CSS / REVIEW |
| assets/css/checkout.css | only inner | CSS / REVIEW |
| assets/css/main12.css | different | CSS / REVIEW |
| assets/css/weui.min.css | different | CSS / REVIEW |
| assets/doc/css/docView.css | different | CSS / REVIEW |
| assets/doc/css/style.css | different | CSS / REVIEW |
| assets/doc/js/docView.js | different | JS / REVIEW |
| assets/doc/js/home.js | different | JS / REVIEW |
| assets/js/bootstrap-table-page-jump-to.min.js | different | JS / REVIEW |
| assets/js/bootstrap-table.min.js | different | JS / REVIEW |
| assets/js/chart.js | different | JS / REVIEW |
| assets/js/checkout.js | only inner | JS / REVIEW |
| assets/pay/css/checkout.css | only outer | CSS / REVIEW |
| assets/pay/css/mobile-style.css | different | CSS / REVIEW |
| assets/pay/icon/wxpay-white.svg | different | image or icon / REVIEW |
| assets/pay/icon/wxpay.svg | different | image or icon / REVIEW |
| assets/pay/js/checkout.js | only outer | JS / REVIEW |
| assets/vendor/animate.css/3.7.2/animate.min.css | different | third-party dependency / REVIEW |
| assets/vendor/bootstrap-colorpicker/2.5.3/css/bootstrap-colorpicker.min.css | different | third-party dependency / REVIEW |
| assets/vendor/bootstrap-colorpicker/2.5.3/js/bootstrap-colorpicker.min.js | different | third-party dependency / REVIEW |
| assets/vendor/bootstrap-datepicker/1.10.0/css/bootstrap-datepicker3.min.css | different | third-party dependency / REVIEW |
| assets/vendor/bootstrap-datepicker/1.10.0/js/bootstrap-datepicker.min.js | different | third-party dependency / REVIEW |
| assets/vendor/font-awesome/4.7.0/css/font-awesome.min.css | different | third-party dependency / REVIEW |
| assets/vendor/font-awesome/4.7.0/fonts/fontawesome-webfont.svg | different | third-party dependency / REVIEW |
| assets/vendor/jquery.scrollbar/0.2.11/jquery.scrollbar.min.js | different | third-party dependency / REVIEW |
| assets/vendor/jquery/3.4.1/jquery.min.js | different | third-party dependency / REVIEW |
| assets/vendor/layer/3.1.1/layer.js | different | third-party dependency / REVIEW |
| assets/vendor/layer/3.1.1/mobile/layer.js | different | third-party dependency / REVIEW |
| assets/vendor/layui/2.6.13/font/iconfont.svg | different | third-party dependency / REVIEW |
| assets/vendor/OwlCarousel2/2.3.4/assets/owl.carousel.min.css | different | third-party dependency / REVIEW |
| assets/vendor/OwlCarousel2/2.3.4/owl.carousel.min.js | different | third-party dependency / REVIEW |
| assets/vendor/select2/4.0.13/css/select2.min.css | different | third-party dependency / REVIEW |
| assets/vendor/select2/4.0.13/js/select2.min.js | different | third-party dependency / REVIEW |
| assets/vendor/three.js/56/three.min.js | different | third-party dependency / REVIEW |
| assets/vendor/twitter-bootstrap/3.4.1/css/bootstrap-theme.min.css | different | third-party dependency / REVIEW |
| assets/vendor/twitter-bootstrap/3.4.1/css/bootstrap.min.css | different | third-party dependency / REVIEW |
| assets/vendor/twitter-bootstrap/3.4.1/fonts/glyphicons-halflings-regular.svg | different | third-party dependency / REVIEW |
| assets/vendor/twitter-bootstrap/3.4.1/js/bootstrap.min.js | different | third-party dependency / REVIEW |
| assets/vendor/twitter-bootstrap/4.6.2/css/bootstrap.min.css | different | third-party dependency / REVIEW |
| assets/vendor/twitter-bootstrap/4.6.2/js/bootstrap.bundle.min.js | different | third-party dependency / REVIEW |
| assets/vendor/twitter-bootstrap/5.1.3/css/bootstrap.min.css | different | third-party dependency / REVIEW |
| assets/vendor/twitter-bootstrap/5.1.3/js/bootstrap.bundle.min.js | different | third-party dependency / REVIEW |
| assets/vendor/vue/2.7.16/vue.min.js | different | third-party dependency / REVIEW |
| assets/vendor/wow/1.1.2/wow.min.js | different | third-party dependency / REVIEW |
| assets/vendor/xlsx/xlsx.full.min.js | different | third-party dependency / REVIEW |
| cashier.php | different | cashier UI / MERGE |
| config.php.example | only inner | configuration or unknown / REVIEW |
| docs/BAOTA-DEPLOYMENT.md | only inner | documentation / REVIEW |
| docs/checkout-audit.md | only outer | documentation / REVIEW |
| docs/checkout-preview.html | only outer | documentation / REVIEW |
| docs/checkout-regression.md | only outer | documentation / REVIEW |
| docs/PRODUCTION-DEPLOYMENT.md | only inner | documentation / REVIEW |
| includes/360safe/360webscan.php | different | configuration or unknown / REVIEW |
| includes/360safe/webscan_cache.php | different | configuration or unknown / REVIEW |
| includes/common.php | different | business or security / KEEP canonical |
| includes/composer.json | different | configuration or unknown / REVIEW |
| includes/functions.php | different | configuration or unknown / REVIEW |
| includes/lib/AliyunCertify.php | different | business or security / KEEP canonical |
| includes/lib/AntiDigitalCertify.php | different | business or security / KEEP canonical |
| includes/lib/Cache.php | different | business or security / KEEP canonical |
| includes/lib/Channel.php | different | business or security / KEEP canonical |
| includes/lib/Ip2Region.php | different | business or security / KEEP canonical |
| includes/lib/mail/Aliyun.php | different | business or security / KEEP canonical |
| includes/lib/mail/PHPMailer/Exception.php | different | business or security / KEEP canonical |
| includes/lib/mail/PHPMailer/PHPMailer.php | different | business or security / KEEP canonical |
| includes/lib/mail/PHPMailer/SMTP.php | different | business or security / KEEP canonical |
| includes/lib/mail/Sendcloud.php | different | business or security / KEEP canonical |
| includes/lib/Order.php | different | business or security / KEEP canonical |
| includes/lib/Payment.php | different | business or security / KEEP canonical |
| includes/lib/PdoHelper.php | different | business or security / KEEP canonical |
| includes/lib/Plugin.php | different | business or security / KEEP canonical |
| includes/lib/QcloudFaceid.php | different | business or security / KEEP canonical |
| includes/lib/sms/Aliyun.php | different | business or security / KEEP canonical |
| includes/lib/sms/SmsBao.php | different | business or security / KEEP canonical |
| includes/lib/Template.php | different | business or security / KEEP canonical |
| includes/lib/TOTP.php | different | business or security / KEEP canonical |
| includes/lib/Transfer.php | different | business or security / KEEP canonical |
| includes/lib/VerifyCode.php | different | business or security / KEEP canonical |
| includes/lib/XdbSearcher.php | different | business or security / KEEP canonical |
| includes/member.php | different | business or security / KEEP canonical |
| includes/pages/alipay_h5.php | different | payment frontend / REVIEW |
| includes/pages/alipay_jspay.php | different | payment frontend / REVIEW |
| includes/pages/alipay_qrcode.php | different | payment frontend / REVIEW |
| includes/pages/alipay_qrcodepc.php | different | payment frontend / REVIEW |
| includes/pages/bank_qrcode.php | different | payment frontend / REVIEW |
| includes/pages/certok.php | different | payment frontend / REVIEW |
| includes/pages/douyinpay_h5.php | different | payment frontend / REVIEW |
| includes/pages/douyinpay_jspay.php | different | payment frontend / REVIEW |
| includes/pages/douyinpay_qrcode.php | different | payment frontend / REVIEW |
| includes/pages/douyinpay_wap.php | different | payment frontend / REVIEW |
| includes/pages/error.php | different | payment frontend / REVIEW |
| includes/pages/jdpay_qrcode.php | different | payment frontend / REVIEW |
| includes/pages/jump.php | different | payment frontend / REVIEW |
| includes/pages/ok.php | different | payment frontend / REVIEW |
| includes/pages/openid.php | different | payment frontend / REVIEW |
| includes/pages/pay_warning.php | different | payment frontend / REVIEW |
| includes/pages/qqpay_jspay.php | different | payment frontend / REVIEW |
| includes/pages/qqpay_qrcode.php | different | payment frontend / REVIEW |
| includes/pages/qqpay_wap.php | different | payment frontend / REVIEW |
| includes/pages/return.php | different | payment frontend / REVIEW |
| includes/pages/verify_invisible.php | different | payment frontend / REVIEW |
| includes/pages/verify_jump.php | different | payment frontend / REVIEW |
| includes/pages/verify_slide.php | different | payment frontend / REVIEW |
| includes/pages/wxopen.php | different | payment frontend / REVIEW |
| includes/pages/wxpay_h5.php | different | payment frontend / REVIEW |
| includes/pages/wxpay_jspay.php | different | payment frontend / REVIEW |
| includes/pages/wxpay_qrcode.php | different | payment frontend / REVIEW |
| includes/pages/wxpay_wap.php | different | payment frontend / REVIEW |
| includes/qrcodedecoder/bootstrap.php | different | third-party dependency / REVIEW |
| includes/qrcodedecoder/Zxing/Binarizer.php | different | third-party dependency / REVIEW |
| includes/qrcodedecoder/Zxing/BinaryBitmap.php | different | third-party dependency / REVIEW |
| includes/qrcodedecoder/Zxing/ChecksumException.php | different | third-party dependency / REVIEW |
| includes/qrcodedecoder/Zxing/Common/AbstractEnum.php | different | third-party dependency / REVIEW |
| includes/qrcodedecoder/Zxing/Common/BitArray.php | different | third-party dependency / REVIEW |
| includes/qrcodedecoder/Zxing/Common/BitMatrix.php | different | third-party dependency / REVIEW |
| includes/qrcodedecoder/Zxing/Common/BitSource.php | different | third-party dependency / REVIEW |
| includes/qrcodedecoder/Zxing/Common/CharacterSetECI.php | different | third-party dependency / REVIEW |
| includes/qrcodedecoder/Zxing/Common/customFunctions.php | different | third-party dependency / REVIEW |
| includes/qrcodedecoder/Zxing/Common/DecoderResult.php | different | third-party dependency / REVIEW |
| includes/qrcodedecoder/Zxing/Common/DefaultGridSampler.php | different | third-party dependency / REVIEW |
| includes/qrcodedecoder/Zxing/Common/Detector/MathUtils.php | different | third-party dependency / REVIEW |
| includes/qrcodedecoder/Zxing/Common/Detector/MonochromeRectangleDetector.php | different | third-party dependency / REVIEW |
| includes/qrcodedecoder/Zxing/Common/DetectorResult.php | different | third-party dependency / REVIEW |
| includes/qrcodedecoder/Zxing/Common/GlobalHistogramBinarizer.php | different | third-party dependency / REVIEW |
| includes/qrcodedecoder/Zxing/Common/GridSampler.php | different | third-party dependency / REVIEW |
| includes/qrcodedecoder/Zxing/Common/HybridBinarizer.php | different | third-party dependency / REVIEW |
| includes/qrcodedecoder/Zxing/Common/PerspectiveTransform.php | different | third-party dependency / REVIEW |
| includes/qrcodedecoder/Zxing/Common/Reedsolomon/GenericGF.php | different | third-party dependency / REVIEW |
| includes/qrcodedecoder/Zxing/Common/Reedsolomon/GenericGFPoly.php | different | third-party dependency / REVIEW |
| includes/qrcodedecoder/Zxing/Common/Reedsolomon/ReedSolomonDecoder.php | different | third-party dependency / REVIEW |
| includes/qrcodedecoder/Zxing/Common/Reedsolomon/ReedSolomonException.php | different | third-party dependency / REVIEW |
| includes/qrcodedecoder/Zxing/FormatException.php | different | third-party dependency / REVIEW |
| includes/qrcodedecoder/Zxing/GDLuminanceSource.php | different | third-party dependency / REVIEW |
| includes/qrcodedecoder/Zxing/IMagickLuminanceSource.php | different | third-party dependency / REVIEW |
| includes/qrcodedecoder/Zxing/LuminanceSource.php | different | third-party dependency / REVIEW |
| includes/qrcodedecoder/Zxing/NotFoundException.php | different | third-party dependency / REVIEW |
| includes/qrcodedecoder/Zxing/PlanarYUVLuminanceSource.php | different | third-party dependency / REVIEW |
| includes/qrcodedecoder/Zxing/Qrcode/Decoder/BitMatrixParser.php | different | third-party dependency / REVIEW |
| includes/qrcodedecoder/Zxing/Qrcode/Decoder/DataBlock.php | different | third-party dependency / REVIEW |
| includes/qrcodedecoder/Zxing/Qrcode/Decoder/DataMask.php | different | third-party dependency / REVIEW |
| includes/qrcodedecoder/Zxing/Qrcode/Decoder/DecodedBitStreamParser.php | different | third-party dependency / REVIEW |
| includes/qrcodedecoder/Zxing/Qrcode/Decoder/Decoder.php | different | third-party dependency / REVIEW |
| includes/qrcodedecoder/Zxing/Qrcode/Decoder/ErrorCorrectionLevel.php | different | third-party dependency / REVIEW |
| includes/qrcodedecoder/Zxing/Qrcode/Decoder/FormatInformation.php | different | third-party dependency / REVIEW |
| includes/qrcodedecoder/Zxing/Qrcode/Decoder/Mode.php | different | third-party dependency / REVIEW |
| includes/qrcodedecoder/Zxing/Qrcode/Decoder/QRCodeDecoderMetaData.php | different | third-party dependency / REVIEW |
| includes/qrcodedecoder/Zxing/Qrcode/Decoder/Version.php | different | third-party dependency / REVIEW |
| includes/qrcodedecoder/Zxing/Qrcode/Detector/AlignmentPattern.php | different | third-party dependency / REVIEW |
| includes/qrcodedecoder/Zxing/Qrcode/Detector/AlignmentPatternFinder.php | different | third-party dependency / REVIEW |
| includes/qrcodedecoder/Zxing/Qrcode/Detector/Detector.php | different | third-party dependency / REVIEW |
| includes/qrcodedecoder/Zxing/Qrcode/Detector/FinderPattern.php | different | third-party dependency / REVIEW |
| includes/qrcodedecoder/Zxing/Qrcode/Detector/FinderPatternFinder.php | different | third-party dependency / REVIEW |
| includes/qrcodedecoder/Zxing/Qrcode/Detector/FinderPatternInfo.php | different | third-party dependency / REVIEW |
| includes/qrcodedecoder/Zxing/Qrcode/QRCodeReader.php | different | third-party dependency / REVIEW |
| includes/qrcodedecoder/Zxing/QrReader.php | different | third-party dependency / REVIEW |
| includes/qrcodedecoder/Zxing/Reader.php | different | third-party dependency / REVIEW |
| includes/qrcodedecoder/Zxing/ReaderException.php | different | third-party dependency / REVIEW |
| includes/qrcodedecoder/Zxing/Result.php | different | third-party dependency / REVIEW |
| includes/qrcodedecoder/Zxing/ResultPoint.php | different | third-party dependency / REVIEW |
| includes/qrcodedecoder/Zxing/RGBLuminanceSource.php | different | third-party dependency / REVIEW |
| includes/vendor/autoload.php | different | third-party dependency / REVIEW |
| includes/vendor/cccyun/alipay-sdk/composer.json | different | third-party dependency / REVIEW |
| includes/vendor/cccyun/alipay-sdk/LICENSE | different | third-party dependency / REVIEW |
| includes/vendor/cccyun/alipay-sdk/README.md | different | third-party dependency / REVIEW |
| includes/vendor/cccyun/alipay-sdk/src/AlipayBillService.php | different | third-party dependency / REVIEW |
| includes/vendor/cccyun/alipay-sdk/src/AlipayCertdocService.php | different | third-party dependency / REVIEW |
| includes/vendor/cccyun/alipay-sdk/src/AlipayCertifyService.php | different | third-party dependency / REVIEW |
| includes/vendor/cccyun/alipay-sdk/src/AlipayComplainService.php | different | third-party dependency / REVIEW |
| includes/vendor/cccyun/alipay-sdk/src/AlipayOauthService.php | different | third-party dependency / REVIEW |
| includes/vendor/cccyun/alipay-sdk/src/AlipayService.php | different | third-party dependency / REVIEW |
| includes/vendor/cccyun/alipay-sdk/src/AlipaySettleService.php | different | third-party dependency / REVIEW |
| includes/vendor/cccyun/alipay-sdk/src/AlipayTradeService.php | different | third-party dependency / REVIEW |
| includes/vendor/cccyun/alipay-sdk/src/AlipayTransferService.php | different | third-party dependency / REVIEW |
| includes/vendor/cccyun/alipay-sdk/src/Aop/AlipayCertHelper.php | different | third-party dependency / REVIEW |
| includes/vendor/cccyun/alipay-sdk/src/Aop/AlipayRequest.php | different | third-party dependency / REVIEW |
| includes/vendor/cccyun/alipay-sdk/src/Aop/AlipayResponse.php | different | third-party dependency / REVIEW |
| includes/vendor/cccyun/alipay-sdk/src/Aop/AlipayResponseException.php | different | third-party dependency / REVIEW |
| includes/vendor/cccyun/alipay-sdk/src/Aop/AopClient.php | different | third-party dependency / REVIEW |
| includes/vendor/cccyun/qqpay-sdk/composer.json | different | third-party dependency / REVIEW |
| includes/vendor/cccyun/qqpay-sdk/LICENSE | different | third-party dependency / REVIEW |
| includes/vendor/cccyun/qqpay-sdk/README.md | different | third-party dependency / REVIEW |
| includes/vendor/cccyun/qqpay-sdk/src/BaseService.php | different | third-party dependency / REVIEW |
| includes/vendor/cccyun/qqpay-sdk/src/PaymentService.php | different | third-party dependency / REVIEW |
| includes/vendor/cccyun/qqpay-sdk/src/QQPayException.php | different | third-party dependency / REVIEW |
| includes/vendor/cccyun/qqpay-sdk/src/TransferService.php | different | third-party dependency / REVIEW |
| includes/vendor/cccyun/wechatpay-sdk/composer.json | different | third-party dependency / REVIEW |
| includes/vendor/cccyun/wechatpay-sdk/LICENSE | different | third-party dependency / REVIEW |
| includes/vendor/cccyun/wechatpay-sdk/README.md | different | third-party dependency / REVIEW |
| includes/vendor/cccyun/wechatpay-sdk/src/BaseService.php | different | third-party dependency / REVIEW |
| includes/vendor/cccyun/wechatpay-sdk/src/JsApiTool.php | different | third-party dependency / REVIEW |
| includes/vendor/cccyun/wechatpay-sdk/src/PaymentService.php | different | third-party dependency / REVIEW |
| includes/vendor/cccyun/wechatpay-sdk/src/ProfitsharingService.php | different | third-party dependency / REVIEW |
| includes/vendor/cccyun/wechatpay-sdk/src/TransferService.php | different | third-party dependency / REVIEW |
| includes/vendor/cccyun/wechatpay-sdk/src/V3/BaseService.php | different | third-party dependency / REVIEW |
| includes/vendor/cccyun/wechatpay-sdk/src/V3/ComplainService.php | different | third-party dependency / REVIEW |
| includes/vendor/cccyun/wechatpay-sdk/src/V3/GlobalPaymentService.php | different | third-party dependency / REVIEW |
| includes/vendor/cccyun/wechatpay-sdk/src/V3/PartnerPaymentService.php | different | third-party dependency / REVIEW |
| includes/vendor/cccyun/wechatpay-sdk/src/V3/PaymentService.php | different | third-party dependency / REVIEW |
| includes/vendor/cccyun/wechatpay-sdk/src/V3/ProfitsharingService.php | different | third-party dependency / REVIEW |
| includes/vendor/cccyun/wechatpay-sdk/src/V3/TransferService.php | different | third-party dependency / REVIEW |
| includes/vendor/cccyun/wechatpay-sdk/src/V3/WeChatPayException.php | different | third-party dependency / REVIEW |
| includes/vendor/cccyun/wechatpay-sdk/src/WeChatPayException.php | different | third-party dependency / REVIEW |
| includes/vendor/composer/autoload_classmap.php | different | third-party dependency / REVIEW |
| includes/vendor/composer/autoload_files.php | different | third-party dependency / REVIEW |
| includes/vendor/composer/autoload_namespaces.php | different | third-party dependency / REVIEW |
| includes/vendor/composer/autoload_psr4.php | different | third-party dependency / REVIEW |
| includes/vendor/composer/autoload_real.php | different | third-party dependency / REVIEW |
| includes/vendor/composer/autoload_static.php | different | third-party dependency / REVIEW |
| includes/vendor/composer/ClassLoader.php | different | third-party dependency / REVIEW |
| includes/vendor/composer/installed.json | different | third-party dependency / REVIEW |
| includes/vendor/composer/installed.php | different | third-party dependency / REVIEW |
| includes/vendor/composer/InstalledVersions.php | different | third-party dependency / REVIEW |
| includes/vendor/composer/LICENSE | different | third-party dependency / REVIEW |
| includes/vendor/composer/platform_check.php | different | third-party dependency / REVIEW |
| includes/vendor/fgrosse/phpasn1/CHANGELOG.md | different | third-party dependency / REVIEW |
| includes/vendor/fgrosse/phpasn1/composer.json | different | third-party dependency / REVIEW |
| includes/vendor/fgrosse/phpasn1/lib/ASN1/AbstractString.php | different | third-party dependency / REVIEW |
| includes/vendor/fgrosse/phpasn1/lib/ASN1/AbstractTime.php | different | third-party dependency / REVIEW |
| includes/vendor/fgrosse/phpasn1/lib/ASN1/ASNObject.php | different | third-party dependency / REVIEW |
| includes/vendor/fgrosse/phpasn1/lib/ASN1/Base128.php | different | third-party dependency / REVIEW |
| includes/vendor/fgrosse/phpasn1/lib/ASN1/Composite/AttributeTypeAndValue.php | different | third-party dependency / REVIEW |
| includes/vendor/fgrosse/phpasn1/lib/ASN1/Composite/RDNString.php | different | third-party dependency / REVIEW |
| includes/vendor/fgrosse/phpasn1/lib/ASN1/Composite/RelativeDistinguishedName.php | different | third-party dependency / REVIEW |
| includes/vendor/fgrosse/phpasn1/lib/ASN1/Construct.php | different | third-party dependency / REVIEW |
| includes/vendor/fgrosse/phpasn1/lib/ASN1/Exception/NotImplementedException.php | different | third-party dependency / REVIEW |
| includes/vendor/fgrosse/phpasn1/lib/ASN1/Exception/ParserException.php | different | third-party dependency / REVIEW |
| includes/vendor/fgrosse/phpasn1/lib/ASN1/ExplicitlyTaggedObject.php | different | third-party dependency / REVIEW |
| includes/vendor/fgrosse/phpasn1/lib/ASN1/Identifier.php | different | third-party dependency / REVIEW |
| includes/vendor/fgrosse/phpasn1/lib/ASN1/OID.php | different | third-party dependency / REVIEW |
| includes/vendor/fgrosse/phpasn1/lib/ASN1/Parsable.php | different | third-party dependency / REVIEW |
| includes/vendor/fgrosse/phpasn1/lib/ASN1/TemplateParser.php | different | third-party dependency / REVIEW |
| includes/vendor/fgrosse/phpasn1/lib/ASN1/Universal/BitString.php | different | third-party dependency / REVIEW |
| includes/vendor/fgrosse/phpasn1/lib/ASN1/Universal/BMPString.php | different | third-party dependency / REVIEW |
| includes/vendor/fgrosse/phpasn1/lib/ASN1/Universal/Boolean.php | different | third-party dependency / REVIEW |
| includes/vendor/fgrosse/phpasn1/lib/ASN1/Universal/CharacterString.php | different | third-party dependency / REVIEW |
| includes/vendor/fgrosse/phpasn1/lib/ASN1/Universal/Enumerated.php | different | third-party dependency / REVIEW |
| includes/vendor/fgrosse/phpasn1/lib/ASN1/Universal/GeneralizedTime.php | different | third-party dependency / REVIEW |
| includes/vendor/fgrosse/phpasn1/lib/ASN1/Universal/GeneralString.php | different | third-party dependency / REVIEW |
| includes/vendor/fgrosse/phpasn1/lib/ASN1/Universal/GraphicString.php | different | third-party dependency / REVIEW |
| includes/vendor/fgrosse/phpasn1/lib/ASN1/Universal/IA5String.php | different | third-party dependency / REVIEW |
| includes/vendor/fgrosse/phpasn1/lib/ASN1/Universal/Integer.php | different | third-party dependency / REVIEW |
| includes/vendor/fgrosse/phpasn1/lib/ASN1/Universal/NullObject.php | different | third-party dependency / REVIEW |
| includes/vendor/fgrosse/phpasn1/lib/ASN1/Universal/NumericString.php | different | third-party dependency / REVIEW |
| includes/vendor/fgrosse/phpasn1/lib/ASN1/Universal/ObjectDescriptor.php | different | third-party dependency / REVIEW |
| includes/vendor/fgrosse/phpasn1/lib/ASN1/Universal/ObjectIdentifier.php | different | third-party dependency / REVIEW |
| includes/vendor/fgrosse/phpasn1/lib/ASN1/Universal/OctetString.php | different | third-party dependency / REVIEW |
| includes/vendor/fgrosse/phpasn1/lib/ASN1/Universal/PrintableString.php | different | third-party dependency / REVIEW |
| includes/vendor/fgrosse/phpasn1/lib/ASN1/Universal/RelativeObjectIdentifier.php | different | third-party dependency / REVIEW |
| includes/vendor/fgrosse/phpasn1/lib/ASN1/Universal/Sequence.php | different | third-party dependency / REVIEW |
| includes/vendor/fgrosse/phpasn1/lib/ASN1/Universal/Set.php | different | third-party dependency / REVIEW |
| includes/vendor/fgrosse/phpasn1/lib/ASN1/Universal/T61String.php | different | third-party dependency / REVIEW |
| includes/vendor/fgrosse/phpasn1/lib/ASN1/Universal/UniversalString.php | different | third-party dependency / REVIEW |
| includes/vendor/fgrosse/phpasn1/lib/ASN1/Universal/UTCTime.php | different | third-party dependency / REVIEW |
| includes/vendor/fgrosse/phpasn1/lib/ASN1/Universal/UTF8String.php | different | third-party dependency / REVIEW |
| includes/vendor/fgrosse/phpasn1/lib/ASN1/Universal/VisibleString.php | different | third-party dependency / REVIEW |
| includes/vendor/fgrosse/phpasn1/lib/ASN1/UnknownConstructedObject.php | different | third-party dependency / REVIEW |
| includes/vendor/fgrosse/phpasn1/lib/ASN1/UnknownObject.php | different | third-party dependency / REVIEW |
| includes/vendor/fgrosse/phpasn1/lib/Utility/BigInteger.php | different | third-party dependency / REVIEW |
| includes/vendor/fgrosse/phpasn1/lib/Utility/BigIntegerBcmath.php | different | third-party dependency / REVIEW |
| includes/vendor/fgrosse/phpasn1/lib/Utility/BigIntegerGmp.php | different | third-party dependency / REVIEW |
| includes/vendor/fgrosse/phpasn1/lib/X509/AlgorithmIdentifier.php | different | third-party dependency / REVIEW |
| includes/vendor/fgrosse/phpasn1/lib/X509/CertificateExtensions.php | different | third-party dependency / REVIEW |
| includes/vendor/fgrosse/phpasn1/lib/X509/CertificateSubject.php | different | third-party dependency / REVIEW |
| includes/vendor/fgrosse/phpasn1/lib/X509/CSR/Attributes.php | different | third-party dependency / REVIEW |
| includes/vendor/fgrosse/phpasn1/lib/X509/CSR/CSR.php | different | third-party dependency / REVIEW |
| includes/vendor/fgrosse/phpasn1/lib/X509/PrivateKey.php | different | third-party dependency / REVIEW |
| includes/vendor/fgrosse/phpasn1/lib/X509/PublicKey.php | different | third-party dependency / REVIEW |
| includes/vendor/fgrosse/phpasn1/lib/X509/SAN/DNSName.php | different | third-party dependency / REVIEW |
| includes/vendor/fgrosse/phpasn1/lib/X509/SAN/IPAddress.php | different | third-party dependency / REVIEW |
| includes/vendor/fgrosse/phpasn1/lib/X509/SAN/SubjectAlternativeNames.php | different | third-party dependency / REVIEW |
| includes/vendor/fgrosse/phpasn1/LICENSE | different | third-party dependency / REVIEW |
| includes/vendor/fgrosse/phpasn1/README.md | different | third-party dependency / REVIEW |
| includes/vendor/lpilp/guomi/.gitignore | different | third-party dependency / REVIEW |
| includes/vendor/lpilp/guomi/composer.json | different | third-party dependency / REVIEW |
| includes/vendor/lpilp/guomi/README.md | different | third-party dependency / REVIEW |
| includes/vendor/lpilp/guomi/src/ecc/Curves/CurveFactory.php | different | third-party dependency / REVIEW |
| includes/vendor/lpilp/guomi/src/ecc/RtEccFactory.php | different | third-party dependency / REVIEW |
| includes/vendor/lpilp/guomi/src/ecc/Serializer/Util/CurveOidMapper.php | different | third-party dependency / REVIEW |
| includes/vendor/lpilp/guomi/src/ecc/Sm2Curve.php | different | third-party dependency / REVIEW |
| includes/vendor/lpilp/guomi/src/ecc/Sm2Signer.php | different | third-party dependency / REVIEW |
| includes/vendor/lpilp/guomi/src/overwrite.php | different | third-party dependency / REVIEW |
| includes/vendor/lpilp/guomi/src/sm/RtSm2.php | different | third-party dependency / REVIEW |
| includes/vendor/lpilp/guomi/src/sm/RtSm3.php | different | third-party dependency / REVIEW |
| includes/vendor/lpilp/guomi/src/sm/RtSm4.php | different | third-party dependency / REVIEW |
| includes/vendor/lpilp/guomi/src/smecc/SM2/Cipher.php | different | third-party dependency / REVIEW |
| includes/vendor/lpilp/guomi/src/smecc/SM2/Hex2ByteBuf.php | different | third-party dependency / REVIEW |
| includes/vendor/lpilp/guomi/src/smecc/SM2/Sm2Enc.php | different | third-party dependency / REVIEW |
| includes/vendor/lpilp/guomi/src/smecc/SM2/Sm2WithSm3.php | different | third-party dependency / REVIEW |
| includes/vendor/lpilp/guomi/src/smecc/SM3/GeneralDigest.php | different | third-party dependency / REVIEW |
| includes/vendor/lpilp/guomi/src/smecc/SM3/SM3Digest.php | different | third-party dependency / REVIEW |
| includes/vendor/lpilp/guomi/src/smecc/SM4/Sm4.php | different | third-party dependency / REVIEW |
| includes/vendor/lpilp/guomi/src/smecc/SPLSM2/SimpleSm2.php | different | third-party dependency / REVIEW |
| includes/vendor/lpilp/guomi/src/smecc/SPLSM2/Sm2Asn1.php | different | third-party dependency / REVIEW |
| includes/vendor/lpilp/guomi/src/smecc/SPLSM2/Sm2Ecc.php | different | third-party dependency / REVIEW |
| includes/vendor/lpilp/guomi/src/smecc/SPLSM2/Sm2Point.php | different | third-party dependency / REVIEW |
| includes/vendor/lpilp/guomi/src/smecc/SPLSM2/Sm3.php | different | third-party dependency / REVIEW |
| includes/vendor/lpilp/guomi/src/util/FormatSign.php | different | third-party dependency / REVIEW |
| includes/vendor/lpilp/guomi/src/util/KeyCompress.php | different | third-party dependency / REVIEW |
| includes/vendor/lpilp/guomi/src/util/MyAsn1.php | different | third-party dependency / REVIEW |
| includes/vendor/lpilp/guomi/src/util/SmSignFormatRS.php | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/.gitattributes | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/.github/workflows/test.yml | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/.gitignore | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/.gitmodules | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/.scrutinizer.yml | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/composer.json | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/Makefile | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/phpunit.full.xml | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/phpunit.xml | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/README.md | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/src/Crypto/EcDH/EcDH.php | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/src/Crypto/EcDH/EcDHInterface.php | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/src/Crypto/Key/PrivateKey.php | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/src/Crypto/Key/PrivateKeyInterface.php | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/src/Crypto/Key/PublicKey.php | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/src/Crypto/Key/PublicKeyInterface.php | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/src/Crypto/Signature/HasherInterface.php | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/src/Crypto/Signature/Signature.php | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/src/Crypto/Signature/SignatureInterface.php | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/src/Crypto/Signature/Signer.php | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/src/Crypto/Signature/SignHasher.php | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/src/Curves/CurveFactory.php | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/src/Curves/NamedCurveFp.php | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/src/Curves/NistCurve.php | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/src/Curves/SecgCurve.php | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/src/EccFactory.php | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/src/Exception/ExchangeException.php | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/src/Exception/NumberTheoryException.php | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/src/Exception/PointException.php | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/src/Exception/PointNotOnCurveException.php | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/src/Exception/PointRecoveryException.php | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/src/Exception/PublicKeyException.php | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/src/Exception/SignatureDecodeException.php | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/src/Exception/SquareRootException.php | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/src/Exception/UnsupportedCurveException.php | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/src/Math/DebugDecorator.php | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/src/Math/GmpMath.php | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/src/Math/GmpMathInterface.php | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/src/Math/MathAdapterFactory.php | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/src/Math/ModularArithmetic.php | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/src/Math/NumberTheory.php | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/src/Primitives/CurveFp.php | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/src/Primitives/CurveFpInterface.php | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/src/Primitives/CurveParameters.php | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/src/Primitives/GeneratorPoint.php | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/src/Primitives/Point.php | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/src/Primitives/PointInterface.php | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/src/Random/DebugDecorator.php | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/src/Random/HmacRandomNumberGenerator.php | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/src/Random/RandomGeneratorFactory.php | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/src/Random/RandomNumberGenerator.php | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/src/Random/RandomNumberGeneratorInterface.php | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/src/Serializer/Point/CompressedPointSerializer.php | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/src/Serializer/Point/PointSerializerInterface.php | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/src/Serializer/Point/UncompressedPointSerializer.php | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/src/Serializer/PrivateKey/DerPrivateKeySerializer.php | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/src/Serializer/PrivateKey/PemPrivateKeySerializer.php | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/src/Serializer/PrivateKey/PrivateKeySerializerInterface.php | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/src/Serializer/PublicKey/Der/Formatter.php | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/src/Serializer/PublicKey/Der/Parser.php | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/src/Serializer/PublicKey/DerPublicKeySerializer.php | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/src/Serializer/PublicKey/PemPublicKeySerializer.php | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/src/Serializer/PublicKey/PublicKeySerializerInterface.php | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/src/Serializer/Signature/Der/Formatter.php | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/src/Serializer/Signature/Der/Parser.php | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/src/Serializer/Signature/DerSignatureSerializer.php | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/src/Serializer/Signature/DerSignatureSerializerInterface.php | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/src/Serializer/Util/CurveOidMapper.php | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/src/Util/BinaryString.php | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/src/Util/NumberSize.php | different | third-party dependency / REVIEW |
| includes/vendor/mdanter/ecc/validate_examples.sh | different | third-party dependency / REVIEW |
| install/index.php | different | business or security / KEEP canonical |
| install/install.sql | different | business or security / KEEP canonical |
| install/migrate_batch_a.php | only inner | business or security / KEEP canonical |
| install/migrate_rbac.php | only inner | business or security / KEEP canonical |
| install/migrate_settle_usdt.php | only inner | business or security / KEEP canonical |
| install/migrate_transfer_safety.php | only inner | business or security / KEEP canonical |
| paypage/error.php | different | payment frontend / REVIEW |
| paypage/index.php | different | payment frontend / REVIEW |
| paypage/js/common.js | different | payment frontend / REVIEW |
| paypage/js/hammer.js | different | payment frontend / REVIEW |
| paypage/js/pay.js | different | payment frontend / REVIEW |
| paypage/red_confirm.php | different | payment frontend / REVIEW |
| paypage/red_confirmwx.php | different | payment frontend / REVIEW |
| paypage/red_success.php | different | payment frontend / REVIEW |
| paypage/success.php | different | payment frontend / REVIEW |
| paypage/wxtrans_confirm.php | different | payment frontend / REVIEW |
| paypage/wxtrans_success.php | different | payment frontend / REVIEW |
| plugins/adapay/inc/Build.class.php | different | business or security / KEEP canonical |
| plugins/alipaycode/inc/qrcode.page.php | different | business or security / KEEP canonical |
| plugins/alipayg/inc/AlipayGlobalClient.php | different | business or security / KEEP canonical |
| plugins/baofu/cert/baofu.cer | different | business or security / KEEP canonical |
| plugins/douyinpay/inc/BaseService.php | different | business or security / KEEP canonical |
| plugins/douyinpay/inc/DouyinPayException.php | different | business or security / KEEP canonical |
| plugins/douyinpay/inc/PaymentService.php | different | business or security / KEEP canonical |
| plugins/epay/epay_plugin.php | different | business or security / KEEP canonical |
| plugins/hnapay/cert/hnapay.pem | different | business or security / KEEP canonical |
| plugins/hnapay/cert/hnapaypay.pem | different | business or security / KEEP canonical |
| plugins/jdpay/inc/cert/wy_rsa_public_key.pem | different | business or security / KEEP canonical |
| plugins/jeepay/jeepay_plugin.php | different | business or security / KEEP canonical |
| plugins/lakala/cert/lkl-apigw-v2.cer | different | business or security / KEEP canonical |
| plugins/lakala/inc/LakalaClient.php | different | business or security / KEEP canonical |
| plugins/ltzf/ltzf_plugin.php | different | business or security / KEEP canonical |
| plugins/ysepay/inc/YsepayResponse.php | different | business or security / KEEP canonical |
| README.md | only inner | documentation / REVIEW |
| RELEASE-NOTES.txt | only inner | configuration or unknown / REVIEW |
| template/default/doc.php | different | public template / REVIEW |
| template/default/doc/index.php | different | public template / REVIEW |
| template/default/doc/merchant_info.php | different | public template / REVIEW |
| template/default/doc/merchant_orders.php | different | public template / REVIEW |
| template/default/doc/pay_close.php | different | public template / REVIEW |
| template/default/doc/pay_create.php | different | public template / REVIEW |
| template/default/doc/pay_notify.php | different | public template / REVIEW |
| template/default/doc/pay_query.php | different | public template / REVIEW |
| template/default/doc/pay_refund.php | different | public template / REVIEW |
| template/default/doc/pay_refundquery.php | different | public template / REVIEW |
| template/default/doc/pay_submit.php | different | public template / REVIEW |
| template/default/doc/paytype.php | different | public template / REVIEW |
| template/default/doc/sdk.php | different | public template / REVIEW |
| template/default/doc/sign_note.php | different | public template / REVIEW |
| template/default/doc/transfer_balance.php | different | public template / REVIEW |
| template/default/doc/transfer_query.php | different | public template / REVIEW |
| template/default/doc/transfer_submit.php | different | public template / REVIEW |
| template/default/payerr.php | different | payment frontend / REVIEW |
| template/default/payok.php | different | payment frontend / REVIEW |
| template/index1/index.php | different | public template / REVIEW |
| template/index10/assets/css/common_contact.css | different | CSS / REVIEW |
| template/index10/assets/css/default.css | different | CSS / REVIEW |
| template/index10/assets/css/header_common.css | different | CSS / REVIEW |
| template/index10/assets/css/index_main.css | different | CSS / REVIEW |
| template/index10/assets/css/media.css | different | CSS / REVIEW |
| template/index10/assets/js/aos.js | different | JS / REVIEW |
| template/index10/assets/js/common_js.js | different | JS / REVIEW |
| template/index10/assets/js/index_main.js | different | JS / REVIEW |
| template/index10/assets/js/xs.js | different | JS / REVIEW |
| template/index10/index.php | different | public template / REVIEW |
| template/index2/index.php | different | public template / REVIEW |
| template/index3/assets/css/idangerous.swiper2.7.6.css | different | CSS / REVIEW |
| template/index3/assets/js/idangerous.swiper2.7.6.min.js | different | JS / REVIEW |
| template/index3/index.php | different | public template / REVIEW |
| template/index5/assets/js/jquery.glide.js | different | JS / REVIEW |
| template/index5/assets/js/script.js | different | JS / REVIEW |
| template/index6/assets/css/style-responsive.min.css | different | CSS / REVIEW |
| template/index6/assets/css/style.min.css | different | CSS / REVIEW |
| template/index6/assets/css/theme/blue.css | different | CSS / REVIEW |
| template/index6/assets/css/theme/default.css | different | CSS / REVIEW |
| template/index6/assets/css/theme/orange.css | different | CSS / REVIEW |
| template/index6/assets/css/theme/purple.css | different | CSS / REVIEW |
| template/index6/assets/css/theme/red.css | different | CSS / REVIEW |
| template/index7/assets/css/aos.css | different | CSS / REVIEW |
| template/index7/assets/js/particles.app.js | different | JS / REVIEW |
| template/index7/assets/js/particles.js | different | JS / REVIEW |
| template/index7/assets/js/typed.js | different | JS / REVIEW |
| template/index8/assets/css/animations.min.css | different | CSS / REVIEW |
| template/index8/assets/css/responsive.css | different | CSS / REVIEW |
| template/index8/assets/css/style.css | different | CSS / REVIEW |
| template/index8/assets/picture/optimised.svg | different | image or icon / REVIEW |
| template/index8/assets/picture/powerfull.svg | different | image or icon / REVIEW |
| template/index8/assets/picture/website.svg | different | image or icon / REVIEW |
| template/index8/index.php | different | public template / REVIEW |
| template/index9/assets/css/style.css | different | CSS / REVIEW |
| template/index9/index.php | different | public template / REVIEW |
| user/ajax.php | different | business or security / KEEP canonical |
| user/ajax2.php | different | business or security / KEEP canonical |
| user/apply.php | different | business or security / KEEP canonical |
| user/assets/css/animate.min.css | different | business or security / KEEP canonical |
| user/assets/css/app.css | different | business or security / KEEP canonical |
| user/assets/js/app.min.js | different | business or security / KEEP canonical |
| user/assets/js/config.json | different | business or security / KEEP canonical |
| user/assets/js/ui-jp.config.js | different | business or security / KEEP canonical |
| user/assets/vendor/chosen/bootstrap-chosen.css | different | business or security / KEEP canonical |
| user/assets/vendor/chosen/chosen.jquery.min.js | different | business or security / KEEP canonical |
| user/assets/vendor/flot.orderbars/js/jquery.flot.orderBars.js | different | business or security / KEEP canonical |
| user/assets/vendor/flot.tooltip/js/jquery.flot.tooltip.min.js | different | business or security / KEEP canonical |
| user/assets/vendor/flot/jquery.flot.js | different | business or security / KEEP canonical |
| user/assets/vendor/flot/jquery.flot.pie.js | different | business or security / KEEP canonical |
| user/assets/vendor/flot/jquery.flot.resize.js | different | business or security / KEEP canonical |
| user/assets/vendor/jquery.sparkline/dist/jquery.sparkline.retina.js | different | business or security / KEEP canonical |
| user/assets/vendor/moment/moment.js | different | business or security / KEEP canonical |
| user/completeinfo.php | different | business or security / KEEP canonical |
| user/download.php | different | business or security / KEEP canonical |
| user/editinfo.php | different | business or security / KEEP canonical |
| user/order.php | different | business or security / KEEP canonical |
| user/settle.php | different | business or security / KEEP canonical |
