{strip}
{foreach from=$reviews item=providerList key=provider}
	{if $provider == 'goodReads'}
		<div class="goodReads">
			<img src="/images/goodreads_logo.png" alt="Reviews from GoodReads">
			<iframe src="{$providerList.sampleReviewsUrl}" width="100%" height="2020px" class="goodReadsIFrame" style="border-width: 0px;"></iframe>
		</div>
	{else}
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
				{/if}
			</div>
			{/if}
			</div>
			<hr>
		{/foreach}
	{/if}
{/foreach}
{/strip}