{strip}
	<div id="main-content" class="col-md-12">
		<h1>{translate text='Reindex Log'}</h1>
		<hr>

		<h4>Filter by</h4>

		<form class="navbar form-inline row">
			<div class="form-group col-xs-7">
				<label for="worksLimit" class="control-label">Min Works Processed: </label>
				<input style="width: 125px;" id="worksLimit" name="worksLimit" type="number" min="0" class="form-control" {if !empty($smarty.request.worksLimit)} value="{$smarty.request.worksLimit}"{/if}>
				<button class="btn btn-primary" type="submit">Go</button>
			</div>
			<div class="form-group col-xs-5">
				<span class="pull-right">
					<label for="pageSize" class="control-label">Entries Per Page&nbsp;</label>
					<select id="pageSize" name="pageSize" class="pageSize form-control input-sm" onchange="AspenDiscovery.changePageSize()">
						<option value="30"{if $recordsPerPage == 30} selected="selected"{/if}>30</option>
						<option value="50"{if $recordsPerPage == 50} selected="selected"{/if}>50</option>
						<option value="75"{if $recordsPerPage == 75} selected="selected"{/if}>75</option>
						<option value="100"{if $recordsPerPage == 100} selected="selected"{/if}>100</option>
					</select>
				</span>
			</div>
		</form>
		<div id="econtentAttachLogContainer">
			<table class="logEntryDetails table table-condensed table-hover">
				<thead>
					<tr><th>Id</th><th>Started</th><th>Last Update</th><th>Finished</th><th>Elapsed</th><th>Works Processed</th><th>Num Errors</th><th>Notes</th></tr>
				</thead>
				<tbody>
					{foreach from=$logEntries item=logEntry}
						<tr>
							<td>{$logEntry->id}</td>
							<td>{$logEntry->startTime|date_format:"%D %T"}</td>
							<td>{$logEntry->lastUpdate|date_format:"%D %T"}</td>
							<td>{$logEntry->endTime|date_format:"%D %T"}</td>
							<td>{$logEntry->getElapsedTime()}</td>
							<td>{$logEntry->numWorksProcessed}</td>
							<td>{$logEntry->numErrors}</td>
							<td><a href="#" onclick="return AspenDiscovery.Admin.showReindexNotes('{$logEntry->id}');">Show Notes</a></td>
						</tr>
					{/foreach}
				</tbody>
			</table>
		</div>

		{if $pageLinks.all}<div class="text-center">{$pageLinks.all}</div>{/if}
	</div>
{/strip}