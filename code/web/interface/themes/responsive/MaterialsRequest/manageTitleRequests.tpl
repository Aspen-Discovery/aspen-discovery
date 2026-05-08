{strip}
<div id="main-content" class="col-md-12">
	<h1>{translate text="Manage Materials Requests by Title" isAdminFacing=true}</h1>
	{if !empty($error)}
		<div class="alert alert-danger">{$error}</div>
	{/if}
	{if !empty($updateMessage)}
		<div class="alert {if !empty($updateMessageIsError)}alert-danger{else}alert-success{/if}">
			{$updateMessage}
		</div>
	{/if}
	{if !empty($loggedIn)}
	<div id="materialsRequestFilters" class="accordion">
		{if count($allRequests) > 0}
			<form id="updateRequests" method="post" action="/MaterialsRequest/ManageRequests" class="form form-horizontal">
				<div class="form-group col-xs-4">
					<label for="pageSize" class="control-label">{translate text="Entries Per Page" isAdminFacing=true}&nbsp;</label>
					<select id="pageSize" name="pageSize" class="pageSize form-control input-sm" onchange="AspenDiscovery.changePageSize()">
						<option value="30"{if $materialsRequestsPerPage == 30} selected="selected"{/if}>30</option>
						<option value="50"{if $materialsRequestsPerPage == 50} selected="selected"{/if}>50</option>
						<option value="75"{if $materialsRequestsPerPage == 75} selected="selected"{/if}>75</option>
						<option value="100"{if $materialsRequestsPerPage == 100} selected="selected"{/if}>100</option>
						<option value="250"{if $materialsRequestsPerPage == 250} selected="selected"{/if}>250</option>
						<option value="500"{if $materialsRequestsPerPage == 500} selected="selected"{/if}>500</option>
						<option value="all"{if $showingAllRequests} selected="selected"{/if}>{translate text="All" isAdminFacing=true inAttribute=true}</option>
					</select>
				</div>
				<div class="form-group col-xs-4">
					<label for="sort" class="control-label">{translate text="Sort By" isAdminFacing=true}&nbsp;</label>
					<select id="sort" name="sort" class="sort form-control input-sm" onchange="AspenDiscovery.changeSort()">
						<option value="title"{if $materialsRequestSort == 'title'} selected="selected"{/if}>{translate text="Title" isPublicFacing=true inAttribute=true}</option>
						<option value="author"{if $materialsRequestSort == 'author'} selected="selected"{/if}>{translate text="Author" isPublicFacing=true inAttribute=true}</option>
						<option value="format"{if $materialsRequestSort == 'format'} selected="selected"{/if}>{translate text="Format" isPublicFacing=true inAttribute=true}</option>
						<option value="dateFirstRequested"{if $materialsRequestSort == 'dateFirstRequested'} selected="selected"{/if}>{translate text="Date First Requested" isPublicFacing=true inAttribute=true}</option>
						<option value="dateLastRequested desc"{if $materialsRequestSort == 'dateLastRequested desc'} selected="selected"{/if}>{translate text="Date Last Requested" isPublicFacing=true inAttribute=true}</option>
						<option value="numRequests desc"{if $materialsRequestSort == 'numRequests desc'} selected="selected"{/if}>{translate text="Number of Requests" isPublicFacing=true inAttribute=true}</option>
					</select>
				</div>
				<table id="requestedMaterials" class="table tablesorter table-striped table-hover table-sticky">
					<thead>
						<tr>
							<th><input type="checkbox" name="selectAll" id="selectAll" aria-label="{translate text="Select All" isAdminFacing=true inAttribute=true}" onchange="AspenDiscovery.toggleCheckboxes('.select', '#selectAll');"></th>
							{foreach from=$columnsToDisplay item=label}
								<th>{translate text=$label isAdminFacing=true}</th>
							{/foreach}
							<th>&nbsp;</th> {* Action Buttons Column *}
						</tr>
					</thead>
					{foreach from=$allRequests item=request}
						<tr>
							<td><input type="checkbox" name="select[{$request->id}]" class="select" aria-label="{translate text="Select Row" isAdminFacing=true inAttribute=true}"></td>
							{foreach name="columnLoop" from=$columnsToDisplay item=label key=column}
								{if $column == 'dateFirstRequested' || $column == 'dateLastRequested'}
									{* Date Columns*}
									<td>{$request->$column|date_format}</td>
								{else}
									{* All columns that can be displayed with out special handling *}
									<td>{$request->$column}</td>
								{/if}
							{/foreach}
							<td>
								<div class="btn-group btn-group-vertical btn-group-sm">
									<button type="button" onclick="AspenDiscovery.MaterialsRequest.manageMaterialsTitleRequest('{$request->id}')" class="btn btn-sm btn-info btn-wrap">{translate text="Manage Requests" isAdminFacing=true}</button>
								</div>
							</td>
						</tr>
					{/foreach}
				</table>
				{if in_array('Manage Library Materials Requests', $userPermissions)}
					<div id="materialsRequestActions">
						<div class="row form-group">
							<div class="col-sm-4">
								<label for="newAssignee" class="control-label">{translate text="Assign selected to" isAdminFacing=true}</label>
							</div>
							<div class="col-sm-8">
								<div class="input-group">
									{if !empty($assignees)}
										<select name="newAssignee" id="newAssignee" class="form-control">
											<option value="unselected">{translate text="Select One" inAttribute=true isAdminFacing=true}</option>
											<option value="unassign">{translate text="Un-assign (remove assignee)" inAttribute=true isAdminFacing=true}</option>

											{foreach from=$assignees item=displayName key=assigneeId}
												<option value="{$assigneeId}">{$displayName|escape}</option>
											{/foreach}

										</select>
									{else}
										<span class="text-warning">{translate text="No Valid Assignees Found" isAdminFacing=true}</span>
									{/if}
								</div>
							</div>
						</div>

						<div class="row form-group">
							<div class="col-sm-4">
								<label for="newStatus" class="control-label">{translate text="Change status of selected to" isAdminFacing=true}</label>
							</div>
							<div class="col-sm-8">
								<div class="input-group">
									<select name="newStatus" id="newStatus" class="form-control">
										<option value="unselected">{translate text="Select One" isAdminFacing=true}</option>
										{foreach from=$availableStatuses item=statusLabel key=status}
											<option value="{$status}">{translate text="$statusLabel"  isAdminFacing=true inAttribute=true}</option>
										{/foreach}
									</select>
								</div>
							</div>
						</div>

						<div class="row">
							<div class="col-xs-12">
								{if !empty($page)}
									<input type="hidden" name="page" value="{$page}">
								{/if}
								<span class="btn btn-default" onclick="return AspenDiscovery.MaterialsRequest.updateSelectedTitleRequests();">{translate text="Update Selected Requests" isAdminFacing=true}</span>
							</div>
						</div>
					</div>
				{/if}
				{if !empty($pageLinks.all)}
					<div class="text-center">{$pageLinks.all}</div>
				{/if}
			</form>
		{else}
			<div class="alert alert-info">{translate text="There are no materials requests that meet your criteria." isAdminFacing=true}</div>
		{/if}
	{/if}
</div>
{/strip}

<script type="text/javascript">
	$(function () {ldelim}
		$("#requestedMaterials").tablesorter({ldelim}
			cssAsc: 'sortAscHeader',
			cssDesc: 'sortDescHeader',
			cssHeader: 'unsortedHeader',
			widgets: ['zebra', 'filter'],
			headers: {ldelim}
				0: {ldelim}sorter: false{rdelim},
				{foreach name=config from=$dateColumns item=columnNumber}
				{$columnNumber+1}: {ldelim}sorter : 'date'{rdelim}{if empty($smarty.foreach.config.last)}, {/if}
				{/foreach}

			}
		});
	});
</script>
