{strip}
	{if !empty($selectedRole)}
	<h1>{translate text="Permissions for %1%" 1=$selectedRole->name isAdminFacing=true}</h1>
	{else}
	<h1>{translate text="Permissions" isAdminFacing=true}</h1>
	{/if}

	<form class="form-inline row" id="selectRoleForm">
		<div class="form-group col-tn-12">
			<label for="roleId" class="control-label">{translate text="Role to edit" isAdminFacing=true}</label>&nbsp;
			<select id="roleId" name="roleId" class="form-control input-sm" onchange="$('#selectRoleForm').submit()">
				{foreach from=$roles key=roleId item=role}
					<option value="{$roleId}" {if $roleId == $selectedRole->roleId}selected{/if}>{$role->name}</option>
				{/foreach}
			</select>
			<a class="btn btn-danger btn-sm" onclick="if (confirm('{translate text="Are you sure you want to delete this role" inAttribute=true isAdminFacing=true}')){ldelim}return AspenDiscovery.Admin.deleteRole({$selectedRole->roleId}){rdelim}else{ldelim}return false{rdelim}">{translate text="Delete" isAdminFacing=true}</a>
			<a class="btn btn-default btn-sm" onclick="return AspenDiscovery.Admin.showCreateRoleForm()">{translate text="Create New Role" isAdminFacing=true}</a>
		</div>
	</form>

	<form>
		<input type="hidden" name="roleId" value="{$selectedRole->roleId}"/>
		<table id="permissionsTable" class="table-striped table-condensed table-sticky" style="display:block; overflow: auto;">
			<thead class="permissionsHeader">
				<tr>
					<th>{translate text="Permission" isAdminFacing=true}</th>
					<th>{translate text=$selectedRole->name isAdminFacing=true isAdminEnteredData=true}</th>
				</tr>
			</thead>
			<tbody>
				{foreach from=$permissions item=sectionPermissions key=sectionName}
					<tr class="permissionSection">
						<td colspan="2">
							{translate text=$sectionName isAdminFacing=true}
						</td>
					</tr>
					{foreach from=$sectionPermissions item=permission}
						<tr class="permissionRow">
							<td>
								<div class="permissionName">{translate text=$permission->name isAdminFacing=true}</div>
								<div class="permissionDescription">{translate text=$permission->description isAdminFacing=true}</div>
							</td>
							<td><input type="checkbox" name="permission[{$permission->id}]" title="{translate text="Toggle %1% for %2%" 1=$permission->name 2=$selectedRole->name inAttribute=true isAdminFacing=true}" {if $selectedRole->hasPermission($permission->name)}checked{/if}/></td>
						</tr>
					{/foreach}
				{/foreach}
			</tbody>
		</table>
		<div>
			<button type="submit" name="submit" value="save" class="btn btn-primary">{translate text="Save Changes" isAdminFacing=true}</button>
		</div>
	</form>
{/strip}