{strip}
<div id="main-content" class="col-md-12">
	<h1>{translate text="Manage Materials Requests" isAdminFacing=true}</h1>
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
			<div class="panel panel-default">
			<div class="panel-heading">
				<div class="panel-title collapsed">
					<a href="#filterPanel" data-toggle="collapse" role="button">
						{translate text="Filters" isAdminFacing=true}
					</a>
				</div>
			</div>
			<div id="filterPanel" class="panel-collapse collapse">
				<div class="panel-body">

					<form action="/MaterialsRequest/ManageRequests" method="get" onsubmit="return AspenDiscovery.MaterialsRequest.validateManageRequestFilters();">
						<fieldset class="fieldset-collapsible">
							<legend>{translate text="Statuses to Show" isAdminFacing=true}</legend>
							<div class="form-group checkbox">
								<label for="selectAllStatusFilter">
									<input type="checkbox" name="selectAllStatusFilter" id="selectAllStatusFilter" onchange="AspenDiscovery.toggleCheckboxes('.statusFilter', '#selectAllStatusFilter');">
									<strong>{translate text="Select All" isAdminFacing=true}</strong>
								</label>
							</div>
							<div class="form-group">
								{foreach from=$availableStatuses item=statusLabel key=status}
									<div class="checkbox">
										<label>
											<input type="checkbox" name="statusFilter[]" value="{$status}" {if in_array($status, $statusFilter)}checked="checked"{/if} class="statusFilter">{translate text=$statusLabel isAdminFacing=true isAdminEnteredData=true}
										</label>
									</div>
								{/foreach}
							</div>
						</fieldset>
						<fieldset class="form-group fieldset-collapsible">
							<legend>{translate text="Date" isPublicFacing=true isAdminFacing=true}</legend>
							<div class="form-group">
								<label for="startDate">{translate text="From" isAdminFacing=true}</label> <input type="date" id="startDate" name="startDate" value="{if !empty($startDate)}{$startDate}{/if}" size="8" max="{$smarty.now|date_format:"%Y-%m-%d"}">
								<label for="endDate">{translate text="To" isAdminFacing=true}</label> <input type="date" id="endDate" name="endDate" value="{if !empty($endDate)}{$endDate}{/if}" size="8" max="{$smarty.now|date_format:"%Y-%m-%d"}">
							</div>
						</fieldset>
						<fieldset class="form-group fieldset-collapsible">
							<legend>{translate text="Request IDs to Show (separated by commas)" isAdminFacing=true}</legend>
							<div class="form-group">
								<label for="idsToShow">{translate text="Request IDs" isAdminFacing=true}</label> <input type="text" id="idsToShow" name="idsToShow" value="{if !empty($idsToShow)}{$idsToShow}{/if}" size="60" class="form-control">
							</div>
						</fieldset>
						<fieldset class="form-group fieldset-collapsible">
							<legend>{translate text="Format" isAdminFacing=true}</legend>
							<div class="form-group checkbox">
								<label for="selectAllFormatFilter">
									<input type="checkbox" name="selectAllFormatFilter" id="selectAllFormatFilter" onchange="AspenDiscovery.toggleCheckboxes('.formatFilter', '#selectAllFormatFilter');">
									<strong>{translate text="Select All" isAdminFacing=true}</strong>
								</label>
							</div>
							<div class="form-group">
								{foreach from=$availableFormats item=formatLabel key=format}
									<div class="checkbox">
										<label><input type="checkbox" name="formatFilter[]" value="{$format}" {if in_array($format, $formatFilter)}checked="checked"{/if} class="formatFilter">{translate text=$formatLabel isAdminFacing=true}</label>
									</div>
								{/foreach}
							</div>
						</fieldset>
						<fieldset class="fieldset-collapsible">
							<legend>{translate text="Assigned To" isAdminFacing=true}</legend>
							<div class="form-group checkbox">
								<label for="showUnassigned">
									<input type="checkbox" name="showUnassigned" id="showUnassigned"{if !empty($showUnassigned)} checked{/if}>
									<strong>{translate text="Unassigned" isAdminFacing=true}</strong>
								</label>
							</div>
								<div class="form-group checkbox">
								<label for="selectAllAssigneesFilter">
									<input type="checkbox" name="selectAllAssigneesFilter" id="selectAllAssigneesFilter" onchange="AspenDiscovery.toggleCheckboxes('.assigneesFilter', '#selectAllAssigneesFilter');">
									<strong>{translate text="Select All" isAdminFacing=true}</strong>
								</label>
							</div>
							<div class="form-group">
								{foreach from=$assignees item=displayName key=assigneeId}
									<div class="checkbox">
										<label>
											<input type="checkbox" name="assigneesFilter[]" value="{$assigneeId}" {if in_array($assigneeId, $assigneesFilter)}checked="checked"{/if} class="assigneesFilter">{$displayName|escape}
										</label>
									</div>
								{/foreach}

							</div>
						</fieldset>

						<input type="submit" name="submit" value="{translate text="Update Filters" inAttribute=true isAdminFacing=true}" class="btn btn-default">
					</form>

				</div>
			</div>
		</div>
		{if count($allRequests) > 0}
			<form id="updateRequests" method="post" action="/MaterialsRequest/ManageRequests" class="form form-horizontal">
				{foreach from=$statusFilter item=status}
					<input type="hidden" name="statusFilter[]" value="{$status}">
				{/foreach}
				{foreach from=$formatFilter item=format}
					<input type="hidden" name="formatFilter[]" value="{$format}">
				{/foreach}
				{if !empty($showUnassigned)}
					<input type="hidden" name="showUnassigned" value="on">
				{/if}
				{foreach from=$assigneesFilter item=assignee}
					<input type="hidden" name="assigneesFilter[]" value="{$assignee}">
				{/foreach}
				{if !empty($startDate)}
					<input type="hidden" name="startDate" value="{$startDate}">
				{/if}
				{if !empty($endDate)}
					<input type="hidden" name="endDate" value="{$endDate}">
				{/if}
				{if !empty($idsToShow)}
					<input type="hidden" name="idsToShow" value="{$idsToShow}">
				{/if}
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
				<table id="requestedMaterials" class="table tablesorter table-striped table-hover table-sticky">
					<thead>
						<tr>
							<th><input type="checkbox" name="selectAll" id="selectAll" aria-label="{translate text="Select All" isAdminFacing=true inAttribute=true}" onchange="AspenDiscovery.toggleCheckboxes('.select', '#selectAll');"></th>
							{foreach from=$columnsToDisplay item=label}
								<th>{translate text=$label isAdminFacing=true}</th>
							{/foreach}
							{if $showExistingTitleInformation}
								<th>{translate text="Exists In Catalog?" isAdminFacing=true}</th>
							{/if}
							<th>&nbsp;</th> {* Action Buttons Column *}
						</tr>
					</thead>
					<tbody>
						{foreach from=$allRequests item=request}
							<tr>
								<td><input type="checkbox" name="select[{$request->id}]" class="select" aria-label="{translate text="Select Row" isAdminFacing=true inAttribute=true}"></td>
								{foreach name="columnLoop" from=$columnsToDisplay item=label key=column}
									{if $column == 'format'}
										<td>
											{if in_array($request->format, array_keys($availableFormats))}
												{assign var="key" value=$request->format}
												{translate text=$availableFormats.$key isAdminFacing=true}
											{else}
												{translate text=$request->format isAdminFacing=true}
											{/if}
										</td>
									{elseif $column == 'abridged'}
										<td>{if $request->$column == 1}{translate text="Yes" isAdminFacing=true}{elseif $request->$column == 2}{translate text="N/A" isAdminFacing=true}{else}{translate text="No" isAdminFacing=true}{/if}</td>
									{elseif $column == 'about' || $column == 'comments' || $column == 'staffCommments'}
										<td>
											{if !empty($request->$column)}
												<textarea cols="30" rows="4" readonly disabled>
												{* TODO: use truncate modifier? *}
													{$request->$column}
											</textarea>
											{/if}
										</td>
									{elseif $column == 'status'}
										<td>{translate text=$request->statusLabel isAdminFacing=true}</td>
									{elseif $column == 'dateCreated' || $column == 'dateUpdated'}
										{* Date Columns*}
										<td>{$request->$column|date_format}</td>
									{elseif $column == 'createdBy'}
										<td>{$request->getCreatedByLastName()|escape}, {$request->getCreatedByFirstName()|escape}<br>{$request->getCreatedByUserBarcode()}</td>

									{elseif $column == 'emailSent' || $column == 'holdsCreated' || $column == 'illItem'}
										{* Simple Boolean Columns *}
										<td>{if $request->$column}{translate text="Yes" isAdminFacing=true}{else}{translate text="No" isAdminFacing=true}{/if}</td>

									{elseif $column == 'email'}
										<td>{$request->email}</td>
									{elseif $column == 'placeHoldWhenAvailable'}
										<td>{if $request->$column}{translate text="Yes" isAdminFacing=true}{if $request->location} - {$request->location|escape}{/if}{else}{translate text="No" isAdminFacing=true}{/if}</td>
									{elseif $column == 'holdPickupLocation'}
										<td>
											{$request->getHoldLocationName($request->holdPickupLocation)|escape}
										</td>
									{elseif $column == 'bookmobileStop'}
										<td>{$request->bookmobileStop}</td>
									{elseif $column == 'assignedTo'}
										<td>{$request->getAssigneeName()|escape}</td>
									{elseif $column == 'source'}
										<td>{if $request->source == 1}{translate text="Materials Request" isAdminFacing=true}{else}{translate text="Local ILL" isAdminFacing=true}{/if}</td>
									{else}
										{* All columns that can be displayed with out special handling *}
										<td>{$request->$column}</td>
									{/if}
								{/foreach}
								{if $showExistingTitleInformation}
									<td id="existingTitleInformation{$request->id}">
										{if $request->hasExistingRecord == 0}
											<span title="{translate text="Checked %1%" 1=$request->lastCheckForExistingRecord|date_format:"%D %T" isAdminFacing=true inAttribute=true}">{translate text="No" isAdminFacing=true}</span>
										{else}
											<a href="{$request->existingRecordUrl}" target="_blank" title="{translate text="Checked %1%" 1=$request->lastCheckForExistingRecord|date_format:"%D %T" isAdminFacing=true inAttribute=true}">
												<strong>{translate text="Yes" isAdminFacing=true}</strong>
											</a>
										{/if}
										{if $request->automaticCheckForExistingRecordNeedsToBeDone()}
											<script>
												{literal}
												$(document).ready(function (){
													AspenDiscovery.MaterialsRequest.checkRequestForExistingRecord({/literal}{$request->id}{literal});
												});
												{/literal}
											</script>
										{/if}
									</td>
								{/if}
								<td>
									<div class="btn-group btn-group-vertical btn-group-sm">
										{if $showExistingTitleInformation && !$request->hasExistingRecord}
											<button type="button" onclick="AspenDiscovery.MaterialsRequest.checkRequestForExistingRecord('{$request->id}')" class="btn btn-sm btn-info btn-wrap">{translate text="Check for Existing Title" isAdminFacing=true}</button>
										{/if}
										<button type="button" onclick="AspenDiscovery.MaterialsRequest.showMaterialsRequestDetails('{$request->id}', true)" class="btn btn-sm btn-info btn-wrap">{translate text="Details" isAdminFacing=true}</button>
										<button type="button" onclick="AspenDiscovery.MaterialsRequest.updateMaterialsRequest('{$request->id}')" class="btn btn-sm btn-primary btn-wrap">{translate text="Update Request" isAdminFacing=true}</button>
									</div>
								</td>
							</tr>
						{/foreach}
					</tbody>
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
								<input class="btn btn-default" type="submit" name="exportSelected" value="{translate text="Export Selected To CSV" inAttribute=true isAdminFacing=true}" onclick="return AspenDiscovery.MaterialsRequest.exportSelectedRequests();">
								<input class="btn btn-default" type="submit" name="exportAll" value="{translate text="Export All To CSV" inAttribute=true isAdminFacing=true}">
								<span class="btn btn-default" onclick="return AspenDiscovery.MaterialsRequest.updateSelectedRequests();">{translate text="Update Selected Requests" isAdminFacing=true}</span>
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
