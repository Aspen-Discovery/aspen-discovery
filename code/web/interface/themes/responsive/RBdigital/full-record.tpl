{include file="GroupedWork/load-full-record-view-enrichment.tpl"}

{strip}
	<div class="col-xs-12">
		{* Search Navigation *}
		{include file="GroupedWork/search-results-navigation.tpl"}

		{* Display Title *}
		<h1>
			{$recordDriver->getTitle()|removeTrailingPunctuation|escape}{if $recordDriver->getSubtitle()}: {$recordDriver->getSubtitle()|removeTrailingPunctuation|escape}{/if}
			{if $recordDriver->getFormats()}
				<br><small>({implode subject=$recordDriver->getFormats() glue=", "})</small>
			{/if}
		</h1>

		<div class="row">
			<div class="col-xs-4 col-sm-5 col-md-4 col-lg-3 text-center">
				{if $disableCoverArt != 1}
					<div id="recordCover" class="text-center row">
						<a href="#" onclick="return AspenDiscovery.RBdigital.getLargeCover('{$recordDriver->getIdWithSource()}')"><img alt="{translate text='Book Cover' inAttribute=true}" class="img-thumbnail" src="{$recordDriver->getBookcoverUrl('medium')}"></a>
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
					<div id="record-details-column" class="col-xs-12 col-sm-9">
						{include file="RBdigital/view-title-details.tpl"}
					</div>

					<div id="recordTools" class="col-xs-12 col-sm-6 col-md-3">
						<div class="btn-toolbar">
							<div class="btn-group btn-group-vertical btn-block">
								{* Show hold/checkout button as appropriate *}
								{if $holdingsSummary.showPlaceHold}
									{* Place hold link *}
									<a href="#" class="btn btn-sm btn-block btn-primary" id="placeHold{$recordDriver->getUniqueID()|escape:"url"}" onclick="return AspenDiscovery.RBdigital.placeHold('{$recordDriver->getUniqueID()}')">{translate text="Place Hold"}</a>
								{/if}
								{if $holdingsSummary.showCheckout}
									{* Checkout link *}
									<a href="#" class="btn btn-sm btn-block btn-primary" id="checkout{$recordDriver->getUniqueID()|escape:"url"}" onclick="return AspenDiscovery.RBdigital.checkOutTitle('{$recordDriver->getUniqueID()}')">{translate text="Checkout"}</a>
								{/if}
							</div>
						</div>
					</div>
				</div>

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