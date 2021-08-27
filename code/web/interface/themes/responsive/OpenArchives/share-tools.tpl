{strip}
	{if $showEmailThis == 1 || $showShareOnExternalSites == 1}
	<div class="share-tools">
		<span class="share-tools-label hidden-inline-xs">{translate text="SHARE" isPublicFacing=true}</span>
		{if $showEmailThis == 1}
{*			<a href="#" onclick="return AspenDiscovery.Events.showEmailForm(this, '{$recordDriver->getId()|escape:"url"}')" title="Share via email">*}
{*				<img src="{img filename='email-icon.png'}" alt="Email this">*}
{*			</a>*}
		{/if}
		{if $showShareOnExternalSites}
{*			<a href="http://twitter.com/home?status={$recordDriver->getTitle()|urlencode}+{$eventUrl}" target="_blank" title="Share on Twitter">*}
{*				<img src="{img filename='twitter-icon.png'}" alt="Share on Twitter">*}
{*			</a>*}
			<a href="http://www.facebook.com/sharer/sharer.php?u={$openArchiveUrl|escape:'url'}" target="_blank" title="{translate text="Share on Facebook" inAttribute=true isPublicFacing=true}">
				<i class="fab fa-facebook-square fa-2x fa-fw"></i>
			</a>

			<a href="http://www.pinterest.com/pin/create/button/?url={$openArchiveUrl}&media={$recordDriver->getBookcoverUrl('medium', true)}&description=Pin%20on%20Pinterest" target="_blank" title="{translate text="Pin on Pinterest" inAttribute=true isPublicFacing=true}">
				<i class="fab fa-pinterest-square fa-2x fa-fw"></i>
			</a>
		{/if}
	</div>
	{/if}
{/strip}