{use class="Yii"}
{use class="backend\assets\ImageMapsAsset"}
{ImageMapsAsset::register($this)|void}


<form action="" class="map-info">
    <input type="hidden" name="maps_id" value="{$mapsId}"/>
<div class="tabbable tabbable-custom">
    <ul class="nav nav-tabs">

        {if $languages|count > 1}
            {foreach $languages as $language}
                <li{if $language.id == $languages_id} class="active"{/if}><a href="#{$item.id}_{$language.id}" data-toggle="tab">{$language.logo} {$language.name}</a></li>
            {/foreach}
        {/if}

    </ul>
    <div class="tab-content">

        {foreach $languages as $language}
            <div class="tab-pane{if $language.id == $languages_id} active{/if}" id="{$item.id}_{$language.id}" data-language="{$language.id}" style="min-height: 0">
                <div class="container">
                    <div class="row">
                        <div class="col-md-2"><label>{$smarty.const.TABLE_HEADING_TITLE}:</label></div>
                        <div class="col-md-4"><input type="text" name="title[{$language.id}]" value="{$title[$language.id]}" class="form-control"></div>
                    </div>
                </div>


            </div>
        {/foreach}

    </div>
</div>
</form>

<div class="tools-bar-wrap">
<div class="tools-bar">
    <span class="btn upload-image">{$smarty.const.UPLOAD_IMAGE}</span>
    {*<span class="btn" id="from_html">from html</span>*}
    <span class="btn" id="rectangle">{$smarty.const.TEXT_RECTANGLE}</span>
    <span class="btn" id="circle">{$smarty.const.TEXT_CIRCLE}</span>
    <span class="btn" id="polygon">{$smarty.const.TEXT_POLYGON}</span>
    <span class="btn" id="edit">{$smarty.const.IMAGE_EDIT}</span>
    {*<span class="btn" id="to_html">to html</span>*}
    {*<span class="btn" id="preview">preview</span>*}
    <span class="btn" id="clear">{$smarty.const.TEXT_CLEAR}</span>
    {*<span class="btn" id="save">local save</span>*}
    {*<span class="btn" id="load">load from local storeg</span>*}
    <span class="btn" id="show_help">?</span>
</div>
</div>


<div id="wrapper">
    <div id="coords"></div>
    <div id="debug"></div>

    <div id="image_wrapper">
        <div id="image">
            <img src="../images/maps/{$image}" alt="#" id="img" class="map-image"{if !$image} style="display: none"{/if} />
            <svg xmlns="http://www.w3.org/2000/svg"
                 version="1.2"
                 baseProfile="tiny"
                 id="svg"
                 viewbox="0 0 1920 1080"
                 preserveAspectRatio="xMinYMin meet"></svg>
        </div>
    </div>
</div>

<!-- For html image map code -->
<div id="code">
    <span class="close_button" title="close"></span>
    <div id="code_content"></div>
</div>

