<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Email/AmazonSesSetting.php';

class Admin_AmazonSesSettings extends ObjectEditor
{
	function launch()
	{
		global $interface;
		$objectAction = isset($_REQUEST['objectAction']) ? $_REQUEST['objectAction'] : null;
		if ($objectAction == 'validateFromAddress') {
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

	function getObjectType() : string
	{
		return 'AmazonSesSetting';
	}

	function getToolName() : string
	{
		return 'AmazonSesSettings';
	}

	function getModule() : string
	{
		return 'Admin';
	}

	function getPageTitle() : string
	{
		return 'Amazon SES Settings';
	}

	function getAllObjects($page, $recordsPerPage) : array
	{
		$object = new AmazonSesSetting();
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$this->applyFilters($object);
		$object->find();
		$objectList = array();
		while ($object->fetch()) {
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
	}
	function getDefaultSort() : string
	{
		return 'id asc';
	}

	function canSort() : bool
	{
		return false;
	}

	function getObjectStructure() : array
	{
		return AmazonSesSetting::getObjectStructure();
	}

	function getPrimaryKeyColumn() : string
	{
		return 'id';
	}

	function getIdKeyColumn() : string
	{
		return 'id';
	}

	function getInstructions() : string
	{
		return '';
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#system_admin', 'System Administration');
		$breadcrumbs[] = new Breadcrumb('/Admin/AmazonSesSettings', 'Amazon SES Settings');
		return $breadcrumbs;
	}

	function getActiveAdminSection() : string
	{
		return 'system_admin';
	}

	function canView() : bool
	{
		return UserAccount::userHasPermission('Administer Amazon SES');
	}
}