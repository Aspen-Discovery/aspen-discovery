<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Translation/Language.php';

class Translation_Languages extends ObjectEditor
{
	function getObjectType()
	{
		return 'Language';
	}

	function getToolName()
	{
		return 'Languages';
	}

	function getModule()
	{
		return 'Translation';
	}

	function getPageTitle()
	{
		return 'User Languages';
	}

	function getAllObjects($page, $recordsPerPage)
	{
		$object = new Language();
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$this->applyFilters($object);
		$object->orderBy($this->getSort());
		$object->find();
		$objectList = array();
		while ($object->fetch()) {
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
	}

	function getDefaultSort()
	{
		return 'displayName asc';
	}

	function getObjectStructure()
	{
		return Language::getObjectStructure();
	}

	function getPrimaryKeyColumn()
	{
		return 'id';
	}

	function getIdKeyColumn()
	{
		return 'id';
	}

	function getAdditionalObjectActions($existingObject)
	{
		return [];
	}

	function getInstructions()
	{
		return '';
	}

	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#translations', 'Languages and Translations');
		$breadcrumbs[] = new Breadcrumb('/Translation/Languages', 'Languages');
		return $breadcrumbs;
	}

	function getActiveAdminSection()
	{
		return 'translations';
	}

	function canView()
	{
		return UserAccount::userHasPermission('Administer Languages');
	}
}