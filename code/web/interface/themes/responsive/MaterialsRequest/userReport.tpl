<div id="main-content" class="col-md-12">
	<h1>{translate text="Materials Request Requests by User Report" isAdminFacing=true}</h1>
	{if !empty($error)}
		<div class="error">{$error}</div>
	{else}
		<div id="materialsRequestFilters">
			<legend>{translate text="Filters" isAdminFacing=true}</legend>

			<form action="/MaterialsRequest/UserReport" method="get">
				<fieldset class="fieldset-collapsible">
					<legend>{translate text="Statuses to Show" isAdminFacing=true}</legend>
					<div class="form-group checkbox">
						<label for="selectAllStatusFilter">
							<input type="checkbox" name="selectAllStatusFilter" id="selectAllStatusFilter" onclick="AspenDiscovery.toggleCheckboxes('.statusFilter', '#selectAllStatusFilter');">
							<strong>{translate text="Select All" isAdminFacing=true}</strong>
						</label>
					</div>
					{foreach from=$availableStatuses item=statusLabel key=status}
						<div class="checkbox">
							<label>
								<input type="checkbox" name="statusFilter[]" value="{$status}" {if in_array($status, $statusFilter)}checked="checked"{/if} class="statusFilter">{$statusLabel}
							</label>
						</div>
					{/foreach}
					<div><input type="submit" name="submit" value="{translate text="Update Filters" isAdminFacing=true inAttribute=true}" class="btn btn-default"></div>
				</fieldset>
			</form>
		</div>


		<legend>{translate text="Table" isAdminFacing=true}</legend>

		{* Display results in table*}
		<table id="summaryTable" class="tablesorter table table-bordered">
			<thead>
				<tr>
					<th>{translate text="Last Name" isAdminFacing=true}</th>
					<th>{translate text="First Name" isAdminFacing=true}</th>
					<th>{translate text="Barcode" isAdminFacing=true}</th>
					{foreach from=$statuses item=status}
						<th>{translate text=$status isAdminFacing=true}</th>
					{/foreach}
				</tr>
			</thead>
			<tbody>
				{foreach from=$userData item=userInfo key=userId}
					<tr>
						<td>{$userInfo.lastName}</td>
						<td>{$userInfo.firstName}</td>
						<td>{$userInfo.barcode}</td>
						{foreach from=$statuses key=status item=statusLabel}
							<th>{if $userInfo.requestsByStatus.$status}{$userInfo.requestsByStatus.$status}{else}0{/if}</th>
						{/foreach}
					</tr>
				{/foreach}
			</tbody>
		</table>
	{/if}

	<form action="{$fullPath}" method="get">
		<input type="submit" id="exportToExcel" name="exportToExcel" value="{translate text="Export to Excel" isAdminFacing=true inAttribute=true}" class="btn btn-default">
		{foreach from=$availableStatuses item=statusLabel key=status}
			{if in_array($status, $statusFilter)}
				<input type="hidden" name="statusFilter[]" value="{$status}">
			{/if}
		{/foreach}
	</form>

	{* Export to Excel option *}
</div>

<script type="text/javascript">
{literal}
	$("#summaryTable").tablesorter({cssAsc: 'sortAscHeader', cssDesc: 'sortDescHeader', cssHeader: 'unsortedHeader', headers: { 0: { sorter: 'date'} } });
{/literal}
</script>