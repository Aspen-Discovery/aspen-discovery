{include file="GroupedWork/load-full-record-view-enrichment.tpl"}

{strip}
	<div class="col-xs-12">
		{* Search Navigation *}
		{include file="GroupedWork/search-results-navigation.tpl"}

		{* Display Title *}
		<h2>
			{$recordDriver->getTitle()|escape}
			{if $recordDriver->getSubTitle()}: {$recordDriver->getSubTitle()|escape}{/if}
			{if $recordDriver->getTitleSection()} {$recordDriver->getTitleSection()|escape}{/if}
			{if $recordDriver->getFormats()}
				<br/><small>({implode subject=$recordDriver->getFormats() glue=", "})</small>
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

				<div class="row">

					<div id="record-details-column" class="col-xs-12 col-sm-12 col-md-9">
						{include file="Hoopla/view-title-details.tpl"}
					</div>

					<div id="recordTools" class="col-xs-12 col-sm-6 col-md-3">
						<div class="btn-toolbar">
							<div class="btn-group btn-group-vertical btn-block">
								{* Options for the user to view online or download *}
								{foreach from=$summaryActions item=link}
									<a href="{if $link.url}{$link.url}{else}#{/if}" {if $link.onclick && strlen($link.onclick) > 0}onclick="{$link.onclick}"{/if} class="btn btn-sm btn-primary"{if $link.url} target="_blank"{/if}>{$link.title}</a>&nbsp;
								{/foreach}
							</div>
						</div>
					</div>

					<div class="row">
						<div class="col-xs-12">
							{include file='GroupedWork/result-tools-horizontal.tpl' summId=$recordDriver->getPermanentId() summShortId=$recordDriver->getPermanentId() ratingData=$recordDriver->getRatingData() recordUrl=$recordDriver->getLinkUrl() showMoreInfo=false}
						</div>
					</div>

				</div>


			</div>
		</div>

		<div class="row">
			{include file=$moreDetailsTemplate}
		</div>

		<span class="Z3988" title="{$recordDriver->getOpenURL()|escape}" style="display:none">&nbsp;</span>
	</div>
{/strip}
