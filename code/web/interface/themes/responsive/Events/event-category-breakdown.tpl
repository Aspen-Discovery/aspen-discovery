{if !empty($item.attendeeCategoryBreakdown)}
	<table style="margin:0; border-collapse:collapse;">
		{assign var=catTotal value=0}
		{foreach from=$item.attendeeCategoryBreakdown item=cat}
			<tr>
				<td style="padding:0 8px 0 0;">{$cat.name|escape}:</td>
				<td style="padding:0; text-align:right;" colspan="2">{$cat.count}</td>
			</tr>
			{assign var=catTotal value=$catTotal+$cat.count}
		{/foreach}
		<tr>
			<td style="padding:2px 8px 0 0; border-top:1px solid #ddd;"><strong>{translate text="Total" isAdminFacing=true}:</strong></td>
			<td style="padding:2px 0 0 0; text-align:right; border-top:1px solid #ddd;"><strong>{$catTotal}</strong></td>
			<td style="padding:2px 0 0 0; border-top:1px solid #ddd;">{if $item.numberOfSeats}<strong> / {$item.numberOfSeats}</strong>{/if}</td>
		</tr>
	</table>
{else}
	{$item.registrationCount}
	{if $item.numberOfSeats}
		/ {$item.numberOfSeats}
	{/if}
{/if}