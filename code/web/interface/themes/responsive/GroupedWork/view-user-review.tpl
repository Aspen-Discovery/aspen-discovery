{strip}
<div class="review" id="review_{$review->id}">
	<div class="reviewHeader">
			<h5>{translate text='By'} <cite>{if strlen($review->displayName) > 0}{$review->displayName} {else}{$review->fullname} {/if}</cite>
			{if $review->dateRated != null && $review->dateRated > 0}
				on <span class="reviewDate">{$review->dateRated|date_format}</span>
			{/if}
			{if $showRatings && $review->rating > 0}
				{* Display the rating the user gave it. *}
				<span class="ui-rater-starsOff" style="width:90px">
					<span class="ui-rater-starsOn{if $loggedIn && ($review->userid == $activeUserId)} userRated{/if}" style="width:{math equation="90*rating/5" rating=$review->rating}px"></span>
				</span>
			{/if}
			{if $loggedIn && ($review->userid == $activeUserId || array_key_exists('opacAdmin', $userRoles))}
				&nbsp;<span onclick='return VuFind.GroupedWork.deleteReview("{$id|escape:"url"}", "{$review->id}");' class="btn btn-danger btn-xs">&times; {translate text='Delete'}</span>
			{/if}</h5>
	</div>
	{if $review->review}
		<blockquote style="white-space: pre-line">{$review->review|escape:"html"}</blockquote>
	{/if}
</div>
{/strip}