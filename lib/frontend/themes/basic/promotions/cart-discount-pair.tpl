{use class="\yii\helpers\Html"}
<div>
	{if $icon}<div class="promo-icon">{$icon}</div>{/if}
	{if $label}<div class="promo-label">{$label}</div>{/if}
	<div class="product-master">
		{Html::img($master->image, ['width'=> 50, 'height'=>50])}
		{if $master->special_price}
			<strike>{$currencies->display_price($master->price, $master->tax_rate)}</strike>
			<span>{$currencies->display_price($master->special_price, $master->tax_rate)}</span>
		{else}
			<span>{$currencies->display_price($master->price, $master->tax_rate)}</span>
		{/if}
		<label>
		{if $is_master}{$master->name}{else}{Html::a($master->name, $master->href)}{/if} {if $master->quantity > 1}({$smarty.const.MINIMAL_QUANTITY}:{$master->quantity}){/if}
		
		</label>
	</div>
	<span>+</span>
	<div class="product-slave">
		{Html::img($slave->image, ['width'=> 50, 'height'=>50])}
		{if $slave->special_price}
			<strike>{$currencies->display_price($slave->price, $slave->tax_rate)}</strike>
			<span>{$currencies->display_price($slave->special_price, $slave->tax_rate)}</span>
		{/if}
		<label>
		{if !$is_master}{$slave->name}{else}{Html::a($slave->name, $slave->href)}{/if} {if $slave->quantity > 1}({$smarty.const.MINIMAL_QUANTITY}:{$slave->quantity}){/if}
		</label>
	</div>
	{if $advantage->diff}
	<span>=</span>
	<div class="price-difference">
		<div>
			<label>{$smarty.const.TEXT_TOGETHER}:<label>
			<label>{$currencies->display_price($advantage->summNew, 0)}</label>
		</div>
		<div>
			<label>{$smarty.const.TEXT_SAVE}:<label>
			<label>{$advantage->percDown}%</label>
		</div>
		<div>
			<label>{$currencies->display_price($advantage->summOld, 0)}</label>
		</div>
	</div>
	{/if}
</div>
