{include file="GroupedWork/load-full-record-view-enrichment.tpl"}
{strip}
	<div class="col-xs-12">
		{* Search Navigation *}
		{include file="GroupedWork/search-results-navigation.tpl"}

		{* Display Title *}
		<h1 class="notranslate">
			{$recordDriver->getShortTitle()|removeTrailingPunctuation|escape}{if $recordDriver->getSubtitle()}
				: {$recordDriver->getSubtitle()|removeTrailingPunctuation|escape}
			{/if}
		</h1>

		<div class="row">
			<div class="col-xs-4 col-sm-5 col-md-4 col-lg-3 text-center">
				{if $disableCoverArt != 1}
					<div id="recordCover" class="text-center row">
						<a href="#" onclick="return AspenDiscovery.GroupedWork.getLargeCover('{$recordDriver->getPermanentId()}')"><img alt="{translate text='Book Cover' isPublicFacing=true inAttribute=true}" class="img-thumbnail" src="{$recordDriver->getBookcoverUrl('medium')}"></a>
					</div>
				{/if}
				{if $showRatings}
					{include file="GroupedWork/title-rating-full.tpl" showFavorites=0 ratingData=$recordDriver->getRatingData() showNotInterested=false hideReviewButton=true}
				{/if}
			</div>
			<div id="main-content" class="col-xs-8 col-sm-7 col-md-8 col-lg-9">

				{if !empty($error)}
					<div class="row">
						<div class="alert alert-danger">
							{$error}
						</div>
					</div>
				{/if}

				{if $recordDriver->getPrimaryAuthor()}
					<div class="row">
						<div class="result-label col-tn-3">{translate text=Author isPublicFacing=true} </div>
						<div class="col-tn-9 result-value notranslate">
							<a href='/Author/Home?author="{$recordDriver->getPrimaryAuthor()|escape:"url"}"'>{$recordDriver->getPrimaryAuthor()|highlight}</a>
						</div>
					</div>
				{/if}

				{if $showSeries}
					<div class="series row" id="seriesPlaceholder{$recordDriver->getPermanentId()}"></div>
				{/if}


				{if $showPublicationDetails}
					<div class="row">
						<div class="result-label col-tn-3">{translate text=Publisher isPublicFacing=true} </div>
						<div class="result-value col-tn-9">
							{if $summPublisher}
								{$summPublisher}
							{else}
								{translate text="Varies, see individual formats and editions"}
							{/if}
						</div>
					</div>

					<div class="row">
						<div class="result-label col-tn-3">{translate text="Publication Date" isPublicFacing=true} </div>
						<div class="result-value col-tn-9">
							{if $summPubDate}
								{$summPubDate|escape}
							{else}
								{translate text="Varies, see individual formats and editions" isPublicFacing=true}
							{/if}
						</div>
					</div>
				{/if}

				{if $showEditions && $summEdition}
					<div class="row">
						<div class="result-label col-tn-3">{translate text="Edition" isPublicFacing=true} </div>
						<div class="result-value col-tn-9">
							{$summEdition}
						</div>
					</div>
				{/if}

				{if $summLanguage}
					<div class="row">
						<div class="result-label col-tn-3">{translate text="Language" isPublicFacing=true} </div>
						<div class="result-value col-tn-9">
							{if is_array($summLanguage)}
								{implode subject=$summLanguage glue=', ' translate=true isPublicFacing=true}
							{else}
								{$summLanguage|translate}
							{/if}
						</div>
					</div>
				{/if}

				{if !empty($showArInfo) && $summArInfo}
					<div class="row">
						<div class="result-label col-tn-3">{translate text='Accelerated Reader' isPublicFacing=true} </div>
						<div class="result-value col-tn-9">
							{$summArInfo}
						</div>
					</div>
				{/if}

				{if !empty($showLexileInfo) && $summLexileInfo}
					<div class="row">
						<div class="result-label col-tn-3">{translate text='Lexile measure' isPublicFacing=true} </div>
						<div class="result-value col-tn-9">
							{$summLexileInfo}
						</div>
					</div>
				{/if}

				{if !empty($showFountasPinnell) && $summFountasPinnell}
					<div class="row">
						<div class="result-label col-tn-3">{translate text='Fountas & Pinnell' isPublicFacing=true} </div>
						<div class="result-value col-tn-9">
							{$summFountasPinnell}
						</div>
					</div>
				{/if}

				{include file="GroupedWork/relatedLists.tpl"}

				{include file="GroupedWork/readingHistoryIndicator.tpl"}

				{include file="GroupedWork/relatedManifestations.tpl" relatedManifestations=$recordDriver->getRelatedManifestations() workId=$recordDriver->getPermanentId()}

				<div class="row">
					{include file='GroupedWork/result-tools-horizontal.tpl' ratingData=$recordDriver->getRatingData() recordUrl=$recordDriver->getLinkUrl() showMoreInfo=false}
				</div>

			</div>
		</div>

		<div class="row">
			{include file=$moreDetailsTemplate}
		</div>

	</div>
{/strip}
