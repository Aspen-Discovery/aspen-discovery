{strip}
	{if $browseMode == '1'}
		<div class="browse-list grid-item">
			<a onclick="return AspenDiscovery.GroupedWork.showGroupedWorkInfo('{$summId}', '{$browseCategoryId}')" href="{$summUrl}">
				<img class="img-responsive" src="{$bookCoverUrl}" alt="{$summTitle} by {$summAuthor}" title="{$summTitle} by {$summAuthor}">
				<div><strong>{$summTitle}</strong><br> by {$summAuthor}</div>
			</a>
		</div>

	{else}{*Default Browse Mode (covers) *}
		<div class="browse-thumbnail grid-item">
			<a onclick="return AspenDiscovery.GroupedWork.showGroupedWorkInfo('{$summId}','{$browseCategoryId}')" href="{$summUrl}">
				<div>
					<img src="{$bookCoverUrlMedium}" alt="{$summTitle} by {$summAuthor}" title="{$summTitle} by {$summAuthor}">
				</div>
			</a>
			{if $showRatings && $browseCategoryRatingsMode != 0}
				<div class="browse-rating{if $browseCategoryRatingsMode == 2} rater{/if}"
				{if $browseCategoryRatingsMode == 1} onclick="return AspenDiscovery.GroupedWork.showReviewForm(this, '{$summId}');" onkeypress="return AspenDiscovery.GroupedWork.showReviewForm(this, '{$summId}');" style="cursor: pointer" title="{translate text="Write a Review" inAttribute=true isPublicFacing=true}" role="button" tabindex="0" {/if}
				{if $browseCategoryRatingsMode == 2}
					{* AJAX rater data fields *}
					{*{if $ratingData.user}data-user_rating="{$ratingData.user}" {/if}*}{* Don't show user ratings in browse results because the results get cached so shouldn't be particular to a single user.*}
					data-average_rating="{$ratingData.average}" data-id="{$summId}"
					data-show_review="{$showComments}"
				{/if}
				>
				<span class="ui-rater-starsOff" style="width:90px">
{* Don't show a user's ratings in browse results because the results get cached so shouldn't be particular to a single user.*}
{*					{if $ratingData.user}
						<span class="ui-rater-starsOn userRated" style="width:{math equation="90*rating/5" rating=$ratingData.user}px"></span>
					{else}*}
						<span class="ui-rater-starsOn" style="width:{math equation="90*rating/5" rating=$ratingData.average}px"></span>
					{*{/if}*}
				</span>
				</div>
			{/if}
		</div>
	{/if}
{/strip}

