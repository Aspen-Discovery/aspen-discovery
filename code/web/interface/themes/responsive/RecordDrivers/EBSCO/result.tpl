{strip}
<div id="record{if $summShortId}{$summShortId}{else}{$summId|escape}{/if}" class="resultsList row">
	{if $showCovers}
		<div class="coversColumn col-xs-3 col-sm-3{if !$viewingCombinedResults} col-md-3 col-lg-2{/if} text-center" aria-hidden="true" role="presentation">
			{if $disableCoverArt != 1 && !empty($bookCoverUrlMedium)}
				<a href="{$summUrl}" onclick="AspenDiscovery.EBSCO.trackEdsUsage('{$summId}')" target="_blank" aria-hidden="true">
					<img src="{$bookCoverUrlMedium}" class="listResultImage img-thumbnail" alt="{translate text='Cover Image' inAttribute=true isPublicFacing=true}">
				</a>
			{/if}
		</div>
	{/if}

	<div class="{if !$showCovers}col-xs-12{else}col-tn-9 col-sm-9{if empty($viewingCombinedResults)} col-md-9 col-lg-10{/if}{/if}">
		<div class="row">
			<div class="col-xs-12">
				<span class="result-index">{$resultIndex})</span>&nbsp;
				<a href="{$summUrl}" class="result-title notranslate" onclick="AspenDiscovery.EBSCO.trackEdsUsage('{$summId}')" target="_blank">
					{if !$summTitle|removeTrailingPunctuation}{translate text='Title not available'}{else}{$summTitle|removeTrailingPunctuation|truncate:180:"..."|highlight}{/if}
				</a>
			</div>
		</div>

		{if $summAuthor}
			<div class="row">
				<div class="result-label col-tn-3">{translate text='Author'}:</div>
				<div class="col-tn-9 result-value">{$summAuthor|escape}</div>
			</div>
		{/if}

		{if strlen($summSourceDatabase)}
			<div class="row hidden-phone">
				<div class="result-label col-tn-3">{translate text='Found in'}:</div>
				<div class="col-tn-9 result-value">{$summSourceDatabase|escape}</div>
			</div>
		{/if}

		{if !empty($summPublicationDates) || !empty($summPublishers) || !empty($summPublicationPlaces)}
			<div class="row">

				<div class="result-label col-tn-3">{translate text='Published'}</div>
				<div class="col-tn-9 result-value">
					{$summPublicationPlaces.0|escape}{$summPublishers.0|escape}{$summPublicationDates.0|escape}
				</div>
			</div>
		{/if}

		{if strlen($summFormats)}
			<div class="row">
				<div class="result-label col-tn-3">{translate text='Format'}</div>
				<div class="col-tn-9 result-value">
					<span>{translate text=$summFormats}</span>
				</div>
			</div>
		{/if}

		{if !empty($summPhysical)}
			<div class="row hidden-phone">
				<div class="result-label col-tn-3">{translate text='Physical Desc'}</div>
				<div class="col-tn-9 result-value">{$summPhysical.0|escape}</div>
			</div>
		{/if}

		<div class="row hidden-phone">
			<div class="result-label col-tn-3">{translate text='Full Text'}</div>
			<div class="col-tn-9 result-value">{if $summHasFullText}Yes{else}No{/if}</div>
		</div>

		{if count($appearsOnLists) > 0}
			<div class="row">
				<div class="result-label col-tn-3">
					{if count($appearsOnLists) > 1}
						{translate text="Appears on these lists"}
					{else}
						{translate text="Appears on list"}
					{/if}
				</div>
				<div class="result-value col-tn-8">
					{assign var=showMoreLists value=false}
					{if count($appearsOnLists) >= 5}
						{assign var=showMoreLists value=true}
					{/if}
					{foreach from=$appearsOnLists item=appearsOnList name=loop}
						<a href="{$appearsOnList.link}">{$appearsOnList.title}</a><br/>
						{if !empty($showMoreLists) && $smarty.foreach.loop.iteration == 3}
							<a onclick="$('#moreLists_OpenArchives{$recordDriver->getId()}').show();$('#moreListsLink_OpenArchives{$recordDriver->getId()}').hide();" id="moreListsLink_OpenArchives{$recordDriver->getId()}">{translate text="More Lists..."}</a>
							<div id="moreLists_OpenArchives{$recordDriver->getId()}" style="display:none">
						{/if}
					{/foreach}
					{if !empty($showMoreLists)}
						</div>
					{/if}
				</div>
			</div>
		{/if}

		{if $summDescription}
			{* Standard Description *}
			<div class="row visible-xs">
				<div class="result-label col-tn-3">{translate text='Description'}</div>
				<div class="result-value col-tn-8"><a id="descriptionLink{$summId|escape}" href="#" onclick="$('#descriptionValue{$summId|escape},#descriptionLink{$summId|escape}').toggleClass('hidden-xs');return false;">Click to view</a></div>
			</div>

			{* Mobile Description *}
			<div class="row hidden-xs">
				{* Hide in mobile view *}
				<div class="result-value col-sm-12" id="descriptionValue{$summId|escape}">
					{$summDescription|highlight|truncate_html:450:"..."}
				</div>
			</div>
		{/if}

		{if empty($viewingCombinedResults)}
			<div class="row">
				<div class="col-xs-12">
					{include file='EBSCO/result-tools-horizontal.tpl' recordUrl=$summUrl showMoreInfo=true}
				</div>
			</div>
		{/if}
	</div>
</div>
{/strip}