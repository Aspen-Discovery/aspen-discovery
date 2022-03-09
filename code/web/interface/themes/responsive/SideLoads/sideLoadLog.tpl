{strip}
	<div id="main-content" class="col-md-12">
		<h1>{translate text="Side Load Processing Log" isAdminFacing=true}</h1>

        {include file='Admin/exportLogFilters.tpl'}
		<div>
			<table class="logEntryDetails table table-bordered table-striped">
				<thead>
				<tr>
					<th>{translate text="Id" isAdminFacing=true}</th>
					<th>{translate text="Started" isAdminFacing=true}</th>
					<th>{translate text="Last Update" isAdminFacing=true}</th>
					<th>{translate text="Finished" isAdminFacing=true}</th>
					<th>{translate text="Elapsed" isAdminFacing=true}</th>
					<th>{translate text="Side Loads Updated" isAdminFacing=true}</th>
					<th>{translate text="Total Products" isAdminFacing=true}</th>
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
						<td>{$logEntry->startTime|date_format:"%D %T"}</td>
						<td>{$logEntry->lastUpdate|date_format:"%D %T"}</td>
						<td>{$logEntry->endTime|date_format:"%D %T"}</td>
						<td>{$logEntry->getElapsedTime()}</td>
						<td>{$logEntry->sideLoadsUpdated}</td>
						<td>{$logEntry->numProducts}</td>
						<td>{$logEntry->numErrors}</td>
						<td>{$logEntry->numAdded}</td>
						<td>{$logEntry->numDeleted}</td>
						<td>{$logEntry->numUpdated}</td>
						<td>{$logEntry->numSkipped}</td>
						<td><a href="#" onclick="return AspenDiscovery.Admin.showExtractNotes('{$logEntry->id}', 'sideload');">{translate text="Show Notes" isAdminFacing=true}</a></td>
					</tr>
				{/foreach}
				</tbody>
			</table>
		</div>

		{if $pageLinks.all}<div class="text-center">{$pageLinks.all}</div>{/if}
	</div>
{/strip}