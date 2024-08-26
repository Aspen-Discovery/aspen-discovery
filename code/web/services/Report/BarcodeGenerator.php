<?php

require_once(ROOT_DIR . '/services/Admin/Admin.php');

class Report_BarcodeGenerator extends Admin_Admin {
	function launch() {
		$this->display('barcodeGenerator.tpl', 'Barcode Generator');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#circulation_reports', 'Circulation Reports');
		$breadcrumbs[] = new Breadcrumb('', 'Barcode Generator');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'circulation_reports';
	}
	function canView(): bool {
		return UserAccount::userHasPermission([
			'Barcode Generators',
		]);
	}
}