<?php
require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Grouping/GroupedWorkDisplaySetting.php';

class Admin_GroupedWorkDisplay extends ObjectEditor
{
	function getObjectType(){
		return 'GroupedWorkDisplaySetting';
	}
	function getToolName(){
		return 'GroupedWorkDisplay';
	}
	function getPageTitle(){
		return 'Grouped Work Display Settings';
	}
	function canDelete(){
		return UserAccount::userHasPermission('Administer All Grouped Work Display Settings');
	}
	function getAllObjects($page, $recordsPerPage){
		$object = new GroupedWorkDisplaySetting();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		if (!UserAccount::userHasPermission('Administer All Grouped Work Display Settings')){
			$library = Library::getPatronHomeLibrary(UserAccount::getActiveUserObj());
			$object->id = $library->groupedWorkDisplaySettingId;
		}
		$object->find();
		$list = array();
		while ($object->fetch()){
			$list[$object->id] = clone $object;
		}
		return $list;
	}
	function getDefaultSort()
	{
		return 'name asc';
	}

	function getObjectStructure(){
		return GroupedWorkDisplaySetting::getObjectStructure();
	}
	function getPrimaryKeyColumn(){
		return 'id';
	}
	function getIdKeyColumn(){
		return 'id';
	}

	function getInstructions(){
		return '/Admin/HelpManual?page=Grouped-Work-Display-Settings';
	}

	/** @noinspection PhpUnused */
	function resetMoreDetailsToDefault(){
		$groupedWorkSetting = new GroupedWorkDisplaySetting();
		$groupedWorkSettingId = $_REQUEST['id'];
		$groupedWorkSetting->id = $groupedWorkSettingId;
		if ($groupedWorkSetting->find(true)){
			$groupedWorkSetting->clearMoreDetailsOptions();

			$defaultOptions = array();
			require_once ROOT_DIR . '/RecordDrivers/RecordInterface.php';
			$defaultMoreDetailsOptions = RecordInterface::getDefaultMoreDetailsOptions();
			$i = 0;
			foreach ($defaultMoreDetailsOptions as $source => $defaultState){
				$optionObj = new GroupedWorkMoreDetails();
				$optionObj->groupedWorkSettingsId = $groupedWorkSettingId;
				$optionObj->collapseByDefault = $defaultState == 'closed';
				$optionObj->source = $source;
				$optionObj->weight = $i++;
				$defaultOptions[] = $optionObj;
			}

			$groupedWorkSetting->setMoreDetailsOptions($defaultOptions);
			$groupedWorkSetting->update();

			$_REQUEST['objectAction'] = 'edit';
		}
		header("Location: /Admin/GroupedWorkDisplay?objectAction=edit&id=" . $groupedWorkSettingId);
	}

	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#cataloging', 'Catalog / Grouped Works');
		$breadcrumbs[] = new Breadcrumb('/Admin/GroupedWorkDisplay', 'Grouped Work Display');
		return $breadcrumbs;
	}

	function getActiveAdminSection()
	{
		return 'cataloging';
	}

	function canView()
	{
		return UserAccount::userHasPermission(['Administer All Grouped Work Display Settings','Administer Library Grouped Work Display Settings']);
	}
}