<?php
/**
 * Admin interface for creating indexing profiles
 *
 * @category Pika
 * @author Mark Noble <mark@marmot.org>
 * Date: 6/30/2015
 * Time: 1:23 PM
 */

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Account/AccountProfile.php';
class Admin_AccountProfiles extends ObjectEditor {
	function getObjectType(){
		return 'AccountProfile';
	}
	function getToolName(){
		return 'AccountProfiles';
	}
	function getPageTitle(){
		return 'Account Profiles';
	}
	function getAllObjects(){
		$list = array();

		$object = new AccountProfile();
		$object->orderBy('weight, name');
		$object->find();
		while ($object->fetch()){
			$list[$object->id] = clone $object;
		}

		return $list;
	}
	function getObjectStructure(){
		return AccountProfile::getObjectStructure();
	}
	function getAllowableRoles(){
		return array('opacAdmin');
	}
	function getPrimaryKeyColumn(){
		return 'id';
	}
	function getIdKeyColumn(){
		return 'id';
	}
	function canAddNew(){
		$user = UserAccount::getLoggedInUser();
		return UserAccount::userHasRole('opacAdmin');
	}
	function canDelete(){
		$user = UserAccount::getLoggedInUser();
		return UserAccount::userHasRole('opacAdmin');
	}

}