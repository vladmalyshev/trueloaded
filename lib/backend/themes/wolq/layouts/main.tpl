{use class="yii\helpers\Html"}
{use class="backend\components\TopLeftMenu"}
{use class="backend\components\TopRightMenu"}
{use class="backend\components\Navigation"}
{use class="backend\components\Breadcrumbs"}
{$this->beginPage()}<!DOCTYPE html PUBLIC "-//W3C//DTD 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=1000" />
        {Html::csrfMetaTags()}
	<title>{$this->title}</title>

	<!--=== CSS ===-->

	<!-- Bootstrap -->
	<link href="{$app->request->baseUrl}/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
	<link href="{$app->request->baseUrl}/css/jquery.filedrop.css" rel="stylesheet" type="text/css" />
	<link href="{$app->request->baseUrl}/css/filedrop.css" rel="stylesheet" type="text/css" />
	
	

	<!-- jQuery UI -->
	<!--<link href="{$app->request->baseUrl}/plugins/jquery-ui/jquery-ui-1.10.2.custom.css" rel="stylesheet" type="text/css" />-->
	<!--[if lt IE 9]>
		<link rel="stylesheet" type="text/css" href="{$app->request->baseUrl}/plugins/jquery-ui/jquery.ui.1.10.2.ie.css"/>
	<![endif]-->
        <link href="{$app->request->baseUrl}/plugins/jquery-ui/jquery.multiselect.css" rel="stylesheet" type="text/css" />

	<!-- Theme -->
	<link href="{$app->view->theme->baseUrl}/css/base.css" rel="stylesheet" type="text/css" />
	<link href="{$app->view->theme->baseUrl}/css/main.css" rel="stylesheet" type="text/css" />
	<link href="{$app->view->theme->baseUrl}/css/style.css" rel="stylesheet" type="text/css" />
	<link href="{$app->view->theme->baseUrl}/css/plugins.css" rel="stylesheet" type="text/css" />
	<link href="{$app->view->theme->baseUrl}/css/icons.css" rel="stylesheet" type="text/css" />

	<link href="{$app->view->theme->baseUrl}/css/menus.css" rel="stylesheet" type="text/css" />

	<link rel="stylesheet" href="{$app->view->theme->baseUrl}/css/fontawesome/font-awesome.min.css">
	<!--[if IE 7]>
		<link rel="stylesheet" href="{$app->view->theme->baseUrl}/css/fontawesome/font-awesome-ie7.min.css">
	<![endif]-->

	<!--[if IE 8]>
		<link href="{$app->view->theme->baseUrl}/css/ie8.css" rel="stylesheet" type="text/css" />
	<![endif]-->
	<link href='//fonts.googleapis.com/css?family=Open+Sans:400,600,700' rel='stylesheet' type='text/css'>

	<!--=== JavaScript ===-->

  <script type="text/javascript" src="{Yii::$aliases['@web']}/index/load-languages-js"></script>

	<script type="text/javascript" src="{$app->view->theme->baseUrl}/js/libs/jquery-1.10.2.min.js"></script>
	<script type="text/javascript" src="{$app->request->baseUrl}/plugins/jquery-ui/jquery-ui-1.10.2.custom.min.js"></script>
	<script type="text/javascript" src="{$app->request->baseUrl}/plugins/jquery-ui/jquery.filedrop.js"></script>
	<script type="text/javascript" src="{$app->request->baseUrl}/plugins/jquery-ui/jquery.multiselect.min.js"></script>
	<script type="text/javascript" src="{$app->request->baseUrl}/plugins/jquery-ui/jquery.jshint.js"></script>

	<script type="text/javascript" src="{$app->request->baseUrl}/js/bootstrap.min.js"></script>
	<script type="text/javascript" src="{$app->view->theme->baseUrl}/js/libs/lodash.compat.min.js"></script>
	<script type="text/javascript" src="{$app->request->baseUrl}/js/ckeditor/ckeditor.js"></script>

	<script type="text/javascript" src="{$app->view->theme->baseUrl}/js/libs/dropzone.js"></script>
	<script type="text/javascript" src="{$app->view->theme->baseUrl}/js/main.js"></script>
        <script type="text/javascript" src="{$app->view->theme->baseUrl}/js/jquery.rating.pack.js"></script>
        <script type="text/javascript" src="{$app->view->theme->baseUrl}/js/jquery.jcarousel.min.js"></script>

	<!-- HTML5 shim, for IE6-8 support of HTML5 elements -->
	<!--[if lt IE 9]>
		<script src="{$app->view->theme->baseUrl}/js/libs/html5shiv.js"></script>
	<![endif]-->

	<!-- Smartphone Touch Events -->
	<script type="text/javascript" src="{$app->request->baseUrl}/plugins/touchpunch/jquery.ui.touch-punch.min.js"></script>
	<script type="text/javascript" src="{$app->request->baseUrl}/plugins/event.swipe/jquery.event.move.js"></script>
	<script type="text/javascript" src="{$app->request->baseUrl}/plugins/event.swipe/jquery.event.swipe.js"></script>

	<!-- General -->
	<script type="text/javascript" src="{$app->view->theme->baseUrl}/js/libs/breakpoints.js"></script>
	<script type="text/javascript" src="{$app->request->baseUrl}/plugins/respond/respond.min.js"></script> <!-- Polyfill for min/max-width CSS3 Media Queries (only for IE8) -->
	<script type="text/javascript" src="{$app->request->baseUrl}/plugins/cookie/jquery.cookie.min.js"></script>
	<script type="text/javascript" src="{$app->request->baseUrl}/plugins/slimscroll/jquery.slimscroll.min.js"></script>
	<script type="text/javascript" src="{$app->request->baseUrl}/plugins/slimscroll/jquery.slimscroll.horizontal.min.js"></script>

	<!-- Page specific plugins -->
	<!-- Charts -->
	<!--[if lt IE 9]>
		<script type="text/javascript" src="{$app->request->baseUrl}/plugins/flot/excanvas.min.js"></script>
	<![endif]-->
	<script type="text/javascript" src="{$app->request->baseUrl}/plugins/sparkline/jquery.sparkline.min.js"></script>
	<script type="text/javascript" src="{$app->request->baseUrl}/plugins/flot/jquery.flot.min.js"></script>
	<script type="text/javascript" src="{$app->request->baseUrl}/plugins/flot/jquery.flot.tooltip.min.js"></script>
	<script type="text/javascript" src="{$app->request->baseUrl}/plugins/flot/jquery.flot.resize.min.js"></script>
	<script type="text/javascript" src="{$app->request->baseUrl}/plugins/flot/jquery.flot.time.min.js"></script>
	<script type="text/javascript" src="{$app->request->baseUrl}/plugins/flot/jquery.flot.growraf.min.js"></script>
	<!--<script type="text/javascript" src="{$app->request->baseUrl}/plugins/easy-pie-chart/jquery.easy-pie-chart.min.js"></script>-->
  <script type="text/javascript" src="{$app->request->baseUrl}/plugins/chart-js-master/Chart.js"></script>
  
	<script type="text/javascript" src="{$app->request->baseUrl}/plugins/daterangepicker/moment.min.js"></script>
	<script type="text/javascript" src="{$app->request->baseUrl}/plugins/daterangepicker/daterangepicker.js"></script>
	<script type="text/javascript" src="{$app->request->baseUrl}/plugins/blockui/jquery.blockUI.min.js"></script>

	<script type="text/javascript" src="{$app->request->baseUrl}/plugins/fullcalendar/fullcalendar.min.js"></script>

	<!-- Noty -->
	<script type="text/javascript" src="{$app->request->baseUrl}/plugins/noty/jquery.noty.js"></script>
	<script type="text/javascript" src="{$app->request->baseUrl}/plugins/noty/layouts/top.js"></script>
	<script type="text/javascript" src="{$app->request->baseUrl}/plugins/noty/themes/default.js"></script>

	<!-- Forms -->
	<script type="text/javascript" src="{$app->request->baseUrl}/plugins/uniform/jquery.uniform.min.js"></script>
	<script type="text/javascript" src="{$app->request->baseUrl}/plugins/select2/select2.min.js"></script>

	<!-- App -->
	<script type="text/javascript" src="{$app->view->theme->baseUrl}/js/jquery.cookie.js"></script>
        <script type="text/javascript" src="{$app->view->theme->baseUrl}/js/bootstrap-switch.js"></script>
	<script type="text/javascript" src="{$app->view->theme->baseUrl}/js/app.js"></script>
	<script type="text/javascript" src="{$app->view->theme->baseUrl}/js/plugins.js"></script>
	<script type="text/javascript" src="{$app->view->theme->baseUrl}/js/plugins.form-components.js"></script>
        <script type="text/javascript" src="{$app->view->theme->baseUrl}/js/jquery.inrow.js"></script>

        <!-- DataTables -->
	<script type="text/javascript" src="{$app->request->baseUrl}/plugins/datatables/jquery.dataTables.1.10.js"></script>
	<script type="text/javascript" src="{$app->request->baseUrl}/plugins/datatables/tabletools/TableTools.min.js"></script> <!-- optional -->
	<script type="text/javascript" src="{$app->request->baseUrl}/plugins/datatables/colvis/ColVis.min.js"></script> <!-- optional -->
	<script type="text/javascript" src="{$app->request->baseUrl}/plugins/datatables/DT_bootstrap.js"></script>
	<script type="text/javascript" src="{$app->request->baseUrl}/plugins/datatables/responsive/datatables.responsive.js"></script> <!-- optional -->

        <!-- Nestable List -->
	<script type="text/javascript" src="{$app->request->baseUrl}/plugins/nestable/jquery.nestable.min.js"></script>
        
        <!-- Bootbox -->
	<script type="text/javascript" src="{$app->request->baseUrl}/plugins/bootbox/bootbox.js"></script>
        
	<script type="text/javascript" src="{$app->view->theme->baseUrl}/js/jquery.edit-products.js"></script>        
  <script type="text/javascript" src="{$app->request->baseUrl}/includes/general.js"></script>

	<link href="{$app->view->theme->baseUrl}/css/bootstrap-colorpicker.min.css" rel="stylesheet" type="text/css" />
	<script type="text/javascript" src="{$app->view->theme->baseUrl}/js/bootstrap-colorpicker.min.js"></script>

	<link href="{$app->request->baseUrl}/plugins/jQuery.ptTimeSelect-0.8/jquery.ptTimeSelect.css" rel="stylesheet" type="text/css" />
	<script type="text/javascript" src="{$app->request->baseUrl}/plugins/jQuery.ptTimeSelect-0.8/jquery.ptTimeSelect.js"></script>

