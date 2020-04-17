{use class="\yii\helpers\Html"}
<h1>Affliate programme</h1>
{Html::beginForm('affiliate/login', 'post', ['name' => 'login'])}
<table width="100%" cellspacing="0" cellpadding="5" border="0">
    <tbody><tr>
            <td class="main grow" width="50%" valign="top"><b>New Affiliate</b></td>
            <td class="main grow" width="50%" valign="top"><b>Returning Affiliate</b></td>
        </tr>
        <tr>
            <td width="50%" valign="top" height="100%"><table class="infoBox" width="100%" height="100%" cellspacing="0" cellpadding="1" border="0">
                    <tbody><tr>
                            <td><table class="infoBoxContents" width="100%" height="100%" cellspacing="0" cellpadding="2" border="0">
                                    <tbody><tr>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td class="main grow" valign="top">I am a new affiliate.<br><br>By creating an affiliate account at Active Mobility Centre Ltd you will be able to earn valuable extra revenue by referring your website's visitors to us.</td>
                                        </tr>
                                        <tr>
                                            <td class="smallText grow" colspan="2"><a href="/terms-conditions">Our Affiliate Terms &amp; Conditions</a></td>
                                        </tr>
                                        <tr>
                                            <td></td>
                                        </tr>
                                    </tbody></table></td>
                        </tr>
                    </tbody></table></td>
            <td width="50%" valign="top" height="100%"><table class="infoBox" width="100%" height="100%" cellspacing="0" cellpadding="1" border="0">
                    <tbody><tr>
                            <td><table class="infoBoxContents" width="100%" height="100%" cellspacing="0" cellpadding="2" border="0">
                                    <tbody><tr>
                                            <td colspan="2"></td>
                                        </tr>
                                        <tr>
                                            <td class="main grow" colspan="2">I am a returning affiliate.</td>
                                        </tr>
                                        <tr>
                                            <td colspan="2"></td>
                                        </tr>
                                        <tr>
                                            <td class="main grow"><b>Affiliate Email:</b></td>
                                            <td class="main grow">
                                                {Html::input('text', 'affiliate_username', '', ['data-required' => "{$smarty.const.EMAIL_REQUIRED}", 'data-pattern' => "email", 'class' => 'form-control'])}
                                        </tr>
                                        <tr>
                                            <td class="main grow"><b>Password:</b></td>
                                            <td class="main grow">
                                                {Html::input('password', 'affiliate_password', '', ['class' => "password", 'autocomplete' => "off", 'data-pattern' => "{$re1}{$smarty.const.ENTRY_PASSWORD_MIN_LENGTH}{$re2}", 'data-required' => "{sprintf($smarty.const.ENTRY_PASSWORD_ERROR, $smarty.const.ENTRY_PASSWORD_MIN_LENGTH)}"])}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="2"></td>
                                        </tr>
                                        <tr>
                                            <td class="smallText grow" colspan="2"><a href="{Yii::$app->urlManager->createUrl('affiliate/password-forgotten')}">Password forgotten? Click here.</a></td>
                                        </tr>
                                        <tr>
                                            <td colspan="2"></td>
                                        </tr>
                                    </tbody></table></td>
                        </tr>
                    </tbody></table></td>
        </tr>
        <tr>
            <td></td>
        </tr>
        <tr>
            <td>
                <a class="btn-1" href="{Yii::$app->urlManager->createUrl('affiliate/signup')}">Continue</a>
            </td>
            <td>
                <button type="submit" class="btn-2">Sign In</button>
            </td>
        </tr>
    </tbody></table>
{Html::endForm()}