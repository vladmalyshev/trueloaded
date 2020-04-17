{use class="yii\helpers\Html"}
<!--=== Page Header ===-->
<div class="page-header">
    <div class="page-title">
        <h3>{$app->controller->view->headingTitle}</h3>
    </div>
</div>
<!-- /Page Header -->


<!--===modules list===-->
{if $isMultiPlatforms}
    <div class="tabbable tabbable-custom" style="margin-bottom: 0;">
        <ul class="nav nav-tabs">
            {foreach $platforms as $platform}
              <li class="{if $platform['id']==$selected_platform_id} active {/if}"><a class="js_link_platform_modules_select" href="{$platform['link']}" data-platform_id="{$platform['id']}"><span>{$platform['text']}</span></a></li>
            {/foreach}
        </ul>
    </div>
{/if}

<div class="order-wrap">
<div class="row order-box-list" id="modules_list">
    <div class="col-md-12">
            <form id="filterForm" name="filterForm" onsubmit="return applyFilter();">
              <input type="hidden" name="set" value="{$set}" />
              <input type="hidden" name="type" value="{$type}" />
              <input type="hidden" name="platform_id" id="page_platform_id" value="{$selected_platform_id}" />
              <input type="hidden" name="row" id="row_id" value="{$app->controller->view->filters->row}" />
                <div class="ord_status_filter_row modules-filter">
                    <div>{Html::checkbox('all_countries', $app->controller->view->filters->all_countries, ['value'=>'1', 'class' => 'js_check_status'])}{$smarty.const.TEXT_ALL_COUNTRIES}</div>
                    <div>{Html::checkbox('inactive', $app->controller->view->filters->inactive, ['value'=>'1', 'class' => 'js_check_status'])}{$smarty.const.SHOW_INACTIVE}</div>
                    <div>{Html::checkbox('not_installed', $app->controller->view->filters->installed, ['value'=>'1', 'class' => 'js_check_status'])}{$smarty.const.SHOW_NOT_INSTALLED}</div>
                </div>
            </form>

            <div class="widget-content" id="modules_list_data">
                <table class="table table-striped table-selectable table-checkable table-hover table-responsive table-bordered datatable double-grid" checkable_list="" data-b-paginate="false" data-paging="false" data-info="false" displayLength = "-1" data_ajax="modules/list?set={$set}" data-param_set="{$set}">
                    <thead>
                    <tr>
                        {foreach $app->controller->view->modulesTable as $tableItem}
                            <th{if $tableItem['not_important'] == 2} class="checkbox-column"{/if}{if $tableItem['not_important'] == 3} class="status-column"{/if}{if $tableItem['not_important'] == 1} class="hidden-xs"{/if}>{$tableItem['title']}</th>
                        {/foreach}
                    </tr>
                    </thead>
                </table>
            </div>
    </div>
</div>
<style type="text/css">.dataTables_wrapper.no-footer .dataTables_footer{ display:none; }</style>
<!--===/modules list===-->

