{strip}
	{if !empty($showEmailThis) || !empty($showShareOnExternalSites)}
	<div class="share-tools">
		<span class="share-tools-label hidden-inline-xs">{translate text="SHARE" isPublicFacing=true}</span>
		{if !empty($showEmailThis)}
			<a href="#" onclick="return AspenDiscovery.GroupedWork.showEmailForm(this, '{$recordDriver->getPermanentId()|escape:"url"}')" title="{translate text="Share via email" inAttribute=true isPublicFacing=true}">
				<i class="fas fa-envelope-square fa-2x fa-fw"></i>
			</a>
		{/if}
		{if !empty($showShareOnExternalSites)}
			<a href="https://twitter.com/intent/tweet?text={$recordDriver->getTitle()|urlencode}+{$url}/GroupedWork/{$recordDriver->getPermanentId()}/Home" target="_blank" title="{translate text="Share on Twitter" inAttribute=true isPublicFacing=true}" aria-label="{translate text="Share on Twitter" isPublicFacing=true inAttribute=true} ({translate text="opens in a new window" isPublicFacing=true inAttribute=true})">
				<i class="fab fa-twitter-square fa-2x fa-fw"></i>
			</a>
			<a href="http://www.facebook.com/sharer/sharer.php?u={$url}/{$recordDriver->getLinkUrl()|escape:'url'}" target="_blank" title="{translate text="Share on Facebook" inAttribute=true isPublicFacing=true}" aria-label="{translate text="Share %1%, by %2% on Facebook" 1=$recordDriver->getTitle()|escapeCSS 2=$recordDriver->getTitle()|escapeCSS inAttribute=true isPublicFacing=true translateParameters=false} ({translate text="opens in a new window" isPublicFacing=true inAttribute=true})">
				<i class="fab fa-facebook-square fa-2x fa-fw"></i>
			</a>

			<a href="http://www.pinterest.com/pin/create/button/?url={$url}/{$recordDriver->getLinkUrl()}&media={$url}{$recordDriver->getBookcoverUrl('large')}&description=Pin%20on%20Pinterest" target="_blank" title="{translate text="Pin on Pinterest" inAttribute=true isPublicFacing=true}" aria-label="{translate text="Pin %1%, by %2% on Pinterest" 1=$recordDriver->getTitle()|escapeCSS 2=$recordDriver->getTitle()|escapeCSS inAttribute=true isPublicFacing=true translateParameters=false} ({translate text="opens in a new window" isPublicFacing=true inAttribute=true})">
				<i class="fab fa-pinterest-square fa-2x fa-fw"></i>
			</a>
		{/if}
	</div>
	{/if}
{/strip}