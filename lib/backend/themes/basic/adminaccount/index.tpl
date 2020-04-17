<div class="page-header">
    <div class="page-title">
        <h3>{$app->controller->view->headingTitle}</h3>
    </div>
</div>

<div id="load"></div>

<script type="text/javascript">

    $(document).ready(function(){
        $("#adminaccount_management").hide();
        loadMyAccount(0)
    });
		function closePopup() {
				$('.popup-box').trigger('popup.close');
				$('.popup-box-wrap').remove();
				return false;
		}
    function loadMyAccount(admin_id) {
        $.post("adminaccount/adminaccountactions", { 'admin_id' : admin_id }, function(data, status){
            if (status == "success") {
								var result = $(data).find('.account1 .ac_value a').text();
								var result_img = $(data).find('div.avatar img').attr('src');
								if($('span.avatar img').attr('src') !=result_img ){
								if(typeof result_img != 'undefined' && result_img.length > 0){
									$('span.avatar').remove('');									
									$('.user .dropdown-toggle').html('<span class="avatar"><img src="'+result_img+'"></span><span class="username">'+result+'</span>');
								}else{
									$('span.avatar img').remove();
									$('span.avatar').addClass('avatar_noimg');
									$('span.avatar').append('<i class="icon-user"></i>');
								}
								}
								$('.dropdown .username').text(result);
                $('#adminaccount_info_data').html(data);
                $("#adminaccount_info").show();
                $('.popup').popUp({
                    box: "<div class='popup-box-wrap'><div class='around-pop-up'></div><div class='popup-box'><div class='pop-up-close'></div><div class='popup-heading cat-head'>{$smarty.const.TEXT_EDITING_ACCOUNT}</div><div class='pop-up-content'><div class='preloader'></div></div></div></div>"
     });
            } else {
                alert("Request error.");

            }
        },"html");
    }

  /*  function getChangeForm(){
        $("#adminaccount_management").show();
        $("#admin_info_collapse").click();
        $.post("adminaccount/getpasswordform", { }, function(data, status){
            if (status == "success") {
                $('#adminaccount_management_data').html(data);
                // $("#adminaccount_management").show();
            } else {
                alert("Request error.");
            }
        },"html");
    }*/

    function hidePasswordForm(refresh){
        $("#adminaccount_management").hide();
        $("#admin_info_collapse").click();

        if(refresh == 1){
            loadMyAccount(0);
        }
    }

    function check_form(admin_id) {
        //ajax save
        $("#admin_management").hide();
        var admin_id = $( "#password_change_form input[name='admin_id']" ).val();
        $.post("adminaccount/passwordsubmit", $('#password_change_form').serialize(), function(data, status){
            if (status == "success") {
                //$('#admin_management_data').html(data);
                //$("#admin_management").show();
                $('#adminaccount_management_data').html(data);
                $("#adminaccount_management").show();
            } else {
                alert("Request error.");
                //$("#adminaccount_management").hide();
            }
        },"html");
        //$('#adminaccount_management_data').html('');
        return false;
    }
		function checkPassword() {
			var admin_id = $( "#check_pass_form input[name='admin_id']" ).val();
        $.post("adminaccount/checkpassword", $('#check_pass_form').serialize(), function(data, status){
            if (status == "success") {
							if($(data).filter('form').text().length > 0){
							$('#accountpopup #check_pass_form').remove();
								$('#accountpopup').html(data);
							}else{
								$('#accountpopup .alert-warning').remove();
								$('#accountpopup').prepend(data);
							}
            } else {
                alert("Request error.");
            }
        },"html");
				return false;
		}

    function saveAccount() {
        var admin_id = $( "#save_account_form input[name='admin_id']" ).val();
        var popupname = $( "#save_account_form input[name='popupname']" ).val();
				
        $.post("adminaccount/saveaccount",$('#save_account_form').serialize(), function(data, status){
            if (status == "success") { 
								$('#accountpopup').html(data);
								setTimeout(function(){									
									closePopup();
									loadMyAccount(admin_id);
								}, 1000)
								
              //  $("#adminaccount_management").show();
            } else {
                alert("Request error.");

            }
        },"html");

        return false;
    }
		function deleteImage(){
			var admin_id = $(this).data('admin_id');
			$.post("adminaccount/deleteimage",{ 'admin_id' : admin_id }, function(data, status){
            if (status == "success") { 								
								loadMyAccount(admin_id);
								$('body').append(data);
								setTimeout(function(){
									closePopup();									
								}, 1000)
								
              //  $("#adminaccount_management").show();
            } else {
                alert("Request error.");

            }
        },"html");

        return false;
		}
    function changeFormCollapse() {
        $("#adminaccount_management").hide();
        $('#adminaccount_management_data').html('');
        $('#admin_info_collapse').click();
    }

