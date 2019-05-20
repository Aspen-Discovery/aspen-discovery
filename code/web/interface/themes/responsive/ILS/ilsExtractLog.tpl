{strip}
	<div id="main-content" class="col-md-12">
		<h3>ILS Export Log</h3>
		<hr>

		<h4>Filter by</h4>

		<form class="navbar form-inline row">
			<div class="form-group col-xs-5">
				<span class="pull-right">
					<label for="pageSize" class="control-label">Entries Per Page&nbsp;</label>
					<select id="pageSize" name="pageSize" class="pageSize form-control input-sm" onchange="VuFind.changePageSize()">
						<option value="30"{if $recordsPerPage == 30} selected="selected"{/if}>30</option>
						<option value="50"{if $recordsPerPage == 50} selected="selected"{/if}>50</option>
						<option value="75"{if $recordsPerPage == 75} selected="selected"{/if}>75</option>
						<option value="100"{if $recordsPerPage == 100} selected="selected"{/if}>100</option>
					</select>
				</span>
			</div>
		</form>
		<div>
			<table class="logEntryDetails table table-bordered table-striped">
				<thead>
				<tr><th>Id</th><th>Indexing Profile</th><th>Started</th><th>Last Update</th><th>Finished</th><th>Elapsed</th><th>Total Products</th><th>Num Errors</th><th>Products Added</th><th>Products Deleted</th><th>Products Updated</th><th>Notes</th></tr>
				</thead>
				<tbody>
				{foreach from=$logEntries item=logEntry}
					<tr>
						<td>{$logEntry->id}</td>
						<td>{$logEntry->indexingProfile}</td>
						<td>{$logEntry->startTime|date_format:"%D %T"}</td>
						<td>{$logEntry->lastUpdate|date_format:"%D %T"}</td>
						<td>{$logEntry->endTime|date_format:"%D %T"}</td>
						<td>{$logEntry->getElapsedTime()}</td>
						<td>{$logEntry->numProducts}</td>
						<td>{$logEntry->numErrors}</td>
						<td>{$logEntry->numAdded}</td>
						<td>{$logEntry->numDeleted}</td>
						<td>{$logEntry->numUpdated}</td>
						<td><a href="#" onclick="return VuFind.Admin.showExtractNotes('{$logEntry->id}', 'ils');">Show Notes</a></td>
					</tr>
				{/foreach}
				</tbody>
			</table>
		</div>

		{if $pageLinks.all}<div class="text-center">{$pageLinks.all}</div>{/if}
	</div>
{/strip}