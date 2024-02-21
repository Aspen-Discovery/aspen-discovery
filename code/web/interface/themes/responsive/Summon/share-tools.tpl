{strip}
	{if $showEmailThis == 1 || $showShareOnExternalSites == 1}
	<div class="share-tools">
		<span class="share-tools-label hidden-inline-xs">{translate text="SHARE" isPublicFacing=true}</span>
		{if $showEmailThis == 1}
		{/if}
		{if !empty($showShareOnExternalSites)}
			<a href="http://www.facebook.com/sharer/sharer.php?u={$url}/{$recordDriver->getLinkUrl()|escape:'url'}" target="_blank" title="{translate text="Share on Facebook" inAttribute=true isPublicFacing=true}"  aria-label="{translate text='Share on Facebook' isPublicFacing=true} ({translate text='opens in new window' isPublicFacing=true})">
				<i class="fab fa-facebook-square fa-2x fa-fw"></i>
			</a>

			<a href="http://www.pinterest.com/pin/create/button/?url={$url}/{$recordDriver->getLinkUrl()}&media={$url}{$recordDriver->getBookcoverUrl('large')}&description=Pin%20on%20Pinterest" target="_blank" title="{translate text="Pin on Pinterest" inAttribute=true isPublicFacing=true}"  aria-label="{translate text='Pin on Pinterest' isPublicFacing=true} ({translate text='opens in new window' isPublicFacing=true})">
				<i class="fab fa-pinterest-square fa-2x fa-fw"></i>
			</a>
		{/if}
	</div>
	{/if}
{/strip}