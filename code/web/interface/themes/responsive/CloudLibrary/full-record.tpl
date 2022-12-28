{include file="GroupedWork/load-full-record-view-enrichment.tpl"}

{strip}
	<div class="col-xs-12">
		{* Search Navigation *}
		{include file="GroupedWork/search-results-navigation.tpl"}

		{* Display Title *}
		<h1>
			{$recordDriver->getTitle()|removeTrailingPunctuation}
			{if $recordDriver->getFormats()}
				<br><small>({implode subject=$recordDriver->getFormats() glue=", " translate=true isPublicFacing=true})</small>
			{/if}
		</h1>

		<div class="row">
			<div class="col-xs-4 col-sm-5 col-md-4 col-lg-3 text-center">
				{if $disableCoverArt != 1}
					<div id="recordCover" class="text-center row">
						<a href="#" onclick="return AspenDiscovery.CloudLibrary.getLargeCover('{$recordDriver->getId()}')"><img alt="{translate text='Book Cover' inAttribute=true isPublicFacing=true}" class="img-thumbnail {$coverStyle}" src="{$recordDriver->getBookcoverUrl('medium')}"></a>
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

				<div class="row">
					<div id="record-details-column" class="col-xs-12 col-sm-9">
						{include file="CloudLibrary/view-title-details.tpl"}
					</div>

					<div id="recordTools" class="col-xs-12 col-sm-6 col-md-3">
						<div class="btn-toolbar">
							<div class="btn-group btn-group-vertical btn-block">
								{foreach from=$actions item=curAction}
									{if !empty($curAction.url) && strlen($curAction.url) > 0}
										<a href="{$curAction.url}" class="btn btn-sm {if empty($curAction.btnType)}btn-action{else}{$curAction.btnType}{/if} btn-wrap" onclick="{if !empty($curAction.requireLogin)}return AspenDiscovery.Account.followLinkIfLoggedIn(this, '{$curAction.url}');{/if}" {if !empty($curAction.alt)}title="{translate text=$curAction.alt inAttribute=true isPublicFacing=true}"{/if}>{translate text=$curAction.title isPublicFacing=true}</a>
									{else}
										<a href="#" class="btn btn-sm {if empty($curAction.btnType)}btn-action{else}{$curAction.btnType}{/if} btn-wrap" onclick="{$curAction.onclick}" {if !empty($curAction.alt)}title="{translate text=$curAction.alt inAttribute=true isPublicFacing=true}"{/if}>{translate text=$curAction.title isPublicFacing=true}</a>
									{/if}
								{/foreach}
							</div>
						</div>
					</div>
				</div>

				<div class="row">
					{include file='GroupedWork/result-tools-horizontal.tpl' ratingData=$recordDriver->getRatingData() recordUrl=$recordDriver->getLinkUrl() showMoreInfo=false showNotInterested=false}
				</div>

			</div>
		</div>

		<div class="row">
			{include file=$moreDetailsTemplate}
		</div>
	</div>
{/strip}