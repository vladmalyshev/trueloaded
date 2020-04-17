<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title>Login | Trueloaded Admin</title>

	<!--=== CSS ===-->

	<!-- Bootstrap -->
	<link href="{$app->request->baseUrl}/css/bootstrap.min.css" rel="stylesheet" type="text/css" />

	<!-- Theme -->
	<link href="{$app->view->theme->baseUrl}/css/main.css" rel="stylesheet" type="text/css" />
	<link href="{$app->view->theme->baseUrl}/css/plugins.css" rel="stylesheet" type="text/css" />
	<link href="{$app->view->theme->baseUrl}/css/responsive.css" rel="stylesheet" type="text/css" />
	<link href="{$app->view->theme->baseUrl}/css/icons.css" rel="stylesheet" type="text/css" />

	<!-- Login -->
	<link href="{$app->view->theme->baseUrl}/css/login.css" rel="stylesheet" type="text/css" />

	<link rel="stylesheet" href="{$app->view->theme->baseUrl}/css/fontawesome/font-awesome.min.css">
	<!--[if IE 7]>
		<link rel="stylesheet" href="{$app->view->theme->baseUrl}/css/fontawesome/font-awesome-ie7.min.css">
	<![endif]-->

	<!--[if IE 8]>
		<link href="{$app->view->theme->baseUrl}/css/ie8.css" rel="stylesheet" type="text/css" />
	<![endif]-->
	<link href='https://fonts.googleapis.com/css?family=Open+Sans:400,600,700' rel='stylesheet' type='text/css'>

	<!--=== JavaScript ===-->

	<script type="text/javascript" src="{$app->view->theme->baseUrl}/js/libs/jquery-3.4.1.min.js"></script>

	<script type="text/javascript" src="{$app->request->baseUrl}/js/bootstrap.min.js"></script>
	<script type="text/javascript" src="{$app->view->theme->baseUrl}/js/libs/lodash.compat.min.js"></script>

	<!-- HTML5 shim, for IE6-8 support of HTML5 elements -->
	<!--[if lt IE 9]>
		<script src="{$app->view->theme->baseUrl}/js/libs/html5shiv.js"></script>
	<![endif]-->

	<!-- Beautiful Checkboxes -->
	<script type="text/javascript" src="{$app->request->baseUrl}/plugins/uniform/jquery.uniform.min.js"></script>

	<!-- Form Validation -->
	<script type="text/javascript" src="{$app->request->baseUrl}/plugins/validation/jquery.validate.min.js"></script>

	<!-- Slim Progress Bars -->
	<script type="text/javascript" src="{$app->request->baseUrl}/plugins/nprogress/nprogress.js"></script>

	<!-- App -->
	<script type="text/javascript" src="{$app->view->theme->baseUrl}/js/login.js"></script>
	<script>
	$(document).ready(function(){
		"use strict";

		Login.init(); // Init login JavaScript
	});
	</script>
</head>

<body class="">
<div class="login">
	<div class="content-login">
	<!-- Logo -->
	<div class="logo">
		{if ((defined('WL_ENABLED') && WL_ENABLED === true) && (defined('WL_COMPANY_LOGO') && WL_COMPANY_LOGO != ''))}
			<img src="{$app->view->theme->baseUrl}/img/{$smarty.const.WL_COMPANY_LOGO}" alt="{$smarty.const.WL_COMPANY_NAME}" />
		{else}
		    <img src="{$app->view->theme->baseUrl}/img/tl-logo.png" alt="logo" />
		{/if}
	</div>
	<!-- /Logo -->

	<!-- Login Box -->
	<div class="box">
		<div class="content">
			<!-- Login Formular -->
			<form class="form-vertical login-form" action="{$app->urlManager->createUrl("login")}?action=process" method="post">
				<!-- Title -->
				<h3 class="form-title">{$smarty.const.TEXT_SIGN_IN_ACCOUNT}</h3>

				<!-- Error Message -->
				<div class="alert fade in alert-danger" style="display: none;">
					<i class="icon-remove close" data-dismiss="alert"></i>
					{$smarty.const.TEXT_ENTER_PASSWORD}
				</div>

