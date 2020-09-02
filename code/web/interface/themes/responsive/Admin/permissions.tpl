{strip}
	<h1>{translate text="Permissions"}</h1>

	<table id="permissionsTable" class="table-striped table-condensed table-sticky" style="display:block; overflow: auto;">
		<thead class="permissionsHeader">
			<tr>
				<th>{translate text="Permission"}</th>
				{foreach from=$roles item=role}
					<th>{$role->name}</th>
				{/foreach}
			</tr>
		</thead>
		<tbody>
			{foreach from=$permissions item=sectionPermissions key=sectionName}
				<tr class="permissionSection">
					<td colspan="{$numRoles}">
						{$sectionName|translate}
					</td>
				</tr>
				{foreach from=$sectionPermissions item=permission}
					<tr class="permissionRow">
						<td>
							<div class="permissionName">{$permission->name}</div>
							<div class="permissionDescription">{$permission->description}</div>
						</td>
						{foreach from=$roles item=role}
							<td><input type="checkbox" name="permission[{$role->id}][{$permission->id}]" title="Toggle {$permission->name} for {$role->name}" {if $role->hasPermission($permission->name)}checked{/if}/></td>
						{/foreach}
					</tr>
				{/foreach}
			{/foreach}
		</tbody>
	</table>
{/strip}