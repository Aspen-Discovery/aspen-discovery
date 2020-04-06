{strip}
	{if $showEmailThis == 1 || $showShareOnExternalSites == 1}
	<div class="share-tools">
		<span class="share-tools-label hidden-inline-xs">{translate text=SHARE}</span>
		{if $showEmailThis == 1}
{*			<a href="#" onclick="return AspenDiscovery.Events.showEmailForm(this, '{$recordDriver->getId()|escape:"url"}')" title="Share via email">*}
{*				<img src="{img filename='email-icon.png'}" alt="Email this">*}
{*			</a>*}
		{/if}
		{if $showShareOnExternalSites}
{*			<a href="http://twitter.com/home?status={$recordDriver->getTitle()|urlencode}+{$eventUrl}" target="_blank" title="Share on Twitter">*}
{*				<img src="{img filename='twitter-icon.png'}" alt="Share on Twitter">*}
{*			</a>*}
			<a href="http://www.facebook.com/sharer/sharer.php?u={$openArchiveUrl|escape:'url'}" target="_blank" title="Share on Facebook">
				<img src="{img filename='facebook-icon.png'}" alt="Share on Facebook">
			</a>

			<a href="http://www.pinterest.com/pin/create/button/?url={$openArchiveUrl}&media={$recordDriver->getBookcoverUrl('medium', true)}&description=Pin%20on%20Pinterest" target="_blank" title="Pin on Pinterest">
				<img src="{img filename='pinterest-icon.png'}" alt="Pin on Pinterest">
			</a>
		{/if}
	</div>
	{/if}
{/strip}