<script type="text/javascript">
    var global = '';
    function viewModule( item_id ) {
        var attr_check = $('input[name="enabled"]').val();
        $.post("modules/view", {
            'set': '{$set}',
            'platform_id': $('#page_platform_id').val(),
            'enabled': attr_check,
            'module': item_id
        }, function (data, status) {
            if (status == "success") {
                $('#modules_management_data .scroll_col').html(data);
                $("#modules_management").show();
                /*switchOnCollapse('modules_management_collapse');*/
                deleteScroll();
                heightColumn();
            } else {
                alert("Request error.");
            }
        }, "html");

    //    $("html, body").animate({ scrollTop: $(document).height() }, "slow");

        return false;
    }

    function editModule(item_id) {

        $.get("modules/edit", {
            'set': '{$set}',
            'platform_id': $('#page_platform_id').val(),
            'module': item_id
        }, function (data, status) {
            if (status == "success") {
                $('#modules_management_data .scroll_col').html(data);
                $("#modules_management").show();
                /*switchOnCollapse('modules_management_collapse');*/
                deleteScroll();
                heightColumn();
            } else {
                alert("Request error.");
            }
        }, "html");
        return false;
    }

    function cancelModule(item_id){

      viewModule(item_id);
      //resetStatement();

      return false;
    }

    function updateModule(item_id) {
        $.post("modules/save?set={$set}", $('form[name=modules]').serialize(), function (data, status) {
            if (status == "success") {
                global = item_id;
                resetStatement();
                //viewModule(item_id);
            } else {
                alert("Request error.");
            }
        }, "html");
        return false;
    }

    function changeModule( item_id, action, enabled) {
        var process_changes = function(){
            var attr_check = $('input[name="enabled"]').val();
            if ( enabled===true ) {
                attr_check = 'on';
            } else if(enabled===false) {
                attr_check = '';
            };
            $.post("modules/change", {
                'set': '{$set}',
                'platform_id': $('#page_platform_id').val(),
                'module': item_id,
                'enabled': attr_check,
                'action': action
            }, function (response, status) {
                if (status == "success") {
                    if (response.need_translate != undefined){
                      window.location.href = '{Yii::$app->urlManager->createUrl("modules/translation")}?module='+response.need_translate+'&set={$set}&row='+$('#row_id').val();
                    } else {
                        global = item_id;
                        if (action=='remove'){
                            global = $('#modules_list_data .cell_identify[value!="'+item_id+'"]').first().val();
                        }
                        resetStatement();
                    }
                } else {
                    alert("Request error.");
                }
            }, "json");
        }
        if ( action=='remove' ) {
            bootbox.dialog({
                message: "{$smarty.const.TEXT_MODULES_REMOVE_CONFIRM}",
                title: "{$smarty.const.TEXT_MODULES_REMOVE_CONFIRM_HEAD}",
                buttons: {
                    success: {
                        label: "{$smarty.const.JS_BUTTON_YES}",
                        className: "btn-delete",
                        callback: process_changes
                    },
                    main: {
                        label: "{$smarty.const.JS_BUTTON_NO}",
                        className: "btn-cancel",
                        callback: function () {
                            //console.log("Primary button");
                        }
                    }
                }
            });
        }else{
            process_changes();
        }

        return false;
    }

    function switchOffCollapse(id) {
        var sID = "#" + id;
        if ( sID=='#' ) return;
        if ($(sID).children('i').hasClass('icon-angle-down')) {
            $(sID).click();
        }
    }

    function switchOnCollapse(id) {
        var sID = "#" + id;
        if ( sID=='#' ) return;
        if ($(sID).children('i').hasClass('icon-angle-up')) {
            $(sID).click();
        }
    }

    function resetStatement() {
        $("#modules_management").hide();

        //switchOnCollapse('modules_list_box_collapse');
        //switchOffCollapse('modules_management_collapse');

        //$('#modules_management_data .scroll_col').html('');
        //$('#modules_management').hide();

        var table = $('.table').DataTable();
        table.draw(false);

      //  $(window).scrollTop(0);

        return false;
    }

    function applyFilter(){
        resetStatement();
        return false;
    }
    function setFilterState() {
        var orig = $('#filterForm').serialize();
        var url = window.location.origin + window.location.pathname + '?' + orig.replace(/[^&]+=\.?(?:&|$)/g, '')
        window.history.replaceState({ }, '', url);
    }

    function onClickEvent(obj, table) {
        var dtable = $(table).DataTable();
        var id = dtable.row('.selected').index();
        $("#row_id").val(id);
        setFilterState();

        var event_id = $(obj).find('input.cell_identify').val();
        if (global != '') event_id = global;
        viewModule(  event_id );
        $('.table tr').removeClass('selected');
        $(obj).find('input.cell_identify').parents('tr').addClass('selected');
        global = '';
        var status_id = $(obj).find('input.check_on_off').val();
        $(".check_on_off").bootstrapSwitch({
            onSwitchChange: function () {
                var event_id = $(this).attr('data-module');
                changeModule(event_id, 'status', this.checked);
                return true;
            },
            onText: "{$smarty.const.SW_ON}",
            offText: "{$smarty.const.SW_OFF}",
            handleWidth: '20px',
            labelWidth: '24px'
        });
    }

    function onUnclickEvent(obj, table) {

        var event_id = $(obj).find('input.cell_identify').val();
    }
$(document).ready(function(){

    $('.js_link_platform_modules_select').on('click', function(){
        var activate_platform_id = $(this).attr('data-platform_id');
        $('#page_platform_id').val(activate_platform_id);

        var $tabs = $('.nav.nav-tabs');
        $tabs.find('li.active').removeClass('active');
        $tabs.find('li').each(function() {
            var $li = $(this);
            if ($li.find('a[data-platform_id="'+activate_platform_id+'"]').length>0) {
                $li.addClass('active');
            }
        });

        applyFilter();
        return false;
    });

 $( ".datatable tbody" ).sortable({
        axis: 'y',
        update: function( event, ui ) {
            $(this).find('[role="row"]').each(function() {
                if ( this.id ) return;
                var cell_ident = $(this).find('.cell_identify');
                if ( cell_ident.length>0 ) {
                  this.id = cell_ident.attr('name')+'_'+cell_ident.val();
                }
            });
            var post_data = [];
            $(this).find('[role="row"]').each(function() {
                var spl = this.id.indexOf('_');
                if ( spl===-1 ) return;
                post_data.push({ name:this.id.substring(0, spl)+'[]', value:this.id.substring(spl+1) });
            });
            post_data.push({ name:'set', value:$(this).parents('table[data-param_set]').attr('data-param_set') });
            post_data.push({ name:'platform_id', value: $('#page_platform_id').val() });

            $.post("{Yii::$app->urlManager->createUrl('modules/sort-order')}", post_data, function(data, status){
                 if (status == "success") {
                     resetStatement();
                 } else {
                     alert("Request error.");
                 }
         },"html");
        },
        handle: ".handle"
    }).disableSelection();
    /*$('.table').on('xhr.dt', function ( e, settings, json, xhr ) {
        console.log(json);
    } );*/
    $('.table').on('draw.dt', function () {
        $(this).find('.modules_divider').each(function(){
            $(this).parent('td').addClass('modules_divider_cell');
        });
    } );

    $('.js_check_status').bootstrapSwitch({
        onText: "{$smarty.const.SW_ON}",
        offText: "{$smarty.const.SW_OFF}",
        handleWidth: '20px',
        labelWidth: '24px'
    });
    $('.js_check_status').on('click switchChange.bootstrapSwitch',function(){
        applyFilter();
    });
});
</script>

<!--===  modules management ===-->
<div class="row right_column" id="modules_management" style="display: none;">
        <div class="widget box">
            <div class="widget-content fields_style" id="modules_management_data">
                <div class="scroll_col"></div>
            </div>
        </div>
</div>
<!--=== modules management ===-->
</div>