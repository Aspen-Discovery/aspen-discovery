{strip}
	<div id="main-content" class="col-md-12">
		<h1>{translate text="Website Indexing Log"}</h1>
		<hr>

		<h4>{translate text="Filter by" isAdminFacing=true}</h4>

        {include file='Admin/exportLogFilters.tpl'}
		<div id="websiteExportLogContainer">
			<table class="logEntryDetails table table-condensed table-hover">
				<thead>
					<tr><th>{translate text="Id"}</th><th>{translate text="Name"}</th><th>{translate text="Started"}</th><th>{translate text="Last Update"}</th><th>{translate text="Finished"}</th><th>{translate text="Elapsed"}</th><th>{translate text="Total Pages"}</th><th>{translate text="Num Errors"}</th><th>{translate text="Pages Added"}</th><th>{translate text="Pages Deleted"}</th><th>{translate text="Pages Updated"}</th><th>{translate text="Notes"}</th></tr>
				</thead>
				<tbody>
					{foreach from=$logEntries item=logEntry}
						<tr>
							<td>{$logEntry->id}</td>
							<td>{$logEntry->websiteName}</td>
							<td>{$logEntry->startTime|date_format:"%D %T"}</td>
							<td>{$logEntry->lastUpdate|date_format:"%D %T"}</td>
							<td>{$logEntry->endTime|date_format:"%D %T"}</td>
							<td>{$logEntry->getElapsedTime()}</td>
							<td>{$logEntry->numPages}</td>
							<td>{$logEntry->numErrors}</td>
							<td>{$logEntry->numAdded}</td>
							<td>{$logEntry->numDeleted}</td>
							<td>{$logEntry->numUpdated}</td>
							<td><a href="#" onclick="return AspenDiscovery.Admin.showExtractNotes('{$logEntry->id}', 'website');">{translate text="Show Notes"}</a></td>
						</tr>
					{/foreach}
				</tbody>
			</table>
		</div>

		{if $pageLinks.all}<div class="text-center">{$pageLinks.all}</div>{/if}
	</div>
{/strip}