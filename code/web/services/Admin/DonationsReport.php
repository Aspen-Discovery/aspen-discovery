<?php

require_once ROOT_DIR . '/sys/Donations/Donation.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';

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
            $locationList = Location::getLocationListAsObjects(true);
            foreach ($locationList as $location) {
                $object->donateToLocationId = $location->locationId;
                $object->find();
                while ($object->fetch()) {
                    $objectList[$object->id] = clone $object;
                }
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

    function exportToCSV($data) {
        try {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="DonationsReport.csv"');
            header('Cache-Control: max-age=0');
            $fp = fopen('php://output', 'w');
            //add BOM to fix UTF-8 in Excel
            fputs($fp, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));
            $count_row = 0;
            foreach ($data as $row) {
                $array = get_object_vars($row);
                $keys = array_keys($array);
                foreach ($keys as $key) {
                    if (str_starts_with($key, '_')) {
                        unset($array[$key]);
                    }
                }
                if ($count_row == 0) {
                    $keys = array_keys($array);
                    fputcsv($fp, $keys);
                }
                fputcsv($fp, $array);
                $count_row++;
            }
            exit;
        } catch (Exception $e) {
            global $logger;
            $logger->log("Error exporting to csv " . $e, Logger::LOG_ERROR);
        }
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