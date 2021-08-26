{strip}
	{if !empty($selectedRole)}
	<h1>{translate text="Permissions for %1%" 1=$selectedRole->name}</h1>
	{else}
	<h1>{translate text="Permissions"}</h1>
	{/if}

	<form class="form-inline row" id="selectRoleForm">
		<div class="form-group col-tn-12">
			<label for="roleId" class="control-label">{translate text="Role to edit"}</label>&nbsp;
			<select id="roleId" name="roleId" class="form-control input-sm" onchange="$('#selectRoleForm').submit()">
				{foreach from=$roles key=roleId item=role}
					<option value="{$roleId}" {if $roleId == $selectedRole->roleId}selected{/if}>{$role->name}</option>
				{/foreach}
			</select>
			<a class="btn btn-danger btn-sm" onclick="if (confirm('{translate text="Are you sure you want to delete this role" inAttribute=true isAdminFacing=true}')){ldelim}return AspenDiscovery.Admin.deleteRole({$selectedRole->roleId}){rdelim}else{ldelim}return false{rdelim}">{translate text="Delete"}</a>
			<a class="btn btn-default btn-sm" onclick="return AspenDiscovery.Admin.showCreateRoleForm()">{translate text="Create New Role"}</a>
		</div>
	</form>

	<form>
		<input type="hidden" name="roleId" value="{$selectedRole->roleId}"/>
		<table id="permissionsTable" class="table-striped table-condensed table-sticky" style="display:block; overflow: auto;">
			<thead class="permissionsHeader">
				<tr>
					<th>{translate text="Permission"}</th>
					<th>{$selectedRole->name|translate}</th>
				</tr>
			</thead>
			<tbody>
				{foreach from=$permissions item=sectionPermissions key=sectionName}
					<tr class="permissionSection">
						<td colspan="2">
							{$sectionName|translate}
						</td>
					</tr>
					{foreach from=$sectionPermissions item=permission}
						<tr class="permissionRow">
							<td>
								<div class="permissionName">{$permission->name|translate}</div>
								<div class="permissionDescription">{$permission->description|translate}</div>
							</td>
							<td><input type="checkbox" name="permission[{$permission->id}]" title="{translate text="Toggle %1% for %2%" 1=$permission->name 2=$selectedRole->name inAttribute=true}" {if $selectedRole->hasPermission($permission->name)}checked{/if}/></td>
						</tr>
					{/foreach}
				{/foreach}
			</tbody>
		</table>
		<div>
			<button type="submit" name="submit" value="save" class="btn btn-primary">{translate text="Save Changes"}</button>
		</div>
	</form>
{/strip}