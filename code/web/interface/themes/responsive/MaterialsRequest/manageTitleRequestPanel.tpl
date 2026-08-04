{strip}
	{if in_array('Manage Library Materials Requests', $userPermissions)}
		<div id="materialsRequestActions" style="padding-top: 1em">
			<div class="row">
				<div class="col-sm-4">
					<label for="newAssignee" class="control-label">{translate text="Assign selected to" isAdminFacing=true}</label>
				</div>
				<div class="col-sm-8">
					<div class="input-group input-group-sm">
						{if !empty($assignees)}
							<select name="newAssignee" id="newAssignee" class="form-control form-control-sm">
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

			<div class="row" style="padding-top: .25em">
				<div class="col-sm-4">
					<label for="newStatus" class="control-label">{translate text="Change status of selected to" isAdminFacing=true}</label>
				</div>
				<div class="col-sm-8">
					<div class="input-group input-group-sm">
						<select name="newStatus" id="newStatus" class="form-control form-control-sm">
							<option value="unselected">{translate text="Select One" isAdminFacing=true}</option>
							{foreach from=$availableStatuses item=statusLabel key=status}
								<option value="{$status}">{translate text="$statusLabel"  isAdminFacing=true inAttribute=true}</option>
							{/foreach}
						</select>
					</div>
				</div>
			</div>

			<div class="row" style="padding-top: .25em">
				<div class="col-sm-8 col-sm-offset-4">
					{if !empty($page)}
						<input type="hidden" name="page" value="{$page}">
					{/if}
					<span class="btn btn-default btn-sm" onclick="return AspenDiscovery.MaterialsRequest.updateSelectedTitleRequests();">{translate text="Update Selected Requests" isAdminFacing=true}</span>
				</div>
			</div>
		</div>
	{/if}
{/strip}