{if \Yii::$app->controller->errorMessage != '' }
				<div class="alert fade in alert-danger">
								<i class="icon-remove close" data-dismiss="alert"></i>
								{\Yii::$app->controller->errorMessage}
				</div>
{/if}

				<!-- Input Fields -->
				<div class="form-group">
					<!--<label for="username">E-Mail Address:</label>-->
					<div class="input-icon">
						<i class="icon-user"></i>
						<input type="text" name="email_address" class="form-control" placeholder="{$smarty.const.ENTRY_EMAIL_ADDRESS}" autofocus="autofocus" data-rule-required="true" data-msg-required="{$smarty.const.TEXT_ENTER_YOUR_PASSWORD}" />
					</div>
				</div>
				<div class="form-group">
					<!--<label for="password">Password:</label>-->
					<div class="input-icon">
						<i class="icon-lock"></i>
						<input type="password" name="password" class="form-control" placeholder="{$smarty.const.TEXT_PASSWORD}" data-rule-required="true" data-msg-required="{$smarty.const.TEXT_ENTER_PASSWORD}" />
					</div>
				</div>
				<!-- /Input Fields -->

				<!-- Form Actions -->
				<div class="form-actions">
					<button type="submit" class="submit btn btn-primary">
						{$smarty.const.TEXT_SIGN_IN} <i class="icon-angle-right"></i>
					</button>
				</div>
			</form>
			<!-- /Login Formular -->
		</div> <!-- /.content -->

		<!-- Forgot Password Form -->
		<div class="inner-box">
			<div class="content">
				<!-- Close Button -->
				<i class="icon-remove close hide-default"></i>

				<!-- Link as Toggle Button -->
				<a href="#" class="forgot-password-link">{$smarty.const.TEXT_FORGOT_PASSWORD}</a>

				<!-- Forgot Password Formular -->
				<form class="form-vertical forgot-password-form hide-default" action="{$app->urlManager->createUrl("password_forgotten")}?action=process" method="post">
					<!-- Input Fields -->
					<div class="form-group">
						<!--<label for="firstname">First name:</label>-->
						<div class="input-icon">
							<i class="icon-envelope"></i>
							<input type="text" name="firstname" class="form-control" placeholder="{$smarty.const.TEXT_ENTER_FIRSTNAME}" data-rule-required="true" data-msg-required="{$smarty.const.TEXT_ENTER_YOUR_FIRSTNAME}" />
						</div>
					</div>
					<div class="form-group">
						<!--<label for="email">Email:</label>-->
						<div class="input-icon">
							<i class="icon-envelope"></i>
							<input type="text" name="email_address" class="form-control" placeholder="{$smarty.const.TEXT_ENTER_EMAIL_ADDRESS}" data-rule-required="true" data-rule-email="true" data-msg-required="{$smarty.const.TEXT_ENTER_YOUR_EMAIL}" />
						</div>
					</div>
					<!-- /Input Fields -->

					<button type="submit" class="submit btn btn-default btn-block">
						{$smarty.const.TEXT_RESET_PASSWORD}
					</button>
				</form>
				<!-- /Forgot Password Formular -->

				<!-- Shows up if reset-button was clicked -->
				<div class="forgot-password-done hide-default">
					<i class="icon-ok success-icon"></i>
					<i class="icon-remove danger-icon"></i>
					<span class="forgot-password-success">{$smarty.const.TEXT_FORGOTTEN_SUCCESS}</span>
					<span class="forgot-password-fail">{$smarty.const.TEXT_FORGOTTEN_ERROR}</span>
				</div>
			</div> <!-- /.content -->
		</div>
		<!-- /Forgot Password Form -->
	</div>
	<!-- /Login Box -->
	</div>

	<!-- Footer -->
	<div class="footer-login">
		<ul class="links">
            {if (defined('WL_ENABLED') && WL_ENABLED === true)}
                {if ((defined('WL_CONTACT_URL') && WL_CONTACT_URL === true) &&
                (defined('WL_CONTACT_TEXT') && WL_CONTACT_TEXT != '') &&
                (defined('WL_CONTACT_WWW') && WL_CONTACT_WWW != ''))}
					<li><a href="{$smarty.const.WL_CONTACT_WWW}" target="_blank">{$smarty.const.WL_CONTACT_TEXT}</a></li>
                {/if}
            {else}
				<li><i class="icon-envelope"></i> <a href="http://www.holbi.co.uk/contact-us" target="_blank">{$smarty.const.TEXT_HEADER_CONTACT_US}</a></li>
            {/if}
            {if (defined('WL_ENABLED') && WL_ENABLED === true)}
                {if ((defined('WL_SERVICES_URL') && WL_SERVICES_URL === true) &&
                (defined('WL_SERVICES_TEXT') && WL_SERVICES_TEXT != '') &&
                (defined('WL_SERVICES_WWW') && WL_SERVICES_WWW != ''))}
					<li><a href="{$smarty.const.WL_SERVICES_WWW}" target="_blank">{$smarty.const.WL_SERVICES_TEXT}</a></li>
                {/if}
            {else}
				<li><i class="icon-comments"></i> <a href="http://www.holbi.co.uk/ecommerce-development" target="_blank">{$smarty.const.TEXT_ECOMMERCE_DEVELOPMENT}</a></li>
            {/if}
            {if (defined('WL_ENABLED') && WL_ENABLED === true)}
                {if ((defined('WL_SUPPORT_URL') && WL_SUPPORT_URL === true) &&
                (defined('WL_SUPPORT_TEXT') && WL_SUPPORT_TEXT != '') &&
                (defined('WL_SUPPORT_WWW') && WL_SUPPORT_WWW != ''))}
					<li><a href="{$smarty.const.WL_SUPPORT_WWW}" target="_blank">{$smarty.const.WL_SUPPORT_TEXT}</a></li>
                {/if}
            {else}
				<li><i class="icon-shopping-cart"></i> <a href="http://www.holbi.co.uk/ecommerce-support" target="_blank">{$smarty.const.TEXT_SUPPORT}</a></li>
            {/if}
		</ul>

	      {if ((defined('WL_ENABLED') && WL_ENABLED === true) && 
	           (defined('WL_COMPANY_NAME') && WL_COMPANY_NAME != ''))}

	        Copyright &copy; {$smarty.now|date_format:"%Y"} <a target="_blank" href="http://loadedcommerce.com">{$smarty.const.WL_COMPANY_NAME}</a>. All rights reserved.

	      {else}


			  <div class="copuright">
			  {$smarty.const.TEXT_COPYRIGHT} {$smarty.now|date_format:"%Y"} <a target="_blank" href="http://www.holbi.co.uk">{$smarty.const.TEXT_COPYRIGHT_HOLBI}</a>
			  {$smarty.const.TEXT_FOOTER_BOTTOM}<br>

			  {$smarty.const.TEXT_FOOTER_COPYRIGHT} {$smarty.now|date_format:"%Y"} {$smarty.const.TEXT_COPYRIGHT_HOLBI}
			  </div>
	      {/if}

	</div>
	<!-- /Footer -->
</div>
</body>
</html>
