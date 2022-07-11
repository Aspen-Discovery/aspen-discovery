<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Theming/Theme.php';

class Admin_Themes extends ObjectEditor
{
	function getObjectType() : string{
		return 'Theme';
	}
	function getToolName() : string{
		return 'Themes';
	}
	function getPageTitle() : string{
		return 'Themes';
	}
	function canDelete(){
		return UserAccount::userHasPermission('Administer All Themes');
	}
	function getAllObjects($page, $recordsPerPage) : array{
		$object = new Theme();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		if (!UserAccount::userHasPermission('Administer All Themes')){
			$library = Library::getPatronHomeLibrary(UserAccount::getActiveUserObj());
			$object->id = $library->theme;
		}
		$object->find();
		$list = array();
		while ($object->fetch()){
			$list[$object->id] = clone $object;
		}
		return $list;
	}
	function getDefaultSort() : string
	{
		return 'themeName asc';
	}
	function getObjectStructure() : array {
		return Theme::getObjectStructure();
	}
	function getPrimaryKeyColumn() : string{
		return 'id';
	}
	function getIdKeyColumn() : string{
		return 'id';
	}

	function getInstructions() : string{
		return 'https://help.aspendiscovery.org/help/admin/theme';
	}

	function getExistingObjectById($id) : ?DataObject {
		$existingObject = parent::getExistingObjectById($id);
		if ($existingObject != null && $existingObject instanceof Theme){
			$existingObject->applyDefaults();
		}
		return $existingObject;
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#theme_and_layout', 'Configuration Templates');
		$breadcrumbs[] = new Breadcrumb('/Admin/Themes', 'Themes');
		if (!empty($this->activeObject) && $this->activeObject instanceof Theme){
			$themes = $this->activeObject->getAllAppliedThemes();
			$themeBreadcrumbs = [];
			foreach ($themes as $theme){
				if ($theme->id == $this->activeObject->id){
					$themeBreadcrumbs[] = new Breadcrumb('', $theme->themeName);
				}else{
					$themeBreadcrumbs[] = new Breadcrumb('/Admin/Themes?objectAction=edit&id=' . $theme->id, $theme->themeName);
				}
			}
			$breadcrumbs = array_merge($breadcrumbs, array_reverse($themeBreadcrumbs));
		}
		return $breadcrumbs;
	}

	function getActiveAdminSection() : string
	{
		return 'theme_and_layout';
	}

	function canView() : bool
	{
		return UserAccount::userHasPermission(['Administer All Themes','Administer Library Themes']);
	}

	protected function getDefaultRecordsPerPage()
	{
		return 100;
	}

	protected function showQuickFilterOnPropertiesList(){
		return true;
	}
}