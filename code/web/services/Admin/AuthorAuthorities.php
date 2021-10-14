<?php
require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/sys/Grouping/AuthorAuthority.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';

class Admin_AuthorAuthorities extends ObjectEditor
{
	function getObjectType() : string{
		return 'AuthorAuthority';
	}
	function getToolName() : string{
		return 'AuthorAuthorities';
	}
	function getPageTitle() : string{
		return 'Author Authorities';
	}
	function getAllObjects($page, $recordsPerPage) : array{
		$object = new AuthorAuthority();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$object->find();
		$objectList = array();
		while ($object->fetch()){
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
	}

	function getDefaultSort() : string{
		return 'author asc';
	}
	function getObjectStructure() : array {
		return AuthorAuthority::getObjectStructure();
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

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#cataloging', 'Catalog / Grouped Works');
		$breadcrumbs[] = new Breadcrumb('/Admin/AuthorAuthorities', 'Author Authorities');
		return $breadcrumbs;
	}

	function getActiveAdminSection() : string
	{
		return 'cataloging';
	}

	function canView() : bool
	{
		return UserAccount::userHasPermission('Manually Group and Ungroup Works');
	}
}