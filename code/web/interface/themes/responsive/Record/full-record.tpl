{include file="GroupedWork/load-full-record-view-enrichment.tpl"}

{strip}
	<div class="col-xs-12">
		{* Search Navigation *}
		{include file="GroupedWork/search-results-navigation.tpl"}

		{if !empty($error) && !$recordDriver}
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
				{if empty($recordDriver->getTitle()) && !empty($recordDriver->get880Title())}
					{$recordDriver->get880Title()|removeTrailingPunctuation}
				{else}
					{$recordDriver->getTitle()|removeTrailingPunctuation}{if !empty($recordDriver->get880Title())} <span class="agrTitle">({$recordDriver->get880Title()|removeTrailingPunctuation})</span>{/if}
				{/if}
				{if $recordDriver->getFormats()}
					<br>
					<small>
						{assign var=formats value=$recordDriver->getFormats()}
						{assign var=hasEmptyFormat value=false}
						{foreach from=$formats item=format}
							{if empty($format)}
								{assign var=hasEmptyFormat value=true}
							{/if}
						{/foreach}
						({if $hasEmptyFormat}{translate text="Missing Format" isPublicFacing=true}{else}{implode subject=$formats glue=", " translate=true isPublicFacing=true}{/if})
						{if $recordDriver->isClosedCaptioned()}
							&nbsp;<i class="fas fa-closed-captioning"></i>
						{/if}
					</small>
				{/if}
			</h1>

			<div class="row">
				<div class="col-xs-4 col-sm-5 col-md-4 col-lg-3 text-center">
					{if $disableCoverArt != 1}
						<a href="#" id="recordCover" class="text-center row" style="display: inline-block;" onclick="return AspenDiscovery.Record.getLargeCover('{$recordDriver->getModule()}', '{$recordDriver->getUniqueID()}')">
							<img alt="{translate text='Book Cover' isPublicFacing=true inAttribute=true}" class="img-thumbnail{if $useOriginalCoverUrls} use-original-covers{/if} {$coverStyle}" src="{$recordDriver->getBookcoverUrl('medium')}" role="presentation">
						</a>
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

					<div class="row">
						<div id="record-details-column" class="col-xs-12 col-sm-12 col-md-9">
							{include file="Record/view-title-details.tpl"}
						</div>

						{if !($recordDriver->hasMultipleVariations())}
							<div id="recordTools" class="col-xs-12 col-sm-6 col-md-3">
								{include file="Record/result-tools.tpl" showMoreInfo=false summShortId=$shortId module=$activeRecordProfileModule summId=$id summTitle=$recordDriver->getTitle()}
							</div>
						{else}
							<div id="multiple-variations-column" class="col-xs-12 col-sm-12 col-md-9">
								{include file="Record/multipleVariationDisplay.tpl" workId=$recordDriver->getPermanentId() summTitle=$recordDriver->getTitle()}
							</div>
						{/if}
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
