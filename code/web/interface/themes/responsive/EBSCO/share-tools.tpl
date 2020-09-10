{strip}
	{if $showEmailThis == 1 || $showShareOnExternalSites == 1}
	<div class="share-tools">
		<span class="share-tools-label hidden-inline-xs">{translate text=SHARE}</span>
		{if $showEmailThis == 1}
		{/if}
		{if $showShareOnExternalSites}
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