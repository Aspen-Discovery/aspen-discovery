{strip}
<div class="userReviewList" id="userReviewList">
	{* Pull in comments from a separate file -- this separation allows the same template
		 to be used for refreshing this list via AJAX. *}
	{foreach from=$userReviews item=review}
		{if $review->review}{* Don't show any items that are only user ratings. (it is implied that show user reviews is on.) *}
			{include file="GroupedWork/view-user-review.tpl"}
			{assign var="atLeastOneReview" value=true}
		{/if}
	{foreachelse}
		<p>No borrower reviews currently exist.</p>
	{/foreach}
	{if !$atLeastOneReview}{* This is for the case when all the reviews were actually just ratings w/o a review included *}
		<p>No borrower reviews currently exist.</p>
	{/if}
</div>
{/strip}