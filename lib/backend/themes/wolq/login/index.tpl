<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta name="viewport" content="width=1000" />
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

	<script type="text/javascript" src="{$app->view->theme->baseUrl}/js/libs/jquery-1.10.2.min.js"></script>

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

<body class="login">
	<div class="header after">
			<div class="phone">
				<span class="ico main_phone_namber">00-31-492-210100</span>
												</div>
		<ul class="header_right">
										<li><a href="http://holbieurope.com/contact-holbi/" target="_blank">Contact Wolq</a></li>
										<li><a href="http://holbieurope.com/service/" target="_blank">Support</a></li>
		</ul>
	</div>
	<!-- Logo -->
	
	<!-- /Logo -->

	<!-- Login Box -->
	<div class="box">
						<div class="logo">
		<img src="{$app->view->theme->baseUrl}/img/logo-wolq.png" alt="logo" />
	</div>
		<div class="content">
			<!-- Login Formular -->
			<form class="form-vertical login-form" action="{$app->urlManager->createUrl("login")}?action=process" method="post">

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
						<input type="text" name="email_address" class="form-control" placeholder="{$smarty.const.TEXT_EMAIL_ADDRESS}" autofocus="autofocus" data-rule-required="true" data-msg-required="{$smarty.const.TEXT_ENTER_YOUR_PASSWORD}" />
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

	<!-- Footer -->
	<div class="footer">
		<ul>
			<li>{$smarty.const.TEXT_COPYRIGHT_WOLQ} {$smarty.now|date_format:"%Y"} <a target="_blank" href="http://www.holbi.co.uk">{$smarty.const.TEXT_COPYRIGHT_HOLBI_WOLQ}</a></li>
			<li>{$smarty.const.TEXT_FOOTER_COPYRIGHT_WOLQ} {$smarty.now|date_format:"%Y"} {$smarty.const.TEXT_COPYRIGHT_HOLBI_WOLQ}</li>
		</ul>
	</div>
	<!-- /Footer -->
</body>
</html>
