<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/LocalEnrichment/Placard.php';

class Admin_Placards extends ObjectEditor
{

	function getObjectType(){
		return 'Placard';
	}
	function getToolName(){
		return 'Placards';
	}
	function getPageTitle(){
		return 'Placards';
	}
	function canDelete(){
		return UserAccount::userHasPermission(['Administer All Placards','Administer Library Placards']);
	}
	function getAllObjects($page, $recordsPerPage){
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
	function getDefaultSort()
	{
		return 'title asc';
	}
	function getObjectStructure(){
		return Placard::getObjectStructure();
	}
	function getPrimaryKeyColumn(){
		return 'id';
	}
	function getIdKeyColumn(){
		return 'id';
	}
	function getInstructions()
	{
		return '/Admin/HelpManual?page=Placards';
	}
	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#local_enrichment', 'Local Enrichment');
		$breadcrumbs[] = new Breadcrumb('/Admin/Placards', 'Placards');
		return $breadcrumbs;
	}

	function getActiveAdminSection()
	{
		return 'local_enrichment';
	}

	function canView()
	{
		return UserAccount::userHasPermission(['Administer All Placards','Administer Library Placards']);
	}
}