{$this->head()}
	<script>
	$(document).ready(function(){
		"use strict";

		App.init(); // Init layout and core plugins
		Plugins.init(); // Init all plugins
		FormComponents.init(); // Init all form-specific plugins
	});
	</script>
        <link href="{$app->view->theme->baseUrl}/css/plugins/bootstrap-switch.css" rel="stylesheet">        
        <link href="{$app->view->theme->baseUrl}/css/responsive.css" rel="stylesheet" type="text/css" />
  <base href="{HTTP_SERVER}{$app->request->baseUrl}/">
</head>

<body class="context-{$this->context->id} theme-dark">
{$this->beginBody()}
	<!-- Header -->
	<header class="header navbar navbar-fixed-top" role="banner">
		<!-- Top Navigation Bar -->
		<div class="container">

			<!-- Only visible on smartphones, menu toggle -->
			<ul class="nav navbar-nav">
				<li class="nav-toggle"><a href="javascript:void(0);" title=""><i class="icon-reorder"></i></a></li>
			</ul>

			<!-- Logo -->
			<a class="navbar-brand" href="{$app->urlManager->createUrl("index")}">
				<img src="{$app->view->theme->baseUrl}/img/logo_wolq.png" width="210" height="45" alt="logo" />
			</a>
			<!-- /logo -->

			<!-- Sidebar Toggler -->
			<a href="#" class="toggle-sidebar bs-tooltip" data-placement="bottom" data-original-title="Toggle navigation">
				<i class="icon-reorder"></i>
			</a>
			<!-- /Sidebar Toggler -->

			<!-- Top Left Menu -->
                     {*   {TopLeftMenu::widget()}*}
			<!-- /Top Left Menu -->


			<!-- Top Right Menu -->
			{TopRightMenu::widget()}
			<!-- /Top Right Menu -->
			<ul class="header_menu_right">
				<li><a href="{tep_catalog_href_link()}" target="_blank">{$smarty.const.TEXT_VIEW_SHOP}</a></li>
				<li><a href="http://holbieurope.com/service/" target="_blank">{$smarty.const.TEXT_SUPPORT}</a></li>
				<li><a href="http://holbieurope.com/contact-holbi/" target="_blank">Contact Wolq</a></li>
			</ul>
		</div>
		<!-- /top navigation bar -->

		<!--=== Project Switcher ===-->
		<!--<div id="project-switcher" class="container project-switcher">
			<div id="scrollbar">
				<div class="handle"></div>
			</div>

			<div id="frame">
				<ul class="project-list">
					<li>
						<a href="javascript:void(0);">
							<span class="image"><i class="icon-desktop"></i></span>
							<span class="title">Lorem ipsum dolor</span>
						</a>
					</li>
					<li>
						<a href="javascript:void(0);">
							<span class="image"><i class="icon-compass"></i></span>
							<span class="title">Dolor sit invidunt</span>
						</a>
					</li>
					<li class="current">
						<a href="javascript:void(0);">
							<span class="image"><i class="icon-male"></i></span>
							<span class="title">Consetetur sadipscing elitr</span>
						</a>
					</li>
					<li>
						<a href="javascript:void(0);">
							<span class="image"><i class="icon-thumbs-up"></i></span>
							<span class="title">Sed diam nonumy</span>
						</a>
					</li>
					<li>
						<a href="javascript:void(0);">
							<span class="image"><i class="icon-female"></i></span>
							<span class="title">At vero eos et</span>
						</a>
					</li>
					<li>
						<a href="javascript:void(0);">
							<span class="image"><i class="icon-beaker"></i></span>
							<span class="title">Sed diam voluptua</span>
						</a>
					</li>
					<li>
						<a href="javascript:void(0);">
							<span class="image"><i class="icon-desktop"></i></span>
							<span class="title">Lorem ipsum dolor</span>
						</a>
					</li>
					<li>
						<a href="javascript:void(0);">
							<span class="image"><i class="icon-compass"></i></span>
							<span class="title">Dolor sit invidunt</span>
						</a>
					</li>
					<li>
						<a href="javascript:void(0);">
							<span class="image"><i class="icon-male"></i></span>
							<span class="title">Consetetur sadipscing elitr</span>
						</a>
					</li>
					<li>
						<a href="javascript:void(0);">
							<span class="image"><i class="icon-thumbs-up"></i></span>
							<span class="title">Sed diam nonumy</span>
						</a>
					</li>
					<li>
						<a href="javascript:void(0);">
							<span class="image"><i class="icon-female"></i></span>
							<span class="title">At vero eos et</span>
						</a>
					</li>
					<li>
						<a href="javascript:void(0);">
							<span class="image"><i class="icon-beaker"></i></span>
							<span class="title">Sed diam voluptua</span>
						</a>
					</li>
				</ul>
			</div> 
		</div>!--> <!-- /#project-switcher -->
	</header> <!-- /.header -->

	<div id="container">
		<div id="sidebar" class="sidebar-fixed">
			<div id="sidebar-content">

				<!-- Search Input -->
				<div class="sidebar-search">
					<div class="input-box">
						<button type="submit" class="submit">
							<i class="icon-search"></i>
						</button>
						<span>
							<input type="text" placeholder="Search..." id="menusearch" autocomplete="off">
						</span>
					</div>
				</div>

				<!-- Search Results -->
				<div class="sidebar-search-results">

					<i class="icon-remove close"></i>
					<!-- Documents -->
					<div class="title">
						Documents
					</div>
					<ul class="notifications">
						<li>
							<a href="javascript:void(0);">
								<div class="col-left">
									<span class="label label-info"><i class="icon-file-text"></i></span>
								</div>
								<div class="col-right with-margin">
									<span class="message"><strong>John Doe</strong> received $1.527,32</span>
									<span class="time">finances.xls</span>
								</div>
							</a>
						</li>
						<li>
							<a href="javascript:void(0);">
								<div class="col-left">
									<span class="label label-success"><i class="icon-file-text"></i></span>
								</div>
								<div class="col-right with-margin">
									<span class="message">My name is <strong>John Doe</strong> ...</span>
									<span class="time">briefing.docx</span>
								</div>
							</a>
						</li>
					</ul>
					<!-- /Documents -->
					<!-- Persons -->
					<div class="title">
						Persons
					</div>
					<ul class="notifications">
						<li>
							<a href="javascript:void(0);">
								<div class="col-left">
									<span class="label label-danger"><i class="icon-female"></i></span>
								</div>
								<div class="col-right with-margin">
									<span class="message">Jane <strong>Doe</strong></span>
									<span class="time">21 years old</span>
								</div>
							</a>
						</li>
					</ul>
				</div> <!-- /.sidebar-search-results -->

				<!--=== Navigation ===-->
				{Navigation::widget()}
				<!-- /Navigation -->
                                <!--=== Notifications ===-->
                                {*widget name="application.components.Notifications"*}
                                <!-- /Notifications -->
				

				<div class="sidebar-widget align-center">
					<div class="btn-group" data-toggle="buttons" id="theme-switcher">
						<label class="btn">
							<input type="radio" name="theme-switcher" data-theme="bright"><i class="icon-sun"></i> {$smarty.const.TEXT_BRIGHT}
						</label>
						<label class="btn active">
							<input type="radio" name="theme-switcher" data-theme="dark"><i class="icon-moon"></i> {$smarty.const.TEXT_DARK}
						</label>
					</div>
				</div>

			</div>
			<div id="divider" class="resizeable"></div>
		</div>
		<!-- /Sidebar -->
		{$dayOfWeek = [$smarty.const.TEXT_SUNDAY, $smarty.const.TEXT_MONDAY, $smarty.const.TEXT_TUESDAY, $smarty.const.TEXT_WEDNESDAY, $smarty.const.TEXT_THURSDAY, $smarty.const.TEXT_FRIDAY, $smarty.const.TEXT_SATURDAY]}
		{$monthNames = [$smarty.const.TEXT_JAN, $smarty.const.TEXT_FAB,	$smarty.const.TEXT_MAR,	$smarty.const.TEXT_APR,	$smarty.const.TEXT_MAY,	$smarty.const.TEXT_JUN,	$smarty.const.TEXT_JUL,	$smarty.const.TEXT_AUG,	$smarty.const.TEXT_SEP,	$smarty.const.TEXT_OCT,	$smarty.const.TEXT_NOV,	$smarty.const.TEXT_DEC]}
		<div id="content">
                    <div class="container">
											<div class="top_header after">

                        <div class="united-date" data-time="{(date("G")*60 + date("i"))}">
                          <div class="clock_right"><i class="icon-clock-o"></i><span id="clock"></span></div>
                          <div class="date_right"><i class="icon-calendar-o"></i><span id="date"></span></div>
                        </div>
                        <div class="current-date" style="display: none;">
                          <div class="text">{$smarty.const.TEXT_CURRENT_TIME}</div>
                          <div class="clock_right"><i class="icon-clock-o"></i><span id="clock-1"></span></div>
                          <div class="date_right"><i class="icon-calendar-o"></i><span id="date-1"></span></div>
                        </div>
                        <div class="server-date" style="display: none;">
                          <div class="text">{$smarty.const.TEXT_SERVER_TIME}</div>
                          <div class="clock_right"><i class="icon-clock-o"></i><span id="clock-2">{date("G:i")}</span></div>
                          <div class="date_right"><i class="icon-calendar-o"></i><span id="date-2">{$dayOfWeek[date("w")]}<br>{date("j")} {$monthNames[date("n")-1]}, {date("Y")}</span></div>
                        </div>


												<!-- Breadcrumbs line -->
												{Breadcrumbs::widget()}
												<!-- /Breadcrumbs line -->
											</div>

                        <span id="messageStack">
                        {if \Yii::$app->controller->view->errorMessage != '' }
                            <div class="popup-box-wrap pop-mess">
                                <div class="around-pop-up"></div>
                                <div class="popup-box">
                                    <div class="pop-up-close pop-up-close-alert"></div>
                                    <div class="pop-up-content">
                                        <div class="popup-heading">{$smarty.const.TEXT_NOTIFIC}</div>
                                        <div class="popup-content pop-mess-cont pop-mess-cont-{\Yii::$app->controller->view->errorMessageType}">
                                            {\Yii::$app->controller->view->errorMessage}
                                        </div>  
                                    </div>    
                                        <div class="noti-btn">
                                            <div></div>
                                            <div><span class="btn btn-primary">{$smarty.const.TEXT_BTN_OK}</span></div>
                                        </div>
                                </div>  
                                        <script>
                                $('body').scrollTop(0);
                                $('.pop-mess .pop-up-close-alert, .noti-btn .btn').click(function(){
                                    $(this).parents('.pop-mess').remove();
                                });
                            </script>
                            </div>
                            
                        {/if}
                        </span>
											<div class="content-container">
                        {$content}
											</div>
                        <div class="footer">
                            <ul>
                                <li>{$smarty.const.TEXT_COPYRIGHT_WOLQ} {$smarty.now|date_format:"%Y"} <a target="_blank" href="http://www.holbi.co.uk">{$smarty.const.TEXT_COPYRIGHT_HOLBI_WOLQ}</a></li>
                                <li>{$smarty.const.TEXT_FOOTER_BOTTOM}</li>
                                <li>{$smarty.const.TEXT_FOOTER_COPYRIGHT_WOLQ} {$smarty.now|date_format:"%Y"} {$smarty.const.TEXT_COPYRIGHT_HOLBI_WOLQ}</li>
                            </ul>
                        </div>
                    </div>
			<!-- /.container -->
		</div>
	</div>
