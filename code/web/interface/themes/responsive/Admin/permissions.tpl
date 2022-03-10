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
				<a class="btn btn-danger btn-sm" style="margin-bottom: 0" onclick="if (confirm('{translate text="Are you sure you want to delete this role" inAttribute=true isAdminFacing=true}')){ldelim}return AspenDiscovery.Admin.deleteRole({$selectedRole->roleId}){rdelim}else{ldelim}return false{rdelim}"><i class="fas fa-trash"></i> {translate text="Delete" isAdminFacing=true}</a>
				<a class="btn btn-default btn-sm" style="margin-bottom: 0"  onclick="return AspenDiscovery.Admin.showCreateRoleForm()"><i class="fas fa-plus"></i> {translate text="Create New Role" isAdminFacing=true}</a>
			</div>
		</div>
	</form>

	<form>
		<input type="hidden" name="roleId" value="{$selectedRole->roleId}" />
		{assign var=panelId value=0}
		<div class="panel-group accordion" id="permissions-table-accordion">
			{foreach from=$permissions item=sectionPermissions key=sectionName}
				{assign var=panelId value=$panelId+1}
				<div class="panel panel-default {if $panelId == 1 && count($selectedSections) == 0 || in_array($sectionName, $selectedSections)}active{/if}">
					<div class="panel-heading" role="tab" id="heading{$panelId}">
						<h2 class="panel-title">
							<a role="button" data-toggle="collapse" data-parent="#permissionsTable" href="#permission{$panelId}Group" aria-expanded="true" aria-controls="permission{$panelId}PanelBody">
							{translate text=$sectionName isAdminFacing=true}
							</a>
						</h2>
					</div>
					<div class="panel-collapse collapse{if $panelId == 1 && count($selectedSections) == 0 || in_array($sectionName, $selectedSections)} in{/if}" id="permission{$panelId}Group" role="tabpanel" aria-labelledby="heading{$panelId}">
						<div class="panel-body">
							<table class="table table-striped table-sticky">
								<thead>
									<tr>
										<th><strong>{translate text="Permission" isAdminFacing=true}</strong></th>
										<th class="text-right" style="min-width: 200px">{translate text=$selectedRole->name isAdminFacing=true isAdminEnteredData=true}</th>
									</tr>
								</thead>
								<tbody>
								{foreach from=$sectionPermissions item=permission}
									<tr>
										<th scope="row" style="vertical-align: middle;">
											<span style="display: block">{translate text=$permission->name isAdminFacing=true}</span>
											<small class="text-muted">{translate text=$permission->description isAdminFacing=true}</small>
										</th>
										<td class="text-right">
											<div class="checkbox pull-right">
												<input type="checkbox" name="permission[{$permission->id}]" title="{translate text="Toggle %1% for %2%" 1=$permission->name 2=$selectedRole->name inAttribute=true isAdminFacing=true}" {if $selectedRole->hasPermission($permission->name)}checked{/if}/>
											</div>
										</td>
									</tr>
								{/foreach}
								</tbody>
							</table>
						</div>
					</div>
				</div>
			{/foreach}
		</div>
		<button type="submit" name="submit" value="save" class="btn btn-primary" style="margin-top: 2em"><i class="fas fa-save"></i> {translate text="Save Changes" isAdminFacing=true}</button>
	</form>
{/strip}