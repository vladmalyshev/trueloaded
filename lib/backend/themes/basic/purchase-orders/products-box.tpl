{use class="frontend\design\Block"}
{use class="frontend\design\Info"}
{use class="yii\helpers\Html"}
{use class="common\helpers\Output"}
<div class="wb-or-prod product_adding">
    <form name="cart_quantity" action="{\Yii::$app->urlManager->createUrl($queryParams)}" method="post" id="product-form">
        <div class="popup-heading">{$smarty.const.TEXT_ADD_A_NEW_PRODUCT}</div>
        <div class="widget-content after bundl-box">
            <div class="attr-box oreder-edit-tree-box oreder-edit-box-1">
                <div class="widget widget-attr-box box box-no-shadow" style="margin-bottom: 0;">
                    <div class="widget-header">
                        <h4>{$smarty.const.TEXT_PRODUCTS}</h4>
                        <div class="box-head-serch after search_product">
                            <input type="text" name="search" value="" id="search_text" class="form-control" autocomplete="off" placeholder="{$smarty.const.TEXT_TYPE_CHOOSE_PRODUCT}">
                            <button onclick="return searchCancel();"></button>
                        </div>
                    </div>
                    <div class="widget-content">
                        <div id="tree" data-tree-server="{\Yii::$app->urlManager->createUrl($queryParams)}" class="oreder-edit-tree">
                            <ul>
                            {foreach $category_tree_array as $tree_item }
                                <li class="{if $tree_item.lazy}lazy {/if}{if $tree_item.folder}folder {/if}" id="{$tree_item.key}">{$tree_item.title}</li>
                            {/foreach}
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="attr-box attr-box-2">
                <span class="btn btn-primary" onclick="productAdd()"></span>
            </div>
            <div class="attr-box attr-box-3 oreder-edit-box-2">
                <div class="product_holder">
                    <div class="widget box box-no-shadow">
                        <div class="widget-content after"></div>
                    </div>
                </div>
            </div>
        </div>
        {tep_draw_hidden_field('action', 'add_product')}
        <div class="noti-btn three-btn">
            <div><span class="btn btn-cancel">{$smarty.const.IMAGE_CANCEL}</span></div>
            <div><input type="submit" class="btn btn-big btn-orange btn-confirm btn-save" style="display:none;" value="{$smarty.const.IMAGE_ADD}"></div>
            <div class="btn-center"><span class="btn btn-reset" style="display:none;">{$smarty.const.TEXT_RESET}</span></div>
        </div>
    </form>
