{include file="GroupedWork/load-full-record-view-enrichment.tpl"}

{strip}
	<div class="col-xs-12">
		{* Search Navigation *}
		{include file="GroupedWork/search-results-navigation.tpl"}

		{if $error && !$recordDriver}
			<div class="row">
				<div class="alert alert-danger">
					{$error}
				</div>
			</div>
		{else}
			{* Display Title *}
			<h1>
				{*{$recordDriver->getTitle()|escape}*}{* // ever a case when the trailing punction is needed? *}
				{* Title includes the title section *}
				{$recordDriver->getTitle()|removeTrailingPunctuation}
				{if $recordDriver->getFormats()}
					<br>
					<small>
						({implode subject=$recordDriver->getFormats() glue=", ", translate=true isPublicFacing=true})
						{if $recordDriver->isClosedCaptioned()}
							&nbsp;<i class="fas fa-closed-captioning"></i>
						{/if}
					</small>
				{/if}
			</h1>

			<div class="row">
				<div class="col-xs-4 col-sm-5 col-md-4 col-lg-3 text-center">
					{if $disableCoverArt != 1}
						<div id="recordCover" class="text-center row">
							<img alt="{translate text='Book Cover' isPublicFacing=true inAttribute=true}" class="img-thumbnail {$coverStyle}" src="{$recordDriver->getBookcoverUrl('medium')}">
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

					<div class="row">

						<div id="record-details-column" class="col-xs-12 col-sm-12 col-md-9">
							{include file="Record/view-title-details.tpl"}
						</div>

						<div id="recordTools" class="col-xs-12 col-sm-6 col-md-3">
							{include file="Record/result-tools.tpl" showMoreInfo=false summShortId=$shortId module=$activeRecordProfileModule summId=$id summTitle=$title recordUrl=$recordUrl}
						</div>
					</div>

					<div class="row">
						<div class="col-xs-12">
						{include file='GroupedWork/result-tools-horizontal.tpl' ratingData=$recordDriver->getRatingData() recordUrl=$recordDriver->getLinkUrl() showMoreInfo=false showNotInterested=false}
						</div>
					</div>

				</div>
			</div>

			<div class="row">
				{include file=$moreDetailsTemplate}
			</div>
		{/if}
	</div>
{/strip}
