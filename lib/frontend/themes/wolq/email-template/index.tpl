{use class="frontend\design\Block"}{use class="frontend\design\Info"}{if Info::isAdmin()}<!DOCTYPE html>
<html lang="{Yii::$app->language}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <base href="{$smarty.const.BASE_URL}">
  <link href='https://fonts.googleapis.com/css?family=Hind:400,700,600,500,300' rel='stylesheet' type='text/css'>
  <script type="text/javascript" src="{Info::themeFile('/js/jquery-3.4.1.min.js')}"></script>
  <script type="text/javascript" src="{Info::themeFile('/js/jquery-ui.min.js')}"></script>
  <script type="text/javascript" src="{Info::themeFile('/js/main.js')}"></script>
</head>
<style type="text/css">
  @font-face {
    font-family: 'FontAwesome';
    src: url('{Info::themeFile('/fonts/fontawesome-webfont.eot')}?v=3.2.1');
    src: url('{Info::themeFile('/fonts/fontawesome-webfont.eot')}?#iefix&v=3.2.1') format('embedded-opentype'),
    url('{Info::themeFile('/fonts/fontawesome-webfont.woff')}?v=3.2.1') format('woff'),
    url('{Info::themeFile('/fonts/fontawesome-webfont.ttf')}?v=3.2.1') format('truetype'),
    url('{Info::themeFile('/fonts/fontawesome-webfont.svg')}#fontawesomeregular?v=3.2.1') format('svg');
    font-weight: normal;
    font-style: normal;
  }
  @font-face {
    font-family: 'trueloaded';
    src:  url('{Info::themeFile('/fonts/trueloaded.eot')}?4rk52p');
    src:  url('{Info::themeFile('/fonts/trueloaded.eot')}?4rk52p#iefix') format('embedded-opentype'),
    url('{Info::themeFile('/fonts/trueloaded.ttf')}?4rk52p') format('truetype'),
    url('{Info::themeFile('/fonts/trueloaded.woff')}?4rk52p') format('woff'),
    url('{Info::themeFile('/fonts/trueloaded.svg')}?4rk52p#trueloaded') format('svg');
    font-weight: normal;
    font-style: normal;
  }
  .edit-blocks .block {
    position: relative;
    padding: 10px;
    border: 2px dashed #eee;
    margin: 0 auto;
  }
  .edit-blocks .block:after {
    content: '';
    clear: both;
    display: block;
  }
  .edit-blocks .block:hover {
    border: 2px solid #0062c0;
    z-index: 100;
  }
  .edit-blocks .block > .add-box {
    position: absolute;
    padding: 3px 15px 3px;
    left: -2px;
    bottom: -43px;
    display: none;
    border-radius: 0 0 5px 5px;
    background: #0062c0;
    color: #fff;
    z-index: 19;
    font-size: 26px;
    font-weight: bold;
    line-height: 36px;
    cursor: pointer;
  }
  .edit-blocks .block .block > .add-box {
    font-size: 20px;
    font-weight: normal;
  }
  .edit-blocks .block:hover > .add-box {
    display: block;
  }
  .edit-blocks .menu-widget {
    position: absolute;
    padding: 2px;
    left: 0;
    bottom: -40px;
    display: none;
    border-radius: 0 0 5px 5px;
    color: #fff;
    z-index: 10000;
    line-height: 35px;
    box-shadow: 0 3px 3px rgba(255, 255, 255, 0.3),  -3px 0 3px rgba(255, 255, 255, 0.3),  3px 0 3px rgba(255, 255, 255, 0.3);
  }
  .edit-blocks .box-block > .menu-widget {
    bottom: -40px;
  }
  .edit-blocks .type-1 > .menu-widget,
  .edit-blocks .type- > .menu-widget {
    bottom: -40px;
  }
  .edit-blocks .box-block {
    position: relative;
    z-index: 1;
  }
  .edit-blocks .box-block .box-block {
    z-index: 2;
  }
  .edit-blocks .box-block .box-block .box-block {
    z-index: 3;
  }
  .edit-blocks .box-block .box-block .box-block .box-block {
    z-index: 4;
  }
  .edit-blocks .box-block .box-block .box-block .box-block .box-block {
    z-index: 5;
  }
  .edit-blocks .box-block:hover {
    z-index: 2;
  }
  .edit-blocks .box-block .box-block:hover {
    z-index: 3;
  }
  .edit-blocks .box-block .box-block .box-block:hover {
    z-index: 4;
  }
  .edit-blocks .box-block .box-block .box-block .box-block:hover {
    z-index: 5;
  }
  .edit-blocks .box-block .box-block .box-block .box-block .box-block:hover {
    z-index: 6;
  }
  .edit-blocks .box-block .menu-widget {
    z-index: 11;
  }
  .edit-blocks .box-block .box-block .menu-widget {
    z-index: 12;
  }
  .edit-blocks .box-block .box-block .box-block .menu-widget {
    z-index: 13;
  }
  .edit-blocks .box-block .box-block .box-block .box-block .menu-widget {
    z-index: 14;
  }
  .edit-blocks .box-block .box-block .box-block .box-block .box-block .menu-widget {
    z-index: 15;
  }
  .edit-blocks .box-block .add-box-single {
    z-index: 1;
  }
  .edit-blocks .box-block .box-block .add-box-single {
    z-index: 2;
  }
  .edit-blocks .box-block .box-block .box-block .add-box-single {
    z-index: 3;
  }
  .edit-blocks .box-block .box-block .box-block .box-block .add-box-single {
    z-index: 4;
  }
  .edit-blocks .box-block .box-block .box-block .box-block .box-block .add-box-single {
    z-index: 5;
  }
  .edit-blocks .box-block:hover,
  .edit-blocks .box-block:hover .menu-widget {
    z-index: 21;
  }
  .edit-blocks .box > .menu-widget {
    left: -1px;
  }
  .edit-blocks .box:hover > .menu-widget,
  .edit-blocks .box-block:hover > .menu-widget {
    display: block;
    white-space: nowrap;
  }
  .edit-blocks .box {
    border: 1px dashed #eee;
    /*margin-bottom: 30px;*/
    position: relative;
  }
  .edit-blocks .box-block {
    /* margin-bottom: 30px;*/
  }

  .edit-blocks .block {
    border: 2px dashed #eee;
    padding: 10px;
    position: relative;
    min-height: 30px;
  }
  .edit-blocks .box {
    min-height: 30px;
  }
  .edit-blocks .box-block.no-over {
    border: 2px dashed #eee !important;
  }
  .edit-blocks .box-block.no-over > .menu-widget {
    display: none !important;
  }


  .edit-blocks .box:hover {
    border: 1px solid #0062c0;
  }
  .edit-blocks .menu-widget {
    background: #0062c0;
  }

  .edit-blocks .box-block:hover > .block {
    border: 2px solid #20a3f4;
  }
  .edit-blocks .box-block .box:hover {
    border: 1px solid #20a3f4;
  }
  .edit-blocks .box-block .menu-widget,
  .edit-blocks .box-block .block > .add-box {
    background: #20a3f4;
  }

  .edit-blocks .box-block .box-block:hover > .block {
    border: 2px solid #1eb725;
  }
  .edit-blocks .box-block .box-block .box:hover {
    border: 1px solid #1eb725;
  }
  .edit-blocks .box-block .box-block .menu-widget,
  .edit-blocks .box-block .box-block .block > .add-box {
    background: #1eb725;
  }

  .edit-blocks .box-block .box-block .box-block:hover > .block {
    border: 2px solid #fda502;
  }
  .edit-blocks .box-block .box-block .box-block .box:hover {
    border: 1px solid #fda502;
  }
  .edit-blocks .box-block .box-block .box-block .menu-widget,
  .edit-blocks .box-block .box-block .box-block .block > .add-box {
    background: #fda502;
  }

  .edit-blocks .box-block .box-block .box-block .box-block:hover > .block {
    border: 2px solid #ff7043;
  }
  .edit-blocks .box-block .box-block .box-block .box-block .box:hover {
    border: 1px solid #ff7043;
  }
  .edit-blocks .box-block .box-block .box-block .box-block .menu-widget,
  .edit-blocks .box-block .box-block .box-block .box-block .block > .add-box {
    background: #ff7043;
  }

  .edit-blocks .menu-widget > span {
    min-width: 35px;
    height: 35px;
    text-align: center;
    display: inline-block;
    font-size: 20px;
    vertical-align: middle;
    cursor: pointer;
  }
  .edit-blocks .menu-widget > span + span {
    border-left: 1px solid rgba(255, 255, 255, 0.5);
  }
  .edit-blocks .add-box {
    padding: 0 15px;
  }
  .edit-blocks span.handle {
    cursor: move;
  }
  .edit-blocks .handle:before {
    content: "\f256";
    font-family: FontAwesome;
  }
  .edit-blocks .remove-box:before {
    content: '\f1f8';
    font-family: FontAwesome;
  }
  .edit-blocks .edit-box:before {
    content: '\f040';
    font-family: FontAwesome;
  }

  .edit-blocks .box,
  .box-block {
    clear: both;
  }

  .no-widget-name {
    display: none;
  }
  .edit-blocks .no-widget-name {
    display: block;
    font-size: 20px;
    color: #ccc;
  }

  .ui-sortable-placeholder {
    background: #efefef;
    border: 2px dashed #ddd;
    visibility: visible !important;
    min-height: 20px;
    min-width: 20px;
  }

  .view-blocks .block .add-box,
  .view-blocks .block .menu-widget {
    display: none;
  }

  .box-block:after {
    content: '';
    clear: both;
    display: block;
  }
  .box-block.type-2 > .block,
  .box-block.type-3 > .block,
  .box-block.type-4 > .block,
  .box-block.type-5 > .block,
  .box-block.type-6 > .block,
  .box-block.type-7 > .block,
  .box-block.type-8 > .block,
  .box-block.type-9 > .block,
  .box-block.type-10 > .block,
  .box-block.type-11 > .block,
  .box-block.type-12 > .block,
  .box-block.type-13 > .block,
  .box-block.type-14 > .block,
  .box-block.type-15 > .block {
    float: left;
  }
  .box-block.type-2 > .block {
    width: 50%;
  }
  .box-block.type-3 > .block {
    width: 33.33%;
  }
  .box-block.type-4 > .block:nth-child(1) {
    width: 66.66%;
  }
  .box-block.type-4 > .block:nth-child(2) {
    width: 33.33%;
  }
  .box-block.type-5 > .block:nth-child(1) {
    width: 33.33%;
  }
  .box-block.type-5 > .block:nth-child(2) {
    width: 66.66%;
  }
  .box-block.type-6 > .block:nth-child(1) {
    width: 25%;
  }
  .box-block.type-6 > .block:nth-child(2) {
    width: 75%;
  }
  .box-block.type-7 > .block:nth-child(1) {
    width: 75%;
  }
  .box-block.type-7 > .block:nth-child(2) {
    width: 25%;
  }
  .box-block.type-8 > .block:nth-child(1) {
    width: 25%;
  }
  .box-block.type-8 > .block:nth-child(2) {
    width: 50%;
  }
  .box-block.type-8 > .block:nth-child(3) {
    width: 25%;
  }
  .box-block.type-9 > .block:nth-child(1) {
    width: 20%;
  }
  .box-block.type-9 > .block:nth-child(2) {
    width: 80%;
  }
  .box-block.type-10 > .block:nth-child(1) {
    width: 80%;
  }
  .box-block.type-10 > .block:nth-child(2) {
    width: 20%;
  }
  .box-block.type-11 > .block:nth-child(1) {
    width: 40%;
  }
  .box-block.type-11 > .block:nth-child(2) {
    width: 60%;
  }
  .box-block.type-12 > .block:nth-child(1) {
    width: 60%;
  }
  .box-block.type-12 > .block:nth-child(2) {
    width: 40%;
  }
  .box-block.type-13 > .block:nth-child(1) {
    width: 20%;
  }
  .box-block.type-13 > .block:nth-child(2) {
    width: 60%;
  }
  .box-block.type-13 > .block:nth-child(3) {
    width: 20%;
  }
  .box-block.type-14 > .block {
    width: 25%;
  }
  .box-block.type-15 > .block {
    width: 20%;
  }
  .block {
    text-align: center;
  }
  .block > .box,
  .block > .box-block {
    text-align: left;
  }
  @media (max-width: 800px) {
    .box-block.type-2 > .block {
      width: 100%;
    }
    .box-block.type-3 > .block {
      width: 50%;
    }
    .box-block.type-3 > .block:nth-child(3) {
      width: 100%;
    }
  }
  @media (max-width: 600px) {
    .box-block.type-3 > .block {
      width: 100%;
    }
  }


  .edit-theme *[data-class].active {
    position: relative;
    z-index: 10000;
    box-shadow: 0 0 10px #999;
  }
  .edit-theme .menu-widget {
    position: absolute;
    background: #ccc;;
    left: 0;
    bottom: -32px;
    display: none;
    border-radius: 0 0 5px 5px;
    color: #000;
    z-index: 10000;
    font-size: 30px;
    line-height: 30px;
    box-shadow: 0 3px 3px rgba(255, 255, 255, 0.5),  -3px 0 3px rgba(255, 255, 255, 0.5),  3px 0 3px rgba(255, 255, 255, 0.5);
  }
  .edit-theme .active > .menu-widget {
    display: block;
    text-align: justify;
    cursor: pointer;
  }
  .edit-theme .edit-box:before {
    padding: 2px 10px;
    font-size: 20px;
    content: '\f040';
    font-family: FontAwesome;
    font-weight: normal;
  }


  .view-blocks .widgets-list {
    display: none;
  }
  .widgets-list {
    padding: 0 35px 0 0;
    position: relative;
    background: #fff;
    box-shadow: 0 5px 8px -5px #ccc;
    z-index: 1000;
    border-bottom: 1px solid #eee;
  }
  .widgets-list .title {
    font-size: 16px;
    font-weight: bold;
    float: left;
    margin-left: -160px;
  }
  .widgets-list .box-group {
    padding: 5px 0 5px 170px;
    overflow: hidden;
  }
  .widgets-list .box-group + .box-group {
    border-top: 1px solid #eee;
  }
  .widgets-list .widget-item {
    font-size: 12px;
    width: 120px;
    display: inline-block;
    margin: 2px 3px 2px;
    border: 2px dashed #eee;
    padding: 2px 5px 4px 35px;
    vertical-align: top;
    cursor: pointer;
    position: relative;
    white-space: nowrap;
    overflow: hidden;
    font-family: 'Hind', sans-serif;
  }
  .widgets-list .widget-item:after {
    position: absolute;
    right: 0;
    top: 0;
    bottom: 0;
    width: 20px;
    content: '';
    background: -webkit-linear-gradient(left, transparent 0%, #fff 100%);
    background: linear-gradient(to right, transparent 0%, #fff 100%);
  }
  .widgets-list .widget-item:before {
    position: absolute;
    left: 5px;
    top: 1px;
    font-size: 20px;
    line-height: 1;
    font-family: 'trueloaded';
    color: #babbbb;
  }
  .widgets-list .ui-sortable-placeholder {
    display: none !important;
    width: 0 !important;
    height: 0 !important;
  }
  .close-widgets {
    position: absolute;
    right: 0;
    top: 0;
    width: 40px;
    height: 40px;
    line-height: 40px;
    text-align: center;
    border: 1px solid #eee;
    cursor: pointer;
    background: #fff;
  }
  .close-widgets:before {
    content: '×';
    font-size: 30px;
  }
  .closed .close-widgets:before {
    content: '\e91b';
    font-family: 'trueloaded';
    font-size: 20px;
    color: #babbbb;
  }
  .closed .box-group {
    display: none;
  }


  .ico-name:before {
    content: '\e949';
  }
  .ico-images:before {
    content: '\e97f';
  }
  .ico-attributes:before {
    content: '\e96d';
  }
  .ico-bundle:before {
    content: '\e96c';
  }
  .ico-in-bundles:before {
    content: '\e96c';
  }
  .ico-price:before {
    content: '\e96a';
  }
  .ico-quantity:before {
    content: '\e969';
  }
  .ico-buttons:before {
    content: '\e968';
  }
  .ico-description:before {
    content: '\e96e';
  }
  .ico-reviews:before {
    content: '\e943';
  }
  .ico-properties:before {
    content: '\e967';
  }
  .ico-also-purchased:before {
    content: '\e966';
  }
  .ico-cross-sell:before {
    content: '\e965';
  }
  .ico-title:before {
    content: '\e973';
  }
  .ico-content:before {
    content: '\e96e';
  }
  .ico-image:before {
    content: '\e97f';
  }
  .ico-paging-bar:before {
    content: '\e96f';
  }
  .ico-listing:before {
    content: '\e970';
  }
  .ico-listing-functionality:before {
    content: '\e971';
  }
  .ico-categories:before {
    content: '\e974';
  }
  .ico-filters:before {
    content: '\e972';
  }
  .ico-continue-button:before {
    content: '\e963';
  }
  .ico-gift-certificate:before {
    content: '\e95f';
  }
  .ico-discount-coupon:before {
    content: '\e95e';
  }
  .ico-order-reference:before {
    content: '\e95d';
  }
  .ico-give-away:before {
    content: '\e95c';
  }
  .ico-up-sell:before {
    content: '\e95b';
  }
  .ico-shipping-estimator:before {
    content: '\e95a';
  }
  .ico-order-total:before {
    content: '\e959';
  }
  .ico-contact-form:before {
    content: '\e957';
  }
  .ico-map:before {
    content: '';
  }
  .ico-contacts:before {
    content: '';
  }
  .ico-date:before {
    content: '\e964';
  }
  .ico-block-box:before {
    content: '\e98c';
  }
  .ico-banner:before {
    content: '\e988';
  }
  .ico-logo:before {
    content: '\e980';
  }
  .ico-text:before {
    content: '\e97e';
  }
  .ico-html:before {
    content: '\e97d';
  }
  .ico-store-address:before {
    content: '\e94a';
  }
  .ico-store-phone:before {
    content: '\e94b';
  }
  .ico-store-email:before {
    content: '\e94c';
  }
  .ico-store-site:before {
    content: '\e94d';
  }
  .ico-shipping-address:before {
    content: '\e94e';
  }
  .ico-shipping-method:before {
    content: '\e94f';
  }
  .ico-address-qrcode:before {
    content: '\e950';
  }
  .ico-order-barcode:before {
    content: '\e951';
  }
  .ico-customer-name:before {
    content: '\e949';
  }
  .ico-customer-email:before {
    content: '\e952';
  }
  .ico-customer-phone:before {
    content: '\e953';
  }
  .ico-totals:before {
    content: '\e959';
  }
  .ico-order-id:before {
    content: '\e95d';
  }
  .ico-payment-date:before {
    content: '\e954';
  }
  .ico-payment-method:before {
    content: '\e955';
  }
  .ico-container:before {
    content: '\e956';
  }
  .ico-tabs:before {
    content: '\e98b';
  }
  .ico-brands:before {
    content: '\e98a';
  }
  .ico-bestsellers:before {
    content: '\e989';
  }
  .ico-specials-products:before {
    content: '\e986';
  }
  .ico-featured-products:before {
    content: '\e987';
  }
  .ico-new-products:before {
    content: '\e982';
  }
  .ico-viewed-products:before {
    content: '\e981';
  }
  .ico-menu:before {
    content: '\e97b';
  }
  .ico-languages:before {
    content: '\e97a';
  }
  .ico-currencies:before {
    content: '\e979';
  }
  .ico-search:before {
    content: '\e978';
  }
  .ico-cart:before {
    content: '\e977';
  }
  .ico-breadcrumb:before {
    content: '\e975';
  }
  .ico-compare:before {
    content: '\e976';
  }
</style>
<body>
{/if}<div style="max-width: 800px; margin: 0 auto">{Block::widget(['name' => 'email', 'params' => ['type' => 'email']])}</div>{if Info::isAdmin()}
</body>
{/if}