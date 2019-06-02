<script type="text/javascript" src="{$path}/MaterialsRequest/ajax.js"></script>
<div id="main-content">
	{if !empty($profile->_web_note)}
		<div class="row">
			<div id="web_note" class="alert alert-info text-center col-xs-12">{$profile->_web_note}</div>
		</div>
	{/if}

	<span class='availableHoldsNoticePlaceHolder'></span>

	<h2>My {translate text='Materials_Request_alt'}s</h2>
	{if !empty($error)}
		<div class="alert alert-danger">{$error}</div>
	{else}
		{if count($allRequests) > 0}
			<form method="post" action="/MaterialsRequest/IlsRequests">
				<table id="requestedMaterials" class="table table-striped table-condensed tablesorter">
					<thead>
						<tr>
							<th>&nbsp;</th>
							<th>Summary</th>
							<th>Suggested On</th>
							<th>Note</th>
							<th>Managed By</th>
							<th>Status</th>
						</tr>
					</thead>
					<tbody>
						{foreach from=$allRequests item=request}
							<tr>
								<td>
									<input type="checkbox" name="delete_field" value="{$request.id}"/>
								</td>
								<td>{$request.summary}</td>
								<td>{$request.suggestedOn}</td>
								<td>{$request.note}</td>
								<td>{$request.managedBy}</td>
								<td>{$request.status}</td>
							</tr>
						{/foreach}
					</tbody>
				</table>
				<input type="submit" value="Delete Selected" class="btn btn-sm btn-danger" name="submit"/>
			</form>
			<br/>
		{else}
			<div class="alert alert-warning">There are no {translate text='materials request'}s that meet your criteria.</div>
		{/if}
		<div id="createNewMaterialsRequest"><a href="{$path}/MaterialsRequest/NewRequestIls" class="btn btn-primary btn-sm">Submit a New {translate text='Materials_Request_alt'}</a></div>
	{/if}
</div>
<script type="text/javascript">
{literal}
$("#requestedMaterials").tablesorter({cssAsc: 'sortAscHeader', cssDesc: 'sortDescHeader', cssHeader: 'unsortedHeader', headers: {0: { sorter: false}, 2: {sorter : 'date'}, 6: { sorter: false} } });
{/literal}
</script>