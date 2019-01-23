<script type="text/javascript" src="{$path}/MaterialsRequest/ajax.js"></script>
<div id="main-content">
	{if $profile->web_note}
		<div class="row">
			<div id="web_note" class="alert alert-info text-center col-xs-12">{$profile->web_note}</div>
		</div>
	{/if}

	<span class='availableHoldsNoticePlaceHolder'></span>

	<h2>My {translate text='Materials_Request_alt'}s</h2>
	{if $error}
		<div class="alert alert-danger">{$error}</div>
	{else}
		<div id="materialsRequestSummary" class="alert alert-info">
			You have used <strong>{$requestsThisYear}</strong> of your {$maxRequestsPerYear} yearly {translate text='materials request'}s.  We also limit patrons to {$maxActiveRequests} active {translate text='materials_request_short'}s at a time.  You currently have <strong>{$openRequests}</strong> active {translate text='materials_request_short'}s.
		</div>
		<div id="materialsRequestFilters">
			<legend>Filters:</legend>
			<form action="{$path}/MaterialsRequest/MyRequests" method="get" class="form-inline">
				<div>
					<div class="form-group">
						<label class="control-label">Show:</label>
						<label for="openRequests" class="radio-inline">
							{*<input type="radio" id="openRequests" name="requestsToShow" value="openRequests" {if $showOpen}checked="checked"{/if}> Open {translate text='materials_request_short'|capitalize}s*}
							<input type="radio" id="openRequests" name="requestsToShow" value="openRequests" {if $showOpen}checked="checked"{/if}> Open {'materials_request_short'|translate|capitalize}s
						</label>
						<label for="allRequests" class="radio-inline">
							<input type="radio" id="allRequests" name="requestsToShow" value="allRequests" {if !$showOpen}checked="checked"{/if}> All {'materials_request_short'|translate|capitalize}s
						</label>
					</div>
					<div class="form-group">
						<input type="submit" name="submit" value="Update Filters" class="btn btn-sm btn-default">
					</div>
				</div>
			</form>
		</div>
		<br>
		{if count($allRequests) > 0}
			<table id="requestedMaterials" class="table table-striped table-condensed tablesorter">
				<thead>
					<tr>
						<th>Title</th>
						<th>Author</th>
						<th>Format</th>
						<th>Status</th>
						<th>Created</th>
						<th>&nbsp;</th>
					</tr>
				</thead>
				<tbody>
					{foreach from=$allRequests item=request}
						<tr>
							<td>{$request->title}</td>
							<td>{$request->author}</td>
							<td>{$request->format}</td>
							<td>{$request->statusLabel|translate}</td>
							<td>{$request->dateCreated|date_format}</td>
							<td>
								<a role="button" onclick='VuFind.MaterialsRequest.showMaterialsRequestDetails("{$request->id}", false)' class="btn btn-info btn-sm">Details</a>
								{if $request->status == $defaultStatus}
								<a role="button" onclick="return VuFind.MaterialsRequest.cancelMaterialsRequest('{$request->id}');" class="btn btn-danger btn-sm">Cancel {'materials_request_short'|translate|capitalize}</a>
								{/if}
							</td>
						</tr>
					{/foreach}
				</tbody>
			</table>
		{else}
			<div class="alert alert-warning">There are no {translate text='materials request'}s that meet your criteria.</div>
		{/if}
		<div id="createNewMaterialsRequest"><a href="{$path}/MaterialsRequest/NewRequest" class="btn btn-primary btn-sm">Submit a New {translate text='Materials_Request_alt'}</a></div>
	{/if}
</div>
<script type="text/javascript">
{literal}
$("#requestedMaterials").tablesorter({cssAsc: 'sortAscHeader', cssDesc: 'sortDescHeader', cssHeader: 'unsortedHeader', headers: { 4: {sorter : 'date'}, 5: { sorter: false} } });
{/literal}
</script>