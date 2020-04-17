{use class="Yii"}
{use class="backend\assets\DesignAsset"}
{use class="frontend\design\Info"}
{DesignAsset::register($this)|void}

{function extensionPages}
    {foreach $extensions[$group] as $page}
        <li class="page-link" data-name="{$page['name']}" data-ref="{$page['name']}" data-href="{$page['link']}"><a data-toggle="tab"><span>{$page['title']}</span></a></li>
    {/foreach}
{/function}

{function addedPages}
    {for $i = 0; $i < 100; $i++}
        {if $editable_links[$group|cat:$i]}
            <li class="page-link-{$group} active" data-name="{$editable_links[$group|cat:$i].name}" data-ref="{$group}{$i}" data-href="{$editable_links[$group|cat:$i].link}"><a data-toggle="tab"><span>{$editable_links[$group|cat:$i].page_title}</span></a></li>
        {/if}
    {/for}
{/function}

<div class="page-elements">
  {include 'menu.tpl'}
    {if !$landing}
  <div class="page-elements-top">
      <div class="tabbable tabbable-custom">

          <ul class="nav nav-tabs  nav-tabs-scroll">

              <li class="active"><a href="#home" data-toggle="tab">{$smarty.const.TEXT_HOME}</a></li>
              <li><a href="#catalog" data-toggle="tab">{$smarty.const.TEXT_CATALOG}</a></li>
              <li><a href="#productListing" data-toggle="tab">{$smarty.const.TEXT_PRODUCT_LISTING_ITEMS}</a></li>
              <li><a href="#informations" data-toggle="tab">Informations</a></li>
              <li><a href="#account" data-toggle="tab">{$smarty.const.TEXT_ACCOUNT}</a></li>
              <li><a href="#cart" data-toggle="tab">{$smarty.const.TEXT_CART}</a></li>

              {if Info::themeSetting('checkout_view')}
                  <li><a href="#checkout2" data-toggle="tab">{$smarty.const.TEXT_STEPS_CHECKOUT}</a></li>
              {else}
                  <li><a href="#checkout" data-toggle="tab">{$smarty.const.TEXT_CHECKOUT}</a></li>
              {/if}

              {if Info::themeSetting('checkout_view')}
                  <li><a href="#checkoutQuote2" data-toggle="tab">{$smarty.const.TEXT_QUOTE_CHECKOUT}</a></li>
              {else}
                  <li><a href="#checkoutQuote" data-toggle="tab">{$smarty.const.TEXT_QUOTE_CHECKOUT}</a></li>
              {/if}

              <li><a href="#checkoutSample" data-toggle="tab">{$smarty.const.TEXT_SAMPLE_CHECKOUT}</a></li>
              {*<li><a href="#checkoutSample2" data-toggle="tab">Checkout Sample</a></li>*}

              <li><a href="#orders" data-toggle="tab">{$smarty.const.IMAGE_ORDERS}</a></li>

              <li><a href="#emails" data-toggle="tab">{$smarty.const.TEXT_EMAIL_GIFT_CARD}</a></li>

              <li><a href="#pdf" data-toggle="tab">{$smarty.const.PDF_CATALOG}</a></li>
              <li><a href="#components" data-toggle="tab">{$smarty.const.TEXT_COMPONENTS}</a></li>

              {foreach $addedGroups as $addedGroup}
                  <li><a href="#{$addedGroup}" data-toggle="tab">{$addedGroup}</a></li>
              {/foreach}

              <li><a href="#any" data-toggle="tab">{$smarty.const.TEXT_ANY}</a></li>
          </ul>
          <div class="tab-content">
              <div class="tab-pane active" id="home">
                  <ul class="nav nav-tabs nav-tabs-platform nav-tabs-scroll js-catalog_url_set">
                      <li class="page-link-home active" data-name="home" data-ref="home" data-href="{$editable_links.home}"><a data-toggle="tab"><span>{$smarty.const.TEXT_HOME}</span></a></li>

                      {extensionPages group = 'home'}
                      {addedPages group = 'home'}

                      <li class="add-page" data-page-type="home{$types['home']}"><a data-toggle="tab"><span>+</span></a></li>
                  </ul>
              </div>
              <div class="tab-pane" id="catalog">
                  <ul class="nav nav-tabs nav-tabs-platform nav-tabs-scroll js-catalog_url_set">
                      <li class="page-link-product" data-name="product" data-ref="product" data-href="{$editable_links.product}" {if empty($editable_links.product)} style="display:none" {/if}><a data-toggle="tab"><span>{$smarty.const.TEXT_PRODUCT}</span></a></li>

                      {addedPages group = 'product'}

                      <li class="page-link-catalog" data-name="categories" data-ref="categories" data-href="{$editable_links.categories}" {if empty($editable_links.categories)} style="display:none" {/if}><a data-toggle="tab"><span>{$smarty.const.TEXT_LISTING_CATEGORIES}</span></a></li>

                      {addedPages group = 'categories'}

                      <li class="page-link-catalog" data-name="products" data-ref="products" data-href="{$editable_links.products}" {if empty($editable_links.products)} style="display:none" {/if}><a data-toggle="tab"><span>{$smarty.const.TEXT_LISTING_PRODUCTS}</span></a></li>

                      {addedPages group = 'products'}
                      {extensionPages group = 'catalog'}

                      <li class="add-page" data-page-type="product products categories{$types['catalog']}"><a data-toggle="tab"><span>+</span></a></li>
                  </ul>
              </div>
              <div class="tab-pane" id="productListing">
                  <ul class="nav nav-tabs nav-tabs-platform nav-tabs-scroll js-catalog_url_set">
                      <li class="page-link-product" data-name="productListing" data-ref="productListing" data-href="{$editable_links.productListing}" {if empty($editable_links.productListing)} style="display:none" {/if}><a data-toggle="tab"><span>{$smarty.const.TEXT_PRODUCT}</span></a></li>

                      {addedPages group = 'productListing'}

                      <li class="add-page" data-page-type="productListing"><a data-toggle="tab"><span>+</span></a></li>
                  </ul>
              </div>
              <div class="tab-pane" id="informations">
                  <ul class="nav nav-tabs nav-tabs-platform nav-tabs-scroll js-catalog_url_set">
                      <li class="page-link-info" data-name="information" data-ref="information" data-href="{$editable_links.information}" {if empty($editable_links.information)} style="display:none" {/if}><a data-toggle="tab"><span>{$smarty.const.TEXT_INFORMATION}</span></a></li>

                      {addedPages group = 'information'}

                      <li class="page-link-contact" data-name="contact" data-ref="contact" data-href="{$editable_links.contact}"><a data-toggle="tab"><span>{$smarty.const.TEXT_HEADER_CONTACT_US}</span></a></li>
                      {if $editable_links.blog}
                          <li class="page-link-blog" data-name="blog" data-ref="blog" data-href="{$editable_links.blog}"><a data-toggle="tab"><span>Blog</span></a></li>
                      {/if}
                      <li class="page-link-delivery-location-default" data-name="delivery-location-default" data-ref="delivery-location-default" data-href="{$editable_links['delivery-location-default']}" {if empty($editable_links['delivery-location-default'])} style="display:none" {/if}><a data-toggle="tab"><span>{$smarty.const.TEXT_DELIVERY_LOCATION_INDEX}</span></a></li>
                      <li class="page-link-delivery-location" data-name="delivery-location" data-ref="delivery-location" data-href="{$editable_links['delivery-location']}" {if empty($editable_links['delivery-location'])} style="display:none" {/if}><a data-toggle="tab"><span>{$smarty.const.TEXT_DELIVERY_LOCATION}</span></a></li>
                      <li class="page-link-delivery-location" data-name="404" data-ref="404" data-href="{$editable_links['404']}"><a data-toggle="tab"><span>404</span></a></li>

                      {extensionPages group = 'informations'}

                      <li class="add-page" data-page-type="info custom{$types['informations']}"><a data-toggle="tab"><span>+</span></a></li>
                  </ul>
              </div>
              <div class="tab-pane" id="account">
                  <ul class="nav nav-tabs nav-tabs-platform nav-tabs-scroll js-catalog_url_set">
                      <li class="page-link-account" data-name="account" data-ref="account" data-href="{$editable_links.account}"><a data-toggle="tab"><span>{$smarty.const.TEXT_ACCOUNT}</span></a></li>
                      <li class="page-link-account" data-name="login_account" data-ref="login_account" data-href="{$editable_links.login_account}"><a data-toggle="tab"><span>Login</span></a></li>
                      <li class="page-link-logoff" data-name="logoff" data-ref="logoff" data-href="{$editable_links.logoff}"><a data-toggle="tab"><span>Logoff</span></a></li>
                      <li class="page-link-delete" data-name="logoff_forever" data-ref="logoff_forever" data-href="{$editable_links.logoff_forever}"><a data-toggle="tab"><span>Account Deleted</span></a></li>
                      <li class="page-link-password-forgotten" data-name="password_forgotten" data-ref="password_forgotten" data-href="{$editable_links.password_forgotten}"><a data-toggle="tab"><span>Password forgotten</span></a></li>

                      {extensionPages group = 'account'}
                      {addedPages group = 'account'}

                      <li class="add-page" data-page-type="account{$types['account']}"><a data-toggle="tab"><span>+</span></a></li>
                  </ul>
              </div>
              <div class="tab-pane" id="cart">
                  <ul class="nav nav-tabs nav-tabs-platform nav-tabs-scroll js-catalog_url_set">
                      <li class="page-link-cart" data-name="cart" data-ref="cart" data-href="{$editable_links.cart}"><a data-toggle="tab"><span>{$smarty.const.TEXT_SHOPPING_CART}</span></a></li>
                      <li class="page-link-quote" data-name="quote" data-ref="quote" data-href="{$editable_links.quote}"><a data-toggle="tab"><span>{$smarty.const.TEXT_QUOTE_CART}</span></a></li>
                      <li class="page-link-sample" data-name="sample" data-ref="sample" data-href="{$editable_links.sample}"><a data-toggle="tab"><span>{$smarty.const.TEXT_SAMPLE_CART}</span></a></li>
                      <li class="page-link-wishlist" data-name="wishlist" data-ref="wishlist" data-href="{$editable_links.wishlist}"><a data-toggle="tab"><span>{$smarty.const.TEXT_WISHLIST}</span></a></li>

                      {extensionPages group = 'cart'}
                  </ul>
              </div>

              <div class="tab-pane" id="checkout">
                  <ul class="nav nav-tabs nav-tabs-platform nav-tabs-scroll js-catalog_url_set">
                      <li class="page-link-login" data-name="login_checkout" data-ref="login_checkout" data-href="{$editable_links.login_checkout}"><a data-toggle="tab"><span>Login</span></a></li>
                      <li class="page-link-checkout" data-name="checkout" data-ref="checkout" data-href="{$editable_links.checkout}"><a data-toggle="tab"><span>{$smarty.const.TEXT_CHECKOUT}</span></a></li>
                      <li class="page-link-confirmation" data-name="confirmation" data-ref="confirmation" data-href="{$editable_links.confirmation}"><a data-toggle="tab"><span>{$smarty.const.TEXT_CHECKOUT_CONFIRMATION}</span></a></li>
                      <li class="page-link-checkout" data-name="checkout_no_shipping" data-ref="checkout_no_shipping" data-href="{$editable_links.checkout_no_shipping}"><a data-toggle="tab"><span>{$smarty.const.CHECKOUT_NO_SHIPPING}</span></a></li>
                      <li class="page-link-confirmation" data-name="confirmation_no_shipping" data-ref="confirmation_no_shipping" data-href="{$editable_links.confirmation_no_shipping}"><a data-toggle="tab"><span>{$smarty.const.CHECKOUT_CONFIRMATION_NO_SHIPPING}</span></a></li>
                      <li class="page-link-success" data-name="success" data-ref="success" data-href="{$editable_links.success}"><a data-toggle="tab"><span>{$smarty.const.TEXT_CHECKOUT_SUCCESS}</span></a></li>

                      {extensionPages group = 'checkout'}
                  </ul>
              </div>
              <div class="tab-pane" id="checkout2">
                  <ul class="nav nav-tabs nav-tabs-platform nav-tabs-scroll js-catalog_url_set">
                      <li class="page-link-login" data-name="login_checkout2" data-ref="login_checkout2" data-href="{$editable_links.login_checkout2}"><a data-toggle="tab"><span>Login</span></a></li>
                      <li class="page-link-checkout" data-name="checkout2" data-ref="checkout2" data-href="{$editable_links.checkout2}"><a data-toggle="tab"><span>{$smarty.const.TEXT_CHECKOUT}</span></a></li>
                      <li class="page-link-confirmation" data-name="confirmation2" data-ref="confirmation2" data-href="{$editable_links.confirmation2}"><a data-toggle="tab"><span>{$smarty.const.TEXT_CHECKOUT_CONFIRMATION}</span></a></li>
                      <li class="page-link-checkout" data-name="checkout_no_shipping2" data-ref="checkout_no_shipping2" data-href="{$editable_links.checkout_no_shipping2}"><a data-toggle="tab"><span>{$smarty.const.CHECKOUT_NO_SHIPPING}</span></a></li>
                      <li class="page-link-confirmation" data-name="confirmation_no_shipping2" data-ref="confirmation_no_shipping2" data-href="{$editable_links.confirmation_no_shipping2}"><a data-toggle="tab"><span>{$smarty.const.CHECKOUT_CONFIRMATION_NO_SHIPPING}</span></a></li>
                      <li class="page-link-success" data-name="success" data-ref="success" data-href="{$editable_links.success}"><a data-toggle="tab"><span>{$smarty.const.TEXT_CHECKOUT_SUCCESS}</span></a></li>

                      {extensionPages group = 'checkout2'}
                  </ul>
              </div>

              <div class="tab-pane" id="checkoutQuote">
                  <ul class="nav nav-tabs nav-tabs-platform nav-tabs-scroll js-catalog_url_set">
                      <li class="page-link-login" data-name="login_checkout_q" data-ref="login_checkout_q" data-href="{$editable_links.login_checkout_q}"><a data-toggle="tab"><span>Login</span></a></li>
                      <li class="page-link-checkout" data-name="checkout_q" data-ref="checkout_q" data-href="{$editable_links.checkout_q}"><a data-toggle="tab"><span>{$smarty.const.TEXT_CHECKOUT}</span></a></li>
                      <li class="page-link-confirmation" data-name="confirmation_q" data-ref="confirmation_q" data-href="{$editable_links.confirmation_q}"><a data-toggle="tab"><span>{$smarty.const.TEXT_CHECKOUT_CONFIRMATION}</span></a></li>
                      <li class="page-link-checkout" data-name="checkout_no_shipping_q" data-ref="checkout_no_shipping_q" data-href="{$editable_links.checkout_no_shipping_q}"><a data-toggle="tab"><span>{$smarty.const.CHECKOUT_NO_SHIPPING}</span></a></li>
                      <li class="page-link-confirmation" data-name="confirmation_no_shipping_q" data-ref="confirmation_no_shipping_q" data-href="{$editable_links.confirmation_no_shipping_q}"><a data-toggle="tab"><span>{$smarty.const.CHECKOUT_CONFIRMATION_NO_SHIPPING}</span></a></li>
                      <li class="page-link-success" data-name="success_q" data-ref="success_q" data-href="{$editable_links.success_q}"><a data-toggle="tab"><span>{$smarty.const.TEXT_CHECKOUT_SUCCESS}</span></a></li>

                      {extensionPages group = 'checkoutQuote'}
                  </ul>
              </div>
              <div class="tab-pane" id="checkoutQuote2">
                  <ul class="nav nav-tabs nav-tabs-platform nav-tabs-scroll js-catalog_url_set">
                      <li class="page-link-login" data-name="login_checkout2_q" data-ref="login_checkout2_q" data-href="{$editable_links.login_checkout2_q}"><a data-toggle="tab"><span>Login</span></a></li>
                      <li class="page-link-checkout" data-name="checkout2_q" data-ref="checkout2_q" data-href="{$editable_links.checkout2_q}"><a data-toggle="tab"><span>{$smarty.const.TEXT_CHECKOUT}</span></a></li>
                      <li class="page-link-confirmation" data-name="confirmation2_q" data-ref="confirmation2_q" data-href="{$editable_links.confirmation2_q}"><a data-toggle="tab"><span>{$smarty.const.TEXT_CHECKOUT_CONFIRMATION}</span></a></li>
                      <li class="page-link-checkout" data-name="checkout_no_shipping2_q" data-ref="checkout_no_shipping2_q" data-href="{$editable_links.checkout_no_shipping2_q}"><a data-toggle="tab"><span>{$smarty.const.CHECKOUT_NO_SHIPPING}</span></a></li>
                      <li class="page-link-confirmation" data-name="confirmation_no_shipping2_q" data-ref="confirmation_no_shipping2_q" data-href="{$editable_links.confirmation_no_shipping2_q}"><a data-toggle="tab"><span>{$smarty.const.CHECKOUT_CONFIRMATION_NO_SHIPPING}</span></a></li>
                      <li class="page-link-success" data-name="success_q" data-ref="success_q" data-href="{$editable_links.success_q}"><a data-toggle="tab"><span>{$smarty.const.TEXT_CHECKOUT_SUCCESS}</span></a></li>

                      {extensionPages group = 'checkoutQuote2'}
                  </ul>
              </div>

              <div class="tab-pane" id="checkoutSample">
                  <ul class="nav nav-tabs nav-tabs-platform nav-tabs-scroll js-catalog_url_set">
                      <li class="page-link-login" data-name="login_checkout_s" data-ref="login_checkout_s" data-href="{$editable_links.login_checkout_s}"><a data-toggle="tab"><span>Login</span></a></li>

                      <li class="page-link-checkout" data-name="checkout_s" data-ref="checkout_s" data-href="{$editable_links.checkout_s}"><a data-toggle="tab"><span>{$smarty.const.TEXT_CHECKOUT}</span></a></li>
                      <li class="page-link-checkout" data-name="checkout_free_s" data-ref="checkout_free_s" data-href="{$editable_links.checkout_free_s}"><a data-toggle="tab"><span>Checkout free</span></a></li>

                      <li class="page-link-confirmation" data-name="confirmation_s" data-ref="confirmation_s" data-href="{$editable_links.confirmation_s}"><a data-toggle="tab"><span>{$smarty.const.TEXT_CHECKOUT_CONFIRMATION}</span></a></li>
                      <li class="page-link-confirmation" data-name="confirmation_free_s" data-ref="confirmation_free_s" data-href="{$editable_links.confirmation_free_s}"><a data-toggle="tab"><span>Checkout confirmation free</span></a></li>

                      <li class="page-link-success" data-name="success_s" data-ref="success_s" data-href="{$editable_links.success_s}"><a data-toggle="tab"><span>{$smarty.const.TEXT_CHECKOUT_SUCCESS}</span></a></li>

                      {extensionPages group = 'checkoutSample'}
                  </ul>
              </div>

              <div class="tab-pane" id="orders">
                  <ul class="nav nav-tabs nav-tabs-platform nav-tabs-scroll js-catalog_url_set">
                      <li class="page-link-invoice" data-name="invoice" data-ref="invoice" data-href="{$editable_links.invoice}" {if empty($editable_links.invoice)} style="display:none" {/if}><a data-toggle="tab"><span>{$smarty.const.TEXT_INVOICE}</span> <span class="edit" data-name="invoice"></span></a></li>
                      <li class="page-link-packingslip" data-name="packingslip" data-ref="packingslip" data-href="{$editable_links.packingslip}" {if empty($editable_links.packingslip)} style="display:none" {/if}><a data-toggle="tab"><span>{$smarty.const.TEXT_PACKINGSLIP}</span> <span class="edit" data-name="packingslip"></span></a></li>

                      <li class="page-link-creditnote" data-name="creditnote" data-ref="creditnote" data-href="{$editable_links.creditnote}"><a data-toggle="tab"><span>{$smarty.const.TEXT_CREDITNOTE}</span> <span class="edit" data-name="creditnote"></span></a></li>

                      {extensionPages group = 'orders'}
                      {addedPages group = 'orders'}

                      <li class="add-page" data-page-type="invoice packingslip label{$types['orders']}"><a data-toggle="tab"><span>+</span></a></li>
                  </ul>
              </div>

              <div class="tab-pane" id="emails">
                  <ul class="nav nav-tabs nav-tabs-platform nav-tabs-scroll js-catalog_url_set">
                      <li class="page-link-email" data-name="email" data-ref="email" data-href="{$editable_links.email}"><a data-toggle="tab"><span>Email</span></a></li>

                      {addedPages group = 'email'}

                      <li class="page-link-gift" data-name="gift" data-ref="gift" data-href="{$editable_links.gift}" {if empty($editable_links.gift)} style="display:none" {/if}><a data-toggle="tab"><span>{$smarty.const.GIFT_PAGE}</span></a></li>

                      <li class="page-link-gift" data-name="gift_card" data-ref="gift_card" data-href="{$editable_links.gift_card}"><a data-toggle="tab"><span>{$smarty.const.TEXT_GIFT_CARD}</span></a></li>

                      {extensionPages group = 'emails'}

                      {addedPages group = 'gift'}

                      <li class="add-page" data-page-type="email gift_card{$types['emails']}"><a data-toggle="tab"><span>+</span></a></li>
                  </ul>
              </div>

              <div class="tab-pane" id="components">
                  <ul class="nav nav-tabs nav-tabs-platform nav-tabs-scroll js-catalog_url_set">

                      {extensionPages group = 'components'}

                      {addedPages group = 'components'}

                      <li class="add-page" data-page-type="components{$types['components']}"><a data-toggle="tab"><span>+</span></a></li>
                  </ul>
              </div>

              <div class="tab-pane" id="pdf">
                  <ul class="nav nav-tabs nav-tabs-platform nav-tabs-scroll js-catalog_url_set">
                      <li class="page-link-pdf" data-name="pdf_cover" data-ref="pdf_cover" data-href="{$editable_links.pdf_cover}"><a data-toggle="tab"><span>{$smarty.const.PDF_COVER}</span></a></li>
                      <li class="page-link-pdf" data-name="pdf" data-ref="pdf" data-href="{$editable_links.pdf}"><a data-toggle="tab"><span>{$smarty.const.PDF_CATALOG}</span></a></li>

                      {extensionPages group = 'pdf'}
                  </ul>
              </div>


              {foreach $addedGroups as $addedGroup}
                  <div class="tab-pane" id="{$addedGroup}">
                      <ul class="nav nav-tabs nav-tabs-platform nav-tabs-scroll js-catalog_url_set">
                          {extensionPages group = $addedGroup}
                      </ul>
                  </div>
              {/foreach}

              <div class="tab-pane" id="any">
                  <div class="any-page-block">
                      <input type="text" name="any_page" value="" class="any-page form-control" placeholder="Enter page URL"/>
                  </div>
              </div>
          </div>
      </div>
  </div>
    {/if}
    

  <script type="text/javascript">
    (function ($) {
      $(function(){
        var tab_links = $('.page-elements-top .nav-tabs');

        $('.edit', tab_links).on('click', function(){
          var name = $(this).data('name');
          $('<a href="{Yii::$app->urlManager->createUrl(['design/add-page-settings'])}"></a>').popUp({ data: {
            theme_name: '{$theme_name}',
            page_name: name
          }}).trigger('click');
          return false;
        });
          $('.remove', tab_links).on('click', function(){
              var name = $(this).data('name');
              $.popUpConfirm('Do you really want to remove this page?', function(){
                  $.get('{Yii::$app->urlManager->createUrl(['design/remove-page-template'])}', { 'page_name': name, 'theme_name': '{$theme_name}'}, function(){
                      location.reload();
                  })
              });
              return false;
          });

          $('li', tab_links).each(function(){
              var tab = $(this);
              if (tab.hasClass('add-page')) return '';

              var copyPageButton = $('<span class="copy-page-button" title="Copy page"></span>');
              if ($('.edit', tab).length > 0){
                  $('.edit', tab).before(copyPageButton)
              } else if ($('.remove', tab).length > 0) {
                  $('.remove', tab).before(copyPageButton)
              } else {
                  $('span', tab).append(copyPageButton)
              }
              copyPageButton.on('click', function (){
                  var name = tab.data('name');
                  $('<a href="{Yii::$app->urlManager->createUrl(['design/copy-page-pop-up'])}"></a>').popUp({ data: {
                      theme_name: '{$theme_name}',
                      page_name: name
                  }}).trigger('click');
                  return false;
              })
          })
      })
    })(jQuery)
  </script>

  <div class="info-view-wrap"{if strpos($theme_name, '-mobile')} style="width: 500px"{/if}>
      <div class="info-view"></div>
  </div>

  <div class="btn-bar btn-bar-edp-page after">
    <div class="btn-left">
      <span data-href="{$link_cancel}" class="btn btn-save-boxes">{$smarty.const.IMAGE_CANCEL}</span>
    </div>
    <div class="btn-right">
      <span class="btn btn-preview">{$smarty.const.ICON_PREVIEW}</span>
      <span class="btn btn-edit" style="display: none">{$smarty.const.IMAGE_EDIT}</span>
      <span data-href="{$link_save}" class="btn btn-confirm btn-save-boxes">{$smarty.const.IMAGE_SAVE}</span>
    </div>
  </div>

