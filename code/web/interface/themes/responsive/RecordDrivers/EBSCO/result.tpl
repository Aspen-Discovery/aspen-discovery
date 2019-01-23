{strip}
<div id="record{if $summShortId}{$summShortId}{else}{$summId|escape}{/if}" class="resultsList row">
	{if $showCovers}
		<div class="coversColumn col-xs-3 col-sm-3{if !$viewingCombinedResults} col-md-3 col-lg-2{/if} text-center">
			{if $disableCoverArt != 1 && $bookCoverUrlMedium}
				<a href="{$summUrl}">
					<img src="{$bookCoverUrlMedium}" class="listResultImage img-thumbnail" alt="{translate text='Cover Image'}">
				</a>
			{/if}

			{if $showRatings}
				{include file="GroupedWork/title-rating.tpl" ratingClass="" id=$summId ratingData=$summRating}
			{/if}
		</div>
	{/if}

	<div class="{if !$showCovers}col-xs-12{else}col-tn-9 col-sm-9{if !$viewingCombinedResults} col-md-9 col-lg-10{/if}{/if}">
		<div class="row">
			<div class="col-xs-12">
				<span class="result-index">{$resultIndex})</span>&nbsp;
				<a href="{$summUrl}" class="result-title notranslate">
					{if !$summTitle|removeTrailingPunctuation}{translate text='Title not available'}{else}{$summTitle|removeTrailingPunctuation|truncate:180:"..."|highlight}{/if}
				</a>
			</div>
		</div>

		{if $summAuthor}
			<div class="row">
				<div class="result-label col-tn-3">{translate text='Author'}:</div>
				<div class="col-tn-9 result-value">{$summAuthor|escape}</div>
			</div>
		{/if}

		{if strlen($summSourceDatabase)}
			<div class="row hidden-phone">
				<div class="result-label col-tn-3">{translate text='Found in'}:</div>
				<div class="col-tn-9 result-value">{$summSourceDatabase|escape}</div>
			</div>
		{/if}

		{if $summPublicationDates || $summPublishers || $summPublicationPlaces}
			<div class="row">

				<div class="result-label col-tn-3">Published: </div>
				<div class="col-tn-9 result-value">
					{$summPublicationPlaces.0|escape}{$summPublishers.0|escape}{$summPublicationDates.0|escape}
				</div>
			</div>
		{/if}

		{if strlen($summFormats)}
			<div class="row">
				<div class="result-label col-tn-3">Format: </div>
				<div class="col-tn-9 result-value">
					<span class="iconlabel">{translate text=$summFormats}</span>
				</div>
			</div>
		{/if}

		{if $summPhysical}
			<div class="row hidden-phone">
				<div class="result-label col-tn-3">{translate text='Physical Desc'}:</div>
				<div class="col-tn-9 result-value">{$summPhysical.0|escape}</div>
			</div>
		{/if}

		<div class="row hidden-phone">
			<div class="result-label col-tn-3">{translate text='Full Text'}:</div>
			<div class="col-tn-9 result-value">{if $summHasFullText}Yes{else}No{/if}</div>
		</div>

	</div>
</div>
{/strip}