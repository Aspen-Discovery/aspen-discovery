{strip}
{if !empty($error)}
	<div class="alert alert-danger">{$error}</div>
{else}
	<div>
		<div class="row form-group">
			<table id="requestedMaterials" class="table tablesorter table-striped table-hover table-sticky">
				<thead>
				<tr>
					{foreach from=$columnsToDisplay item=label}
						<th>{translate text=$label isAdminFacing=true}</th>
					{/foreach}
				</tr>
				</thead>
				{foreach from=$materialsRequests item=materialsRequest}
					<tr data-request-id="{$materialsRequest->id}">
						{foreach name="columnLoop" from=$columnsToDisplay item=label key=column}
							{if $column == 'assignedTo'}
								<td>
									<div class="input-group">
										{if !empty($assignees)}
											<label for="newAssignee"></label>
											<select name="newAssignee" id="newAssignee" class="form-control" data-original="{$materialsRequest->assignedTo}">
												<option value="unselected">{translate text="Select One" inAttribute=true isAdminFacing=true}</option>
												<option value="unassign">{translate text="Un-assign (remove assignee)" inAttribute=true isAdminFacing=true}</option>

												{foreach from=$assignees item=displayName key=assigneeId}
													<option value="{$assigneeId}"{if $assigneeId == $materialsRequest->assignedTo} selected="selected"{/if}>{$displayName|escape}</option>
												{/foreach}

											</select>
										{else}
											<span class="text-warning">{translate text="No Valid Assignees Found" isAdminFacing=true}</span>
										{/if}
									</div>
								</td>
							{elseif $column == 'statusLabel'}
								<td>
									<div class="input-group">
										<label for="newStatus"></label>
										<select name="newStatus" id="newStatus" class="form-control" data-original="{$materialsRequest->status}">
											<option value="unselected">{translate text="Select One" isAdminFacing=true}</option>
											{foreach from=$availableStatuses item=statusLabel key=status}
												<option value="{$status}"{if $materialsRequest->status == $status} selected="selected"{/if}>{translate text=$statusLabel isPublicFacing=true isAdminEnteredData=true}</option>
											{/foreach}
										</select>
									</div>
								</td>
							{elseif $column == 'dateCreated' || $column == 'dateUpdated'}
								{* Date Columns*}
								<td>{$materialsRequest->$column|date_format}</td>
							{else}
								{* All columns that can be displayed with out special handling *}
								<td>{$materialsRequest->$column}</td>
							{/if}
						{/foreach}
					</tr>
				{/foreach}
			</table>
		</div>
	</div>
{/if}
{/strip}