</div>

<script type="text/javascript" src="{$app->request->baseUrl}/plugins/html2canvas.js"></script>
<script type="text/javascript">
    (function($){
        $(function(){
            var linksButton = $('.tp-all-pages-btn-wrapp');
            var linksBox = $('.tp-all-pages-btn');
            var body = $('body');
            var hideLinksBoxKey = true;

            var hideLinksBox = function(){
                if (hideLinksBoxKey) {
                    linksBox.removeClass('active');
                    body.off('click', hideLinksBox)
                }
            };
            linksButton.on('click', function(){
                if (!linksBox.hasClass('active')){
                    linksBox.addClass('active');

                    setTimeout(function(){
                        body.on('click', hideLinksBox)
                    }, 100)
                }
            });


            var resizeBar = $('.resize-bar');
            var infoViewWrap = $('.info-view-wrap');

            infoViewWrap.resizable({
                resize: function(event, ui) {
                    ui.size.height = ui.originalSize.height;
                }
            });
        })
    })(jQuery);

  var per_platform_links = {json_encode($per_platform_links)};
  (function($){
	$('#platform_selector').on('change',function(){
      if ( !per_platform_links[$(this).val()] ) return;
      var new_links = per_platform_links[$(this).val()];
      var reset_active = false;
      $('.js-catalog_url_set [data-ref]').each( function(){
        var $a = $(this);
        var ref = $a.attr('data-ref');
        if ( typeof new_links[ref] === 'string' ) {
          $a.attr('data-href', new_links[ref]);
          $a.data('href', new_links[ref]);
        } else if ( typeof new_links[ref] === 'object') {
            $a.attr('data-href', new_links[ref]['link']);
            $a.data('href', new_links[ref]['link']);
            console.log(new_links[ref]['link']);
        }
        if ( $a.attr('data-href').length==0 && $a.is(':visible') ) {
          $a.hide();
          if ( $a.hasClass('active') ) reset_active = true;
        }
        if ( $a.attr('data-href').length>0 && $a.not(':visible') ) $a.show();
      } );
      if ( reset_active ) {
        $('.js-catalog_url_set li[data-href]').filter(':not([data-href=""])').first().trigger('click')
      }else {
        $('.js-catalog_url_set li.active').first().trigger('click');
      }
    });

    $(function(){
      $('.btn-save-boxes').on('click', function(){
        $.get($(this).data('href'), { theme_name: '{$theme_name}'}, function(d){
          alertMessage(d);
          setTimeout(function(){
            $(window).trigger('reload-frame');

            $('body').append('<iframe src="{$app->request->baseUrl}/../?theme_name={$theme_name}" width="100%" height="0" frameborder="no" id="home-page"></iframe>');

            var home_page = $('#home-page');
            home_page.on('load', function(){
                html2canvas(home_page.contents().find('body').get(0), {
                  background: '#ffffff',
                  onrendered: function(canvas) {
                    $.post('upload/screenshot', { theme_name: '{$theme_name}', image: canvas.toDataURL('image/png')});
                    home_page.remove()
                  }
                })
            });

            saveEmailScreenshots();
              var timeDilay = 0;
            $('.scrtabs-tab-container li[data-href]').each(function(){
                var url = $(this).data('href');
                setTimeout(function(){
                    $.get(url)
                }, timeDilay)
                timeDilay += 100;
            })

          }, 500)
        })
      });

      var url = '';

      var cooked_url = $.cookie('page-url') || '';

      var cookie_url_match = cooked_url.match(/theme_name=([a-z0-9\-_]+)/);
      if (
          !cookie_url_match || (cookie_url_match && cookie_url_match[1] != '{$theme_name}')
      ){
        url = $('.js-catalog_url_set .active').attr('data-href');

        $.cookie('page-url', url);
      } else {
        url = $.cookie('page-url');
      }

      $('.info-view').infoView({
        page_url: url,
        theme_name: '{$theme_name}',
        //clear_url: {$clear_url}
      });


      /*$(window).on('scroll', function(){
        if ($(window).scrollTop() > 70) {
          $('.page-elements-top')
                  .css('top', $(window).scrollTop() - 70)
                  .addClass('scrolled')
        } else {
          $('.page-elements-top')
                  .css('top', 0)
                  .removeClass('scrolled')
        }
      });*/

      var redo_buttons = $('.redo-buttons');
      redo_buttons.on('click', '.btn-undo', function(){
        var event = $(this).data('event');
        $(redo_buttons).hide();
        $.get('design/undo', { 'theme_name': '{$theme_name}'}, function(){
          if (event == 'addPage' ){
            location.href = location.href
          }
          $(window).trigger('reload-frame');

        })
      });
      redo_buttons.on('click', '.btn-redo', function(){
        var event = $(this).data('event');
        $(redo_buttons).hide();
        $.get('design/redo', { 'theme_name': '{$theme_name}', 'steps_id': $(this).data('id')}, function(){
          if (event == 'addPage'){
            location.href = location.href
          }
          $(window).trigger('reload-frame');
        })
      });
      $.get('design/redo-buttons', { 'theme_name': '{$theme_name}'}, function(data){
        redo_buttons.html(data)
      });
      $(window).on('reload-frame', function(){
        $.get('design/redo-buttons', { 'theme_name': '{$theme_name}'}, function(data){
          redo_buttons.html(data);
          $(redo_buttons).show();
        })
      })

    })
  })(jQuery);
    $(document).ready(function(){
        var navTabsScroll = $('.nav-tabs-scroll');
        var all_page_btn = $('.tp-all-pages-btn').width() + 8;
        $('.scrtabs-tab-container').css('margin-right', all_page_btn);
        $('.tl-all-pages-block ul li').on('click', function () {
            $('.nav-tabs-scroll li.active').removeClass('active');
            var activeTab = $('.nav-tabs-scroll li[data-href="' + $(this).attr('data-href') + '"]');
            activeTab.addClass('active');
        });

        $('.nav-tabs-scroll li').on('click', function () {
            $('.tl-all-pages-block ul li.active').removeClass('active');
            $('.tl-all-pages-block ul li[data-href="' + $(this).attr('data-href') + '"]').addClass('active');
        });
  });


    function saveEmailScreenshots(){


        $('.page-link-email').each(function(){

            var email = $(this);
            var page_name = email.data('href');
            var email_page = $('<iframe src="' + page_name + '" width="850" height="0" frameborder="no"></iframe>');
            $('body').append(email_page);

            email_page.on('load', function(){
                html2canvas(email_page.contents().find('body').get(0), {
                    background: '#ffffff',
                    onrendered: function(canvas) {
                        $.post('upload/screenshot', {
                            theme_name: '{$theme_name}',
                            image: canvas.toDataURL('image/png'),
                            file_name: 'img/emails/' + email.data('ref')
                        });
                        email_page.remove()
                    }
                })
            });
        })

    };

</script>