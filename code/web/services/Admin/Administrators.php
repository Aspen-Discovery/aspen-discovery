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
	function getAllObjects(){
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
						$admin->homeLibraryName = $homeLibrary->displayName;
					}else{
						$admin->homeLibraryName = 'Unknown';
					}

					$location = new Location();
					$location->locationId = $admin->homeLocationId;
					if ($location->find(true)) {
						$admin->homeLocation = $location->displayName;
					}else{
						$admin->homeLocation = 'Unknown';
					}
					$adminList[$userId] = $admin;
				}
			}
		}

		return $adminList;
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
	function getAllowableRoles(){
		return array('userAdmin');
	}
	function canAddNew(){
		return false;
	}
	function customListActions(){
		return array(
		array('label'=>'Add Administrator', 'action'=>'addAdministrator'),
		);
	}
	function addAdministrator(){
		global $interface;
		//Basic List
		$interface->setTemplate('addAdministrator.tpl');
	}
	function processNewAdministrator(){
		global $interface;
		global $configArray;
		$login = $_REQUEST['login'];
		$newAdmin = new User();
		$barcodeProperty = $configArray['Catalog']['barcodeProperty'];

		$newAdmin->$barcodeProperty = $login;
		$newAdmin->find();
		if ($newAdmin->getNumResults() == 1){
			global $logger;
			//$logger->log(print_r($_REQUEST['roles'], TRUE));
			if (isset($_REQUEST['roles'])){
				$newAdmin->fetch();
				$newAdmin->roles = $_REQUEST['roles'];
				$newAdmin->update();
			}else{
				$newAdmin->fetch();
				$newAdmin->query('DELETE FROM user_roles where user_roles.userId = ' . $newAdmin->id);
			}

			global $configArray;
			header("Location: /{$this->getModule()}/{$this->getToolName()}");
			die();
		}else{
			if ($newAdmin->getNumResults() == 0){
				$interface->assign('error', 'Could not find a user with that barcode. (The user needs to have logged in at least once.)');
			}else{
				$interface->assign('error', "Found multiple users with that barcode {$newAdmin->getNumResults()}. (The database needs to be cleaned up.)");
			}

			$interface->setTemplate('addAdministrator.tpl');
		}
	}

	function getInstructions(){
		return '';
	}
	function getListInstructions(){
		return $this->getInstructions();
	}
}