<script type="text/javascript">
function updateDate ( )
{
	var currentDate = new Date ( );
	var monthNames = ["{$smarty.const.TEXT_JAN}", "{$smarty.const.TEXT_FAB}", "{$smarty.const.TEXT_MAR}", "{$smarty.const.TEXT_APR}", "{$smarty.const.TEXT_MAY}", "{$smarty.const.TEXT_JUN}",
		"{$smarty.const.TEXT_JUL}", "{$smarty.const.TEXT_AUG}", "{$smarty.const.TEXT_SEP}", "{$smarty.const.TEXT_OCT}", "{$smarty.const.TEXT_NOV}", "{$smarty.const.TEXT_DEC}"
	];
	var dayOfWeek = ["{$smarty.const.TEXT_SUNDAY}", "{$smarty.const.TEXT_MONDAY}", "{$smarty.const.TEXT_TUESDAY}", "{$smarty.const.TEXT_WEDNESDAY}", "{$smarty.const.TEXT_THURSDAY}", "{$smarty.const.TEXT_FRIDAY}", "{$smarty.const.TEXT_SATURDAY}"];
	var currentDay = dayOfWeek[currentDate.getDay()];
	var currentDateW = currentDate.getDate();
	var numberMonth = currentDate.getMonth();
	var currentMonth = monthNames[numberMonth];
	var currentYear = currentDate.getFullYear();

	// Compose the string for display
	var currentDateString = currentDay + "<br>" + currentDateW + " " + currentMonth + ", " + currentYear;
	$("#date").html(currentDateString);
}
$(document).ready(function()
{
setInterval('updateDate()', 1000);
})
</script>
{$this->endBody()}
</body>
</html>
{$this->endPage()}