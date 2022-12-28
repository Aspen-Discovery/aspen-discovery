<div class="result-tools-horizontal btn-toolbar" role="toolbar">
	<div class="btn-group btn-group-sm">
		{if !empty($showMoreInfo)}
			<a href="/MyAccount/MyList/{$summShortId}" class="btn btn-sm">{translate text="More Info" isPublicFacing=true}</a>
		{/if}
	</div>

	{if $showFavorites == 1}
		<div class="btn-group btn-group-sm">
			<button onclick="return AspenDiscovery.Account.showSaveToListForm(this, 'Lists', '{$summShortId|escape}');" class="btn btn-sm addToListBtn">{translate text="Add to list" isPublicFacing=true}</button>
		</div>
	{/if}

	<div class="btn-group btn-group-sm">
        {include file="Lists/share-tools.tpl"}
	</div>
</div>