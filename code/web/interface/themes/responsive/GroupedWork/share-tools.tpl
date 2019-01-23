{strip}
	{if $showEmailThis == 1 || $showShareOnExternalSites == 1}
	<div class="share-tools">
		<span class="share-tools-label hidden-inline-xs">SHARE</span>
		{if false && $showTextThis == 1}
			<a href="#" title="Text Title" onclick="return VuFind.GroupedWork.showSmsForm(this, '{$recordDriver->getPermanentId()|escape:"url"}')" title="Share via text message">
				<img src="{img filename='sms-icon.png'}" alt="Text This">
			</a>
		{/if}
		{if $showEmailThis == 1}
			<a href="#" onclick="return VuFind.GroupedWork.showEmailForm(this, '{$recordDriver->getPermanentId()|escape:"url"}')" title="Share via e-mail">
				<img src="{img filename='email-icon.png'}" alt="E-mail this">
			</a>
		{/if}
		{if $showShareOnExternalSites}
			<a href="http://twitter.com/home?status={$recordDriver->getTitle()|urlencode}+{$url}/GroupedWork/{$recordDriver->getPermanentId()}/Home" target="_blank" title="Share on Twitter">
				<img src="{img filename='twitter-icon.png'}" alt="Share on Twitter">
			</a>
			<a href="http://www.facebook.com/sharer/sharer.php?u={$url}/{$recordDriver->getLinkUrl()|escape:'url'}" target="_blank" title="Share on Facebook">
				<img src="{img filename='facebook-icon.png'}" alt="Share on Facebook">
			</a>

			<a href="http://www.pinterest.com/pin/create/button/?url={$url}/{$recordDriver->getLinkUrl()}&media={$url}{$recordDriver->getBookcoverUrl('medium')}&description=Pin%20on%20Pinterest" target="_blank" title="Pin on Pinterest">
				<img src="{img filename='pinterest-icon.png'}" alt="Pin on Pinterest">
			</a>
		{/if}
	</div>
	{/if}
{/strip}