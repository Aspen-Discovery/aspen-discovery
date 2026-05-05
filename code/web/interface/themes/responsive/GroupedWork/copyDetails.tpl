{strip}
<div id="itemSummaryPopup_{$itemSummaryId|escapeCSS}_{$relatedManifestation->format|escapeCSS}" class="itemSummaryPopup">
	<table class="table table-striped table-condensed itemSummaryTable">
		<thead>
		<tr>
			{if ($whereIsItDisplayStyle == 1)}
				<th>{translate text="Available Copies" isPublicFacing=true}</th>
			{/if}
			{if ($whereIsItDisplayStyle == 2) && $infoToShow.volume}
				<th>{translate text="Volume" isPublicFacing=true}</th>
			{/if}
			<th>{translate text="Location" isPublicFacing=true}</th>
			{if empty($isEContent)}
				<th>{translate text="Call #" isPublicFacing=true}</th>
			{elseif !empty($showEContentHoldCounts)}
				<th>{translate text="Holds" isPublicFacing=true}</th>
			{/if}
			{if ($whereIsItDisplayStyle == 2)}
				{if ($infoToShow.barcode) && $showItemBarcodes}
					<th>{translate text="Barcode" isPublicFacing=true}</th>
				{/if}
				{if ($infoToShow.note) && $showItemNotes}
					<th>{translate text="Note" isPublicFacing=true}</th>
				{/if}
				<th>{translate text="Status" isPublicFacing=true}</th>
				{if ($infoToShow.dueDate) && $showItemDueDates}
					<th>{translate text="Due Date" isPublicFacing=true}</th>
				{/if}
			{/if}
		</tr>
		</thead>
		<tbody>
		{assign var=numRowsShown value=0}
		{foreach from=$summary item="item"}
			<tr {if !empty($item.availableCopies)}class="available" {/if}>
				{if ($whereIsItDisplayStyle == 1)}
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
				{/if}
				{if ($whereIsItDisplayStyle == 2) && $infoToShow.volume}
					<td class="notranslate">{$item.volume}</td>
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
				{if ($whereIsItDisplayStyle == 2)}
					{if ($infoToShow.barcode) && $showItemBarcodes}
						<td>
							{if !empty($item.barcode)}{$item.barcode}{/if}
						</td>
					{/if}
					{if !empty($infoToShow.note) && $showItemNotes}
						<td>
							{if !empty($item.note)}{$item.note}{/if}
						</td>
					{/if}
					<td class="notranslate">{$item.status}</td>
					{if !empty($infoToShow.dueDate) && $showItemDueDates}
						<td>
							{if !empty($item.dueDate)}{$item.dueDate|date_format:"%B %e, %Y"}{/if}
						</td>
					{/if}
				{/if}

			</tr>
		{/foreach}
		</tbody>
	</table>
</div>
{/strip}
