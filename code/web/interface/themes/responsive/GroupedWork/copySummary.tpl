{strip}
	{if !empty($format) && ($format == 'Journal' || $format == 'Newspaper' || $format == 'Print Periodical' || $format == 'Magazine')}
		{if $recordViewUrl && $showQuickCopy}
			<div class="itemSummary">
				&nbsp;&nbsp;<a href="{$recordViewUrl}#copiesPanelBody">
					{translate text="Where is it?" isPublicFacing=true}
				</a>
			</div>
		{/if}
	{else}
		{assign var=numDefaultItems value="0"}
		{assign var=numRowsShown value="0"}
		{foreach from=$summary item="item"}
			{if $item.displayByDefault && $numRowsShown<3}
				{if $item.isEContent == false}
					<div class="itemSummary row" style="margin: 0">
						<div class="col-lg-7">
							<span class="notranslate">{if !$item.isEContent}<strong>{$item.shelfLocation}</strong>{/if}
								{if $item.availableCopies < 999}
									&nbsp; {translate text="%1% available" 1=$item.availableCopies isPublicFacing=true}
								{/if}
							</span>
						</div>
						<div class="col-lg-4">
								<span class="notranslate"><strong>{$item.callNumber}</strong></span>
						</div>
					</div>
				{/if}
				{assign var=numDefaultItems value=$numDefaultItems+$item.totalCopies}
				{assign var=numRowsShown value=$numRowsShown+1}
			{/if}
		{/foreach}
		{if empty($inPopUp)}
			{assign var=numRemainingCopies value=$totalCopies-$numDefaultItems}
			{if $numRemainingCopies > 0}
				{if $showQuickCopy}
					<div class="itemSummary">
						{* showElementInPopup('Copy Summary', '#itemSummaryPopup_{$itemSummaryId|escapeCSS}_{$relatedManifestation->format|escapeCSS}'{if !empty($recordViewUrl)}, '#itemSummaryPopupButtons_{$itemSummaryId|escapeCSS}_{$relatedManifestation->format|escapeCSS}'{/if}); *}
						<a href="#" onclick="return AspenDiscovery.GroupedWork.showCopyDetails('{$workId}', '{$relatedManifestation->format|urlencode}', '{$itemSummaryId}');">
							{translate text="Where is it?" isPublicFacing=true}
						</a>
					</div>
				{/if}
				{if !empty($recordViewUrl)}
					<div id="itemSummaryPopupButtons_{$itemSummaryId|escapeCSS}_{$relatedManifestation->format|escapeCSS}" style="display: none">
						<a href="{$recordViewUrl}" class="btn btn-primary" role="button">{translate text="See Full Copy Details" isPublicFacing=true}</a>
					</div>
				{/if}
			{/if}
		{/if}
	{/if}
{/strip}