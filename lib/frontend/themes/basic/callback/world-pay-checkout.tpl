    {use class="\yii\helpers\Html"}
    
    <h1>{$smarty.const.STORE_NAME}</h1>
    
    <p>{$smarty.const.MODULE_PAYMENT_RBSWORLDPAY_HOSTED_TEXT_SUCCESSFUL_TRANSACTION}</p>
    
    {Html::beginForm($form_url, 'post', ['target' => '_top'])}
        <p><input type="submit" value="{sprintf($smarty.const.MODULE_PAYMENT_RBSWORLDPAY_HOSTED_TEXT_CONTINUE_BUTTON, addslashes(STORE_NAME))}" /></p>
   {Html::endForm()}
   
    <p>&nbsp;</p>

    <WPDISPLAY ITEM=banner>
