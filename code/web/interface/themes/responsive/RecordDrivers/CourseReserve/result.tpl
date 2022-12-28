{strip}
<div id="record{$summId|escape}" class="resultsList row">
	{if !empty($showCovers)}
		<div class="coversColumn col-xs-3 col-sm-3 col-md-3 col-lg-2 text-center" aria-hidden="true" role="presentation">
			{if $disableCoverArt != 1}
				<a href="/CourseReserves/{$summShortId}" class="alignleft listResultImage" tabindex="-1">
					<img src="{$bookCoverUrl}" class="listResultImage img-thumbnail {$coverStyle}" alt="{$summTitle|removeTrailingPunctuation|highlight|escapeCSS|truncate:180:"..."}">
				</a>
			{/if}
		</div>
	{/if}


	<div class="{if empty($showCovers)}col-xs-12{else}col-xs-9 col-sm-9 col-md-9 col-lg-10{/if}">{* May turn out to be more than one situation to consider here *}
		{* Title Row *}

		<div class="row">
			<div class="col-xs-12">
				<span class="result-index">{$resultIndex})</span>&nbsp;
				<a href="/CourseReserves/{$summShortId}" class="result-title notranslate">
					{if !$summTitle|removeTrailingPunctuation} {translate text='Title not available' isPublicFacing=true}{else}{$summTitle|removeTrailingPunctuation|highlight|truncate:180:"..."}{/if}
				</a>
				{if isset($summScore)}
					&nbsp;(<a href="#" onclick="return AspenDiscovery.showElementInPopup('Score Explanation', '#scoreExplanationValue{$summId|escape}');">{$summScore}</a>)
				{/if}
			</div>
		</div>

		{if !empty($summAuthor)}
			<div class="row">
				<div class="result-label col-tn-3">{translate text="Instructor" isPublicFacing=true} </div>
				<div class="result-value col-tn-9 notranslate">
					{if is_array($summAuthor)}
						{foreach from=$summAuthor item=author}
							{$author|highlight}
						{/foreach}
					{else}
						{$summAuthor|highlight}
					{/if}
				</div>
			</div>
		{/if}

		{if !empty($summNumTitles)}
			<div class="row">
				<div class="result-label col-tn-3">{translate text="Number of Titles" isPublicFacing=true} </div>
				<div class="result-value col-tn-9 notranslate">
					{translate text="%1% titles are in this list." 1=$summNumTitles isPublicFacing=true}
				</div>
			</div>
		{/if}

		<div class="resultActions row">
			{include file='CourseReserves/result-tools.tpl' id=$summId shortId=$shortId module=$summModule summTitle=$summTitle ratingData=$summRating recordUrl=$summUrl}
		</div>
	</div>
</div>
{/strip}