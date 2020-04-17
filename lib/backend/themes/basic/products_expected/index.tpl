<!--=== Page Header ===-->
<div class="page-header">
    <div class="page-title">
        <h3>{$app->controller->view->headingTitle}</h3>
    </div>
</div>
<!-- /Page Header -->


<!--===featured list===-->
<div class="row" id="featured_list">
    <div class="col-md-12">
        <div class="widget box">
            <div class="widget-content" id="featured_list_data">
                <table class="table table-striped table-bordered table-hover table-responsive table-checkable datatable table-products_expected"
                       checkable_list="0,1" data_ajax="products_expected/list">
                    <thead>
                    <tr>
                        {foreach $app->controller->view->productsTable as $tableItem}
                            <th{if $tableItem['not_important'] == 1} class="hidden-xs"{/if}>{$tableItem['title']}</th>
                        {/foreach}
                    </tr>
                    </thead>
                </table>
            <p class="btn-toolbar" style="display:none; margin: 0px;">
             {*<input type="button" class="btn btn-primary" value="{$smarty.const.IMAGE_EDIT}" onClick="return showProduct()">*}
			 <a class="btn btn-primary" href="javascript:void(0)" onClick="showProduct()">{$smarty.const.IMAGE_EDIT}</a>
            </p>
            </div>
        </div>
    </div>
</div>
<script>
  var products_id = 0;
                                    function onClickEvent(obj, table) {
                                      products_id = $(obj).find('input[name=products_id]').val();
                                      if (products_id > 0)
                                        $('.btn-toolbar').show();
                                    }
                                    
                                    function onUnclickEvent(obj, table) {
                                     $('.btn-toolbar').hide();
                                    }
                                    
                                    function showProduct(){
                                     if (products_id){
									 
									 window.location.href = "{Yii::$app->urlManager->createUrl(['categories/productedit'])}?"+"products_id="+products_id;
									 
                                      $("#catalog_management_title").text('Product Management');
                                      $("#catalog_management").hide();
                                      $.post("categories/productedit", { 'products_id' : products_id }, function(data, status){
                                          if (status == "success") {
                                              $('#catalog_management_data').html(data);
                                              $("#catalog_management").show();
                                          } else {
                                              alert("Request error.");
                                          }
                                        },"html");
                                      }
                                    }
                                    
                                    function checkProductForm() {
                                      $("#catalog_management").hide();
                                      var products_id = $( "input[name='products_id']" ).val();
                                      $.post("categories/productsubmit", $('#products_edit').serialize(), function(data, status){
                                          if (status == "success") {
                                              //switchOnCollapse('catalog_list_collapse');
                                              var table = $('.table').DataTable();
                                              table.draw(false);
                                              //setTimeout('$(".cell_identify[value=\''+products_id+'\']").click();', 500);
                                          } else {
                                              alert("Request error.");
                                          }
                                      },"html");       
                                    }
</script>


                                <!--===Actions ===-->
				<div class="row" id="catalog_management" style="display: none;">
					<div class="col-md-12">
						<div class="widget box">
							<div class="widget-header">
								<h4><i class="icon-reorder"></i> <span id="catalog_management_title">Catalog Management</span></h4>
								<div class="toolbar no-padding">
									<div class="btn-group">
										<span class="btn btn-xs widget-collapse"><i class="icon-angle-down"></i></span>
									</div>
								</div>
							</div>
							<div class="widget-content" id="catalog_management_data">
                                                            Action
							</div>
						</div>
					</div>
                                </div>
				<!--===Actions ===-->