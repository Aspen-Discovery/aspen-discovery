<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Indexing/SideLoad.php';

class SideLoads_SideLoads extends ObjectEditor
{
	function launch()
	{
		global $interface;
		$objectAction = isset($_REQUEST['objectAction']) ? $_REQUEST['objectAction'] : null;
		if ($objectAction == 'viewMarcFiles') {
			$id = $_REQUEST['id'];
			$interface->assign('id', $id);
			$files = array();
			$sideLoadConfiguration = new SideLoad();
			$sideLoadConfiguration->id = $id;
			if ($sideLoadConfiguration->find(true) && !empty($sideLoadConfiguration->marcPath)) {
				$interface->assign('sideload', $sideLoadConfiguration);
				$marcPath = $sideLoadConfiguration->marcPath;
				if ($handle = opendir($marcPath)) {
					while (false !== ($entry = readdir($handle))) {
						if ($entry != "." && $entry != "..") {
							$fullName = $marcPath . DIR_SEP . $entry;
							$files[$entry] = [
								'date' => filectime($fullName),
								'size' => filesize($fullName)
							];
						}
					}
					closedir($handle);
					$interface->assign('files', $files);
					$interface->assign('SideLoadName', $sideLoadConfiguration->name);
					$this->display('marcFiles.tpl', 'Marc Files');
				}
			}
		} else {
			parent::launch();
		}
	}

	function getObjectType()
	{
		return 'SideLoad';
	}

	function getModule()
	{
		return "SideLoads";
	}

	function getToolName()
	{
		return 'SideLoads';
	}

	function getPageTitle()
	{
		return 'Side Loaded eContent Collections';
	}

	function getAllObjects()
	{
		$list = array();

		$object = new SideLoad();
		$object->orderBy('name');
		$object->find();
		while ($object->fetch()) {
			$list[$object->id] = clone $object;
		}

		return $list;
	}

	function getObjectStructure()
	{
		return SideLoad::getObjectStructure();
	}

	function getAllowableRoles()
	{
		return array('opacAdmin');
	}

	function getPrimaryKeyColumn()
	{
		return 'id';
	}

	function getIdKeyColumn()
	{
		return 'id';
	}

	function canAddNew()
	{
		return UserAccount::userHasRole('opacAdmin');
	}

	function canDelete()
	{
		return UserAccount::userHasRole('opacAdmin');
	}

	function getInstructions()
	{
		return null;
	}

	function getAdditionalObjectActions($existingObject)
	{
		$actions = array();
		if ($existingObject && $existingObject->id != '') {
			$actions[] = array(
				'text' => 'View MARC files',
				'url' => '/SideLoads/SideLoads?objectAction=viewMarcFiles&id=' . $existingObject->id,
			);
			$actions[] = array(
				'text' => 'Upload MARC file',
				'url' => '/SideLoads/UploadMarc?id=' . $existingObject->id,
			);
		}

		return $actions;
	}

}