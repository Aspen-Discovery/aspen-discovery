{strip}
	<div class="row">
		<div class="col-tn-8">
			<div class="row">
				{capture assign=statusIndicator}{include file='GroupedWork/statusIndicator.tpl' statusInformation=$firstRecord->getStatusInformation() viewingIndividualRecord=0 applyColors=false}{/capture}
				<div class="result-label col-tn-12">{translate text="This %1% is currently %2%" 1=$firstRecord->getFormat() 2=$statusIndicator isPublicFacing=true}</div>
			</div>
			<div style="margin-bottom: 3px; font-size: smaller">
				{if !empty($firstRecord->publicationDate) || !empty($firstRecord->publisher)}
					{$firstRecord->publicationDate} {$firstRecord->publisher}
				{/if}
				{if !empty($firstRecord->edition)} {$firstRecord->edition}{/if}
				{if !empty($firstRecord->getEContentSource())} {translate text=$firstRecord->getEContentSource() isPublicFacing=true}{/if}
				{if !empty($firstRecord->physical)} {$firstRecord->physical} {if $firstRecord->closedCaptioned}<i class="fas fa-closed-captioning"></i> {/if}{/if}
				{if !empty($firstRecord->languageNote)} {$firstRecord->languageNote}{/if}
			</div>
		</div>
		<div class="col-tn-4">
			<div class="btn-group btn-group-vertical btn-group-md btn-block">
				<a href="{$firstRecord->getUrl()}" class="btn btn-sm btn-info">{translate text="More Info" isPublicFacing=true}</a>
				{foreach from=$firstRecord->getActions($variationId) item=curAction}
					<a href="{if !empty($curAction.url)}{$curAction.url}{else}#{/if}" {if !empty($curAction.onclick)}onclick="{$curAction.onclick}"{/if} class="btn btn-sm {if empty($curAction.btnType)}btn-action{else}{$curAction.btnType}{/if} btn-wrap" {if !empty($curAction.target)}target="{$curAction.target}"{/if} {if !empty($curAction.id)}id="firstRecord{$curAction.id}"{/if} {if !empty($curAction.alt)}title="{$curAction.alt}"{/if}>{$curAction.title}</a>
				{/foreach}
			</div>
		</div>
	</div>
	{* Show Shelf Locations *}
	{if !$isEContent}
		<div class="row horizDisplayShelfLocations" id="horizDisplayShelfLocations_{$workId}">
			{foreach from=$itemSummary item=$curItemSummary name=itemSummary}
				{if $smarty.foreach.itemSummary.index < 2}
				<div class="col-tn-5">
					<div><strong>{$curItemSummary.shelfLocation}</strong></div>
					<div>{$curItemSummary.callNumber}</div>
					<div>{$curItemSummary.availableCopies} of {$curItemSummary.totalCopies} available</div>
				</div>
				{/if}
			{/foreach}
			{if count($itemSummary) > 2}
				<div class="col-tn-2">
					<button class="btn btn-default btn-sm btn-wrap viewAllCopiesBtn" onclick="return AspenDiscovery.GroupedWork.showCopyDetails('{$workId}', '{if !empty($relatedManifestation)}{$relatedManifestation->format|urlencode}{else}{$format}{/if}', '{$workId}');">{translate text="View All Copies" isPublicFacing=true}</button>
				</div>
			{/if}
		</div>
	{/if}


	{if count($relatedRecords) > 1}
		<div class="row horizDisplayShowEditionsRow" id="horizDisplayShowEditionsRow_{$workId}">
			<div class="col-tn-12">
				<button class="horizDisplayShowEditionsBtn btn btn-sm" onclick="AspenDiscovery.GroupedWork.showAllEditionsForVariation('{$workId}', '{$format}', '{$variationId}')">{translate text="Show %1% Editions" 1=count($relatedRecords)}</button>
			</div>
		</div>
		<div class="row horizDisplayAllEditions" id="horizDisplayAllEditions_{$workId}">
		</div>
	{/if}
{/strip}
