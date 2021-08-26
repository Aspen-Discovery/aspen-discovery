{strip}
	{if $recordDriver}
		<div class="row">
			<div class="col-xs-12">
				<a href="/GroupedWork/{$recordDriver->getPermanentId()}" class="btn btn-sm btn-default">Go To Grouped Work</a>
				<button onclick="return AspenDiscovery.GroupedWork.reloadCover('{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default">Reload Cover</button>
				{if $loggedIn && in_array('Upload Covers', $userPermissions)}
					<button onclick="return AspenDiscovery.GroupedWork.getUploadCoverForm('{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default">{translate text="Upload Cover by from Computer"}</button>
					<button onclick="return AspenDiscovery.GroupedWork.getUploadCoverFormByURL('{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default">{translate text="Upload Cover by URL"}</button>
				{/if}
				<button onclick="return AspenDiscovery.GroupedWork.reloadEnrichment('{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default" >Reload Enrichment</button>
				{if $loggedIn && in_array('Force Reindexing of Records', $userPermissions)}
					<button onclick="return AspenDiscovery.GroupedWork.forceReindex('{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default">Force Reindex</button>
				{/if}
				{if $loggedIn && in_array('Set Grouped Work Display Information', $userPermissions)}
					<button onclick="return AspenDiscovery.GroupedWork.getDisplayInfoForm('{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default">{translate text="Set Display Info"}</button>
				{/if}
				{if $loggedIn && in_array('Manually Group and Ungroup Works', $userPermissions)}
					<button onclick="return AspenDiscovery.GroupedWork.getGroupWithForm(this, '{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default">Group With Work</button>
					<button onclick="return AspenDiscovery.GroupedWork.ungroupRecord(this, '{$recordDriver->getIdWithSource()}')" class="btn btn-sm btn-default">Ungroup</button>
				{/if}
			</div>
		</div>
	{/if}

	{include file="RecordDrivers/GroupedWork/grouping-information.tpl"}

	{if $hooplaExtract}
		<h3>Hoopla Extract Information</h3>
		<pre>
		{$hooplaExtract|print_r}
	</pre>
	{/if}

{/strip}