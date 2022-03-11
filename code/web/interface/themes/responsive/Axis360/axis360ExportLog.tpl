{strip}
	<div id="main-content" class="col-md-12">
		<h1>{translate text="Axis 360 Export Log" isAdminFacing=true}</h1>

		{include file='Admin/exportLogFilters.tpl'}
		<div id="exportLogContainer adminTableRegion">
			<table class="logEntryDetails table table-condensed table-hover adminTable">
				<thead>
				<tr><th>Id</th><th>{translate text="Setting ID" isAdminFacing=true}</th><th>{translate text="Started" isAdminFacing=true}</th><th>{translate text="Last Update" isAdminFacing=true}</th><th>{translate text="Finished" isAdminFacing=true}</th><th>{translate text="Elapsed" isAdminFacing=true}</th><th>{translate text="Total Products" isAdminFacing=true}</th><th>{translate text="Num Errors" isAdminFacing=true}</th><th>{translate text="Products Added" isAdminFacing=true}</th><th>{translate text="Products Deleted" isAdminFacing=true}</th><th>{translate text="Products Updated" isAdminFacing=true}</th><th>{translate text="Num Availability Changes" isAdminFacing=true}</th><th>{translate text="Num Metadata Changes" isAdminFacing=true}</th><th>{translate text="Notes" isAdminFacing=true}</th></tr>
				</thead>
				<tbody>
					{foreach from=$logEntries item=logEntry}
						<tr>
							<td>{$logEntry->id}</td>
							<td>{$logEntry->settingId}</td>
							<td>{$logEntry->startTime|date_format:"%D %T"}</td>
							<td>{$logEntry->lastUpdate|date_format:"%D %T"}</td>
							<td>{$logEntry->endTime|date_format:"%D %T"}</td>
							<td>{$logEntry->getElapsedTime()}</td>
							<td>{$logEntry->numProducts}</td>
							<td>{$logEntry->numErrors}</td>
							<td>{$logEntry->numAdded}</td>
							<td>{$logEntry->numDeleted}</td>
							<td>{$logEntry->numUpdated}</td>
							<td>{$logEntry->numAvailabilityChanges}</td>
							<td>{$logEntry->numMetadataChanges}</td>
							<td><a href="#" onclick="return AspenDiscovery.Admin.showExtractNotes('{$logEntry->id}', 'axis360');">{translate text="Show Notes" isAdminFacing=true}</a></td>
						</tr>
					{/foreach}
				</tbody>
			</table>
		</div>

		{if $pageLinks.all}<div class="text-center">{$pageLinks.all}</div>{/if}
	</div>
{/strip}