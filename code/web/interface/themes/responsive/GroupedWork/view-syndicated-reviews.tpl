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

				{if $provider == "amazon" || $provider == "amazoneditorial"}
					<div class='reviewProvider'><small><a target="new" href="http://amazon.com/dp/{$isbn}">{translate text="Supplied by Amazon"}</a></small></div>
				{elseif $provider == "syndetics"}
					<div class='reviewProvider'><small>{translate text="Powered by Syndetics"}</small></div>
				{elseif $provider == "contentcafe"}
					<div class='reviewProvider'><small>{translate text="Powered by Content Cafe"}</small></div>
				{/if}
			</div>
			{/if}
			</div>
		{/foreach}
	{foreachelse}
		<p>No syndicated reviews currently exist.</p>
	{/foreach}
{/strip}