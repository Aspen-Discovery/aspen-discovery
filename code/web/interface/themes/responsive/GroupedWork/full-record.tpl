{include file="GroupedWork/load-full-record-view-enrichment.tpl"}
{strip}
	<div class="col-xs-12">
		{* Search Navigation *}
		{include file="GroupedWork/search-results-navigation.tpl"}

		{* Display Title *}
		<h1 class="notranslate">
			{if empty($recordDriver->getShortTitle())}
				{$recordDriver->getTitle()|removeTrailingPunctuation|escape}
			{else}
				{$recordDriver->getShortTitle()|removeTrailingPunctuation|escape}{if $recordDriver->getSubtitle()}
					: {$recordDriver->getSubtitle()|removeTrailingPunctuation|escape}
				{/if}
			{/if}
		</h1>

		{if !$minimalInterface}
		<div class="row">
			<div class="col-xs-4 col-sm-5 col-md-4 col-lg-3 text-center">
				{if $disableCoverArt != 1}
					<div id="recordCover" class="text-center row">
						<a href="#" onclick="return AspenDiscovery.GroupedWork.getLargeCover('{$recordDriver->getPermanentId()}')"><img alt="{translate text='Book Cover' isPublicFacing=true inAttribute=true}" class="img-thumbnail {$coverStyle}" src="{$recordDriver->getBookcoverUrl('medium')}"></a>
					</div>
				{/if}
				{if !empty($showRatings)}
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
						<div class="result-label col-sm-4 col-xs-12">{translate text=Author isPublicFacing=true} </div>
						<div class="result-value col-sm-8 col-xs-12 notranslate">
							<a href='/Author/Home?author="{$recordDriver->getPrimaryAuthor()|escape:"url"}"'>{$recordDriver->getPrimaryAuthor()|highlight}</a>
						</div>
					</div>
				{/if}

				{if !empty($showSeries)}
					<div class="series row" id="seriesPlaceholder{$recordDriver->getPermanentId()}"></div>
				{/if}


				{if !empty($showPublicationDetails)}
					<div class="row">
						<div class="result-label col-sm-4 col-xs-12">{translate text=Publisher isPublicFacing=true} </div>
						<div class="result-value col-sm-8 col-xs-12">
							{if !empty($summPublisher)}
								{$summPublisher}
							{else}
								{translate text="Varies, see individual formats and editions" isPublicFacing=true}
							{/if}
						</div>
					</div>

					<div class="row">
						<div class="result-label col-sm-4 col-xs-12">{translate text="Publication Date" isPublicFacing=true} </div>
						<div class="result-value col-sm-8 col-xs-12">
							{if !empty($summPubDate)}
								{$summPubDate|escape}
							{else}
								{translate text="Varies, see individual formats and editions" isPublicFacing=true}
							{/if}
						</div>
					</div>
				{/if}

				{if !empty($showEditions) && $summEdition}
					<div class="row">
						<div class="result-label col-sm-4 col-xs-12">{translate text="Edition" isPublicFacing=true} </div>
						<div class="result-value col-sm-8 col-xs-12">
							{$summEdition}
						</div>
					</div>
				{/if}

				{if !empty($showAudience)}
					<div class="row result-audience result-{str_replace(" ", "-", join(" ", $recordDriver->getFormats()))|lower}">
						<div class="result-label col-sm-4 col-xs-12">{translate text='Audience' isPublicFacing=true} </div>
						<div class="result-value col-sm-8 col-xs-12">
							{if !empty($summAudience)}
								{$summAudience}
							{else}
								{translate text="Varies, see individual formats and editions" isPublicFacing=true}
							{/if}
						</div>
					</div>
				{/if}

				{if !empty($summLanguage)}
					<div class="row">
						<div class="result-label col-sm-4 col-xs-12">{translate text="Language" isPublicFacing=true} </div>
						<div class="result-value col-sm-8 col-xs-12">
							{if is_array($summLanguage)}
								{implode subject=$summLanguage glue=', ' translate=true isPublicFacing=true isMetadata=true}
							{else}
								{translate text=$summLanguage isPublicFacing=true isMetadata=true}
							{/if}
						</div>
					</div>
				{/if}

				{if !empty($showArInfo) && $summArInfo}
					<div class="row">
						<div class="result-label col-sm-4 col-xs-12">{translate text='Accelerated Reader' isPublicFacing=true} </div>
						<div class="result-value col-sm-8 col-xs-12">
							{$summArInfo}
						</div>
					</div>
				{/if}

				{if !empty($showLexileInfo) && $summLexileInfo}
					<div class="row">
						<div class="result-label col-sm-4 col-xs-12">{translate text='Lexile measure' isPublicFacing=true} </div>
						<div class="result-value col-sm-8 col-xs-12">
							{$summLexileInfo}
						</div>
					</div>
				{/if}

				{if !empty($showFountasPinnell) && $summFountasPinnell}
					<div class="row">
						<div class="result-label col-sm-4 col-xs-12">{translate text='Fountas & Pinnell' isPublicFacing=true} </div>
						<div class="result-value col-sm-8 col-xs-12">
							{$summFountasPinnell}
						</div>
					</div>
				{/if}


				{include file="GroupedWork/relatedLists.tpl" isSearchResults=false}

				{include file="GroupedWork/readingHistoryIndicator.tpl" isSearchResults=false}

				{include file="GroupedWork/allManifestations.tpl" relatedManifestations=$recordDriver->getRelatedManifestations() summId=$recordDriver->getPermanentId() workId=$recordDriver->getPermanentId() summTitle=$recordDriver->getTitle()}

				<div class="row">
					<div class="col-xs-12">
						{include file='GroupedWork/result-tools-horizontal.tpl' ratingData=$recordDriver->getRatingData() recordUrl=$recordDriver->getLinkUrl() showMoreInfo=false showNotInterested=false}
					</div>
				</div>

			</div>
		</div>
		{/if}

		<div class="row">
			{include file=$moreDetailsTemplate}
		</div>

	</div>
{/strip}
