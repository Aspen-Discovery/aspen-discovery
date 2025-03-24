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
								<img src="{$bookCoverUrlMedium}" alt="{$summTitle|removeTrailingPunctuation|escapeCSS}" title="{$summTitle|removeTrailingPunctuation|escapeCSS}">
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
						<h3 style="margin-top:0">{if !empty($resultIndex)}<span class="result-index">{$resultIndex})</span>{/if}&nbsp;
						<a href="{$summUrl}&referred=resultIndex" class="result-title notranslate" aria-label="{$summTitle|removeTrailingPunctuation|escapeCSS} {if !empty($summSubTitle)}{if $summSubTitle|removeTrailingPunctuation} {$summSubTitle|removeTrailingPunctuation|highlight|escapeCSS|truncate:180:'...'}{/if}{/if}">
							{if !$summTitle|removeTrailingPunctuation} {translate text='Title not available' isPublicFacing=true}{else}{$summTitle|removeTrailingPunctuation|highlight|truncate:180:"..."}{/if}
							{if !empty($summSubTitle)}{if $summSubTitle|removeTrailingPunctuation}: {$summSubTitle|removeTrailingPunctuation|highlight|truncate:180:"..."}{/if}{/if}
						</a>
						{if isset($summScore)}
							&nbsp;(<a href="#" onclick="return AspenDiscovery.showElementInPopup('Score Explanation', '#scoreExplanationValue{$summId|escape}');">{$summScore}</a>)
						{/if}
						</h3>
					</div>


				{if !empty($summAuthor)}

						<div class="result-label col-sm-4 col-xs-12">{translate text="Author" isPublicFacing=true} </div>
						<div class="result-value col-sm-8 col-xs-12 notranslate">
							{if is_array($summAuthor)}
								{foreach from=$summAuthor item=author}
									<a href='/Author/Home?author="{$author|escape:"url"}"'>{$author|highlight}</a>
								{/foreach}
							{else}
								<a href='/Author/Home?author="{$summAuthor|escape:"url"}"'>{$summAuthor|highlight}</a>
							{/if}
						</div>

				{/if}

				{if !empty($showSeries)}
					{assign var=indexedSeries value=$recordDriver->getIndexedSeries()}
					{if $summSeries || $indexedSeries}
						<div class="series{$summISBN}">
							<div class="result-label col-sm-4 col-xs-12">{translate text="Series" isPublicFacing=true} </div>
							<div class="result-value col-sm-8 col-xs-12">
								{if !empty($summSeries)}
									{if !empty($summSeries.fromNovelist)}
										<a href="/GroupedWork/{$summId}/Series">{$summSeries.seriesTitle}</a>{if !empty($summSeries.volume)} <strong>{translate text=volume isPublicFacing=true} {$summSeries.volume|format_float_with_min_decimals}</strong>{/if}<br>
									{elseif !empty($summSeries.fromSeriesIndex)}
										<a href="/Series/{$summSeries.seriesId}">{$summSeries.seriesTitle}</a>{if !empty($summSeries.volume)}<strong> {translate text="volume %1%" 1=$summSeries.volume|format_float_with_min_decimals isPublicFacing=true}</strong>{/if}
									{else}
										<a href="/Search/Results?searchIndex=Series&lookfor={$summSeries.seriesTitle}&sort=year+asc%2Ctitle+asc">{$summSeries.seriesTitle}</a>{if !empty($summSeries.volume)}<strong> {translate text="volume %1%" 1=$summSeries.volume|format_float_with_min_decimals isPublicFacing=true}</strong>{/if}<br>
									{/if}
								{/if}
								{if !empty($indexedSeries)}
									{assign var=numSeriesShown value=0}
									{foreach from=$indexedSeries item=seriesItem name=loop}
										{if !isset($summSeries.seriesTitle) || ((strpos(strtolower($seriesItem.seriesTitle), strtolower($summSeries.seriesTitle)) === false) && (strpos(strtolower($summSeries.seriesTitle), strtolower($seriesItem.seriesTitle)) === false))}
											{assign var=numSeriesShown value=$numSeriesShown+1}
											{if $numSeriesShown == 4}
												<a onclick="$('#moreSeries_{$summId}').show();$('#moreSeriesLink_{$summId}').hide();" id="moreSeriesLink_{$summId}">{translate text='More Series...' isPublicFacing=true}</a>
												<div id="moreSeries_{$summId}" style="display:none">
											{/if}
											<a href="/Search/Results?searchIndex=Series&lookfor=%22{$seriesItem.seriesTitle|escape:"url"}%22&sort=year+asc%2Ctitle+asc">{$seriesItem.seriesTitle|escape}</a>{if !empty($seriesItem.volume)}<strong> {translate text="volume %1%" 1=$seriesItem.volume|format_float_with_min_decimals isPublicFacing=true}</strong>{/if}<br>
										{/if}
									{/foreach}
									{if $numSeriesShown >= 4}
										</div>
									{/if}
								{/if}
							</div>
						</div>
					{/if}
				{/if}

				{if !empty($showPublisher) && $showPublisher}
					{if $alwaysShowSearchResultsMainDetails || $summPublisher}

							<div class="result-label col-sm-4 col-xs-12">{translate text="Publisher" isPublicFacing=true} </div>
							<div class="result-value col-sm-8 col-xs-12">
								{if !empty($summPublisher)}
									{$summPublisher}
								{elseif $alwaysShowSearchResultsMainDetails}
									{translate text="Not Supplied" isPublicFacing=true}
								{/if}
							</div>

					{/if}
				{/if}

				{if !empty($showPublicationDate) && $showPublicationDate}
					{if $alwaysShowSearchResultsMainDetails || $summPubDate}

							<div class="result-label col-sm-4 col-xs-12">{translate text="Pub. Date" isPublicFacing=true} </div>
							<div class="result-value col-sm-8 col-xs-12">
								{if !empty($summPubDate)}
									{$summPubDate|escape}
								{elseif $alwaysShowSearchResultsMainDetails}
									{translate text="Not Supplied" isPublicFacing=true}
								{/if}
							</div>

					{/if}
				{/if}

				{if !empty($showPlaceOfPublication) && $showPlaceOfPublication}
					{if $alwaysShowSearchResultsMainDetails || $summPlaceOfPublication}
						<div class="result-label col-sm-4 col-xs-12">{translate text="Pub. Places" isPublicFacing=true} </div>
						<div class="result-value col-sm-8 col-xs-12">
							{if !empty($summPlaceOfPublication)}
								{$summPlaceOfPublication|escape}
							{elseif $alwaysShowSearchResultsMainDetails}
								{translate text="Not Supplied" isPublicFacing=true}
							{/if}
						</div>
					{/if}
				{/if}

				{if !empty($showEditions)}
					{if $alwaysShowSearchResultsMainDetails || $summEdition}

							<div class="result-label col-sm-4 col-xs-12">{translate text="Edition" isPublicFacing=true} </div>
							<div class="result-value col-sm-8 col-xs-12">
								{if !empty($summEdition)}
									{$summEdition}
								{elseif $alwaysShowSearchResultsMainDetails}
									{translate text="Not Supplied" isPublicFacing=true}
								{/if}
							</div>

					{/if}
				{/if}

				{if !empty($showArInfo) && $summArInfo}

						<div class="result-label col-sm-4 col-xs-12">{translate text='Accelerated Reader' isPublicFacing=true} </div>
						<div class="result-value col-sm-8 col-xs-12">
							{$summArInfo}
						</div>

				{/if}

				{if !empty($showLexileInfo) && $summLexileInfo}

						<div class="result-label col-sm-4 col-xs-12">{translate text='Lexile measure' isPublicFacing=true} </div>
						<div class="result-value col-sm-8 col-xs-12">
							{$summLexileInfo}
						</div>

				{/if}

				{if !empty($showFountasPinnell) && $summFountasPinnell}

						<div class="result-label col-sm-4 col-xs-12">{translate text='Fountas &amp; Pinnell' isPublicFacing=true} </div>
						<div class="result-value col-sm-8 col-xs-12">
							{$summFountasPinnell}
						</div>

				{/if}

				{if !empty($showPhysicalDescriptions)}
					{if $alwaysShowSearchResultsMainDetails || $summPhysicalDesc}

							<div class="result-label col-sm-4 col-xs-12">{translate text='Physical Desc' isPublicFacing=true} </div>
							<div class="result-value col-sm-8 col-xs-12">
								{if !empty($summPhysicalDesc)}
									{$summPhysicalDesc}
								{elseif $alwaysShowSearchResultsMainDetails}
									{translate text="Not Supplied" isPublicFacing=true}
								{/if}
							</div>

					{/if}
				{/if}

				{if !empty($showLanguages) && $summLanguage}

						<div class="result-label col-sm-4 col-xs-12">{translate text="Language" isPublicFacing=true} </div>
						<div class="result-value col-sm-8 col-xs-12">
							{if is_array($summLanguage)}
								{implode subject=$summLanguage glue=', ' translate=true isPublicFacing=true isMetadata=true}
							{else}
								{translate text=$summLanguage isPublicFacing=true isMetadata=true}
							{/if}
						</div>

				{/if}

				{include file="GroupedWork/relatedLists.tpl" isSearchResults=true}

				{include file="GroupedWork/readingHistoryIndicator.tpl" isSearchResults=true}

				{* Short Mobile Entry for Formats when there aren't hidden formats *}
				<div class="visible-xs">

					{* Determine if there were hidden Formats for this entry *}
					{assign var=hasHiddenFormats value=false}
					{foreach from=$relatedManifestations item=relatedManifestation}
					{if $relatedManifestation->hasHiddenFormats()}
						{assign var=hasHiddenFormats value=true}
					{/if}
					{/foreach}

					{* If there weren't hidden formats, show this short Entry (mobile view only). The exception is single format manifestations, they
					   won't have any hidden formats and will be displayed *}
					{if empty($hasHiddenFormats) && count($relatedManifestations) != 1}
						<div class="hidethisdiv{$summId|escape} result-label col-sm-4 col-xs-12">
							{translate text="Formats" isPublicFacing=true}
						</div>
						<div class="hidethisdiv{$summId|escape} result-value col-sm-8 col-xs-12">
							<a onclick="$('#relatedManifestationsValue{$summId|escape},.hidethisdiv{$summId|escape}').toggleClass('hidden-xs');return false;" role="button">
								{implode subject=$relatedManifestations|@array_keys glue=", "}
							</a>
						</div>
					{/if}

				</div>

				{* Formats Section *}

					<div class="{if empty($hasHiddenFormats) && count($relatedManifestations) != 1}hidden-xs {/if}col-xs-12" id="relatedManifestationsValue{$summId|escape}">
						{* Hide Formats section on mobile view, unless there is a single format or a format has been selected by the user *}
						{* relatedManifestationsValue ID is used by the Formats button *}

						{include file="GroupedWork/relatedManifestations.tpl" id=$summId workId=$summId}
					</div>


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
