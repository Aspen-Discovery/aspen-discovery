{strip}
	{assign var="vSummAuthor" value=""}
	{if !empty($summAuthor)}
		{assign var="vSummAuthor" value="by $summAuthor"}
	{/if}

    {if $accessibleBrowseCategories == '1' && $action != 'Results' && !$isForSearchResults}
	<div class="swiper-slide browse-thumbnail {$coverStyle}">
		<a onclick="return AspenDiscovery.GroupedWork.showGroupedWorkInfo('{$summId}', {if !empty($browseCategoryId)}'{$browseCategoryId}'{/if});" href="{$summUrl}">
			<img src="{$bookCoverUrlMedium}" alt="{$summTitle|escape}" title="{$summTitle|escape}" class="{$coverStyle}" loading="lazy">
			<div class="swiper-lazy-preloader"></div>
		</a>
	</div>
    {else}
		{if $browseMode == '1'}
			<div class="browse-list grid-item {$coverStyle} {if $browseStyle == 'grid'}browse-grid-style col-tn-6 col-xs-6 col-sm-6 col-md-4 col-lg-3{/if}">
				<a onclick="return AspenDiscovery.GroupedWork.showGroupedWorkInfo('{$summId}', '{$browseCategoryId}');" href="{$summUrl}">
					<img class="img-responsive" src="{$bookCoverUrl}" alt="{$summTitle|escape} {$vSummAuthor|escape}" title="{$summTitle|escape} {$vSummAuthor|escape}">
					<div class="info">{if !empty($isNew)}<span class="new-result-badge">{translate text="New!" isPublicFacing=true}</span><br/>{/if}<strong>{$summTitle|truncate:40}</strong><span>{$vSummAuthor|truncate:40}</span></div>
				</a>
			</div>

		{else}{*Default Browse Mode (covers) *}
			<div class="browse-thumbnail grid-item {$coverStyle} {if $browseStyle == 'grid'}col-tn-6 col-xs-4 col-sm-4 col-md-3 col-lg-2{/if}">
				<a onclick="return AspenDiscovery.GroupedWork.showGroupedWorkInfo('{$summId}', {if !empty($browseCategoryId)}'{$browseCategoryId}'{/if});" href="{$summUrl}">
					{if !empty($isNew)}<span class="browse-cover-badge">{translate text="New!" isPublicFacing=true}</span> {/if}
					<div>
						<img src="{$bookCoverUrlMedium}" alt="{$summTitle|escape} {$vSummAuthor|escape}" title="{$summTitle|escape} {$vSummAuthor|escape}" class="{$coverStyle} browse-{$browseStyle} {if $browseCategoryRatingsMode != 0}ratings-on{/if}">
					</div>
				</a>
				{if !empty($showRatings) && $browseCategoryRatingsMode != 0}
					<div class="browse-rating{if $browseCategoryRatingsMode == 2} rater{/if}"
					{if $browseCategoryRatingsMode == 1} onclick="return AspenDiscovery.GroupedWork.showReviewForm(this, '{$summId}');" onkeypress="return AspenDiscovery.GroupedWork.showReviewForm(this, '{$summId}');" style="cursor: pointer" title="{translate text="Write a Review" inAttribute=true isPublicFacing=true}" role="button" tabindex="0" {/if}
					{if $browseCategoryRatingsMode == 2}
						{* AJAX rater data fields *}
						{*{if !empty($ratingData.user)}data-user_rating="{$ratingData.user}" {/if}*}{* Don't show user ratings in browse results because the results get cached so shouldn't be particular to a single user.*}
						data-average_rating="{$ratingData.average}" data-id="{$summId}"
						data-show_review="{$showComments}"
					{/if}
					>
					<span class="ui-rater-starsOff" style="width:90px">
	{* Don't show a user's ratings in browse results because the results get cached so shouldn't be particular to a single user.*}
	{*					{if !empty($ratingData.user)}
							<span class="ui-rater-starsOn userRated" style="width:{math equation="90*rating/5" rating=$ratingData.user}px"></span>
						{else}*}
							<span class="ui-rater-starsOn" style="width:{math equation="90*rating/5" rating=$ratingData.average}px"></span>
						{*{/if}*}
					</span>
					</div>
				{/if}
			</div>
		{/if}
	{/if}
{/strip}