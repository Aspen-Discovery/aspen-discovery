{strip}
<div class="review" id="review_{$review->id}">
	<div class="reviewHeader">
			<h5>{if strlen($review->getDisplayName()) > 0}{translate text='By' isPublicFacing=true} <cite>{$review->getDisplayName()} </cite>{/if}
			{if $review->dateRated != null && $review->dateRated > 0}
				- <span class="reviewDate">{$review->dateRated|date_format}</span>
			{/if}
			{if !empty($showRatings) && $review->rating > 0}
				{* Display the rating the user gave it. *}
				<span class="ui-rater-starsOff" style="width:90px">
					<span class="ui-rater-starsOn{if !empty($loggedIn) && ($review->userId == $activeUserId)} userRated{/if}" style="width:{math equation="90*rating/5" rating=$review->rating}px"></span>
				</span>
			{/if}
			{if !empty($loggedIn) && ($review->userId == $activeUserId || in_array('Moderate User Reviews', $userPermissions))}
				&nbsp;<span onclick='return AspenDiscovery.GroupedWork.deleteReview("{$id|escape:"url"}", "{$review->id}");' class="btn btn-danger btn-xs">&times; {translate text='Delete' isPublicFacing=true}</span>
			{/if}</h5>
	</div>
	{if $review->review}
		<blockquote style="white-space: pre-line">{$review->review|escape:"html"}</blockquote>
	{/if}
</div>
{/strip}