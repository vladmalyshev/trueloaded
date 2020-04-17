{use class="Yii"}
{use class="frontend\design\Info"}
{use class="frontend\design\Block"}
{use class = "yii\helpers\Html"}
{\frontend\design\Info::addBoxToCss('info')}
{\frontend\design\Info::addBoxToCss('select-suggest')}
{\frontend\design\Info::addBoxToCss('autocomplete')}

<script type="text/javascript" src="{Info::themeFile('/js/checkout.js')}"></script>
<script type="text/javascript">
    var $frmCheckout;
    var submitter = 0;   
    
    var checkout;
    
    function checkCountryVatState() {
        var selected = $('select[name="country"]').val();
        if (selected == {$smarty.const.STORE_COUNTRY}) {
            $('.company_vat_box').hide();
        } else {
            $('.company_vat_box').show();
        }
    }

    tl([      
      '{Info::themeFile('/js/main.js')}',
    ], function(){

        checkout = new checkout('{$worker}');

        $frmCheckout = $('#frmCheckout');
      
        $frmCheckout.append('<input type="hidden" name="xwidth" value="'+screen.width+'">').append('<input type="hidden" name="xheight" value="'+screen.height+'">');

        if ( typeof window.check_form == 'function' ) {
          $frmCheckout.on('submit',function(){
            return window.check_form();
          });
        }
        
        //checkCountryVatState();
 
        /*$('.js_discount_apply').on('click',function() {
          checkout.data_changed('credit_class', [{
            name:'coupon_apply',value:'y'
          }]);
          return false;
        });*/
    })

  </script>
{if $payment_error && $payment_error.title }
    <div class="" id="payment_error-box" style="display:none;">
        <div class="" style="padding: 20px">
            <strong>{$payment_error.title}</strong><br>
            {$payment_error.error}
        </div>
    </div>
    <script>
        tl('{Info::themeFile('/js/main.js')}', function(){
            $('<a href="#payment_error-box"></a>').popUp().trigger('click')
        });
    </script>
{/if}


{if $message != ''}
    <div class="" id="messages-box" style="display:none;"><div class="" style="padding: 20px">{$message}</div></div>
    <script>
        tl('{Info::themeFile('/js/main.js')}', function(){
            $('<a href="#messages-box"></a>').popUp().trigger('click')
        });
    </script>
{/if}


{Html::beginForm($checkout_process_link, 'post', ['id' => 'frmCheckout', 'name' => 'one_page_checkout'])}
  {\frontend\design\Info::addBoxToCss('form')}


    {Block::widget(['name' => $page_name, 'params' => ['type' => 'checkout', 'params' => $params]])}


{Html::endForm()}

{$payment_javascript_validation}


<script type="text/javascript">
    tl('{Info::themeFile('/js/main.js')}', function(){
        $('.order-summary').scrollBox();

        $('.closeable-box').closeable();
    })
</script>



