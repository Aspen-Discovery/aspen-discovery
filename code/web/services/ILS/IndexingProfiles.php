<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Indexing/IndexingProfile.php';

class ILS_IndexingProfiles extends ObjectEditor
{
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

	function getObjectType()
	{
		return 'IndexingProfile';
	}

	function getModule()
	{
		return "ILS";
	}

	function getToolName()
	{
		return 'IndexingProfiles';
	}

	function getPageTitle()
	{
		return 'ILS Indexing Information';
	}

	function getAllObjects($page, $recordsPerPage)
	{
		$list = array();

		$object = new IndexingProfile();
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
		return IndexingProfile::getObjectStructure();
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
		return true;
	}

	function canDelete()
	{
		return true;
	}

	function getInstructions()
	{
		return '';
	}

	function getAdditionalObjectActions($existingObject)
	{
		$actions = array();
		if ($existingObject && $existingObject->id != '') {
			$actions[] = array(
				'text' => 'View MARC files',
				'url' => '/ILS/IndexingProfiles?objectAction=viewMarcFiles&id=' . $existingObject->id,
			);
		}

		return $actions;
	}

	function getInitializationJs()
	{
		return 'return AspenDiscovery.Admin.updateIndexingProfileFields();';
	}

	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#ils_integration', 'ILS Integration');
		$breadcrumbs[] = new Breadcrumb('/ILS/IndexingProfiles', 'Indexing Profiles');
		return $breadcrumbs;
	}

	function getActiveAdminSection()
	{
		return 'ils_integration';
	}

	function canView()
	{
		return UserAccount::userHasPermission('Administer Indexing Profiles');
	}
}