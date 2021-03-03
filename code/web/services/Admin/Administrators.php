<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';

class Admin_Administrators extends ObjectEditor
{
	function getObjectType(){
		return 'User';
	}
	function getToolName(){
		return 'Administrators';
	}
	function getPageTitle(){
		return 'Administrators';
	}

	//TODO: This currently does not respect loading by page or filtering
	function getAllObjects($page, $recordsPerPage){
		require_once ROOT_DIR . '/sys/Administration/UserRoles.php';
		$userRole = new UserRoles();
		$userRole->find();
		$adminList = array();
		while ($userRole->fetch()){
			$userId = $userRole->userId;
			if (!array_key_exists($userId, $adminList)){
				$admin = new User();
				$admin->id = $userId;
				if ($admin->find(true)){
					$homeLibrary = Library::getLibraryForLocation($admin->homeLocationId);
					if ($homeLibrary != null){
						/** @noinspection PhpUndefinedFieldInspection */
						$admin->homeLibraryName = $homeLibrary->displayName;
					}else{
						/** @noinspection PhpUndefinedFieldInspection */
						$admin->homeLibraryName = 'Unknown';
					}

					$location = new Location();
					$location->locationId = $admin->homeLocationId;
					if ($location->find(true)) {
						/** @noinspection PhpUndefinedFieldInspection */
						$admin->homeLocation = $location->displayName;
					}else{
						/** @noinspection PhpUndefinedFieldInspection */
						$admin->homeLocation = 'Unknown';
					}
					$adminList[$userId] = $admin;
				}
			}
		}

		return $adminList;
	}
	function getDefaultSort()
	{
		return 'id';
	}
	function canSort()
	{
		return false;
	}

	function getObjectStructure(){
		return User::getObjectStructure();
	}
	function getPrimaryKeyColumn(){
		return 'cat_password';
	}
	function getIdKeyColumn(){
		return 'id';
	}
	function canAddNew(){
		return false;
	}
	function canCompare()
	{
		return false;
	}
	function canCopy()
	{
		return false;
	}

	function customListActions(){
		return array(
		array('label'=>'Add Administrator', 'action'=>'addAdministrator'),
		);
	}

	/** @noinspection PhpUnused */
	function addAdministrator(){
		global $interface;
		//Basic List
		$interface->setTemplate('addAdministrator.tpl');
	}

	/** @noinspection PhpUnused */
	function processNewAdministrator(){
		global $interface;
		global $configArray;
		$login = trim($_REQUEST['login']);
		$newAdmin = new User();
		$barcodeProperty = $configArray['Catalog']['barcodeProperty'];

		$newAdmin->$barcodeProperty = $login;
		$newAdmin->find();
		$numResults = $newAdmin->getNumResults();
		if ($numResults == 0){
			//See if we can fetch the user from the ils
			$newAdmin = UserAccount::findNewUser($login);
			if ($newAdmin == false){
				$interface->assign('error', 'Could not find a user with that barcode.');
			}
		}elseif ($numResults == 1){
			$newAdmin->fetch();
		}elseif ($numResults > 1){
			$newAdmin = false;
			$interface->assign('error', "Found multiple ({$numResults}) users with that barcode. (The database needs to be cleaned up.)");
		}

		if ($newAdmin != false) {
			if (isset($_REQUEST['roles'])) {
				$newAdmin->setRoles($_REQUEST['roles']);
				$newAdmin->update();
			} else {
				$newAdmin->query('DELETE FROM user_roles where user_roles.userId = ' . $newAdmin->id);
			}

			header("Location: /{$this->getModule()}/{$this->getToolName()}");
			die();
		}else{
			$interface->setTemplate('addAdministrator.tpl');
		}
	}

	function getInstructions(){
		return '';
	}

	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#system_admin', 'System Administration');
		$breadcrumbs[] = new Breadcrumb('', 'Administrators');
		return $breadcrumbs;
	}

	function getActiveAdminSection()
	{
		return 'system_admin';
	}

	function canView()
	{
		return UserAccount::userHasPermission('Administer Users');
	}

	function canBatchEdit()
	{
		return false;
	}

	function canFilter($objectStructure)
	{
		return false;
	}
}