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
					$index = 0;
					while (false !== ($entry = readdir($handle))) {
						if ($entry != "." && $entry != "..") {
							$fullName = $marcPath . DIR_SEP . $entry;
							$files[$entry] = [
								'date' => filectime($fullName),
								'size' => filesize($fullName),
								'index' => $index++,
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

	function getAllObjects($page, $recordsPerPage)
	{
		$list = array();

		$object = new SideLoad();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$object->find();
		while ($object->fetch()) {
			$list[$object->id] = clone $object;
		}

		return $list;
	}

	function getDefaultSort()
	{
		return 'name asc';
	}

	function getObjectStructure()
	{
		return SideLoad::getObjectStructure();
	}

	function getPrimaryKeyColumn()
	{
		return 'id';
	}

	function getIdKeyColumn()
	{
		return 'id';
	}

	function getInstructions()
	{
		return '/Admin/HelpManual?page=Side-Loaded-eContent';
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

	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#side_loads', 'Side Load');
		$breadcrumbs[] = new Breadcrumb('/SideLoads/SideLoads', 'Side Load Settings');
		return $breadcrumbs;
	}

	function getActiveAdminSection()
	{
		return 'side_loads';
	}

	function canView()
	{
		return UserAccount::userHasPermission('Administer Side Loads');
	}
}