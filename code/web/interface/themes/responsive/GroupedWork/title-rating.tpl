{if $showRatings == 1}
	<div class="rate{$summId}">
		<div class="title-rating rater"
						{* AJAX rater data fields *}
         data-user_rating="{$ratingData.user}" data-average_rating="{$ratingData.average}"
         data-id="{$id}"
         data-show_review="{if $showComments  && (!$loggedIn || !$user->noPromptForUserReviews)}1{else}0{/if}"
		>
			<span class="ui-rater-starsOff" style="width:90px">
				{if $ratingData.user}
					<span class="ui-rater-starsOn userRated" style="width:{math equation="90*rating/5" rating=$ratingData.user}px"></span>
				{else}
					<span class="ui-rater-starsOn" style="width:{math equation="90*rating/5" rating=$ratingData.average}px"></span>
				{/if}
			</span>
		</div>
		{if $showNotInterested == true}
			<button id="notInterested{$summId}" class="button notInterested" title="Select if you don't want to see this title recommended to you." onclick="return AspenDiscovery.GroupedWork.markNotInterested('{$summId}');">{translate text="Don't Recommend"}</button>
		{/if}
	</div>
{/if}