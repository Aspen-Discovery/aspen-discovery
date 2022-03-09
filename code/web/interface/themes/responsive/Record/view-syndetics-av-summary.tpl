<dl>
	{if count($avSummaryData.trackListing) > 0}
		<dt>{translate text="Track Listing" isPublicFacing=true}</dt>
		<dd>
			{foreach from=$avSummaryData.trackListing item=track}
				<div class='track'>
				<span class='trackNumber'>{$track.number}</span>
				<span class='trackName'>{$track.name}</span>
				</div>
			{/foreach}
		</dd>
	{/if}
</dl>