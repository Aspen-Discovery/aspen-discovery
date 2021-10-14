{if $recordDriver}
	<div class="row">
		<div class="col-xs-12">
			<a href="/GroupedWork/{$recordDriver->getPermanentId()}" class="btn btn-sm btn-default">{translate text="Go To Grouped Work" isPublicFacing=true}</a>
			<button onclick="return AspenDiscovery.GroupedWork.reloadCover('{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default">{translate text="Reload Cover" isAdminFacing=true}</button>
			{if $loggedIn && in_array('Upload Covers', $userPermissions)}
				<button onclick="return AspenDiscovery.GroupedWork.getUploadCoverForm('{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default">{translate text="Upload Cover by from Computer" isAdminFacing=true}</button>
				<button onclick="return AspenDiscovery.GroupedWork.getUploadCoverFormByURL('{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default">{translate text="Upload Cover by URL" isAdminFacing=true}</button>
			{/if}
			<button onclick="return AspenDiscovery.GroupedWork.reloadEnrichment('{$recordDriver->getGroupedWorkId()}')" class="btn btn-sm btn-default" >{translate text="Reload Enrichment" isAdminFacing=true}</button>
			{if $loggedIn && in_array('Force Reindexing of Records', $userPermissions)}
				<button onclick="return AspenDiscovery.GroupedWork.forceReindex('{$recordDriver->getGroupedWorkId()}')" class="btn btn-sm btn-default">{translate text="Force Reindex" isAdminFacing=true}</button>
			{/if}
			{if $loggedIn && in_array('Set Grouped Work Display Information', $userPermissions)}
				<button onclick="return AspenDiscovery.GroupedWork.getDisplayInfoForm('{$recordDriver->getGroupedWorkId()}')" class="btn btn-sm btn-default">{translate text="Set Display Info" isAdminFacing=true}</button>
			{/if}
			{if $loggedIn && in_array('Manually Group and Ungroup Works', $userPermissions)}
				<button onclick="return AspenDiscovery.GroupedWork.getGroupWithForm(this, '{$recordDriver->getGroupedWorkId()}')" class="btn btn-sm btn-default">{translate text="Group With Work" isAdminFacing=true}</button>
				<button onclick="return AspenDiscovery.GroupedWork.ungroupRecord(this, '{$recordDriver->getIdWithSource()}')" class="btn btn-sm btn-default">{translate text="Ungroup" isAdminFacing=true}</button>
			{/if}
		</div>
	</div>
{/if}

{include file="RecordDrivers/GroupedWork/grouping-information.tpl"}

<div class="row">
	<div class="result-label col-xs-3">{translate text="Date Added" isPublicFacing=true}</div>
	<div class="col-xs-9 result-value">
		{$overDriveProduct->dateAdded|date_format:"%b %d, %Y %T"}
	</div>
</div>
<div class="row">
	<div class="result-label col-xs-3">{translate text="Date Updated" isPublicFacing=true}</div>
	<div class="col-xs-9 result-value">
		{$overDriveProduct->dateUpdated|date_format:"%b %d, %Y %T"}
	</div>
</div>
<div class="row">
	{if $overDriveProduct->deleted}
		<div class="result-label col-xs-3">{translate text="Deleted" isPublicFacing=true}</div>
		<div class="col-xs-9 result-value">
			{$overDriveProduct->dateDeleted|date_format:"%b %d, %Y %T"}
		</div>
	{/if}
</div>
<div class="row">
	<div class="result-label col-xs-3">{translate text="Last Metadata Check" isPublicFacing=true}</div>
	<div class="col-xs-9 result-value">
		{$overDriveProduct->lastMetadataCheck|date_format:"%b %d, %Y %T"}
	</div>
</div>
<div class="row">
	<div class="result-label col-xs-3">{translate text="Last Metadata Change" isPublicFacing=true}</div>
	<div class="col-xs-9 result-value">
		{$overDriveProduct->lastMetadataChange|date_format:"%b %d, %Y %T"}
	</div>
</div>
<div class="row">
	<div class="result-label col-xs-3">{translate text="Last Availability Check" isPublicFacing=true}</div>
	<div class="col-xs-9 result-value">
		{$overDriveProduct->lastAvailabilityCheck|date_format:"%b %d, %Y %T"}
	</div>
</div>
<div class="row">
	<div class="result-label col-xs-3">{translate text="Last Availability Change" isPublicFacing=true}</div>
	<div class="col-xs-9 result-value">
		{$overDriveProduct->lastAvailabilityChange|date_format:"%b %d, %Y %T"}
	</div>
</div>

{if $overDriveMetaDataRaw}
	<div id="formattedSolrRecord">
		<h3>{translate text="OverDrive MetaData" isPublicFacing=true}</h3>
		{formatJSON subject=$overDriveMetaDataRaw}
	</div>
{/if}