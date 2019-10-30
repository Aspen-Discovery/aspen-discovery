{strip}
	<div id="main-content" class="col-md-12">
		<h1>{translate text="Hoopla Export Log"}</h1>

		<form>
			<div class="row">
				<div class="col-sm-5 col-md-4">
					<div class="form-group">
						<label for="pageSize">{translate text='Entries Per Page'}</label>
						<select id="pageSize" name="pageSize" class="pageSize form-control input-sm">
							<option value="30"{if $recordsPerPage == 30} selected="selected"{/if}>30</option>
							<option value="50"{if $recordsPerPage == 50} selected="selected"{/if}>50</option>
							<option value="75"{if $recordsPerPage == 75} selected="selected"{/if}>75</option>
							<option value="100"{if $recordsPerPage == 100} selected="selected"{/if}>100</option>
						</select>
					</div>
				</div>
				<div class="col-sm-5 col-md-4">
					<div class="form-group">
						<label for="processedLimit">{translate text='Min Processed'}</label>
						<div class="input-group-sm input-group">
							<input id="processedLimit" name="processedLimit" type="number" min="0" class="form-control input-sm" {if !empty($processedLimit)} value="{$processedLimit}"{/if}>
						</div>
					</div>
				</div>

			</div>
			<div class="row">
				<div class="col-sm-2 col-md-4">
					<div class="form-group">
						<button class="btn btn-primary btn-sm" type="submit">Apply</button>
					</div>
				</div>
			</div>
		</form>
		<div id="exportLogContainer">
			<table class="logEntryDetails table table-condensed table-hover">
				<thead>
					<tr><th>{translate text="Id"}</th><th>{translate text="Started"}</th><th>{translate text="Last Update"}</th><th>{translate text="Finished"}</th><th>{translate text="Elapsed"}</th><th>{translate text="Total Products"}</th><th>{translate text="Num Errors"}</th><th>{translate text="Products Added"}</th><th>{translate text="Products Deleted"}</th><th>{translate text="Products Updated"}</th><th>{translate text="Products Skipped"}</th><th>{translate text="Notes"}</th></tr>
				</thead>
				<tbody>
					{foreach from=$logEntries item=logEntry}
						<tr>
							<td>{$logEntry->id}</td>
							<td>{$logEntry->startTime|date_format:"%D %T"}</td>
							<td>{$logEntry->lastUpdate|date_format:"%D %T"}</td>
							<td>{$logEntry->endTime|date_format:"%D %T"}</td>
							<td>{$logEntry->getElapsedTime()}</td>
							<td>{$logEntry->numProducts}</td>
							<td>{$logEntry->numErrors}</td>
							<td>{$logEntry->numAdded}</td>
							<td>{$logEntry->numDeleted}</td>
							<td>{$logEntry->numUpdated}</td>
							<td>{$logEntry->numSkipped}</td>
							<td><a href="#" onclick="return AspenDiscovery.Admin.showExtractNotes('{$logEntry->id}', 'hoopla');">{translate text="Show Notes"}</a></td>
						</tr>
					{/foreach}
				</tbody>
			</table>
		</div>

		{if $pageLinks.all}<div class="text-center">{$pageLinks.all}</div>{/if}
	</div>
{/strip}