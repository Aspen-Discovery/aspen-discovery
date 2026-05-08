{strip}
	<div id="groupedRecord{$summId|escape}" class="resultsList">
		<a id="record{$summId|escape}"></a>
		{if isset($summExplain)}
			<div class="hidden" id="scoreExplanationValue{$summId|escape}">{$summExplain}</div>
		{/if}

		<div class="row">
			{if !empty($showCovers)}
				<div class="coversColumn col-xs-3 col-sm-3{if !empty($viewingCombinedResults)} col-md-3 col-lg-2{/if} text-center" aria-hidden="true" role="presentation">
					{if $disableCoverArt != 1}
						<div class="listResultImage img-thumbnail {$coverStyle}">
							<a href="{$summUrl}" tabindex="-1">
								{if !empty($isNew)}<span class="list-cover-badge">{translate text="New!" isPublicFacing=true}</span> {/if}
								<img src="{$bookCoverUrlMedium}" class="{if $useOriginalCoverUrls}use-original-covers{/if}" alt="{$summTitle|removeTrailingPunctuation|escapeCSS}" title="{$summTitle|removeTrailingPunctuation|escapeCSS}">
							</a>
						</div>
					{/if}

					{if !empty($showRatings)}
						{include file="GroupedWork/title-rating.tpl" id=$summId ratingData=$summRating}
					{/if}
				</div>
			{/if}

			<div class="{if empty($showCovers)}col-xs-12{else}col-xs-9 col-sm-9{if !empty($viewingCombinedResults)} col-md-9 col-lg-10{/if}{/if}">{* May turn out to be more than one situation to consider here *}
				<div class="row">
					{* Title Row *}

					<div class="col-xs-12">
						<h2 style="margin:0;font-size:inherit;">{if !empty($resultIndex)}<span class="result-index">{$resultIndex})</span>{/if}&nbsp;
						<a href="{$summUrl}&referred=resultIndex" class="result-title notranslate" aria-label="{$summTitle|removeTrailingPunctuation|escapeCSS} {if !empty($summSubTitle)}{if $summSubTitle|removeTrailingPunctuation} {$summSubTitle|removeTrailingPunctuation|highlight|escapeCSS|truncate:180:'...'}{/if}{/if}">
							{if !$summTitle|removeTrailingPunctuation} {translate text='Title not available' isPublicFacing=true}{else}{$summTitle|removeTrailingPunctuation|highlight|truncate:180:"..."}{/if}
							{if !empty($summSubTitle)}{if $summSubTitle|removeTrailingPunctuation}: {$summSubTitle|removeTrailingPunctuation|highlight|truncate:180:"..."}{/if}{/if}
						</a>
						{if isset($summScore)}
							&nbsp;(<a href="#" onclick="return AspenDiscovery.showElementInPopup('Score Explanation', '#scoreExplanationValue{$summId|escape}');">{$summScore}</a>)
						{/if}
						</h2>
					</div>


					{if $allowHoldsToBeGrouped}
						<div class="col-xs-12" style="display: flex; flex-wrap: wrap; align-items: flex-start; margin-top:10px;">
							<div class="metadata-columns" style="display: flex; flex-wrap: wrap; flex:1;">
								{include file="GroupedWork/metadataBlocks.tpl"}
							</div>
							<div class="metadata-action" style="margin-left:auto; flex-shrink:0;">
								<a href="#" id="hyperholdGroupedWorkButton" class="btn btn-primary btn-sm" aria-label="{translate text='Place a hold on this grouped work' isPublicFacing=true}" onclick="AspenDiscovery.Record.placeHyperhold('{$summId}'); return false;">
									{translate text="Place Hyperhold" isPublicFacing=true}
								</a>
							</div>
						</div>
					{else}
						{include file="GroupedWork/metadataBlocks.tpl"}
					{/if}

					{include file="GroupedWork/relatedLists.tpl" isSearchResults=true}

					{include file="GroupedWork/readingHistoryIndicator.tpl" isSearchResults=true}

					{include file="GroupedWork/allManifestations.tpl" isSearchResults=true}

					{if empty($viewingCombinedResults)}
						{* Description Section *}
						{if !empty($summDescription)}
							{* Standard Description *}
							<div class="visible-xs">
								<div class="result-label col-sm-4 col-xs-12">{translate text="Description" isPublicFacing=true}</div>
								<div class="result-value col-sm-8 col-xs-12"><a id="descriptionLink{$summId|escape}" href="#" onclick="$('#descriptionValue{$summId|escape},#descriptionLink{$summId|escape}').toggleClass('hidden-xs');return false;">{translate text="Read Description" isPublicFacing=true}</a></div>
							</div>

							{* Mobile Description *}
							{* Hide in mobile view *}
							<div class="hidden-xs result-value col-sm-12" id="descriptionValue{$summId|escape}">
								{$summDescription|highlight|truncate_html:450:"..."}
							</div>
						{/if}

						<div class="col-xs-12">
							{include file='GroupedWork/result-tools-horizontal.tpl' ratingData=$summRating recordUrl=$summUrl showMoreInfo=true showNotInterested=false}
						</div>

					{/if}

				</div>
			</div>

		</div>
	</div>
{/strip}
