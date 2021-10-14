<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Browse/BrowseCategory.php';

class Admin_BrowseCategories extends ObjectEditor
{

	function getObjectType() : string{
		return 'BrowseCategory';
	}
	function getToolName() : string{
		return 'BrowseCategories';
	}
	function getPageTitle() : string{
		return 'Browse Categories';
	}
	function getAllObjects($page, $recordsPerPage) : array{
		$object = new BrowseCategory();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		if (!UserAccount::userHasPermission('Administer All Browse Categories')){
			$library = Library::getPatronHomeLibrary(UserAccount::getActiveUserObj());
			$libraryId = $library == null ? -1 : $library->libraryId;
			$object->whereAdd("sharing = 'everyone'");
			$object->whereAdd("sharing = 'library' AND libraryId = " . $libraryId, 'OR');
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
		return 'label asc';
	}

	function getObjectStructure() : array{
		return BrowseCategory::getObjectStructure();
	}
	function getPrimaryKeyColumn() : string{
		return 'id';
	}
	function getIdKeyColumn() : string{
		return 'id';
	}

	function getInstructions() : string{
		return '';
	}

	function getInitializationJs() : string {
		return 'AspenDiscovery.Admin.updateBrowseSearchForSource();return AspenDiscovery.Admin.updateBrowseCategoryFields();';
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#local_enrichment', 'Local Enrichment');
		$breadcrumbs[] = new Breadcrumb('/Admin/BrowseCategories', 'Browse Categories');
		return $breadcrumbs;
	}

	function getActiveAdminSection() : string
	{
		return 'local_enrichment';
	}

	function canView() : bool
	{
		return UserAccount::userHasPermission(['Administer All Browse Categories','Administer Library Browse Categories']);
	}

	protected function getDefaultRecordsPerPage()
	{
		return 100;
	}

	protected function showQuickFilterOnPropertiesList(){
		return true;
	}

	function getNumObjects(): int
	{
		if ($this->_numObjects == null){
			if (!UserAccount::userHasPermission('Administer All Browse Categories')) {
				/** @var DataObject $object */
				$library = Library::getPatronHomeLibrary(UserAccount::getActiveUserObj());
				$libraryId = $library == null ? -1 : $library->libraryId;
				$objectType = $this->getObjectType();
				$object = new $objectType();
				$object->whereAdd("sharing = 'everyone'");
				$object->whereAdd("sharing = 'library' AND libraryId = " . $libraryId, 'OR');
				$this->applyFilters($object);
				$this->_numObjects = $object->count();
			} else if (UserAccount::userHasPermission('Administer All Browse Categories')) {
				/** @var DataObject $object */
				$objectType = $this->getObjectType();
				$object = new $objectType();
				$this->applyFilters($object);
				$this->_numObjects = $object->count();
			}
		}
		return $this->_numObjects;
	}

}