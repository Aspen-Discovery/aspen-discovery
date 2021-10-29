{if $recordDriver}
	<div class="row">
		<div class="result-label col-xs-2">Grouped Work ID: </div>
		<div class="col-xs-10 result-value">
			{$recordDriver->getPermanentId()}
		</div>
	</div>

	<div class="row">
		<div class="col-xs-12">
			<a href="/GroupedWork/{$recordDriver->getPermanentId()}" class="btn btn-sm btn-default">Go To Grouped Work</a>
			<button onclick="return AspenDiscovery.GroupedWork.reloadCover('{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default">{translate text="Reload Cover" isAdminFacing=true}</button>
			{if $loggedIn && in_array('Upload Covers', $userPermissions)}
				<button onclick="return AspenDiscovery.GroupedWork.getUploadCoverForm('{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default">{translate text="Upload Cover by from Computer" isAdminFacing=true}</button>
				<button onclick="return AspenDiscovery.GroupedWork.getUploadCoverFormByURL('{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default">{translate text="Upload Cover by URL" isAdminFacing=true}</button>
			{/if}
			<button onclick="return AspenDiscovery.GroupedWork.reloadEnrichment('{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default" >{translate text="Reload Enrichment" isAdminFacing=true}</button>
			{if $loggedIn && in_array('Force Reindexing of Records', $userPermissions)}
				<button onclick="return AspenDiscovery.GroupedWork.forceReindex('{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default">{translate text="Force Reindex" isAdminFacing=true}</button>
			{/if}
			{if $loggedIn && in_array('Set Grouped Work Display Information', $userPermissions)}
				<button onclick="return AspenDiscovery.GroupedWork.getDisplayInfoForm('{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default">{translate text="Set Display Info" isAdminFacing=true}</button>
			{/if}
			{if $loggedIn && in_array('Manually Group and Ungroup Works', $userPermissions)}
				<button onclick="return AspenDiscovery.GroupedWork.getGroupWithForm(this, '{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default">{translate text="Group With Work" isAdminFacing=true}</button>
				<button onclick="return AspenDiscovery.GroupedWork.ungroupRecord(this, '{$recordDriver->getIdWithSource()}')" class="btn btn-sm btn-default">{translate text="Ungroup" isAdminFacing=true}</button>
			{/if}
			{if $loggedIn && in_array('Download MARC Records', $userPermissions)}
				<a href="/{$recordDriver->getModule()}/{$id|escape:"url"}/AJAX?method=downloadMarc" class="btn btn-sm btn-default">{translate text="Download Marc" isAdminFacing=true}</a>
			{/if}
		</div>
	</div>
{/if}

{include file="RecordDrivers/GroupedWork/grouping-information.tpl"}

<h4>{translate text="Cloud Library Information" isPublicFacing=true}</h4>
<table class="table-striped table table-condensed notranslate">
	<tr>
		<th>{translate text="First Detected" isPublicFacing=true}</th>
		<td>{$cloudLibraryProduct->dateFirstDetected|date_format:"%b %d, %Y %r"}</td>
	</tr>
	<tr>
		<th>{translate text="Last Change" isPublicFacing=true}</th>
		<td>{$cloudLibraryProduct->lastChange|date_format:"%b %d, %Y %r"}</td>
	</tr>
</table>

{if $marcRecord}
	<div id="formattedMarcRecord">
		<h3>{translate text="MARC Record" isPublicFacing=true}</h3>
		<table class="citation" border="0">
			<tbody>
			{*Output leader*}
			<tr><th>LEADER</th><td colspan="3">{$marcRecord->getLeader()}</td></tr>
			{foreach from=$marcRecord->getFields() item=field}
				{if get_class($field) == "File_MARC_Control_Field"}
					<tr><th>{$field->getTag()}</th><td colspan="3">{$field->getData()|escape|replace:' ':'&nbsp;'}</td></tr>
				{else}
					<tr><th>{$field->getTag()}</th><th>{$field->getIndicator(1)}</th><th>{$field->getIndicator(2)}</th><td>
							{foreach from=$field->getSubfields() item=subfield}
								<strong>|{$subfield->getCode()}</strong>&nbsp;{$subfield->getData()|escape}
							{/foreach}
						</td></tr>
				{/if}
			{/foreach}
			</tbody>
		</table>
	</div>
{/if}
