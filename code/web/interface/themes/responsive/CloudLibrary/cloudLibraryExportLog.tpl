{strip}
	<div id="main-content" class="col-md-12">
		<h1>Cloud Library Export Log</h1>

		{include file='Admin/exportLogFilters.tpl'}
		<div id="exportLogContainer">
			<table class="logEntryDetails table table-condensed table-hover">
				<thead>
					<tr><th>Id</th><th>Setting ID</th><th>Started</th><th>Last Update</th><th>Finished</th><th>Elapsed</th><th>Total Products</th><th>Num Errors</th><th>Products Added</th><th>Products Deleted</th><th>Products Updated</th><th>Num Availability Changes</th><th>Num Metadata Changes</th><th>Notes</th></tr>
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
							<td><a href="#" onclick="return AspenDiscovery.Admin.showExtractNotes('{$logEntry->id}', 'cloud_library');">Show Notes</a></td>
						</tr>
					{/foreach}
				</tbody>
			</table>
		</div>

		{if $pageLinks.all}<div class="text-center">{$pageLinks.all}</div>{/if}
	</div>
{/strip}