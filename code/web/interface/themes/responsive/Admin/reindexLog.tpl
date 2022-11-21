{strip}
	<div id="main-content" class="col-md-12">
		<h1>{translate text='Nightly Index Log' isAdminFacing=true}</h1>
		<p class="alert alert-info">
			{translate text="The nightly index log is run when we detect changes to settings related to indexing or when new Accelerated Reader data is available.  It can be forced to run by selecting <b>Run full index tonight</b> from the System Variables in the System Administration section." isAdminFacing=true}
		</p>

		<legend>{translate text="Filter by" isAdminFacing=true}</legend>

		<form class="navbar form-inline row">
			<div class="form-group col-xs-7">
				<label for="worksLimit" class="control-label">{translate text="Min Works Processed" isAdminFacing=true}</label>&nbsp;
				<input style="width: 125px;" id="worksLimit" name="worksLimit" type="number" min="0" class="form-control" {if !empty($smarty.request.worksLimit)} value="{$smarty.request.worksLimit}"{/if}>
				<button class="btn btn-primary" type="submit">{translate text="Go" isAdminFacing=true}</button>
			</div>
			<div class="form-group col-xs-5">
				<span class="pull-right">
					<label for="pageSize" class="control-label">{translate text="Entries Per Page" isAdminFacing=true}</label>&nbsp;
					<select id="pageSize" name="pageSize" class="pageSize form-control input-sm" onchange="AspenDiscovery.changePageSize()">
						<option value="30"{if $recordsPerPage == 30} selected="selected"{/if}>30</option>
						<option value="50"{if $recordsPerPage == 50} selected="selected"{/if}>50</option>
						<option value="75"{if $recordsPerPage == 75} selected="selected"{/if}>75</option>
						<option value="100"{if $recordsPerPage == 100} selected="selected"{/if}>100</option>
					</select>
				</span>
			</div>
		</form>
		<div class="adminTableRegion fixed-height-table">
			<table class="adminTable table table-condensed table-hover table-condensed smallText table-sticky" aria-label="Index Log">
				<thead>
					<tr>
						<th>{translate text="Id" isAdminFacing=true}</th>
						<th>{translate text="Started" isAdminFacing=true}</th>
						<th>{translate text="Last Update" isAdminFacing=true}</th>
						<th>{translate text="Finished" isAdminFacing=true}</th>
						<th>{translate text="Elapsed" isAdminFacing=true}</th>
						<th>{translate text="Works Processed" isAdminFacing=true}</th>
						<th>{translate text="Num Errors" isAdminFacing=true}</th>
						<th>{translate text="Num Invalid Records" isAdminFacing=true}</th>
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
							<td>{$logEntry->numWorksProcessed}</td>
							<td>{$logEntry->numErrors}</td>
							<td>{$logEntry->numInvalidRecords}</td>
							<td><a href="#" onclick="return AspenDiscovery.Admin.showReindexNotes('{$logEntry->id}');">{translate text="Show Notes" isAdminFacing=true}</a></td>
						</tr>
					{/foreach}
				</tbody>
			</table>
		</div>

		{if $pageLinks.all}<div class="text-center">{$pageLinks.all}</div>{/if}
	</div>
{/strip}