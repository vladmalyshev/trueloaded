{use class="Yii"}
{use class="frontend\design\Info"}
{if $stock_indicator}
<div class="stock js-stock">
  <span class="{$stock_indicator.text_stock_code}"
        {if $stock_indicator.backorderFirst}
          title="{sprintf($smarty.const.TEXT_EXPECTED_ON , $stock_indicator.backorderFirst['qty'], \common\helpers\Date::formatDate($stock_indicator.backorderFirst['date']) )|escape:'html'}"
        {/if}><span class="{$stock_indicator.stock_code}-icon">&nbsp;</span>{$stock_indicator.stock_indicator_text}</span>
</div>
{/if}