<!-- Edit details block -->
<div id="svg-item-attributes" style="display: none">
    <form class="edit-details">
        <div class="popup-heading">{$smarty.const.TEXT_ATTRIBUTES}</div>
        <div class="popup-content">



            <div class="form-container">
                <div class="row">
                    <div class="col-md-3"><label>{$smarty.const.TEXT_LINK}:</label></div>
                    <div class="col-md-7">
                        <select name="link" id="" class="form-control">
                            <option value="link-to-product">{$smarty.const.TEXT_TO_PRODUCT}</option>
                            <option value="link-to-category">to category</option>
                            <option value="link-to-info">to infopage</option>
                            <option value="link-to-brand">to brand</option>
                            <option value="link-to-delivery">to delivery location</option>
                            <option value="link-to-common">to common page</option>
                            <option value="link-default">{$smarty.const.TEXT_CUSTOM}</option>
                        </select>
                    </div>
                </div>
                <div class="row link-to-page link-default">
                    <div class="col-md-3"><label>{$smarty.const.TEXT_URL}:</label></div>
                    <div class="col-md-7">
                        <input type="text" name="href" value="" class="form-control">
                    </div>

                    <div class="popup-tabs-wrap" style="clear: both; padding: 20px 10px 10px 10px;">
                        <ul class="popup-tabs">

                            {if $languages|count > 1}
                                {foreach $languages as $language}
                                    <li{if $language.id == $languages_id} class="active"{/if}><a href="#attr_{$language.id}" data-toggle="tab">{$language.logo} {$language.name}</a></li>
                                {/foreach}
                            {/if}

                        </ul>
                        <div class="popup-tab-content">

                            {foreach $languages as $language}
                                <div class="popup-tab-pane{if $language.id == $languages_id} active{/if}" id="attr_{$language.id}" data-language="{$language.id}" style="min-height: 0">
                                    <div class="container form-container">
                                        <div class="row">
                                            <div class="col-md-3"><label>{$smarty.const.TABLE_HEADING_TITLE}:</label></div>
                                            <div class="col-md-7">
                                                <input type="text" name="title[{$language.id}]" value="" class="form-control">
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-3"><label>{$smarty.const.TEXT_DESCRIPTION}:</label></div>
                                            <div class="col-md-7">
                                                <textarea name="description[{$language.id}]" class="form-control"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            {/foreach}

                        </div>
                    </div>

                </div>
                <div class="row link-to-page link-to-product" style="display: none">
                    <div class="col-md-3"><label>{$smarty.const.PRODUCT_NAME_MODEL}:</label></div>
                    <div class="col-md-7">
                        <div class="form-container">
                            <div class="row r-products_id r-product_name">
                                <div class="col-md-12">
                                    <input type="hidden" name="products_id" value="">
                                    <input type="text" name="product_name" value="" class="form-control product-name" autocomplete="off">
                                </div>
                            </div>
                        </div>
                        <div class="" style="text-align: right; margin-top: 10px">
                            <span class="btn btn-add-more" data-repeat="r-products_id">Add more</span>
                        </div>
                    </div>
                </div>
                <div class="row link-to-page link-to-category" style="display: none">
                    <div class="col-md-3"><label>Category:</label></div>
                    <div class="col-md-7">
                        <div class="form-container">
                            <div class="row r-categories_id r-category_name">
                                <div class="col-md-12">
                                    <input type="hidden" name="categories_id" value="">
                                    <input type="text" name="category_name" value="" class="form-control category-name" autocomplete="off">
                                </div>
                            </div>
                        </div>
                        <div class="" style="text-align: right; margin-top: 10px">
                            <span class="btn btn-add-more" data-repeat="r-categories_id">Add more</span>
                        </div>
                    </div>
                </div>
                <div class="row link-to-page link-to-info" style="display: none">
                    <div class="col-md-3"><label>Info page:</label></div>
                    <div class="col-md-7">
                        <div class="form-container">
                            <div class="row r-information_id r-product_info">
                                <div class="col-md-12">
                                    <input type="hidden" name="information_id" value="">
                                    <input type="text" name="product_info" value="" class="form-control information-name" autocomplete="off">
                                </div>
                            </div>
                        </div>
                        <div class="" style="text-align: right; margin-top: 10px">
                            <span class="btn btn-add-more" data-repeat="r-information_id">Add more</span>
                        </div>
                    </div>
                </div>


                <div class="row link-to-page link-to-brand" style="display: none">
                    <div class="col-md-3"><label>Brand page:</label></div>
                    <div class="col-md-7">
                        <div class="form-container">
                            <div class="row r-brand_id r-brand_name">
                                <div class="col-md-12">
                                    <input type="hidden" name="brand_id" value="">
                                    <input type="text" name="brand_name" value="" class="form-control brand-name" autocomplete="off">
                                </div>
                            </div>
                        </div>
                        <div class="" style="text-align: right; margin-top: 10px">
                            <span class="btn btn-add-more" data-repeat="r-brand_id">Add more</span>
                        </div>
                    </div>
                </div>
                <div class="row link-to-page link-to-delivery" style="display: none">
                    <div class="col-md-3"><label>Delivery location page:</label></div>
                    <div class="col-md-7">
                        <div class="form-container">
                            <div class="row r-delivery_id r-delivery_name">
                                <div class="col-md-12">
                                    <input type="hidden" name="delivery_id" value="">
                                    <input type="text" name="prod_delivery_name" value="" class="form-control delivery-name" autocomplete="off">
                                </div>
                            </div>
                        </div>
                        <div class="" style="text-align: right; margin-top: 10px">
                            <span class="btn btn-add-more" data-repeat="r-delivery_id">Add more</span>
                        </div>
                    </div>
                </div>
                <div class="row link-to-page link-to-common" style="display: none">
                    <div class="col-md-3"><label>Common page:</label></div>
                    <div class="col-md-7">
                        <select name="common_name" class="form-control common-name">
                            <option value="index/index">Home page</option>
                            <option value="contact/index">Contact Us</option>
                            <option value="account/login">Sign In</option>
                            <option value="account/index">My Account</option>
                            <option value="checkout/index">Checkout</option>
                            <option value="shopping-cart/index">Shopping Cart</option>
                            <option value="catalog/products-new">New Product</option>
                            <option value="catalog/featured-products">Featured Products</option>
                            <option value="catalog/sales">Special Products</option>
                            <option value="catalog/gift-card">Gift Card</option>
                            <option value="catalog/all-products">All Products</option>
                            <option value="sitemap/index">Site map</option>
                            <option value="promotions/index">Promotions</option>
                        </select>
                    </div>
                    <div class="popup-tabs-wrap" style="clear: both; padding: 20px 10px 10px 10px;">
                        <ul class="popup-tabs">

                            {if $languages|count > 1}
                                {foreach $languages as $language}
                                    <li{if $language.id == $languages_id} class="active"{/if}><a href="#attr_{$language.id}" data-toggle="tab">{$language.logo} {$language.name}</a></li>
                                {/foreach}
                            {/if}

                        </ul>
                        <div class="popup-tab-content">

                            {foreach $languages as $language}
                                <div class="popup-tab-pane{if $language.id == $languages_id} active{/if}" id="attr_{$language.id}" data-language="{$language.id}" style="min-height: 0">
                                    <div class="container form-container">
                                        <div class="row">
                                            <div class="col-md-3"><label>{$smarty.const.TABLE_HEADING_TITLE}:</label></div>
                                            <div class="col-md-7">
                                                <input type="text" name="title_c[{$language.id}]" value="" class="form-control">
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-3"><label>{$smarty.const.TEXT_DESCRIPTION}:</label></div>
                                            <div class="col-md-7">
                                                <textarea name="description_c[{$language.id}]" class="form-control"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            {/foreach}

                        </div>
                    </div>
                </div>


            </div>

        </div>
        <div class="popup-buttons">
            <button type="submit" class="btn btn-primary btn-save">{$smarty.const.IMAGE_SAVE}</button>
            <span class="btn btn-cancel">{$smarty.const.IMAGE_CANCEL}</span>
        </div>
    </form>
