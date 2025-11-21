<?php

require_once ROOT_DIR . '/sys/Account/UserPayment.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';

class Admin_eCommerceReport extends ObjectEditor {
	function getObjectType(): string {
		return 'UserPayment';
	}

	function getToolName(): string {
		return 'eCommerceReport';
	}

	function getPageTitle(): string {
		return 'eCommerce Report';
	}

	function getAllObjects(int $page, int $recordsPerPage): array {
		$object = new UserPayment();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$objectList = [];
		if (UserAccount::userHasPermission('View eCommerce Reports for Home Library')) {
			$adminHomeLibraryList = Library::getLibraryList(true);
			$adminHomeLibraryListIds = array_keys($adminHomeLibraryList);
			$adminHomeLibraryLocationList = Location::getLocationList(true);
			$adminHomeLibraryLocationListIds = array_keys($adminHomeLibraryLocationList);

			$object->joinAdd(new User(), 'LEFT', 'user', 'userId', 'id');
			$object->joinAdd(new Library(), 'LEFT', 'library', 'paidFromInstance', 'subdomain');
			$object->whereAdd('user.homeLocationId IN (' . implode(', ', $adminHomeLibraryLocationListIds) . ') OR library.libraryId in (' . implode(', ', $adminHomeLibraryListIds) . ')');
		}
		$object->find();
		while ($object->fetch()) {
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
	}

	function getDefaultSort(): string {
		return 'transactionDate desc';
	}

	function getObjectStructure($context = ''): array {
		return UserPayment::getObjectStructure($context);
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function canAddNew() : bool {
		return false;
	}

	function canDelete() : bool {
		return false;
	}

    function canExportToCSV() : bool {
        return true;
    }

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#ecommerce', 'eCommerce');
		$breadcrumbs[] = new Breadcrumb('/Admin/eCommerceReport', 'eCommerce Report');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'ecommerce';
	}

	function canView(): bool {
		return UserAccount::userHasPermission([
            'View eCommerce Reports for All Libraries',
            'View eCommerce Reports for Home Library'
        ]);
	}

	function applyFilter(DataObject $object, string $fieldName, array $filter) : void {
		if ($fieldName == 'user') {
			$this->applySpecialFilter($object, $filter, [
				'sourceTable' => 'user_payments',
				'sourceField' => 'userId',
				'targetClass' => 'User',
				'targetField' => 'id',
				'getCompareValueMethod' => 'getDisplayName',
				'compareFormat' => 'nameWithBarcode',
			]);
		} elseif ($fieldName == 'library') {
			$this->applySpecialFilter($object, $filter, [
				'sourceTable' => 'user_payments',
				'sourceField' => 'userId',
				'targetClass' => 'User',
				'targetField' => 'id',
				'getCompareValueMethod' => 'getHomeLibrarySystemName',
			]);
		} else {
			parent::applyFilter($object, $fieldName, $filter);
		}
	}
}
