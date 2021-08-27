{strip}
	{if $showEmailThis == 1 || $showShareOnExternalSites == 1}
	<div class="share-tools">
		<span class="share-tools-label hidden-inline-xs">{translate text="SHARE" isPublicFacing=true}</span>
		{if $showEmailThis == 1}
			<a href="#" onclick="return AspenDiscovery.GroupedWork.showEmailForm(this, '{$recordDriver->getPermanentId()|escape:"url"}')" title="{translate text="Share via email" inAttribute=true}">
				<i class="fas fa-envelope-square fa-2x fa-fw"></i>
			</a>
		{/if}
		{if $showShareOnExternalSites}
			<a href="https://twitter.com/intent/tweet?text={$recordDriver->getTitle()|urlencode}+{$url}/GroupedWork/{$recordDriver->getPermanentId()}/Home" target="_blank" title="{translate text="Share on Twitter" inAttribute=true}">
				<i class="fab fa-twitter-square fa-2x fa-fw"></i>
			</a>
			<a href="http://www.facebook.com/sharer/sharer.php?u={$url}/{$recordDriver->getLinkUrl()|escape:'url'}" target="_blank" title="{translate text="Share on Facebook" inAttribute=true}" aria-label="Share {$summTitle|escape:css}, by {$recordDriver->getPrimaryAuthor()|escape} on Facebook">
				<i class="fab fa-facebook-square fa-2x fa-fw"></i>
			</a>

			<a href="http://www.pinterest.com/pin/create/button/?url={$url}/{$recordDriver->getLinkUrl()}&media={$url}{$recordDriver->getBookcoverUrl('medium')}&description=Pin%20on%20Pinterest" target="_blank" title="{translate text="Pin on Pinterest" inAttribute=true}" aria-label="Pin {$summTitle|escape:css}, by {$recordDriver->getPrimaryAuthor()|escape} on Pinterest">
				<i class="fab fa-pinterest-square fa-2x fa-fw"></i>
			</a>
		{/if}
	</div>
	{/if}
{/strip}