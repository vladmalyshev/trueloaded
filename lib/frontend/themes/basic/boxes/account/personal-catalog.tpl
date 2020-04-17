{use class="frontend\design\Info"}
{use class = "yii\helpers\Html"}
{if $productsPersonalCatalog }
    {*<div class="heading-3">{$smarty.const.TEXT_PERSONAL_CATALOG}</div>*}
    <div class="w-cart-listing{\frontend\design\Info::addBlockToWidgetsList('cart-listing')}">
        <div class="headings">
            <div class="head remove">{$smarty.const.TEXT_REMOVE_CART}</div>
            <div class="head image">{$smarty.const.PRODUCTS}</div>
            <div class="head name"></div>
            <div class="head price">{$smarty.const.PRICE}</div>
            <div class="head qty"></div>
        </div>
        {foreach $productsPersonalCatalog as $productPC}
            <div class="item">
                <div class="remove"><a class="remove-btn pc-delete-item" data-reload="1" data-id="{$productPC.products_id}" href="#"></a></div>
                <div class="image">
                    {if $productPC.products_status}
                        <a href="{$productPC.link}"><img src="{$productPC.image}" alt="{$productPC.products_name|escape:'html'}" title="{$productPC.products_name|escape:'html'}"></a>
                    {else}
                        <img src="{$productPC.image}" alt="{$productPC.products_name|escape:'html'}" title="{$productPC.products_name|escape:'html'}">
                    {/if}
                </div>
                <div class="name">
                    {if $productPC.products_status}
                        <a href="{$productPC.link}">{$productPC.products_name}</a>
                    {else}
                        {$productPC.products_name}
                    {/if}
                    <div class="attributes">
                        {foreach $productPC.attr as $attr}
                            <div class="">
                                <strong>{$attr.products_options_name}:</strong>
                                <span>{$attr.products_options_values_name}</span>
                            </div>
                        {/foreach}
                    </div>
                </div>
                <div class="price">
                    {if $productPC.stock_indicator.flags.request_for_quote}
                        {$smarty.const.HEADING_REQUEST_FOR_QUOTE}
                    {else}
                        {$productPC.price}
                    {/if}
                </div>
                <div class="qty">
                    {if $productPC.products_status}
                        {if $productPC.stock_indicator.flags.add_to_cart}
                            <a class="view_link pc-send-shop" data-qty="{$productPC.add_qty}" data-id="{$productPC.id}" data-cart="{if $productPC.stock_indicator.flags.request_for_quote}quote{else}cart{/if}" href="#">{$smarty.const.BOX_WISHLIST_MOVE_TO_CART}</a>
                        {else}
                            {$smarty.const.TEXT_PRODUCT_OUT_STOCK}
                        {/if}
                    {else}
                        {$smarty.const.TEXT_PRODUCT_DISABLED}
                    {/if}
                </div>
            </div>
        {/foreach}
    </div>
{else}
    {$empty = true}
{/if}
<script type="text/javascript">
    tl('' , function(){
        var body = $('body');
        body.on('click','.pc-send-shop', function(event) {
            event.preventDefault();
            actionPersonalCatalog(
                '{Yii::$app->urlManager->createUrl('personal-catalog/add-to-cart')}',
                $(this).attr('data-id'),
                $(this).attr('data-cart'),
                $(this).attr('data-qty'),
            );
        });
        body.on('click', '.pc-delete-item', function (event) {
            event.preventDefault();
            actionPersonalCatalog(
                '{Yii::$app->urlManager->createUrl('personal-catalog/confirm-delete')}',
                $(this).attr('data-id'),
                '',
                1,
                1
            );
        });
        function actionPersonalCatalog(action, id, in_cart, qty, reload) {
            if (qty === undefined ) {
                qty = 1;
            }
            if (reload === undefined) {
                reload = 0;
            }

            $.post(action,
                {
                    _csrf: $('meta[name="csrf-token"]').attr('content'),
                    products_id:id,
                    pc_in_cart:in_cart,
                    pc_qty: qty,
                    reload: reload,
                }
                , function(response){
                    alertMessage(response.message);
                });
        }
    {if $empty && !Info::isAdmin()}
        {if $settings[0].hide_parents == 1}
        $('#box-{$id}').hide()
        {elseif $settings[0].hide_parents == 2}
        $('#box-{$id}').closest('.box-block').hide()
        {elseif $settings[0].hide_parents == 3}
        $('#box-{$id}').closest('.box-block').closest('.box-block').hide()
        {elseif $settings[0].hide_parents == 4}
        $('#box-{$id}').closest('.box-block').closest('.box-block').closest('.box-block').hide()
        {/if}
    {/if}
    });
</script>
