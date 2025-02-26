<div class="result-tools-horizontal btn-toolbar" role="toolbar">
	<div class="btn-group btn-group-sm">
		{if !empty($showMoreInfo)}
			<a href="/Series/{$summShortId}" class="btn btn-sm">{translate text="More Info" isPublicFacing=true}</a>
		{/if}
	</div>

	{if $showFavorites == 1 && (empty($offline) || $enableEContentWhileOffline)}
		<div class="btn-group btn-group-sm">
			<button onclick="return AspenDiscovery.Account.showSaveToListForm(this, 'Series', '{$summShortId|escape}');" class="btn btn-sm addToListBtn">{translate text="Add to List" isPublicFacing=true}</button>
		</div>
	{/if}

	<div class="btn-group btn-group-sm">
		{include file="Series/share-tools.tpl"}
	</div>
</div>
