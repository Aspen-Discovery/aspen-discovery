{include file="GroupedWork/load-full-record-view-enrichment.tpl"}

{strip}
	<div class="col-xs-12">
		{* Search Navigation *}
		{include file="GroupedWork/search-results-navigation.tpl"}

		{* Display Title *}
		<h1>
			{$recordDriver->getTitle()|escape}
			{if $recordDriver->getSubtitle()}: {$recordDriver->getSubtitle()|escape}{/if}
			{if $recordDriver->getFormats()}
				<br/><small>({implode subject=$recordDriver->getFormats() glue=", "})</small>
			{/if}
		</h1>

		<div class="row">
			<div class="col-xs-4 col-sm-5 col-md-4 col-lg-3 text-center">
				{if $disableCoverArt != 1}
					<div id="recordCover" class="text-center row">
						<img alt="{translate text='Book Cover' inAttribute=true}" class="img-thumbnail" src="{$recordDriver->getBookcoverUrl('medium')}">
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
						{include file="Hoopla/view-title-details.tpl"}
					</div>

					<div id="recordTools" class="col-xs-12 col-sm-6 col-md-3">
						<div class="btn-toolbar">
							<div class="btn-group btn-group-vertical btn-block">
								{* Options for the user to view online or download *}
								{foreach from=$summaryActions item=link}
									<a href="{if $link.url}{$link.url}{else}#{/if}" {if $link.onclick && strlen($link.onclick) > 0}onclick="{$link.onclick}"{/if} class="btn btn-sm {if empty($curAction.btnType)}btn-action{else}{$curAction.btnType}{/if}"{if $link.url} target="_blank"{/if}>{$link.title}</a>&nbsp;
								{/foreach}
							</div>
						</div>
					</div>

					<div class="row">
						<div class="col-xs-12">
							{include file='GroupedWork/result-tools-horizontal.tpl' ratingData=$recordDriver->getRatingData() recordUrl=$recordDriver->getLinkUrl() showMoreInfo=false}
						</div>
					</div>

				</div>


			</div>
		</div>

		<div class="row">
			{include file=$moreDetailsTemplate}
		</div>
	</div>
{/strip}
