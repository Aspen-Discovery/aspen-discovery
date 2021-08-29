{strip}
	{foreach from=$syndicatedReviews item=providerList key=provider}
		{foreach from=$providerList item=review}
			{if $review.Content}
			<div class='review'>
				{if $review.Source}
					<h4 class='reviewSource'>{$review.Source}</h4>
				{/if}
				<div>
				<p class="reviewContent">{$review.Content}</p>
				<div class='reviewCopyright'><small>{$review.Copyright}</small></div>

				{if $provider == "syndetics"}
					<div class='reviewProvider'><small>{translate text="Powered by Syndetics" isPublicFacing=true}</small></div>
				{elseif $provider == "contentCafe"}
					<div class='reviewProvider'><small>{translate text="Powered by Content Cafe" isPublicFacing=true}</small></div>
				{/if}
			</div>
			{/if}
			</div>
		{/foreach}
	{foreachelse}
		<p>{translate text="No syndicated reviews currently exist." isPublicFacing=true}</p>
	{/foreach}
{/strip}