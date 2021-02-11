<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Email/SendGridSetting.php';

class Admin_SendGridSettings extends ObjectEditor
{
	function getObjectType()
	{
		return 'SendGridSetting';
	}

	function getToolName()
	{
		return 'SendGridSettings';
	}

	function getModule()
	{
		return 'Admin';
	}

	function getPageTitle()
	{
		return 'SendGrid Settings';
	}

	function getAllObjects($page, $recordsPerPage)
	{
		$object = new SendGridSetting();
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$this->applyFilters($object);
		$object->find();
		$objectList = array();
		while ($object->fetch()) {
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
	}
	function getDefaultSort()
	{
		return 'id asc';
	}

	function canSort()
	{
		return false;
	}

	function getObjectStructure()
	{
		return SendGridSetting::getObjectStructure();
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
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#system_admin', 'System Administration');
		$breadcrumbs[] = new Breadcrumb('/Admin/SendGridSettings', 'Send Grid Settings');
		return $breadcrumbs;
	}

	function getActiveAdminSection()
	{
		return 'system_admin';
	}

	function canView()
	{
		return UserAccount::userHasPermission('Administer SendGrid');
	}


}