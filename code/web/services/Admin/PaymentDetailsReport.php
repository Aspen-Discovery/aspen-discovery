<?php

require_once ROOT_DIR . '/sys/Account/UserPaymentLine.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';

class Admin_PaymentDetailsReport extends ObjectEditor {
	function getObjectType(): string {
		return 'UserPaymentLine';
	}

	function getToolName(): string {
		return 'PaymentDetailsReport';
	}

	function getPageTitle(): string {
		return 'Payment Details Report';
	}

	function getAllObjects(int $page, int $recordsPerPage): array {
		$object = new UserPaymentLine();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);

		$objectList = [];
		$object->find();
		while ($object->fetch()) {
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
	}

	function getDefaultSort(): string {
		return 'paymentId asc, id asc';
	}

	function getObjectStructure($context = ''): array {
		return UserPaymentLine::getObjectStructure($context);
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function canAddNew(): bool {
		return false;
	}

	function canDelete(): bool {
		return false;
	}

	function canExportToCSV(): bool {
		return true;
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#ecommerce', 'eCommerce');
		$breadcrumbs[] = new Breadcrumb('/Admin/PaymentDetailsReport', 'Payment Details Report');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'ecommerce';
	}

	public function getViewPermissions(): array {
		return [
			'View eCommerce Reports for All Libraries',
			'View eCommerce Reports for Home Library'
		];
	}

	function getAdditionalObjectActions(?DataObject $existingObject): array {
		if ($existingObject == null) {
			return [];
		}

		return [
			[
				'text' => 'View Payment',
				'url' => '/Admin/eCommerceReport?objectAction=view&id=' . $existingObject->paymentId,
			],
		];
	}
}