$(document).ready(function(){
    $(window).resize(function(){
        var height_line = $('.account_wrapper').height();
        $('.account_wrapper > div').css('min-height', height_line);
    })
    $(window).resize();
  $.fn.uploads1 = function(options){
  var option = jQuery.extend({
    overflow: false,
    box_class: false
  },options);

  var body = $('body');
  var html = $('html');

  return this.each(function() {

    var _this = $(this);

    _this.html('\
    <div class="upload-file-wrap">\
      <div class="upload-file-template">{$smarty.const.TEXT_DROP_FILES}<br>{$smarty.const.TEXT_OR}<br><span class="btn">{$smarty.const.IMAGE_UPLOAD}</span></div>\
      <div class="upload-file"></div>\
      <div class="upload-hidden"><input type="hidden" name="'+_this.data('name')+'"/></div>\
    </div>');


    $('.upload-file', _this).dropzone({
      url: "{Yii::$app->urlManager->createUrl('upload')}",
      sending:  function(e, data) {
        $('.upload-hidden input[type="hidden"]', _this).val(e.name);
        $('.upload-remove', _this).on('click', function(){
          $('.dz-details', _this).remove()
        })
      },
      dataType: 'json',
      previewTemplate: '<div class="dz-details"><img data-dz-thumbnail /><div class="upload-remove"></div></div>',
      drop: function(){
        $('.upload-file', _this).html('')
      }
    });

  })
};   
})
</script>
{$accountFeatures = DEPARTMENTS_ID}
<div class="account_wrapper after{if $accountFeatures  > 0}{else} full_account{/if}">
{if $accountFeatures  > 0}
    <div class="account_left">
        <div class="sub_acc_title"><i class="icon-ok-sign"></i>{$smarty.const.TEXT_ACCOUNT_SUBSCRIPTION}</div>
            <div class="ac_box">                
                <div class="ac_box_price">
                    <div class="ac_box_title">Ecommerce<span>supermarket</span></div>
                    <div class="acbp_01">&pound;18<span>per month</span></div>                 
                </div>
                <div class="ac_box_ac">
                    <span>If people don't notice</span>
                    <span>Sed ut perspiciatis</span>
                    <span>Nemo enim ipsam voluptatem</span>
                    <span>Ut enim ad minima veniam</span>
                    <span>Quis autem</span>
                </div>
                <div class="ac_box_but">
                    <a href="#" class="change_sub">Change subscription</a>
                </div>
                <div class="ac_box_link">
                    <a href="#" class="popup-edit-acc">Subscription history</a>
                </div>
            </div>
    </div>
{/if}
    <div class="account_right  {if $accountFeatures  > 0}account_right_dep{/if}">
        <div class="sub_acc_title"><i class="icon-user"></i>{$smarty.const.TEXT_MAIN_DETAILS}
				<div class="admin_lang after">
				<span class="title_lang">{$smarty.const.TEXT_LANGUAGES}</span>
				{$languages = \common\helpers\Language::get_languages()}
					{foreach $languages as $lKey => $lItem}
            <a href="{Yii::$app->urlManager->createUrl(['adminaccount?language='])}{$lItem['code']}">{$lItem['image_svg']}</a>
          {/foreach}
				</div>
				</div>
        <div id="adminaccount_info">     
            <div class="admin_pad" id="adminaccount_info_data"></div>
        </div>
    </div>
</div>
<!--===Admin account info ===-->

<!--===Admin account info ===-->

<!--===Password change form ===-->
<div class="row" id="adminaccount_management">
    <div class="col-md-12">
        <div class="widget box">
            <div class="widget-header">
                <h4><i class="icon-reorder"></i>{$smarty.const.TEXT_PERSONAL_DATA_CHANGE}</h4>
                <div class="toolbar no-padding">
                    <div class="btn-group">
                        <span id="passwordchange_form_collapse" class="btn btn-xs widget-collapse">
                            <i class="icon-angle-down"></i>
                        </span>
                    </div>
                </div>
            </div>
            <div class="widget-content fields_style" id="adminaccount_management_data">
                
            </div>
        </div>
    </div>
</div>
<!--===Password change form ===-->