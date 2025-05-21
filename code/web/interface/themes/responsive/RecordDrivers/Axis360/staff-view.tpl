{strip}
	{if !empty($recordDriver)}
		<div class="row">
			<div class="col-xs-12">
				<a href="/GroupedWork/{$recordDriver->getPermanentId()}" class="btn btn-sm btn-default">Go To Grouped Work</a>
				{if !empty($bookcoverInfo)}
					<button onclick="return AspenDiscovery.GroupedWork.reloadCover('{$recordDriver->getPermanentId()}', '{$bookcoverInfo->recordType}', '{$bookcoverInfo->recordId}')" class="btn btn-sm btn-default">{translate text="Reload Cover" isAdminFacing=true}</button>
					{if !empty($loggedIn) && in_array('Upload Covers', $userPermissions)}
						<button onclick="return AspenDiscovery.GroupedWork.getUploadCoverForm('{$recordDriver->getPermanentId()}', '{$bookcoverInfo->recordType}', '{$bookcoverInfo->recordId}')" class="btn btn-sm btn-default">{translate text="Upload Cover by from Computer" isAdminFacing=true}</button>
						<button onclick="return AspenDiscovery.GroupedWork.getUploadCoverFormByURL('{$recordDriver->getPermanentId()}', '{$bookcoverInfo->recordType}', '{$bookcoverInfo->recordId}')" class="btn btn-sm btn-default">{translate text="Upload Cover by URL" isAdminFacing=true}</button>
						<button onclick="return AspenDiscovery.GroupedWork.clearUploadedCover('{$recordDriver->getPermanentId()}', '{$bookcoverInfo->recordType}', '{$bookcoverInfo->recordId}')" class="btn btn-sm btn-default">{translate text="Clear Uploaded Cover" isAdminFacing=true}</button>
					{/if}
				{/if}
				<button onclick="return AspenDiscovery.GroupedWork.reloadEnrichment('{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default" >{translate text="Reload Enrichment" isAdminFacing=true}</button>
				{if !empty($loggedIn) && in_array('Force Reindexing of Records', $userPermissions)}
					<button onclick="return AspenDiscovery.GroupedWork.forceReindex('{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default">{translate text="Force Reindex" isAdminFacing=true}</button>
					<button onclick="return AspenDiscovery.GroupedWork.viewDebugging('{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default">{translate text="Diagnostics" isAdminFacing=true}</button>
				{/if}
				{if !empty($loggedIn) && in_array('Set Grouped Work Display Information', $userPermissions)}
					<button onclick="return AspenDiscovery.GroupedWork.getDisplayInfoForm('{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default">{translate text="Set Display Info" isAdminFacing=true}</button>
					<button onclick="return AspenDiscovery.GroupedWork.getGroupWithForm(this, '{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default">{translate text="Group With Work" isAdminFacing=true}</button>
				{/if}
				{if !empty($loggedIn) && in_array('Manually Group and Ungroup Works', $userPermissions)}
					<button onclick="return AspenDiscovery.GroupedWork.ungroupRecord(this, '{$recordDriver->getIdWithSource()}')" class="btn btn-sm btn-default">{translate text="Ungroup" isAdminFacing=true}</button>
				{/if}
			</div>
		</div>
	{/if}

	{include file="RecordDrivers/GroupedWork/grouping-information.tpl"}

	{if !empty($axis360Extract)}
		<h3>{translate text="Boundless Extract Information" isPublicFacing=true}</h3>
		<pre>
		{$axis360Extract|print_r}
	</pre>
	{/if}
{/strip}