{php}$index = 0;{/php}
{strip}
{foreach from=$reviews item=providerList key=provider}
	{if $provider == 'goodReads'}
		<div class="goodReads">
			<img src="/images/goodreads_logo.png" alt="{translate text="Reviews from GoodReads" isPublicFacing=true inAttribute=true}" />
			<iframe src="{$providerList.sampleReviewsUrl}" width="100%" height="2020px" class="goodReadsIFrame" style="border-width: 0px;"></iframe>
		</div>
	{else}
		{foreach from=$providerList item=review}
			{if !empty($review.Content)}
			<div class='review'>
				{if !empty($review.Source)}
					<div class='reviewSource'>{$review.Source}</div>
				{/if}
				<div id = 'review{php}$index ++;{/php}{$index}'>
				{if !empty($review.Teaser)}
					 <div class="reviewTeaser" id="reviewTeaser{php}echo $index;{/php}">
					 {$review.Teaser} <span onclick="$('#reviewTeaser{php}echo $index;{/php}').hide();$('#reviewContent{php}echo $index;{/php}').show();" class='reviewMoreLink'>(more)</span>
					 </div>
					 <div class="reviewTeaser" id="reviewContent{php}echo $index;{/php}" style='display:none'>
					 {$review.Content}
					 <span onclick="$('#reviewTeaser{php}echo $index;{/php}').show();$('#reviewContent{php}echo $index;{/php}').hide();" class='reviewMoreLink'> (less)</span>
					 </div>
				{else}
					 <div class="reviewContent">{$review.Content}</div>
				{/if}
				<div class='reviewCopyright'>{$review.Copyright}</div>

				{if $provider == "syndetics"}
					<div class='reviewProvider'>{translate text="Powered by Syndetics" isPublicFacing=true}</div>
				{/if}
			</div>
			{/if}
			</div>
			<hr/>
		{/foreach}
	{/if}
{/foreach}
{/strip}