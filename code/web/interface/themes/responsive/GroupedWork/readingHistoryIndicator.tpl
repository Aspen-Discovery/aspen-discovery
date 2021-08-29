{if $inReadingHistory}
	<div class="row">
		<div class="result-label col-tn-3">{translate text='Last Checkout' isPublicFacing=true} </div>
		<div class="result-value col-tn-9">
			<span class="readingHistoryIndicator badge">{$lastCheckedOut|date_format:"%b %Y"}</span>
		</div>
	</div>
{/if}