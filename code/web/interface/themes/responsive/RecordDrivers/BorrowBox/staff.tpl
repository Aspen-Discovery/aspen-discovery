{if !empty($recordDriver)}
	<div class="row">
		<div class="col-xs-12">
			<a href="/GroupedWork/{$recordDriver->getPermanentId()}" class="btn btn-sm btn-default">{translate text="Go To Grouped Work" isPublicFacing=true}</a>
			{if !empty($bookcoverInfo)}
				<button onclick="return AspenDiscovery.GroupedWork.reloadCover('{$recordDriver->getPermanentId()}', '{$bookcoverInfo->recordType}', '{$bookcoverInfo->recordId}')" class="btn btn-sm btn-default">{translate text="Reload Cover" isAdminFacing=true}</button>
				{if !empty($loggedIn) && in_array('Upload Covers', $userPermissions)}
					<button onclick="return AspenDiscovery.GroupedWork.getUploadCoverForm('{$recordDriver->getPermanentId()}', '{$bookcoverInfo->recordType}', '{$bookcoverInfo->recordId}')" class="btn btn-sm btn-default">{translate text="Upload Cover by from Computer" isAdminFacing=true}</button>
					<button onclick="return AspenDiscovery.GroupedWork.getUploadCoverFormByURL('{$recordDriver->getPermanentId()}', '{$bookcoverInfo->recordType}', '{$bookcoverInfo->recordId}')" class="btn btn-sm btn-default">{translate text="Upload Cover by URL" isAdminFacing=true}</button>
					<button onclick="return AspenDiscovery.GroupedWork.clearUploadedCover('{$recordDriver->getPermanentId()}', '{$bookcoverInfo->recordType}', '{$bookcoverInfo->recordId}')" class="btn btn-sm btn-default">{translate text="Clear Uploaded Cover" isAdminFacing=true}</button>
				{/if}
			{/if}
			<button onclick="return AspenDiscovery.GroupedWork.reloadEnrichment('{$recordDriver->getGroupedWorkId()}')" class="btn btn-sm btn-default" >{translate text="Reload Enrichment" isAdminFacing=true}</button>
			{if !empty($loggedIn) && in_array('Force Reindexing of Records', $userPermissions)}
				<button onclick="return AspenDiscovery.GroupedWork.forceReindex('{$recordDriver->getGroupedWorkId()}')" class="btn btn-sm btn-default">{translate text="Force Reindex" isAdminFacing=true}</button>
				<button onclick="return AspenDiscovery.GroupedWork.viewDebugging('{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default">{translate text="Diagnostics" isAdminFacing=true}</button>
			{/if}
			{if !empty($loggedIn) && in_array('Set Grouped Work Display Information', $userPermissions)}
				<button onclick="return AspenDiscovery.GroupedWork.getDisplayInfoForm('{$recordDriver->getGroupedWorkId()}')" class="btn btn-sm btn-default">{translate text="Set Display Info" isAdminFacing=true}</button>
			{/if}
			{if !empty($loggedIn) && in_array('Manually Group and Ungroup Works', $userPermissions)}
				<div class="btn-group">
					<button type="button" class="btn btn-sm btn-default dropdown-toggle" data-toggle="dropdown">
						{translate text="Grouping Options" isAdminFacing=true} <span class="caret"></span>
					</button>
					<ul class="dropdown-menu">
						<li><a onclick="return AspenDiscovery.GroupedWork.getGroupWithForm(this, '{$recordDriver->getGroupedWorkId()}')">
							{translate text="Group with Work" isAdminFacing=true}
						</a></li>
						<li><a onclick="return AspenDiscovery.GroupedWork.getMoveRecordForm(this, '{$recordDriver->getIdWithSource()}', '{$recordDriver->getGroupedWorkId()}')">
							{translate text="Move Record" isAdminFacing=true}
						</a></li>
						<li><a onclick="return AspenDiscovery.GroupedWork.ungroupRecord(this, '{$recordDriver->getIdWithSource()}')">
							{translate text="Ungroup Record" isAdminFacing=true}
						</a></li>
					</ul>
				</div>
			{/if}
		</div>
	</div>
{/if}

{include file="RecordDrivers/GroupedWork/grouping-information.tpl"}

{if !empty($borrowBoxProduct)}
	<div class="row">
		<div class="result-label col-xs-3">{translate text="Date Added" isPublicFacing=true}</div>
		<div class="col-xs-9 result-value">
			{$borrowBoxProduct->dateAdded|format_date_locale:"medium":"medium"}
		</div>
	</div>
	<div class="row">
		<div class="result-label col-xs-3">{translate text="Date Updated" isPublicFacing=true}</div>
		<div class="col-xs-9 result-value">
			{$borrowBoxProduct->dateUpdated|format_date_locale:"medium":"medium"}
		</div>
	</div>
	{if $borrowBoxProduct->deleted}
		<div class="row">
			<div class="result-label col-xs-3">{translate text="Deleted" isPublicFacing=true}</div>
			<div class="col-xs-9 result-value">
				{$borrowBoxProduct->dateDeleted|format_date_locale:"medium":"medium"}
			</div>
		</div>
	{/if}
	<div class="row">
		<div class="result-label col-xs-3">{translate text="Last Metadata Check" isPublicFacing=true}</div>
		<div class="col-xs-9 result-value">
			{$borrowBoxProduct->lastMetadataCheck|format_date_locale:"medium":"medium"}
		</div>
	</div>
	<div class="row">
		<div class="result-label col-xs-3">{translate text="Last Metadata Change" isPublicFacing=true}</div>
		<div class="col-xs-9 result-value">
			{$borrowBoxProduct->lastMetadataChange|format_date_locale:"medium":"medium"}
		</div>
	</div>
	<div class="row">
		<div class="result-label col-xs-3">{translate text="Last Availability Check" isPublicFacing=true}</div>
		<div class="col-xs-9 result-value">
			{$borrowBoxProduct->lastAvailabilityCheck|format_date_locale:"medium":"medium"}
		</div>
	</div>
	<div class="row">
		<div class="result-label col-xs-3">{translate text="Last Availability Change" isPublicFacing=true}</div>
		<div class="col-xs-9 result-value">
			{$borrowBoxProduct->lastAvailabilityChange|format_date_locale:"medium":"medium"}
		</div>
	</div>
{/if}
