{if $recordDriver}
	<div class="row">
		<div class="result-label col-xs-2">Grouped Work ID: </div>
		<div class="col-xs-10 result-value">
			{$recordDriver->getPermanentId()}
		</div>
	</div>

	<div class="row">
		<div class="col-xs-12">
			<a href="/GroupedWork/{$recordDriver->getPermanentId()}" class="btn btn-sm btn-default">{translate text="Go To Grouped Work"}</a>
			<button onclick="return AspenDiscovery.GroupedWork.reloadCover('{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default">{translate text="Reload Cover"}</button>
			{if $loggedIn && in_array('Upload Covers', $userPermissions)}
				<button onclick="return AspenDiscovery.GroupedWork.getUploadCoverForm('{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default">{translate text="Upload Cover from Computer"}</button>
				<button onclick="return AspenDiscovery.GroupedWork.getUploadCoverFormByURL('{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default">{translate text="Upload Cover by URL"}</button>
			{/if}
			{if $loggedIn && in_array('Upload PDFs', $userPermissions)}
				<button onclick="return AspenDiscovery.Record.getUploadPDFForm('{$recordDriver->getId()}')" class="btn btn-sm btn-default">{translate text="Upload PDF Version"}</button>
			{/if}
			{if $loggedIn && in_array('Upload Supplemental Files', $userPermissions)}
				<button onclick="return AspenDiscovery.Record.getUploadSupplementalFileForm('{$recordDriver->getId()}')" class="btn btn-sm btn-default">{translate text="Upload Supplemental File"}</button>
			{/if}
			<button onclick="return AspenDiscovery.GroupedWork.reloadEnrichment('{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default" >{translate text="Reload Enrichment"}</button>
			{if $staffClientUrl}
				<a href="{$staffClientUrl}" class="btn btn-sm btn-info" target="_blank"><i class="fas fa-external-link-alt"></i> {translate text="View in Staff Client"}</a>
			{/if}
			{if $classicUrl && $loggedIn && in_array('View ILS records in native OPAC', $userPermissions)}
				<a href="{$classicUrl}" class="btn btn-sm btn-info" target="_blank"><i class="fas fa-external-link-alt"></i> {translate text="View in Native OPAC"}</a>
			{/if}
			{if $loggedIn && in_array('Force Reindexing of Records', $userPermissions)}
				<button onclick="return AspenDiscovery.GroupedWork.forceReindex('{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default">{translate text="Force Reindex"}</button>
			{/if}
			{if $loggedIn && in_array('Set Grouped Work Display Information', $userPermissions)}
				<button onclick="return AspenDiscovery.GroupedWork.getDisplayInfoForm('{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default">{translate text="Set Display Info"}</button>
			{/if}
			{if $loggedIn && in_array('Manually Group and Ungroup Works', $userPermissions)}
				<button onclick="return AspenDiscovery.GroupedWork.getGroupWithForm(this, '{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-default">{translate text="Group With Work"}</button>
				<button onclick="return AspenDiscovery.GroupedWork.ungroupRecord(this, '{$recordDriver->getIdWithSource()}')" class="btn btn-sm btn-default">{translate text="Ungroup"}</button>
			{/if}
			{if $loggedIn && in_array('Download MARC Records', $userPermissions)}
				<a href="/{$recordDriver->getModule()}/{$recordDriver->getId()|escape:"url"}/AJAX?method=downloadMarc" class="btn btn-sm btn-default">{translate text="Download Marc"}</a>
			{/if}
		</div>
	</div>
{/if}

{include file="RecordDrivers/GroupedWork/grouping-information.tpl"}

{if !empty($uploadedPDFs)}
	<h4>{translate text="Uploaded PDFs"}</h4>
	<table class="table-striped table table-condensed notranslate">
		<thead>
			<tr>
				<th>{translate text='Title'}</th>
				<th>{translate text='Path'}</th>
				{if $loggedIn && in_array('Upload PDFs', $userPermissions)}
					<th>{translate text='Actions'}</th>
				{/if}
			</tr>
		</thead>
		<tbody>
		{foreach from=$uploadedPDFs item=uploadedPDF}
			<tr>
				<td>{$uploadedPDF->title|truncate:30}</td>
				<td>{$uploadedPDF->getFileName()}</td>
				{if $loggedIn && in_array('Upload PDFs', $userPermissions)}
					<td><button class="btn btn-sm btn-danger" onclick="AspenDiscovery.Record.deleteUploadedFile('{$recordDriver->getId()}', '{$uploadedPDF->id}')">{translate text="Delete"}</button></td>
				{/if}
			</tr>
		{/foreach}
		</tbody>
	</table>
{/if}

{if !empty($uploadedSupplementalFiles)}
	<h4>{translate text="Uploaded Supplemental Files"}</h4>
	<table class="table-striped table table-condensed notranslate">
		<thead>
		<tr>
			<th>{translate text='Title'}</th>
			<th>{translate text='Path'}</th>
			{if $loggedIn && in_array('Upload Supplemental Files', $userPermissions)}
				<th>{translate text='Actions'}</th>
			{/if}
		</tr>
		</thead>
		<tbody>
		{foreach from=$uploadedSupplementalFiles item=uploadedFile}
			<tr>
				<td>{$uploadedFile->title}</td>
				<td>{$uploadedFile->getFileName()}</td>
				{if $loggedIn && in_array('Upload Supplemental Files', $userPermissions)}
					<td><button class="btn btn-sm btn-danger" onclick="AspenDiscovery.Record.deleteUploadedFile('{$recordDriver->getId()}', '{$uploadedFile->id}')">{translate text="Delete"}</button></td>
				{/if}
			</tr>
		{/foreach}
		</tbody>
	</table>
{/if}

{if $marcRecord}
	<h4>{translate text="Marc Record"}</h4>
	<table class="table-striped table table-condensed notranslate">
		{if !empty($lastMarcModificationTime)}
			<tr>
				<th>{translate text="Last File Modification Time"}</th>
				<td>{$lastMarcModificationTime|date_format:"%b %d, %Y %r"}</td>
			</tr>
		{/if}
	</table>

	<div id="formattedMarcRecord">
		<h3>{translate text="MARC Record"}</h3>
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

