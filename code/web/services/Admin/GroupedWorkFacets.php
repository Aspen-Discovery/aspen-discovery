<?php
require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Grouping/GroupedWorkFacetGroup.php';

class Admin_GroupedWorkFacets extends ObjectEditor
{
	function getObjectType(){
		return 'GroupedWorkFacetGroup';
	}
	function getToolName(){
		return 'GroupedWorkFacets';
	}
	function getPageTitle(){
		return 'Grouped Work Facets';
	}
	function getAllObjects($page, $recordsPerPage){
		$object = new GroupedWorkFacetGroup();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		if (!UserAccount::userHasPermission('Administer All Grouped Work Facets')){
			$library = Library::getPatronHomeLibrary(UserAccount::getActiveUserObj());
			$groupedWorkDisplaySettings = new GroupedWorkDisplaySetting();
			$groupedWorkDisplaySettings->id = $library->groupedWorkDisplaySettingId;
			$groupedWorkDisplaySettings->find(true);
			$object->id = $groupedWorkDisplaySettings->facetGroupId;
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
		return GroupedWorkFacetGroup::getObjectStructure();
	}
	function getPrimaryKeyColumn(){
		return 'id';
	}
	function getIdKeyColumn(){
		return 'id';
	}

	function getInstructions(){
		//return 'For more information on themes see TBD';
		return '/Admin/HelpManual?page=Grouped-Work-Facets';
	}

	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#cataloging', 'Catalog / Grouped Works');
		$breadcrumbs[] = new Breadcrumb('/Admin/GroupedWorkFacets', 'Grouped Work Facets');
		return $breadcrumbs;
	}

	function getActiveAdminSection()
	{
		return 'cataloging';
	}

	function canView()
	{
		return UserAccount::userHasPermission(['Administer All Grouped Work Facets','Administer Library Grouped Work Facets']);
	}
}