</div>

<!-- From html block -->
<div id="from_html_wrapper">
    <form id="from_html_form">
        <h5>Loading areas</h5>
        <span class="close_button" title="close"></span>
        <p>
            <label for="code_input">Enter your html code:</label>
            <textarea id="code_input"></textarea>
        </p>
        <button id="load_code_button">Load</button>
    </form>
</div>

<!-- Help block -->
<div id="overlay"></div>
<div id="help">
    <span class="close_button" title="close"></span>
    <div class="txt">
        <section>
            <h4>{$smarty.const.DRAWING_RECTANGLE_CIRCLE_POLYGON}</h4>
            <p><span class="key">ENTER</span> &mdash; {$smarty.const.STOP_POLYGON_DRAWING}</p>
            <p><span class="key">ESC</span> &mdash; {$smarty.const.CANCEL_DRAWING_NEW_AREA}</p>
            <p><span class="key">SHIFT</span> &mdash; {$smarty.const.SQUARE_DRAWING_IN_CASE}</p>
        </section>
        <section>
            <h4>{$smarty.const.EDITING_MODE}</h4>
            <p><span class="key">DELETE</span> &mdash; {$smarty.const.REMOVE_SELECTED_AREA}</p>
            <p><span class="key">ESC</span> &mdash; {$smarty.const.CANCEL_EDITING_SELECTED_AREA}</p>
            <p><span class="key">SHIFT</span> &mdash; {$smarty.const.EDIT_SAVE_PROPORTIONS_RECTANGLE}</p>
            <p><span class="key">I</span> &mdash; {$smarty.const.EDIT_ATTRIBUTES_SELECTED_AREA}</p>
            <p><span class="key">CTRL</span> + <span class="key">C</span> &mdash; {$smarty.const.COPY__SELECTED_AREA}</p>
            <p><span class="key">&uarr;</span> &mdash; {$smarty.const.MOVE_SELECTED_AREA_UP}</p>
            <p><span class="key">&darr;</span> &mdash; {$smarty.const.MOVE_SELECTED_AREA_DOWN}</p>
            <p><span class="key">&larr;</span> &mdash; {$smarty.const.MOVE_SELECTED_AREA_LEFT}</p>
            <p><span class="key">&rarr;</span> &mdash; {$smarty.const.MOVE_SELECTED_AREA_RIGHT}</p>
        </section>
    </div>
</div>


<script>
    var imageMapsData = {
        mapsId: {$mapsId},
        items: '{if $items}{$items}{else}{json_encode(['areas' => []])}{/if}'
    }
</script>

