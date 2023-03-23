<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/ECommerce/DeluxeCertifiedPaymentsSetting.php';

class Admin_DeluxeCertifiedPaymentsSettings extends ObjectEditor {
	function getObjectType(): string {
		return 'DeluxeCertifiedPaymentsSetting';
	}

	function getToolName(): string {
		return 'DeluxeCertifiedPaymentsSettings';
	}

	function getPageTitle(): string {
		return 'Deluxe Certified Payments Settings';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$list = [];

		$object = new DeluxeCertifiedPaymentsSetting();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$object->find();
		while ($object->fetch()) {
			$list[$object->id] = clone $object;
		}

		return $list;
	}

	function getDefaultSort(): string {
		return 'name asc';
	}

	function getObjectStructure($context = ''): array {
		return DeluxeCertifiedPaymentsSetting::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#ecommerce', 'eCommerce');
		$breadcrumbs[] = new Breadcrumb('', 'Deluxe Certified Payments Settings');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'ecommerce';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Administer Deluxe Certified Payments');
	}
}