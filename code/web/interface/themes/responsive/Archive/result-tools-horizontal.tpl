{strip}
	{* Requires $summId & $summUrl to be defined by calling template *}
	<div class="result-tools-horizontal btn-toolbar" role="toolbar">
		{if $showMoreInfo !== false && $summUrl}
			<div class="btn-group btn-group-sm">
				<a href="{$summUrl}" class="btn btn-sm btn-tools" onclick="AspenDiscovery.OpenArchives.trackUsage('{$summId}')" target="_blank">{translate text="More Info"}</a>
			</div>
		{/if}
		{if $showFavorites == 1}
			<div class="btn-group btn-group-sm">
				<button onclick="return AspenDiscovery.Account.showSaveToListForm(this, 'Islandora', '{$summId|escape}');" class="btn btn-sm btn-tools">{translate text='Add to list'}</button>
			</div>
		{/if}

	</div>

{/strip}