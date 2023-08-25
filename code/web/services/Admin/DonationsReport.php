<?php

require_once ROOT_DIR . '/sys/Donations/Donation.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Account/UserPayment.php';

class Admin_DonationsReport extends ObjectEditor {
	function getObjectType(): string {
		return 'Donation';
	}

	function getToolName(): string {
		return 'DonationsReport';
	}

	function getPageTitle(): string {
		return 'Donations Report';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$object = new Donation();
        $object->orderBy($this->getSort());
        $this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
        $objectList = [];
        if (UserAccount::userHasPermission('View Donations Reports for All Libraries')){
            $object->find();
            while ($object->fetch()) {
                $objectList[$object->id] = clone $object;
            }
        } elseif (UserAccount::userHasPermission('View Donations Reports for Home Library')) {
			$adminHomeLibraryId = Library::getPatronHomeLibrary()->libraryId;
			$adminHomeLibraryLocationList = Location::getLocationList(true);
			$adminHomeLibraryLocationListIds = array_keys($adminHomeLibraryLocationList);
			// Donations report should be visible to Library System admins when

			// 1. the payment is made from a subdomain within the admin's Library System
			$object->joinAdd(new UserPayment(), 'LEFT', 'donorPayment', 'paymentId', 'id');
			$object->joinAdd(new Library(), 'LEFT', 'paidFromInstance', 'donorPayment.paidFromInstance', 'subdomain');

			// 2. the payment is from a User in the admin's Library System (donations require a user to be logged in)
			$object->joinAdd(new User(), 'LEFT', 'donor', 'donorPayment.userId', 'id');

			// 3. the donation donateToLocation is a location within the admin's Library System
			$object->joinAdd(new Location(), 'LEFT', 'donateToLocation', 'donateToLocationId', 'locationId');
			$object->whereAdd('donateToLocation.locationId IN (' . implode(', ', $adminHomeLibraryLocationListIds) . ') OR donor.homeLocationId IN (' . implode(', ', $adminHomeLibraryLocationListIds) . ') OR paidFromInstance.libraryId = ' . $adminHomeLibraryId);


			$object->find();
			while ($object->fetch()) {
				$objectList[$object->id] = clone $object;
			}
		}
		return $objectList;
	}

	function getDefaultSort(): string {
		return 'id desc';
	}

	function getObjectStructure($context = ''): array {
		return Donation::getObjectStructure($context);
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function canAddNew() {
		return false;
	}

	function canDelete() {
		return false;
	}

    function canExportToCSV() {
        return true;
    }

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#ecommerce', 'eCommerce');
		$breadcrumbs[] = new Breadcrumb('/Admin/donationsReport', 'Donations Report');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'ecommerce';
	}

	function canView(): bool {
		return UserAccount::userHasPermission([
            'View Donations Reports for All Libraries',
            'View Donations Reports for Home Library'
        ]);
	}

}