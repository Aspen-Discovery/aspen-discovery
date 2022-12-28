{strip}
	{if !empty($showShareOnExternalSites)}
	<div class="share-tools">
		<span class="share-tools-label hidden-inline-xs">{translate text="SHARE" isPublicFacing=true}</span>
		{if !empty($showShareOnExternalSites)}
			<a href="http://www.facebook.com/sharer/sharer.php?u={$recordUrl|escape:'url'}" target="_blank" title="{translate text="Share on Facebook" inAttribute=true isPublicFacing=true}">
				<i class="fab fa-facebook-square fa-2x fa-fw"></i>
			</a>

			<a href="http://www.pinterest.com/pin/create/button/?url={$recordUrl}&media={$recordDriver->getBookcoverUrl('medium', true)}&description=Pin%20on%20Pinterest" target="_blank" title="{translate text="Pin on Pinterest" inAttribute=true isPublicFacing=true}">
				<i class="fab fa-pinterest-square fa-2x fa-fw"></i>
			</a>
		{/if}
	</div>
	{/if}
{/strip}