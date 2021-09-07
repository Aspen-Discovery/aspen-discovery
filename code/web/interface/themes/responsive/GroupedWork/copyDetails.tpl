{strip}
<div id="itemSummaryPopup_{$itemSummaryId|escapeCSS}_{$relatedManifestation->format|escapeCSS}" class="itemSummaryPopup">
	<table class="table table-striped table-condensed itemSummaryTable">
		<thead>
		<tr>
			<th>{translate text="Available Copies" isPublicFacing=true}</th>
			<th>{translate text="Location" isPublicFacing=true}</th>
			<th>{translate text="Call #" isPublicFacing=true}</th>
		</tr>
		</thead>
		<tbody>
		{assign var=numRowsShown value=0}
		{foreach from=$summary item="item"}
			<tr {if $item.availableCopies}class="available" {/if}>
				{if $item.onOrderCopies > 0}
					{if $showOnOrderCounts}
						<td>{translate text="%1% on order" 1=$item.onOrderCopies isPublicFacing=true}</td>
					{else}
						<td>{translate text="Copies on order" isPublicFacing=true}</td>
					{/if}
				{else}
					<td>{translate text="%1% of %2%" 1=$item.availableCopies 2=$item.totalCopies isPublicFacing=true}</td>
				{/if}
				<td class="notranslate">{$item.shelfLocation}</td>
				<td class="notranslate">
					{if !$item.isEContent}
						{$item.callNumber}
					{/if}
				</td>
			</tr>
		{/foreach}
		</tbody>
	</table>
</div>
{/strip}