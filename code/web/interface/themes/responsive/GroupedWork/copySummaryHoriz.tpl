{strip}
	<div class="row horizDisplayShelfLocations">
	{if !empty($isEContent)}
		<div class="itemSummary">
			<a href="#" onclick="return AspenDiscovery.GroupedWork.showCopyDetails('{$workId}', '{if !empty($relatedManifestation)}{$relatedManifestation->format|urlencode}{else}{$format}{/if}', '{$itemSummaryId}');">
				{translate text="where_is_it_button" defaultText="View All Locations" isPublicFacing=true}
			</a>
		</div>
	{else}
		{assign var=numDefaultItems value="0"}
		{assign var=numRowsShown value="0"}
		{if $showQuickCopy != 3}
			{assign var=numDisplayed value=0}
			{assign var=totalSummariesToDisplay value=0}
			{foreach from=$summary item=$curItemSummary name=itemSummary}
				{if $curItemSummary.displayByDefault}
					{assign var=totalSummariesToDisplay value=$totalSummariesToDisplay+1}
				{/if}
			{/foreach}
			{foreach from=$summary item="item"}
				{*If we only have 3 or fewer summaries to show, show all 3. If we have more than 3, display 2 and a button to see the rest *}
				{if ($numDisplayed < 2 || ($totalSummariesToDisplay == 3 && count($itemSummary) == 3)) && $item.displayByDefault}
					{assign var=numDisplayed value=$numDisplayed+1}
					{if empty($item.isEContent)}
						<div class="col-tn-4" data-shelfLocation="{$item.shelfLocation|escape:"javascript"}" data-subLocation="{$item.subLocation|escape:"javascript"}" data-callNumber="{$item.callNumber|escape:"javascript"}">
							<span class="notranslate">
								<div><strong>{$item.shelfLocation}</strong></div>
								<div>{$item.callNumber}</div>
								<div>
									{if $item.availableCopies < 999}
										{translate text="%1% available" 1=$item.availableCopies isPublicFacing=true}
									{/if}
								</div>
							</span>
						</div>
					{/if}
					{assign var=numDefaultItems value=$numDefaultItems+$item.totalCopies}
					{assign var=numRowsShown value=$numRowsShown+1}
				{/if}
			{/foreach}
		{/if}
		{if empty($inPopUp)}
			{assign var=numRemainingCopies value=$totalCopies-$numDefaultItems}
			{if $numRemainingCopies > 0 || ($showQuickCopy == 2 || $showQuickCopy == 3)}
				{if $showQuickCopy == 1 || $showQuickCopy == 2 || $showQuickCopy == 3}
					{if $totalCopies > 0}
						<div class="col-tn-4 pull-right">
							<button class="btn btn-default btn-sm btn-wrap viewAllLocationsBtn" href="#" onclick="return AspenDiscovery.GroupedWork.showCopyDetails('{$workId}', '{if !empty($relatedManifestation)}{$relatedManifestation->format|urlencode}{else}{$format}{/if}', '{$itemSummaryId}');">
								{translate text="where_is_it_button" defaultText="View All Locations" isPublicFacing=true}
							</button>
						</div>
					{/if}
				{/if}
				{if !empty($recordViewUrl)}
					<div class="col-tn-4 pull-right">
						<div id="itemSummaryPopupButtons_{$itemSummaryId|escapeCSS}_{if !empty($relatedManifestation)}{$relatedManifestation->format|escapeCSS}{else}{$format|escapeCSS}{/if}" style="display: none">
							<a href="{$recordViewUrl}" class="btn btn-primary" role="button">{translate text="See Full Copy Details" isPublicFacing=true}</a>
						</div>
					</div>
				{/if}
			{/if}
		{/if}
	{/if}
	</div>
{/strip}
