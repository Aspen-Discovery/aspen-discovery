<div id="main-content">
	{if !empty($profile->_web_note)}
		<div class="row">
			<div id="web_note" class="alert alert-info text-center col-xs-12">{$profile->_web_note}</div>
		</div>
	{/if}
	{if !empty($accountMessages)}
		{include file='systemMessages.tpl' messages=$accountMessages}
	{/if}
	{if !empty($ilsMessages)}
		{include file='ilsMessages.tpl' messages=$ilsMessages}
	{/if}

	<h1>{translate text='My Materials Requests' isPublicFacing=true}</h1>
	{if !empty($error)}
		<div class="alert alert-danger">{$error}</div>
	{else}
		<div id="materialsRequestSummary" class="alert alert-info">
			{translate text="You have used <strong>%1%</strong> of your %2% yearly material requests.  We also limit patrons to %3% active material requests at a time.  You currently have <strong>%4%</strong> active material requests." 1=$requestsThisYear 2=$maxRequestsPerYear 3=$maxActiveRequests 4=$openRequests isPublicFacing=true}
		</div>
		<div id="materialsRequestFilters">
			<legend>{translate text="Filters" isPublicFacing=true}</legend>
			<form action="/MaterialsRequest/MyRequests" method="get" class="form-inline">
				<div>
					<div class="form-group">
						<label class="control-label">{translate text="Show" isPublicFacing=true}</label>
						<label for="openRequests" class="radio-inline">
							<input type="radio" id="openRequests" name="requestsToShow" value="openRequests" {if $showOpen}checked="checked"{/if}> {translate text="Open material requests" isPublicFacing=true}
						</label>
						<label for="allRequests" class="radio-inline">
							<input type="radio" id="allRequests" name="requestsToShow" value="allRequests" {if !$showOpen}checked="checked"{/if}> {translate text="All material requests" isPublicFacing=true}
						</label>
					</div>
					<div class="form-group">
						<button type="submit" name="submit" class="btn btn-sm btn-default">{translate text="Update Filters" isPublicFacing=true}</button>
					</div>
				</div>
			</form>
		</div>
		<br>
		{if count($allRequests) > 0}
			<table id="requestedMaterials" class="table table-striped table-condensed tablesorter">
				<thead>
					<tr>
						<th>{translate text="Title" isPublicFacing=true}</th>
						<th>{translate text="Author" isPublicFacing=true}</th>
						<th>{translate text="Format" isPublicFacing=true}</th>
						<th>{translate text="Status" isPublicFacing=true}</th>
						<th>{translate text="Created" isPublicFacing=true}</th>
						<th>&nbsp;</th>
					</tr>
				</thead>
				<tbody>
					{foreach from=$allRequests item=request}
						<tr>
							<td>{$request->title}</td>
							<td>{$request->author}</td>
							<td>{translate text=$request->format isPublicFacing=true isAdminEnteredData=true}</td>
							<td>{translate text=$request->statusLabel isPublicFacing=true isAdminEnteredData=true}</td>
							<td>{$request->dateCreated|date_format}</td>
							<td>
								<a role="button" onclick='AspenDiscovery.MaterialsRequest.showMaterialsRequestDetails("{$request->id}", false)' class="btn btn-info btn-sm">{translate text="Details" isPublicFacing=true}</a>
								{if $request->status == $defaultStatus}
								<a role="button" onclick="return AspenDiscovery.MaterialsRequest.cancelMaterialsRequest('{$request->id}');" class="btn btn-danger btn-sm">{translate text="Cancel Materials Request" isPublicFacing=true}</a>
								{/if}
							</td>
						</tr>
					{/foreach}
				</tbody>
			</table>
		{else}
			<div class="alert alert-warning">{translate text='There are no materials requests that meet your criteria.' isPublicFacing=true}</div>
		{/if}
		<div id="createNewMaterialsRequest"><a href="/MaterialsRequest/NewRequest" class="btn btn-primary btn-sm">{translate text='Submit a New Materials Request' isPublicFacing=true}</a></div>
	{/if}
</div>
<script type="text/javascript">
{literal}
$("#requestedMaterials").tablesorter({cssAsc: 'sortAscHeader', cssDesc: 'sortDescHeader', cssHeader: 'unsortedHeader', headers: { 4: {sorter : 'date'}, 5: { sorter: false} } });
{/literal}
</script>