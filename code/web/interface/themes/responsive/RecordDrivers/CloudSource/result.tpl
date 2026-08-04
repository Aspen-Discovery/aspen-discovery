{strip}
<div id="record{if !empty($summShortId)}{$summShortId}{else}{$summId|escape}{/if}" class="resultsList row">
	{if !empty($showCovers)}
		<div class="coversColumn col-xs-3 col-sm-3{if empty($viewingCombinedResults)} col-md-3 col-lg-2{/if} text-center" aria-hidden="true" role="presentation">
			{if $disableCoverArt != 1 && !empty($bookCoverUrlMedium)}
				{if !empty($summUrl)}
					<a href="{$summUrl}" onclick="AspenDiscovery.CloudSource.trackCloudSourceUsage('{$summId}')" aria-hidden="true">
						<img src="{$bookCoverUrlMedium}" class="listResultImage img-thumbnail{if $useOriginalCoverUrls} use-original-covers{/if} {$coverStyle}" alt="{translate text='Cover Image' inAttribute=true isPublicFacing=true}">
					</a>
				{else}
					<label aria-hidden="true">
						<img src="{$bookCoverUrlMedium}" class="listResultImage img-thumbnail{if $useOriginalCoverUrls} use-original-covers{/if} {$coverStyle}" alt="{translate text='Cover Image' inAttribute=true isPublicFacing=true}">
					</label>
				{/if}
			{/if}
		</div>
	{/if}

	<div class="{if empty($showCovers)}col-xs-12{else}col-tn-9 col-sm-9{if empty($viewingCombinedResults)} col-md-9 col-lg-10{/if}{/if}">
		<div class="row">
			<div class="col-xs-12">
				{if !empty($summUrl)}
					<span class="result-index">{$resultIndex})</span>&nbsp;
					<a href="{$summUrl}" class="result-title notranslate" onclick="AspenDiscovery.CloudSource.trackCloudSourceUsage('{$summId}')">
					{if !$summTitle|removeTrailingPunctuation} {translate text='Title not available' isPublicFacing=true}{else}{$summTitle|removeTrailingPunctuation|truncate:180:"..."|highlight}{/if}
					</a>
				{else}
					<p class="result-title notranslate">
						{$resultIndex}) {if !$summTitle|removeTrailingPunctuation} {translate text='Title not available' isPublicFacing=true}{else}{$summTitle|removeTrailingPunctuation|truncate:180:"..."|highlight}{/if}
					</p>
				{/if}
			</div>
		</div>
		<div class="row">
			<div class="col-tn-12 col-xs-12 col-md-10 col-lg-8">
				{if !empty($summAuthor)}
					<div class="row">
						<div class="result-label col-tn-3"> {translate text='Author' isPublicFacing=true}</div>
						<div class="col-tn-9 result-value">{$summAuthor|escape}</div>
					</div>
				{/if}

				{if !empty($summPublicationDates)}
					<div class="row">
						<div class="result-label col-tn-3">{translate text='Publication Date' isPublicFacing=true}</div>
						<div class="col-tn-9 result-value">
							{$summPublicationDates|escape}
						</div>
					</div>
				{/if}

				{if strlen($summFormats)}
					<div class="row">
						<div class="result-label col-tn-3">{translate text='Format' isPublicFacing=true}</div>
						<div class="col-tn-9 result-value">
							<span>{translate text=$summFormats isPublicFacing=true}</span>
						</div>
					</div>
				{/if}

				{if !empty($summPhysical)}
					<div class="row hidden-phone">
						<div class="result-label col-tn-3">{translate text='Physical Desc' isPublicFacing=true}</div>
						<div class="col-tn-9 result-value">{$summPhysical.0|escape}</div>
					</div>
				{/if}

				{if count($appearsOnLists) > 0}
					<div class="row">
						<div class="result-label col-tn-3">
							{if count($appearsOnLists) > 1}
								{translate text="Appears on these lists" isPublicFacing=true}
							{else}
								{translate text="Appears on list" isPublicFacing=true}
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
									<a onclick="$('#moreLists_OpenArchives{$recordDriver->getId()}').show();$('#moreListsLink_OpenArchives{$recordDriver->getId()}').hide();" id="moreListsLink_OpenArchives{$recordDriver->getId()}">{translate text="More Lists..." isPublicFacing=true}</a>
									<div id="moreLists_OpenArchives{$recordDriver->getId()}" style="display:none">
								{/if}
							{/foreach}
							{if !empty($showMoreLists)}
									</div>
							{/if}
						</div>
					</div>
				{/if}

				{if !empty($summDescription)}
					{* Standard Description *}
					<div class="row visible-xs">
						<div class="result-label col-tn-3">{translate text='Description' isPublicFacing=true}</div>
						<div class="result-value col-tn-8"><a id="descriptionLink{$summId|escape}" href="#" onclick="$('#descriptionValue{$summId|escape},#descriptionLink{$summId|escape}').toggleClass('hidden-xs');return false;">{translate text="Click to view" isPublicFacing=true}</a></div>
					</div>

					{* Mobile Description *}
					<div class="row">
						{* Hide in mobile view *}
						<div class="hidden-xs result-value col-sm-12" id="descriptionValue{$summId|escape}">
							{$summDescription|highlight|truncate_html:450:"..."}
						</div>
					</div>
				{/if}
			</div>

			{if empty($viewingCombinedResults)}
				{if (!empty($directLinkUrl))}
					<div class="col-tn-8 col-xs-8 col-md-4 col-lg-3">
						<div class="btn-toolbar">
							<div class="btn-group btn-group-vertical btn-block">
								<a href="{$directLinkUrl}" class="btn btn-sm btn-action btn-wrap" target="_blank" aria-label="{translate text="View Article" isPublicFacing=true inAttribute=true} ({translate text="opens in a new window" isPublicFacing=true inAttribute=true})"><i class="fas fa-external-link-alt" role="presentation"></i> {translate text="View Article" isPublicFacing=true}</a>
							</div>
						</div>
					</div>
				{/if}

				<div class="col-xs-12">
					{include file='CloudSource/result-tools-horizontal.tpl' recordUrl=$summUrl showMoreInfo=true}
				</div>
			{/if}
		</div>
	</div>
</div>
{/strip}
