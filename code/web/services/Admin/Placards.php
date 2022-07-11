<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/LocalEnrichment/Placard.php';

class Admin_Placards extends ObjectEditor
{

	function getObjectType() : string{
		return 'Placard';
	}
	function getToolName() : string{
		return 'Placards';
	}
	function getPageTitle() : string{
		return 'Placards';
	}
	function canDelete(){
		return UserAccount::userHasPermission(['Administer All Placards','Administer Library Placards']);
	}
	function getAllObjects($page, $recordsPerPage) : array{
		$object = new Placard();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$userHasExistingPlacards = true;
		if (!UserAccount::userHasPermission('Administer All Placards')){
			$libraryPlacard = new PlacardLibrary();
			$library = Library::getPatronHomeLibrary(UserAccount::getActiveUserObj());
			if ($library != null){
				$libraryPlacard->libraryId = $library->libraryId;
				$placardsForLibrary = [];
				$libraryPlacard->find();
				while ($libraryPlacard->fetch()){
					$placardsForLibrary[] = $libraryPlacard->placardId;
				}
				if (count($placardsForLibrary) > 0) {
					$object->whereAddIn('id', $placardsForLibrary, false);
				}else{
					$userHasExistingPlacards = false;
				}
			}
		}
		$object->find();
		$list = array();
		if ($userHasExistingPlacards) {
			while ($object->fetch()) {
				$list[$object->id] = clone $object;
			}
		}
		return $list;
	}
	function getDefaultSort() : string
	{
		return 'title asc';
	}
	function getObjectStructure() : array {
		return Placard::getObjectStructure();
	}
	function getPrimaryKeyColumn() : string{
		return 'id';
	}
	function getIdKeyColumn() : string{
		return 'id';
	}
	function getInstructions() : string
	{
		return 'https://help.aspendiscovery.org/help/promote/placards';
	}
	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#local_enrichment', 'Local Enrichment');
		$breadcrumbs[] = new Breadcrumb('/Admin/Placards', 'Placards');
		return $breadcrumbs;
	}

	function getActiveAdminSection() : string
	{
		return 'local_enrichment';
	}

	function canView() : bool
	{
		return UserAccount::userHasPermission(['Administer All Placards','Administer Library Placards', 'Edit Library Placards']);
	}

	function canAddNew()
	{
		return UserAccount::userHasPermission(['Administer All Placards','Administer Library Placards']);
	}
}