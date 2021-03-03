{strip}
	<div id="main-content" class="col-md-12">
		<h1>Open Archives Extract Log</h1>

        {include file='Admin/exportLogFilters.tpl'}
		<div>
			<table class="logEntryDetails table table-bordered table-striped">
				<thead>
				<tr><th>Id</th><th>Started</th><th>Last Update</th><th>Finished</th><th>Elapsed</th><th>Collection Name</th><th>Total Products</th><th>Num Errors</th><th>Products Added</th><th>Products Deleted</th><th>Products Updated</th><th>Products Skipped</th><th>Notes</th></tr>
				</thead>
				<tbody>
				{foreach from=$logEntries item=logEntry}
					<tr>
						<td>{$logEntry->id}</td>
						<td>{$logEntry->startTime|date_format:"%D %T"}</td>
						<td>{$logEntry->lastUpdate|date_format:"%D %T"}</td>
						<td>{$logEntry->endTime|date_format:"%D %T"}</td>
						<td>{$logEntry->getElapsedTime()}</td>
						<td>{$logEntry->collectionName}</td>
						<td>{$logEntry->numProducts}</td>
						<td>{$logEntry->numErrors}</td>
						<td>{$logEntry->numAdded}</td>
						<td>{$logEntry->numDeleted}</td>
						<td>{$logEntry->numUpdated}</td>
						<td>{$logEntry->numSkipped}</td>
						<td><a href="#" onclick="return AspenDiscovery.Admin.showExtractNotes('{$logEntry->id}', 'open_archives');">Show Notes</a></td>
					</tr>
				{/foreach}
				</tbody>
			</table>
		</div>

		{if $pageLinks.all}<div class="text-center">{$pageLinks.all}</div>{/if}
	</div>
{/strip}