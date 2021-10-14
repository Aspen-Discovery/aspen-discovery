<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/ECommerce/ProPaySetting.php';
class Admin_ProPaySettings extends ObjectEditor {
	function getObjectType() : string{
		return 'ProPaySetting';
	}
	function getToolName() : string{
		return 'ProPaySettings';
	}
	function getPageTitle() : string{
		return 'ProPay Settings';
	}
	function getAllObjects($page, $recordsPerPage) : array{
		$list = array();

		$object = new ProPaySetting();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$object->find();
		while ($object->fetch()){
			$list[$object->id] = clone $object;
		}

		return $list;
	}
	function getDefaultSort() : string
	{
		return 'name asc';
	}

	function getObjectStructure() : array{
		return ProPaySetting::getObjectStructure();
	}
	function getPrimaryKeyColumn() : string{
		return 'id';
	}
	function getIdKeyColumn() : string{
		return 'id';
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#ecommerce', 'eCommerce');
		$breadcrumbs[] = new Breadcrumb('', 'ProPay Settings');
		return $breadcrumbs;
	}

	function getActiveAdminSection() : string
	{
		return 'ecommerce';
	}

	function canView() : bool
	{
		return UserAccount::userHasPermission('Administer ProPay');
	}
}