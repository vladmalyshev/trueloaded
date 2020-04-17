{use class="Yii"}

<form action="" class="email-editor">
    <input type="hidden" name="email_id" value="{$id}"/>

    <div class="row" style="margin-bottom: 20px">
        <div class="col-md-1"><label>{$smarty.const.TEXT_SUBJECT}</label></div>
        <div class="col-md-3"><input type="text" name="subject" value="{$subject}" class="form-control"/></div>
    </div>


    <div class="row">
        <div class="col-md-9">
            <div class="edit-field edit-blocks"></div>
        </div>
        <div class="col-md-3">

            <div class="editor-right-col">

            <div class="widget box" id="box-themes">
                <div class="widget-header">
                    <h4>{$smarty.const.BOX_HEADING_THEMES}</h4>
                    <div class="toolbar no-padding">
                        <div class="btn-group">
                            <span class="btn btn-xs"><i class="icon-angle-down"></i></span>
                        </div>
                    </div>
                </div>
                <div class="widget-content" style="display: block;">

                    <div class="themes">
                        {foreach $themes as $theme}
                            <div class="theme">
                                <div class="theme-title">{$theme.title}</div>
                                <div class="theme-items">
                                    {foreach $theme.templates as $key => $template}
                                        <div class="item" data-theme_name="{$theme.theme_name}" data-template="{$key}">
                                            <div class="image"><img src="{$app->request->baseUrl}/../themes/{$theme.theme_name}/img/emails/{$key}.png" alt=""></div>
                                            <div class="title">{$template}</div>
                                        </div>
                                    {/foreach}
                                </div>
                            </div>
                        {/foreach}
                    </div>

                </div>
            </div>

            <div class="widget box widget-closed" id="box-blocks">
                <div class="widget-header">
                    <h4>{$smarty.const.TEXT_BLOCKS}</h4>
                    <div class="toolbar no-padding">
                        <div class="btn-group">
                            <span class="btn btn-xs widget-collapse"><i class="icon-angle-down"></i></span>
                        </div>
                    </div>
                </div>
                <div class="widget-content" style="display: block;">

                    <div class="widgets-list">

                        <div class="block_text widget-item box">
                            <div class="widget-handle">{$smarty.const.TEXT_TEXT}</div>
                            <div class="text-content widget-box-content"><div class="text-cell">{$smarty.const.TEXT_TEXT}</div></div>
                        </div>

                        <div class="block_image widget-item box">
                            <div class="widget-handle">{$smarty.const.TEXT_IMAGE_}</div>
                            <div class="image-content widget-box-content block">
                                <div class="image-area-holder">{$smarty.const.DROP_IMAGE_HERE}</div>
                            </div>
                        </div>

                        <div class="block_text_image widget-item box">
                            <div class="widget-handle">Text + Image</div>
                            <table class="widget-box-content" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td style="width: 70%" class="text-cell">
                                        <div class="edit-text">{$smarty.const.TEXT_TEXT}</div>
                                    </td>
                                    <td style="width: 20px">&nbsp;</td>
                                    <td style="width: 30%" class="image-cell">
                                        <div class="image-content widget-box-content block">
                                            <div class="image-area-holder">{$smarty.const.DROP_IMAGE_HERE}</div>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <div class="block_image_text widget-item box">
                            <div class="widget-handle">Image + Text</div>
                            <table class="widget-box-content" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td style="width: 30%" class="image-cell">
                                        <div class="image-content widget-box-content block">
                                            <div class="image-area-holder">{$smarty.const.DROP_IMAGE_HERE}</div>
                                        </div>
                                    </td>
                                    <td style="width: 20px">&nbsp;</td>
                                    <td style="width: 66%" class="text-cell">{$smarty.const.TEXT_TEXT}</td>
                                </tr>
                            </table>
                        </div>

                        <div class="block_product_row widget-item box" data-cols="4">
                            <div class="widget-handle">Products</div>
                            <table class="widget-box-content" width="100%">
                                <tr>
                                    <td style="width: 25%" class="product-content block">
                                        <div class="product-area-holder">{$smarty.const.DROP_PRODUCT_HERE}</div>
                                    </td>
                                    <td style="width: 20px"></td>
                                    <td style="width: 25%" class="product-content block">
                                        <div class="product-area-holder">{$smarty.const.DROP_PRODUCT_HERE}</div>
                                    </td>
                                    <td style="width: 20px"></td>
                                    <td style="width: 25%" class="product-content block">
                                        <div class="product-area-holder">{$smarty.const.DROP_PRODUCT_HERE}</div>
                                    </td>
                                    <td style="width: 20px"></td>
                                    <td style="width: 25%" class="product-content block">
                                        <div class="product-area-holder">{$smarty.const.DROP_PRODUCT_HERE}</div>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <div class="block_block widget-item box" data-cols="3">
                            <div class="widget-handle">Block</div>
                            <table class="widget-box-content" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="width: 33%">
                                        <div class="block-content widget-box-content block"></div>
                                    </td>
                                    <td style="width: 33%">
                                        <div class="block-content widget-box-content block"></div>
                                    </td>
                                    <td style="width: 33%">
                                        <div class="block-content widget-box-content block"></div>
                                    </td>
                                </tr>
                            </table>
                        </div>


                    </div>

                </div>
            </div>

            <div class="widget box widget-closed" id="box-images">
                <div class="widget-header">
                    <h4>{$smarty.const.TAB_IMAGES}</h4>
                    <div class="toolbar no-padding">
                        <div class="btn-group">
                            <span class="btn btn-xs widget-collapse"><i class="icon-angle-down"></i></span>
                        </div>
                    </div>
                </div>
                <div class="widget-content" style="display: block;">

                    <div class="upload-image"><span class="btn">{$smarty.const.UPLOAD_IMAGE}</span></div>
                    <div class="suggest-images"></div>

                </div>
            </div>

            <div class="widget box widget-closed" id="box-products">
                <div class="widget-header">
                    <h4>{$smarty.const.TABLE_HEADING_PRODUCTS}</h4>
                    <div class="toolbar no-padding">
                        <div class="btn-group">
                            <span class="btn btn-xs widget-collapse"><i class="icon-angle-down"></i></span>
                        </div>
                    </div>
                </div>
                <div class="widget-content" style="display: block;">

                    <select name="" class="platform form-control">
                        {foreach $platforms as $platform}
                            <option value="{$platform.id}"{if $platform.is_default} selected{/if}>{$platform.text}</option>
                        {/foreach}
                    </select>
                    <input type="" class="product-name form-control" placeholder="{$smarty.const.START_TYPING_PRODUCT_NAME}"/>
                    <div class="suggest-products"></div>

                </div>
            </div>

            </div>






        </div>
    </div>

</form>


<link href="{$app->view->theme->baseUrl}/css/email-editor/edit.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="{$app->view->theme->baseUrl}/js/email-editor/edit.js"></script>
<script>
    emailEditor.init({
        id: {$id},
        themes: JSON.parse('{$themesJSON}'),
        styles: JSON.parse('{$styles}'),
        baseUrl: '{$app->request->baseUrl}',
        absUrl: '{Yii::$app->urlManager->createAbsoluteUrl('')}',
        data: '{$data}',
        theme_name: '{$theme_name}',
        template: '{$template}',
        tr: JSON.parse('{$tr}'),
    })
</script>

