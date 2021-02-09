<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/OpenArchives/OpenArchivesCollection.php';
class OpenArchives_Collections extends ObjectEditor {
	function getObjectType(){
		return 'OpenArchivesCollection';
	}
	function getToolName(){
		return 'Collections';
	}
    function getModule(){
        return 'OpenArchives';
    }
	function getPageTitle(){
		return 'Open Archives collections to include';
	}
	function getAllObjects($page, $recordsPerPage){
		$list = array();

		$object = new OpenArchivesCollection();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$object->find();
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
		return OpenArchivesCollection::getObjectStructure();
	}
	function getPrimaryKeyColumn(){
		return 'id';
	}
	function getIdKeyColumn(){
		return 'id';
	}

	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#open_archives', 'Open Archives');
		$breadcrumbs[] = new Breadcrumb('/OpenArchives/Collections', 'Collections');
		return $breadcrumbs;
	}

	function getActiveAdminSection()
	{
		return 'open_archives';
	}

	function canView()
	{
		return UserAccount::userHasPermission('Administer Open Archives');
	}
}