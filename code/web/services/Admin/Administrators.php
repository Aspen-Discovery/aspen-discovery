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
		/** @var User $admin */
		$admin = new User();
		$admin->query('SELECT * FROM user INNER JOIN user_roles on user.id = user_roles.userId ORDER BY cat_password');
		$adminList = array();
		while ($admin->fetch()){
			$homeLibrary = Library::getLibraryForLocation($admin->homeLocationId);
			if ($homeLibrary != null){
				$admin->_homeLibraryName = $homeLibrary->displayName;
			}else{
				$admin->_homeLibraryName = 'Unknown';
			}

			$location = new Location();
			$location->locationId = $admin->homeLocationId;
			if ($location->find(true)) {
				$admin->_homeLocation = $location->displayName;
			}else{
				$admin->_homeLocation = 'Unknown';
			}

			$adminList[$admin->id] = clone $admin;
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
		if ($newAdmin->N == 1){
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
			header("Location: {$configArray['Site']['path']}/Admin/{$this->getToolName()}");
			die();
		}else{
			if ($newAdmin->N == 0){
				$interface->assign('error', 'Could not find a user with that barcode. (The user needs to have logged in at least once.)');
			}else{
				$interface->assign('error', "Found multiple users with that barcode {$newAdmin->N}. (The database needs to be cleaned up.)");
			}

			$interface->setTemplate('addAdministrator.tpl');
		}
	}

	function getInstructions(){
		return 'For more information about what each role can do, see the <a href="https://docs.google.com/spreadsheets/d/1sPR8mIidkg00B2XzgiEq1MMDO3Y2ZOZNH-y_xonN-zA">online documentation</a>.';
	}
	function getListInstructions(){
		return $this->getInstructions();
	}
}