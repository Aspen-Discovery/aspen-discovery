{strip}
	{if $browseMode == 'grid'}
		<div class="{*browse-title *}browse-list">
			<a onclick="return VuFind.GroupedWork.showGroupedWorkInfo('{$summId}', '{$browseCategoryId}')" href="{$summUrl}">
					<img class="img-responsive" src="{$bookCoverUrl}" alt="{$summTitle} by {$summAuthor}" title="{$summTitle} by {$summAuthor}">
				<div><strong>{$summTitle}</strong><br> by {$summAuthor}</div>
			</a>
		</div>

	{else}{*Default Browse Mode (covers) *}
		<div class="browse-thumbnail">
			<a onclick="return VuFind.GroupedWork.showGroupedWorkInfo('{$summId}','{$browseCategoryId}')" href="{$summUrl}">
				<div>
					<img src="{$bookCoverUrlMedium}" alt="{$summTitle} by {$summAuthor}" title="{$summTitle} by {$summAuthor}">
				</div>
			</a>
			{if $showRatings && $browseCategoryRatingsMode != 'none'}
				<div class="browse-rating{if $browseCategoryRatingsMode == 'stars'} rater{/if}"
				{if $browseCategoryRatingsMode == 'popup'} onclick="return VuFind.GroupedWork.showReviewForm(this, '{$summId}');" style="cursor: pointer"{/if}
				{if $browseCategoryRatingsMode == 'stars'}
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

