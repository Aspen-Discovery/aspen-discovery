<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';

class Admin_Administrators extends ObjectEditor
{
	function getObjectType() : string{
		return 'User';
	}
	function getToolName() : string{
		return 'Administrators';
	}
	function getPageTitle() : string{
		return 'Administrators';
	}

	//TODO: This currently does not respect loading by page or filtering
	function getAllObjects($page, $recordsPerPage) : array{
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
	function getDefaultSort() : string
	{
		return 'id';
	}
	function canSort() : bool
	{
		return false;
	}

	function getObjectStructure() : array {
		return User::getObjectStructure();
	}
	function getPrimaryKeyColumn() : string{
		return 'cat_password';
	}
	function getIdKeyColumn() : string{
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
		$loginRaw = trim($_REQUEST['login']);
		$logins = preg_split("/\\r\\n|\\r|\\n/", $loginRaw);
		$errors = [];
		foreach ($logins as $login) {
			$newAdmin = new User();
			$barcodeProperty = $configArray['Catalog']['barcodeProperty'];

			$newAdmin->$barcodeProperty = $login;
			$newAdmin->find();
			$numResults = $newAdmin->getNumResults();
			if ($numResults == 0) {
				//See if we can fetch the user from the ils
				$newAdmin = UserAccount::findNewUser($login);
				if ($newAdmin == false) {
					$errors[$login] = translate(['text' => 'Could not find a user with that barcode.', 'isAdminFacing' => true]);
				}
			} elseif ($numResults == 1) {
				$newAdmin->fetch();
			} elseif ($numResults > 1) {
				$newAdmin = false;
				$errors[$login] = translate(['text' => "Found multiple (%1%) users with that barcode. (The database needs to be cleaned up.)", 'isAdminFacing' => true]);
			}

			if ($newAdmin != false) {
				if (isset($_REQUEST['roles'])) {
					$newAdmin->setRoles($_REQUEST['roles']);
					$newAdmin->update();
				} else {
					$newAdmin->query('DELETE FROM user_roles where user_roles.userId = ' . $newAdmin->id);
				}
			}
		}

		if (count($errors) == 0){
			header("Location: /{$this->getModule()}/{$this->getToolName()}");
			die();
		} else {
			$interface->assign('errors', $errors);
			$interface->setTemplate('addAdministrator.tpl');
		}
	}

	function getInstructions() : string{
		return '';
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#system_admin', 'System Administration');
		$breadcrumbs[] = new Breadcrumb('', 'Administrators');
		return $breadcrumbs;
	}

	function getActiveAdminSection() : string
	{
		return 'system_admin';
	}

	function canView() : bool
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

	protected function showQuickFilterOnPropertiesList(){
		return true;
	}

	protected function supportsPagination(){
		return false;
	}
}