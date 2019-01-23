{strip}
{if $review.editorialReviewId}
<div class='review'>
	{if $review.source}
		<div class='reviewSource'>{if $review.sourceUrl}<a href='{$review.sourceUrl}'>{/if}{$review.source}{if $review.sourceUrl}</a>{/if}</div>
	{/if}
	<div id = 'review{$review.editorialReviewId}'>
	{if $review.teaser}
		<div class="reviewTeaser" id="editorialReviewTeaser{$review.editorialReviewId}">
			{$review.teaser} <span onclick="$('#editorialReviewTeaser{$review.editorialReviewId}').hide();$('#editorialReviewContent{$review.editorialReviewId}').show();" class='reviewMoreLink'>(more)</span>
		</div>
		<div class="reviewTeaser" id="editorialReviewContent{$review.editorialReviewId}" style='display:none'>
			{$review.review}
		<span onclick="$('#editorialReviewTeaser{$review.editorialReviewId}').show();$('#editorialReviewContent{$review.editorialReviewId}').hide();" class='reviewMoreLink'> (less)</span>
		</div>
	{else}
		<div class="reviewContent">{$review.review}</div>
	{/if}
	</div>
</div>
{/if}
{/strip}