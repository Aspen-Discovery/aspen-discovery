<?php

require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Redwood/UserContribution.php';

class Admin_RedwoodUserContributions extends ObjectEditor
{
	function getObjectType()
	{
		return 'UserContribution';
	}

	function getToolName()
	{
		return 'RedwoodUserContributions';
	}

	function getPageTitle()
	{
		return 'Submit Material to the Archive';
	}

	function getAllObjects($page, $recordsPerPage)
	{
		$list = array();

		$object = new UserContribution();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$object->find();
		while ($object->fetch()) {
			$list[$object->id] = clone $object;
		}

		return $list;
	}

	function getDefaultSort()
	{
		return 'dateContributed desc';
	}

	function getObjectStructure()
	{
		return UserContribution::getObjectStructure();
	}

	function getPrimaryKeyColumn()
	{
		return 'id';
	}

	function getIdKeyColumn()
	{
		return 'id';
	}

	function canAddNew()
	{
		return false;
	}

	function getBreadcrumbs()
	{
		return [];
	}

	function display($mainContentTemplate, $pageTitle, $sidebarTemplate = 'Admin/admin-sidebar.tpl', $translateTitle = true)
	{
		parent::display($mainContentTemplate, $pageTitle, '', false);
	}

	function getActiveAdminSection()
	{
		return '';
	}

	function canView()
	{
		return true;
	}
}