<?php

require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/Administration/Role.php';
require_once ROOT_DIR . '/sys/Administration/Permission.php';
class Admin_Permissions extends Admin_Admin
{
	function launch(){
		global $interface;
		global $enabledModules;

		$roles = [];
		$role = new Role();
		$role->orderBy('name');
		$role->find();
		/** @var Role $selectedRole */
		$selectedRole = null;
		while ($role->fetch()){
			$roles[$role->roleId] = clone $role;
			if ($selectedRole == null){
				$selectedRole = $roles[$role->roleId];
			}
			if (isset($_REQUEST['roleId']) && $_REQUEST['roleId'] == $role->roleId){
				$selectedRole = $roles[$role->roleId];
			}
		}
		$interface->assign('selectedRole', $selectedRole);
		if (isset($_REQUEST['submit']) && $selectedRole != null){
			$selectedPermissions = [];
			foreach ($_REQUEST['permission'] as $permissionId => $selected){
				if ($selected){
					$selectedPermissions[] = $permissionId;
				}
			}
			$selectedRole->setActivePermissions($selectedPermissions);
		}
		$interface->assign('roles', $roles);
		$interface->assign('numRoles', count($roles));
		$permissions = [];
		$permission = new Permission();
		$permission->orderBy(['sectionName', 'weight']);
		$permission->find();
		while ($permission->fetch()){
			if (!empty($permission->requiredModule) && !array_key_exists($permission->requiredModule, $enabledModules)){
				continue;
			}
			if (!array_key_exists($permission->sectionName, $permissions)){
				$permissions[$permission->sectionName] = [];
			}
			$permissions[$permission->sectionName][$permission->id] = clone $permission;
		}
		$interface->assign('permissions', $permissions);

		$this->display('permissions.tpl', 'Permissions');

	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#system_admin', 'System Administration');
		$breadcrumbs[] = new Breadcrumb('/Admin/Administrators', 'Administrators');
		$breadcrumbs[] = new Breadcrumb('', 'Permissions');
		return $breadcrumbs;
	}

	function canView() : bool
	{
		return UserAccount::userHasPermission('Administer Permissions');
	}

	function getActiveAdminSection() : string
	{
		return 'system_admin';
	}
}