</div>
<script>
    var treeRoot = [];
    var productArray = [];
    var productKeySelected;

    function searchQuery(text) {
        $.post($('#tree').attr('data-tree-server'), {
            search: text,
            action: 'search'
        }, function (response) {
            $('#tree').fancytree('getRootNode').removeChildren();
            $('#tree').fancytree('getRootNode').addChildren(response);
            $('#tree').fancytree('getTree').filterNodes(text);
            $('.fancytree-icon.icon-cubes').prev().hide();
        }, 'json');
    }

    function searchCancel(isSoft) {
        if (isSoft != true) {
            $('#search_text').val('');
        }
        $('#tree').fancytree('getRootNode').removeChildren();
        $('#tree').fancytree('getRootNode').addChildren(treeRoot);
        $('#tree').fancytree('getTree').clearFilter();
        return false;
    }

    function productDelete(uprid) {
        $.each(productArray, function(index) {
            if ((this.uprid == uprid || uprid == 'doDeleteAll') && this.saved != true) {
                delete(productArray[index]);
            }
        });
        productTable();
    }

    $('.btn-reset').click(function() {
        productDelete('doDeleteAll');
    });

    function productAdd() {
        if (productKeySelected) {
            let value = productKeySelected.substr(1).split('_');
            productRead(value[0]);
        }
    }

    function productRead(id) {
        $.post("{\Yii::$app->urlManager->createUrl($queryParams)}", {
            'productId': id,
            'action': 'read'
        }, function (response, status) {
            if (status == 'success') {
                $.each(response, function() {
                    let that = this;
                    let add = false;
                    if (that.hasOwnProperty('uprid')) {
                        add = true;
                        $.each(productArray, function() {
                            if (that.uprid == this.uprid) {
                                add = false;
                                return false;
                            }
                        });
                        if (add == true) {
                            that.saved = false;
                            productArray.push(that);
                        }
                    }
                });
                productTable();
            }
        }, 'json');
    }

    function productTable() {
        $('.add-product .btn-save').hide();
        $('.add-product .btn-reset').hide();
        $('.product_holder .widget .widget-content').html('{$smarty.const.TEXT_PRODUCT_NOT_SELECTED}');
        let tableHtml = '<table class="table" border="0" width="100%" cellspacing="0" cellpadding="2">'
            + '<thead><tr class="dataTableHeadingRow"><th class="dataTableHeadingContent">{$smarty.const.TABLE_HEADING_QUANTITY}</th>'
            + '<th class="dataTableHeadingContent left" colspan="2">{$smarty.const.TABLE_HEADING_PRODUCTS}</th>'
            + '<th class="dataTableHeadingContent" width="10%">{$smarty.const.TABLE_HEADING_PRODUCTS_MODEL}</th>'
            + '<th class="dataTableHeadingContent" width="10%" align="center">{$smarty.const.TABLE_HEADING_PRICE_EXCLUDING_TAX}</th>'
            + '<th class="dataTableHeadingContent" width="100px"></th></tr></thead>';
            let isTable = false;
        $.each(productArray, function() {
            if (this.saved != false) {
                return;
            }
            isTable = true;
            tableHtml += '<tr class="dataTableRow product_info">'
                + '<td class="dataTableContent plus_td box_al_center" align="center">'
                    + '<input class="form-control qty" name="qty[' + this.uprid + ']" value="' + this.qty + '" uprid="' + this.uprid + '" type="text">'
                + '</td>'
                + '<td class="dataTableContent box_al_center" width="100px">'
                    + this.image
                + '</td>'
                + '<td class="dataTableContent left">'
                    + '<input class="form-control name" name="name[' + this.uprid + ']" value="' + this.name + '" type="text">' + this.attribute
                + '</td>'
                + '<td class="dataTableContent left" width="10%">'
                    + '<input class="form-control name" name="model[' + this.uprid + ']" value="' + this.model + '" type="text">'
                + '</td>'
                + '<td class="dataTableContent" width="10%">'
                    + '<input class="form-control price" name="price[' + this.uprid + ']" value="' + this.price + '" type="text">'
                + '</td>'
                + '<td class="dataTableContent adjust-bar" style="text-align: center;">'
                    + '<div class="del-pt" onclick="productDelete(\'' + this.uprid + '\');"></div>'
                + '</td>'
                + '</tr>';
        });
        tableHtml += '</table>';
        if (isTable == true) {
            $('.product_holder .widget .widget-content').html(tableHtml);
            $('.add-product .btn-save').show();
            $('.add-product .btn-reset').show();
        }
    }

    (function($) {
        productArray = [];
        $('#order_management_data #create_order #products_holder input[name^="qty["]').each(function() {
            productArray.push({ uprid: $(this).attr('uprid'), qty: $(this).val(), saved: true });
        });

        $('div.add-product form#product-form').submit(function (e) {
            $.each(productArray, function() {
                if (this.saved == false) {
                    $('.product_holder .widget .widget-content input[uprid="' + this.uprid + '"]').parents('tr').each(function() {
                        $(this).find('div.del-pt').attr('onclick', 'deletePurchaseOrderProduct(this);');
                        $('#order_management_data #create_order #products_holder table tr:last').after(this);
                    });
                }
            });
            $('div.add-product form#product-form span.btn-cancel').trigger('click');
            return false;
        })

        $('#tree').fancytree({
            extensions: ["glyph", "filter"],
            checkbox: false,
            init: function (event, data) {
                treeRoot = data.tree.rootNode.children;
            },
            lazyLoad: function (event, data) {
                data.result = {
                    url: $(this).attr('data-tree-server'),
                    type: 'POST',
                    data: {
                        'action': 'category',
                        'category_id': data.node.key
                    },
                    dataType: 'json'
                };
            },
            _postProcess: function (event, data) {
                $('.fancytree-icon.icon-cubes').prev().hide();
            },
            click: function (event, data) {
                var node = data.node;
                if (!node.isFolder()) {
                    productKeySelected = node.key;
                } else {
                    productKeySelected = false;
                }
            },
            dblclick: function (event, data) {
                var node = data.node;
                if (!node.isFolder()) {
                    productKeySelected = node.key;
                    let value = node.key.substr(1).split('_');
                    productRead(value[0]);
                }
            },
            glyph: {
                map: {
                    doc: "icon-cubes", //"fa fa-file-o",
                    docOpen: "icon-cubes", //"fa fa-file-o",
                    checkboxUnknown: "icon-check-empty", //"fa fa-square",
                    dragHelper: "fa fa-arrow-right",
                    dropMarker: "fa fa-long-arrow-right",
                    error: "fa fa-warning",
                    expanderClosed: "icon-expand", //"fa fa-caret-right",
                    expanderLazy: "icon-plus-sign-alt", //"icon-expand-alt", //"fa fa-angle-right",
                    expanderOpen: "icon-minus-sign-alt", //"fa fa-caret-down",
                    folder: "icon-folder-close-alt", //"fa fa-folder-o",
                    folderOpen: "icon-folder-open-alt", //"fa fa-folder-open-o",
                    loading: "icon-spinner" //"fa fa-spinner fa-pulse"
                }
            }
        });

        $('#search_text')
            .off()
            .on('keypress', function(event) {
                if (event.keyCode == 13 || event.which == 13) {
                    event.preventDefault();
                    $(this).autocomplete('source', { term: $(this).val() });
                    return false;
                } else if (event.keyCode == 27 || event.which == 27) {
                    event.preventDefault();
                    searchCancel();
                    return false;
                }
            })
            .autocomplete({
                source: function(request) {
                    if (request.term.length > 2) {
                        searchQuery(request.term);
                    } else {
                        searchCancel(true);
                    }
                },
                delay: 50,
                minLength: 0,
                autoFocus: true,
                appendTo: '.auto-wrapp'
            })
            .focus(function() {
                $(this).autocomplete('source', { term: $(this).val() });
            });

        productTable();
        $('#search_text').focus();
    })(jQuery);
</script>