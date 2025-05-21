{strip}
<div id="itemSummaryPopup_{$itemSummaryId|escapeCSS}_{$relatedManifestation->format|escapeCSS}" class="itemSummaryPopup">
	<table class="table table-striped table-condensed itemSummaryTable">
		<thead>
		<tr>
			<th>{translate text="Available Copies" isPublicFacing=true}</th>
			<th>{translate text="Location" isPublicFacing=true}</th>
			{if empty($isEContent)}
				<th>{translate text="Call #" isPublicFacing=true}</th>
			{elseif !empty($showEContentHoldCounts)}
				<th>{translate text="Holds" isPublicFacing=true}</th>
			{/if}
		</tr>
		</thead>
		<tbody>
		{assign var=numRowsShown value=0}
		{foreach from=$summary item="item"}
			<tr {if !empty($item.availableCopies)}class="available" {/if}>
				{if $item.onOrderCopies > 0}
					{if !empty($showOnOrderCounts)}
						<td>{translate text="%1% on order" 1=$item.onOrderCopies isPublicFacing=true}</td>
					{else}
						<td>{translate text="Copies on order" isPublicFacing=true}</td>
					{/if}
				{else}
					{if $item.availableCopies > 9999}
						<td>{translate text="Always Available" isPublicFacing=true}</td>
					{else}
						<td>{translate text="%1% of %2%" 1=$item.availableCopies 2=$item.totalCopies isPublicFacing=true}{if !empty($item.availableCopies)} <i class="fa fa-check"></i>{/if}</td>
					{/if}
				{/if}
				<td class="notranslate">{$item.shelfLocation}</td>

				{if empty($item.isEContent)}
					<td class="notranslate">
						{$item.callNumber}
					</td>
				{elseif !empty($showEContentHoldCounts)}
					<td class="notranslate">
						{if $item.availableCopies <= 9999}
							{$item.numHolds}
						{/if}
					</td>
				{/if}

			</tr>
		{/foreach}
		</tbody>
	</table>
</div>
{/strip}