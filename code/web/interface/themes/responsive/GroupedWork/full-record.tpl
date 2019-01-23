{include file="GroupedWork/load-full-record-view-enrichment.tpl"}
{strip}
	<div class="col-xs-12">
		{* Search Navigation *}
		{include file="GroupedWork/search-results-navigation.tpl"}

		{* Display Title *}
		<h2 class="notranslate">
			{$recordDriver->getTitleShort()|removeTrailingPunctuation|escape}{if $recordDriver->getSubtitle()}
				: {$recordDriver->getSubtitle()|removeTrailingPunctuation|escape}
			{/if}
		</h2>

		<div class="row">
			<div class="col-xs-4 col-sm-5 col-md-4 col-lg-3 text-center">
				{if $disableCoverArt != 1}
					<div id="recordcover" class="text-center row">
						<img alt="{translate text='Book Cover'}" class="img-thumbnail" src="{$recordDriver->getBookcoverUrl('medium')}">
					</div>
				{/if}
				{if $showRatings}
					{include file="GroupedWork/title-rating-full.tpl" ratingClass="" showFavorites=0 ratingData=$recordDriver->getRatingData() showNotInterested=false hideReviewButton=true}
				{/if}
			</div>
			<div id="main-content" class="col-xs-8 col-sm-7 col-md-8 col-lg-9">

				{if $error}{* TODO: Does this get used? *}
					<div class="row">
						<div class="alert alert-danger">
							{$error}
						</div>
					</div>
				{/if}

				{if $recordDriver->getPrimaryAuthor()}
					<div class="row">
						<div class="result-label col-tn-3">Author: </div>
						<div class="col-tn-9 result-value notranslate">
							<a href='{$path}/Author/Home?author="{$recordDriver->getPrimaryAuthor()|escape:"url"}"'>{$recordDriver->getPrimaryAuthor()|highlight}</a>
						</div>
					</div>
				{/if}

				{assign var=indexedSeries value=$recordDriver->getIndexedSeries()}
				{assign var=series value=$recordDriver->getSeries()}
				{if $showSeries && ($series || $indexedSeries)}
					<div class="series row">
						<div class="result-label col-tn-3">{translate text='Series'}:</div>
						<div class="col-tn-9 result-value">
							{if $series}
								<a href="{$path}/GroupedWork/{$recordDriver->getPermanentId()}/Series">{$series.seriesTitle}</a>{if $series.volume} volume {$series.volume}{/if}<br/>
							{/if}
							{if $indexedSeries}
								{if count($indexedSeries) >= 5}
									{assign var=showMoreSeries value="true"}
								{/if}
								{foreach from=$indexedSeries item=seriesItem name=loop}
									{if !isset($series.seriesTitle) || ((strpos(strtolower($seriesItem.seriesTitle), strtolower($series.seriesTitle)) === false) && (strpos(strtolower($series.seriesTitle), strtolower($seriesItem.seriesTitle)) === false))}
										<a href="{$path}/Search/Results?basicType=Series&lookfor=%22{$seriesItem.seriesTitle|removeTrailingPunctuation|escape:"url"}%22">{$seriesItem.seriesTitle|removeTrailingPunctuation|escape}</a>{if $seriesItem.volume} volume {$seriesItem.volume}{/if}<br/>
										{if $showMoreSeries && $smarty.foreach.loop.iteration == 3}
											<a onclick="$('#moreSeries_{$recordDriver->getPermanentId()}').show();$('#moreSeriesLink_{$recordDriver->getPermanentId()}').hide();" id="moreSeriesLink_{$summId}">More Series...</a>
											<div id="moreSeries_{$recordDriver->getPermanentId()}" style="display:none">
										{/if}
									{/if}
								{/foreach}
								{if $showMoreSeries}
									</div>
								{/if}
							{/if}
						</div>
					</div>
				{/if}

				{if $showPublicationDetails}
					<div class="row">
						<div class="result-label col-tn-3">Publisher: </div>
						<div class="result-value col-tn-9">
							{if $summPublisher}
								{$summPublisher}
							{else}
								Varies, see individual formats and editions
							{/if}
						</div>
					</div>

					<div class="row">
						<div class="result-label col-tn-3">Pub. Date: </div>
						<div class="result-value col-tn-9">
							{if $summPubDate}
								{$summPubDate|escape}
							{else}
								Varies, see individual formats and editions
							{/if}
						</div>
					</div>
				{/if}

				{if $showEditions && $summEdition}
					<div class="row">
						<div class="result-label col-tn-3">Edition: </div>
						<div class="result-value col-tn-9">
							{$summEdition}
						</div>
					</div>
				{/if}

				{if $summLanguage}
					<div class="row">
						<div class="result-label col-tn-3">Language: </div>
						<div class="result-value col-tn-9">
							{if is_array($summLanguage)}
								{', '|implode:$summLanguage}
							{else}
								{$summLanguage}
							{/if}
						</div>
					</div>
				{/if}

				{if $showArInfo && $summArInfo}
					<div class="row">
						<div class="result-label col-tn-3">{translate text='Accelerated Reader'}: </div>
						<div class="result-value col-tn-9">
							{$summArInfo}
						</div>
					</div>
				{/if}

				{if $showLexileInfo && $summLexileInfo}
					<div class="row">
						<div class="result-label col-tn-3">{translate text='Lexile measure'}: </div>
						<div class="result-value col-tn-9">
							{$summLexileInfo}
						</div>
					</div>
				{/if}

				{if $showFountasPinnell && $summFountasPinnell}
					<div class="row">
						<div class="result-label col-tn-3">{translate text='Fountas &amp; Pinnell'}: </div>
						<div class="result-value col-tn-9">
							{$summFountasPinnell}
						</div>
					</div>
				{/if}

				{include file="GroupedWork/relatedManifestations.tpl" relatedManifestations=$recordDriver->getRelatedManifestations()}

				<div class="row">
					{include file='GroupedWork/result-tools-horizontal.tpl' summId=$recordDriver->getPermanentId() summShortId=$recordDriver->getPermanentId() ratingData=$recordDriver->getRatingData() recordUrl=$recordDriver->getLinkUrl() showMoreInfo=false}
				</div>

			</div>
		</div>

		<div class="row">
			{include file=$moreDetailsTemplate}
		</div>

	</div>


	<span class="Z3988" title="{$recordDriver->getOpenURL()|escape}" style="display:none">&nbsp;</span>
{/strip}
