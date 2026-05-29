{strip}
	<div class="row">
		<div class="col-tn-8">
			<div class="row">
				{capture assign=statusIndicator}{include file='GroupedWork/statusIndicator.tpl' statusInformation=$firstRecord->getStatusInformation() viewingIndividualRecord=0 applyColors=false hideCopiesLine=false}{/capture}
				{capture assign=formatWithLink}<a href="{$firstRecord->getUrl()}">{translate text=$firstRecord->getFormat() isPublicFacing=true inAttribute=true}</a>{/capture}
				<div class="result-label col-tn-12">{translate text="This %1% is currently %2%" 1=$formatWithLink 2=$statusIndicator isPublicFacing=true}</div>
			</div>
			<div style="margin-bottom: 3px; font-size: smaller">
				{if !empty($firstRecord->publicationDate) || !empty($firstRecord->publisher)}
					{$firstRecord->publicationDate} {$firstRecord->publisher}
				{/if}
				{if !empty($firstRecord->edition)} {$firstRecord->edition}{/if}
				{* {if !empty($firstRecord->getEContentSource())} {translate text=$firstRecord->getEContentSource() isPublicFacing=true}{/if} *}
				{if !empty($firstRecord->physical)} {$firstRecord->physical} {if $firstRecord->closedCaptioned}<i class="fas fa-closed-captioning"></i> {/if}{/if}
				{if !empty($firstRecord->languageNote)} {$firstRecord->languageNote}{/if}
			</div>
		</div>
		<div class="col-tn-4" style="padding-right: 0">
			<div class="btn-group btn-group-vertical btn-group-md btn-block">
				{foreach from=$firstRecord->getActions($variationId) item=curAction}
					<a href="{if !empty($curAction.url)}{$curAction.url}{else}#{/if}" data-prompt-edition="{if count($relatedRecords) > 1 && $curAction.type == 'ils_hold' && $curAction.subtype == 'standard_ils_hold'}true{else}false{/if}" {if !empty($curAction.onclick)}onclick="{$curAction.onclick}"{/if} class="btn btn-sm {if empty($curAction.btnType)}btn-action{else}{$curAction.btnType}{/if} btn-wrap" {if !empty($curAction.target)}target="{$curAction.target}"{/if} {if !empty($curAction.id)}id="firstRecord{$curAction.id}"{/if} {if !empty($curAction.alt)}title="{$curAction.alt}"{/if} {if !empty($curAction['data-needs-refresh'])}data-needs-refresh="{$curAction['data-needs-refresh']}"{/if} {if !empty($curAction['data-record-id'])}data-record-id="{$curAction['data-record-id']}"{/if} {if !empty($curAction['data-record-source'])}data-record-source="{$curAction['data-record-source']}"{/if}>{$curAction.title}</a>
				{/foreach}
			</div>
		</div>
	</div>
	{* Show Shelf Locations *}
	{if !$isEContent}
		<div class="row horizDisplayShelfLocations" id="horizDisplayShelfLocations_{$workId}">
			{assign var=numDisplayed value=0}
			{assign var=totalSummariesToDisplay value=0}
			{foreach from=$itemSummary item=$curItemSummary name=itemSummary}
				{if $curItemSummary.displayByDefault}
					{assign var=totalSummariesToDisplay value=$totalSummariesToDisplay+1}
				{/if}
			{/foreach}
			{foreach from=$itemSummary item=$curItemSummary name=itemSummary}
				{*If we only have 3 or fewe summaries to show, show all 3. If we have more than 3, display 2 and a button to see the rest *}
				{if ($numDisplayed < 2 || ($totalSummariesToDisplay == 3 && count($itemSummary) == 3)) && $curItemSummary.displayByDefault}
					{assign var=numDisplayed value=$numDisplayed+1}
					<div class="col-tn-4">
						<div><strong>{$curItemSummary.shelfLocation}</strong></div>
						<div>{$curItemSummary.callNumber}</div>
						<div>{$curItemSummary.availableCopies} of {$curItemSummary.totalCopies} available</div>
					</div>
				{/if}
			{/foreach}
			{if count($itemSummary) > 2 && $totalSummariesToDisplay != count($itemSummary) || ($showQuickCopy == 2 || $showQuickCopy == 3)}
				<div class="col-tn-4 pull-right">
					<button class="btn btn-default btn-sm btn-wrap viewAllLocationsBtn" onclick="return AspenDiscovery.GroupedWork.showCopyDetails('{$workId}', '{if !empty($relatedManifestation)}{$relatedManifestation->format|urlencode}{else}{$format}{/if}', '{$workId}');">{translate text="View All Locations" isPublicFacing=true}</button>
				</div>
			{/if}
		</div>
	{/if}


	{if count($relatedRecords) > 1}
		<div class="row horizDisplayShowEditionsRow" id="horizDisplayShowEditionsRow_{$workId}">
			<div class="col-tn-12">
				<div class="horiz-line-left"></div>
				<button class="horizDisplayShowEditionsBtn btn btn-sm btn-editions" onclick="AspenDiscovery.GroupedWork.showAllEditionsForVariation('{$workId}', '{$format}', '{$variationId}')">{translate text="Show %1% Editions" 1=count($relatedRecords) isPublicFacing=true}</button>
				<button class="horizDisplayHideEditionsBtn btn btn-sm btn-editions" style="display:none" onclick="AspenDiscovery.GroupedWork.hideAllEditionsForVariation('{$workId}', '{$format}', '{$variationId}')">{translate text="Hide %1% Editions" 1=count($relatedRecords) isPublicFacing=true}</button>
				<div class="horiz-line-right"></div>
			</div>
		</div>
		<div class="row horizDisplayAllEditions" id="horizDisplayAllEditions_{$workId}">
		</div>
	{/if}
{/strip}
