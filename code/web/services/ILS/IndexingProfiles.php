<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Indexing/IndexingProfile.php';

class ILS_IndexingProfiles extends ObjectEditor {
	function launch()
	{
		global $interface;
		$objectAction = isset($_REQUEST['objectAction']) ? $_REQUEST['objectAction'] : null;
		if ($objectAction == 'viewMarcFiles') {
			$id = $_REQUEST['id'];
			$interface->assign('id', $id);
			$files = array();
			$indexProfile = new IndexingProfile();
			if ($indexProfile->get($id) && !empty($indexProfile->marcPath)) {

				$marcPath = $indexProfile->marcPath;
				if ($handle = opendir($marcPath)) {
					while (false !== ($entry = readdir($handle))) {
						if ($entry != "." && $entry != "..") {
							$files[$entry] = filectime($marcPath . DIR_SEP . $entry);
						}
					}
					closedir($handle);
					$interface->assign('files', $files);
					$interface->assign('IndexProfileName', $indexProfile->name);
					$this->display('marcFiles.tpl', 'Marc Files');
				}
			}
		} else {
			parent::launch();
		}
	}


	function getObjectType(){
		return 'IndexingProfile';
	}
	function getModule()
    {
        return "ILS";
    }

    function getToolName(){
		return 'IndexingProfiles';
	}
	function getPageTitle(){
		return 'ILS Indexing Information';
	}
	function getAllObjects(){
		$list = array();

		$object = new IndexingProfile();
		$object->orderBy('name');
		$object->find();
		while ($object->fetch()){
			$list[$object->id] = clone $object;
		}

		return $list;
	}
	function getObjectStructure(){
		return IndexingProfile::getObjectStructure();
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

	function getInstructions(){
		return '';
	}

	function getAdditionalObjectActions($existingObject){
		$actions = array();
		if ($existingObject && $existingObject->id != ''){
			$actions[] = array(
				'text' => 'View MARC files',
				'url' => '/ILS/IndexingProfiles?objectAction=viewMarcFiles&id=' . $existingObject->id,
			);
		}

		return $actions;
	}

}