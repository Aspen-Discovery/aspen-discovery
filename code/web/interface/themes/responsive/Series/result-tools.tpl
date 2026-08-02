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
		{if !empty($loggedIn) && (in_array('Administer Series', $userPermissions))}
			{if $seriesVersion == 2}
				<div class="btn-group btn-group-sm">
					<button onclick="return AspenDiscovery.Series.getGroupSeriesSearchForm(this, '{$summShortId|escape}', '{$searchId}', '{if empty($page)}1{else}{$page}{/if}');" class="btn btn-sm btn-tools">{translate text="Group With" isPublicFacing=true}</button>
				</div>
			{/if}
			{if $seriesObjectId != -1}
				<div class="btn-group btn-group-sm">
					<button value="editList" id="FavEdit" class="btn btn-sm btn-tools" onclick="return AspenDiscovery.Series.editAction({$seriesObjectId})">{translate text='Edit Series' isPublicFacing=true}</button>
				</div>
			{/if}
		{/if}
	{/if}

	<div class="btn-group btn-group-sm">
		{include file="Series/share-tools.tpl"}
	</div>
</div>
