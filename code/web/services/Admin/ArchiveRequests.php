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
require_once ROOT_DIR . '/sys/Archive/ArchiveRequest.php';
class Admin_ArchiveRequests extends ObjectEditor {
	function getObjectType(){
		return 'ArchiveRequest';
	}
	function getToolName(){
		return 'ArchiveRequests';
	}
	function getPageTitle(){
		return 'Requests for Copies of Archive Materials';
	}
	function getAllObjects(){
		$list = array();

		$object = new ArchiveRequest();
		$object->orderBy('dateRequested desc');
		$user = UserAccount::getLoggedInUser();
		if (!UserAccount::userHasRole('opacAdmin')){
			$homeLibrary = $user->getHomeLibrary();
			$archiveNamespace = $homeLibrary->archiveNamespace;
			$object->whereAdd("pid LIKE '{$archiveNamespace}:%'");
		}
		$object->find();
		while ($object->fetch()){
			$list[$object->id] = clone $object;
		}

		return $list;
	}
	function getObjectStructure(){
		return ArchiveRequest::getObjectStructure();
	}
	function getAllowableRoles(){

		return array('opacAdmin', 'archives');
	}
	function getPrimaryKeyColumn(){
		return 'id';
	}
	function getIdKeyColumn(){
		return 'id';
	}
	function canAddNew(){
		return false;
	}
	function canDelete(){
		return UserAccount::userHasRole('opacAdmin');
	}

}