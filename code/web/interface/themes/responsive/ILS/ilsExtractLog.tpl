{strip}
	<div id="main-content" class="col-md-12">
		<h1>{translate text="ILS Export Log" isAdminFacing=true}</h1>
		<p class="alert alert-info">
			{translate text="The ILS Export log shows records extraction of MARC records from the ILS.  For most ILSs, this is done on a continuous basis as we detect changes within the ILS. A full extract can be done by selecting <b>Run Full Update</b> within the Indexing Profile." isAdminFacing=true}
		</p>

        {include file='Admin/exportLogFilters.tpl'}
		<div class="adminTableRegion fixed-height-table">
			<table class="adminTable table table-condensed table-hover table-condensed smallText table-sticky">
				<thead>
					<tr>
						<th>{translate text="Id" isAdminFacing=true}</th>
						<th>{translate text="Indexing Profile" isAdminFacing=true}</th>
						<th>{translate text="Full Update?" isAdminFacing=true}</th>
						<th>{translate text="Started" isAdminFacing=true}</th>
						<th>{translate text="Last Update" isAdminFacing=true}</th>
						<th>{translate text="Current Id" isAdminFacing=true}</th>
						<th>{translate text="Finished" isAdminFacing=true}</th>
						<th>{translate text="Elapsed" isAdminFacing=true}</th>
						<th>{translate text="Products Regrouped" isAdminFacing=true}</th>
						<th>{translate text="Products Changed After Grouping" isAdminFacing=true}</th>
						<th>{translate text="Total Products" isAdminFacing=true}</th>
						<th>{translate text="Records With Invalid MARC" isAdminFacing=true}</th>
						<th>{translate text="Invalid Records" isAdminFacing=true}</th>
						<th>{translate text="Num Errors" isAdminFacing=true}</th>
						<th>{translate text="Products Added" isAdminFacing=true}</th>
						<th>{translate text="Products Deleted" isAdminFacing=true}</th>
						<th>{translate text="Products Updated" isAdminFacing=true}</th>
						<th>{translate text="Products Skipped" isAdminFacing=true}</th>
						<th>{translate text="Notes" isAdminFacing=true}</th>
					</tr>
				</thead>
				<tbody>
				{foreach from=$logEntries item=logEntry}
					<tr>
						<td>{$logEntry->id}</td>
						<td>{$logEntry->indexingProfile}</td>
						<td>{if $logEntry->isFullUpdate == 1}{translate text='Yes' isAdminFacing=true}{else}{translate text='No' isAdminFacing=true}{/if}</td>
						<td>{$logEntry->startTime|date_format:"%D %T"}</td>
						<td>{$logEntry->lastUpdate|date_format:"%D %T"}</td>
						<td>{$logEntry->currentId}</td>
						<td>{$logEntry->endTime|date_format:"%D %T"}</td>
						<td>{$logEntry->getElapsedTime()}</td>
						<td>{$logEntry->numRegrouped}</td>
						<td>{$logEntry->numChangedAfterGrouping}</td>
						<td>{$logEntry->numProducts}</td>
						<td>{$logEntry->numRecordsWithInvalidMarc}</td>
						<td>{$logEntry->numInvalidRecords}</td>
						<td>{$logEntry->numErrors}</td>
						<td>{$logEntry->numAdded}</td>
						<td>{$logEntry->numDeleted}</td>
						<td>{$logEntry->numUpdated}</td>
						<td>{$logEntry->numSkipped}</td>
						<td><a href="#" onclick="return AspenDiscovery.Admin.showExtractNotes('{$logEntry->id}', 'ils');">{translate text="Show Notes" isAdminFacing=true}</a></td>
					</tr>
				{/foreach}
				</tbody>
			</table>
		</div>

		{if $pageLinks.all}<div class="text-center">{$pageLinks.all}</div>{/if}
	</div>
{/strip}