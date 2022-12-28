{strip}
	<div id="main-content" class="col-md-12">
		<h1>{translate text="Hoopla Export Log" isAdminFacing=true}</h1>

		<form>
			<div class="row">
				<div class="col-sm-5 col-md-4">
					<div class="form-group">
						<label for="pageSize">{translate text='Entries Per Page' isAdminFacing=true}</label>
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
						<label for="processedLimit">{translate text='Min Processed' isAdminFacing=true}</label>
						<div class="input-group-sm input-group">
							<input id="processedLimit" name="processedLimit" type="number" min="0" class="form-control input-sm" {if !empty($processedLimit)} value="{$processedLimit}"{/if}>
						</div>
					</div>
				</div>

			</div>
			<div class="row">
				<div class="col-sm-2 col-md-4">
					<div class="form-group">
						<button class="btn btn-primary btn-sm" type="submit">{translate text="Apply" isAdminFacing=true}</button>
					</div>
				</div>
			</div>
		</form>
		<div class="adminTableRegion fixed-height-table">
			<table class="adminTable table table-condensed table-hover table-condensed smallText table-sticky">
				<thead>
					<tr>
						<th>{translate text="Id" isAdminFacing=true}</th>
						<th>{translate text="Started" isAdminFacing=true}</th>
						<th>{translate text="Last Update" isAdminFacing=true}</th>
						<th>{translate text="Finished" isAdminFacing=true}</th>
						<th>{translate text="Elapsed" isAdminFacing=true}</th>
						<th>{translate text="Products Regrouped" isAdminFacing=true}</th>
						<th>{translate text="Products Changed After Grouping" isAdminFacing=true}</th>
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
							<td>{$logEntry->numRegrouped}</td>
							<td>{$logEntry->numChangedAfterGrouping}</td>
							<td>{$logEntry->numProducts}</td>
							<td>{$logEntry->numErrors}</td>
							<td>{$logEntry->numAdded}</td>
							<td>{$logEntry->numDeleted}</td>
							<td>{$logEntry->numUpdated}</td>
							<td>{$logEntry->numSkipped}</td>
							<td><a href="#" onclick="return AspenDiscovery.Admin.showExtractNotes('{$logEntry->id}', 'hoopla');">{translate text="Show Notes" isAdminFacing=true}</a></td>
						</tr>
					{/foreach}
				</tbody>
			</table>
		</div>

		{if !empty($pageLinks.all)}<div class="text-center">{$pageLinks.all}</div>{/if}
	</div>
{/strip}