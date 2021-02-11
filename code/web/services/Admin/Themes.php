<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Theming/Theme.php';

class Admin_Themes extends ObjectEditor
{
	function getObjectType(){
		return 'Theme';
	}
	function getToolName(){
		return 'Themes';
	}
	function getPageTitle(){
		return 'Themes';
	}
	function canDelete(){
		return UserAccount::userHasPermission('Administer All Themes');
	}
	function getAllObjects($page, $recordsPerPage){
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
	function getDefaultSort()
	{
		return 'themeName asc';
	}
	function getObjectStructure(){
		return Theme::getObjectStructure();
	}
	function getPrimaryKeyColumn(){
		return 'id';
	}
	function getIdKeyColumn(){
		return 'id';
	}

	function getInstructions(){
		//return 'For more information on themes see TBD';
		return '';
	}

	function getExistingObjectById($id){
		$existingObject = parent::getExistingObjectById($id);
		if ($existingObject != null && $existingObject instanceof Theme){
			$existingObject->applyDefaults();
		}
		return $existingObject;
	}

	function getBreadcrumbs()
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

	function getActiveAdminSection()
	{
		return 'theme_and_layout';
	}

	function canView()
	{
		return UserAccount::userHasPermission(['Administer All Themes','Administer Library Themes']);
	}
}