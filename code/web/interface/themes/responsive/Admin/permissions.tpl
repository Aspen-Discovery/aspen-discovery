{strip}
	{if !empty($selectedRole)}
		<h1>{translate text="Permissions for %1%" 1=$selectedRole->name isAdminFacing=true}</h1>
	{else}
		<h1>{translate text="Permissions" isAdminFacing=true}</h1>
	{/if}

	<form class="form-inline row" id="selectRoleForm" style="margin: 0; padding-bottom: 2em;">
		<div class="form-group">
			<label for="roleId" class="control-label">{translate text="Role to edit" isAdminFacing=true}</label>&nbsp;
			<select id="roleId" name="roleId" class="form-control input-sm" onchange="$('#selectRoleForm').submit()">
				{foreach from=$roles key=roleId item=role}
					<option value="{$roleId}" {if $roleId == $selectedRole->roleId}selected{/if}>{$role->name}</option>
				{/foreach}
			</select>
			<div class="btn-group" style="padding-left: 1em; padding-top: 0">
				<a class="btn btn-danger btn-sm" style="margin-bottom: 0" onclick="if (confirm('{translate text="Are you sure you want to delete this role" inAttribute=true isAdminFacing=true}')){ldelim}return AspenDiscovery.Admin.deleteRole({$selectedRole->roleId}){rdelim}else{ldelim}return false{rdelim}"><i class="fas fa-trash" role="presentation"></i> {translate text="Delete" isAdminFacing=true}</a>
				<a class="btn btn-default btn-sm" style="margin-bottom: 0"  onclick="return AspenDiscovery.Admin.showCreateRoleForm()"><i class="fas fa-plus" role="presentation"></i> {translate text="Create New Role" isAdminFacing=true}</a>
			</div>
		</div>
	</form>

	<form class='alert alert-info'role="form">
		<div class="form-group">
			<label for="settingsSearch">{translate text="Search for a Permission" isAdminFacing=true}</label>
			<div class="input-group">
				<input  type="text" name="searchPermissions" id="searchPermissions" onkeyup="return AspenDiscovery.Admin.searchPermissions();" class="form-control" />
				<span class="input-group-btn"><button class="btn btn-default" type="button" onclick="$('#searchPermissions').val('');return AspenDiscovery.Admin.searchPermissions();" title="{translate text="Clear" inAttribute=true isAdminFacing=true}"><i class="fas fa-times-circle" role="presentation"></i></button></span>
				<script type="text/javascript">
					{literal}
					$(document).ready(function() {
						$("#searchPermissions").keydown("keydown", function (e) {
							if (e.which === 13) {
								e.preventDefault();
							}
						});
					});
					{/literal}
				</script>
			</div>
		</div>
	</form>

	<form>
		<input type="hidden" name="roleId" value="{$selectedRole->roleId}" />
		{assign var=panelId value=0}
		<div class="panel-group accordion" id="permissions-table-accordion">
			{foreach from=$permissions item=sectionPermissions key=sectionName}
				{assign var=panelId value=$panelId+1}
				<div class="permissionSection panel panel-default {if $panelId == 1 && count($selectedSections) == 0 || in_array($sectionName, $selectedSections)}active{/if}">
					<div class="permissionHeading panel-heading" role="tab" id="heading{$panelId}">
						<h2 class="panel-title">
							<a role="button" data-toggle="collapse" data-parent="#permissionsTable" href="#permission{$panelId}Group" aria-expanded="true" aria-controls="permission{$panelId}PanelBody">
								{translate text=$sectionName isAdminFacing=true}
							</a>
						</h2>
					</div>
					<div class="searchCollapse panel-collapse collapse{if $panelId == 1 && count($selectedSections) == 0 || in_array($sectionName, $selectedSections)} in{/if}" id="permission{$panelId}Group" role="tabpanel" aria-labelledby="heading{$panelId}">
						<div class="panel-body">
							<table class="table table-striped table-sticky">
								<thead >
								<tr class="permissionRow">
									<th id='permissionLabel' style="vertical-align: middle;"><strong>{translate text="Permissions for" isAdminFacing=true}</strong> {translate text=$selectedRole->name isAdminFacing=true isAdminEnteredData=true}</th>
									<th style="min-width: 100px">
										<label class="btn btn-default btn-sm pull-right">
											<input style="position: absolute; clip: rect(0,0,0,0); pointer-events: none;" type="checkbox" name="permission[{$sectionName}]" id="allPermissions{$panelId}" title="{translate text="Toggle all permissions in %1 for %2%" 1=$sectionName 2=$selectedRole->name inAttribute=true isAdminFacing=true}" onclick="AspenDiscovery.toggleCheckboxes('.selectedPermission{$panelId}', '#allPermissions{$panelId}');" />
											{translate text="Select All" isAdminFacing=true}
										</label>
									</th>
								</tr>
								</thead>
								<tbody>
								{* Render any dropdowns for groups that belong to this section *}
								{foreach from=$permissionGroups key=groupKey item=groupDef}
									{if $sectionName == $groupDef.sectionName}
										<tr class="permissionRow">
											<th scope="row" style="vertical-align: middle;">
												<span id='permissionLabel' style="display: block">{translate text=$groupDef.label isAdminFacing=true}</span>
												<small id='permissionDescription' class="text-muted">{translate text=$groupDef.description isAdminFacing=true}</small>
											</th>
											<td class="text-right">
												<select name="permissionGroup[{$groupKey}]" class="form-control input-sm">
													<option value="">{translate text="None" isAdminFacing=true}</option>
													{foreach from=$groupDef.permissions item=permName}
														{foreach from=$sectionPermissions key=permId item=permObj}
															{if $permObj->name == $permName}
																<option value="{$permId}" {if $selectedRole->hasPermission($permName)}selected{/if}>{translate text=$permName isAdminFacing=true}</option>
															{/if}
														{/foreach}
													{/foreach}
												</select>
											</td>
										</tr>
									{/if}
								{/foreach}
								{* Render all remaining checkboxes, skipping grouped and ProPay *}
								{foreach from=$sectionPermissions item=permission}
									{assign var=skipPermission value=false}
									{foreach from=$permissionGroups key=groupKey item=groupDef}
										{if $sectionName == $groupDef.sectionName && in_array($permission->name, $groupDef.permissions)}
											{assign var=skipPermission value=true}
										{/if}
									{/foreach}
									{if !$skipPermission && $permission->name != "Administer ProPay"}
										<tr class="permissionRow" id="{$permission->name}">
											<th scope="row" style="vertical-align: middle;">
												<span id='permissionLabel' style="display: block">{translate text=$permission->name isAdminFacing=true}</span>
												<small id='permissionDescription' class="text-muted">{translate text=$permission->description isAdminFacing=true}</small>
											</th>
											<td class="text-right">
												<div class="checkbox pull-right">
													<input type="checkbox" class="selectedPermission{$panelId}" name="permission[{$permission->id}]" title="{translate text="Toggle %1% for %2%" 1=$sectionName 2=$selectedRole->name inAttribute=true isAdminFacing=true}" {if $selectedRole->hasPermission($permission->name)}checked{/if}/>
												</div>
											</td>
										</tr>
									{/if}
								{/foreach}
								</tbody>
							</table>
						</div>
					</div>
				</div>
			{/foreach}
		</div>
		<button type="submit" name="submit" value="save" class="btn btn-primary" style="margin-top: 2em"><i class="fas fa-save" role="presentation"></i> {translate text="Save Changes" isAdminFacing=true}</button>
	</form>
{/strip}