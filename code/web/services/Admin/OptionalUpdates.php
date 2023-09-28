<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/DBMaintenance/OptionalUpdate.php';

/**
 * Provides a method of running SQL updates to the database.
 * Shows a list of updates that are available with a description of the
 */
class Admin_OptionalUpdates extends Admin_Admin {
	function launch() {
		global $interface;

		if (!empty($_REQUEST['updatesToApply'])) {
			foreach ($_REQUEST['updatesToApply'] as $name => $updateValue) {
				$optionalUpdate = new OptionalUpdate();
				$optionalUpdate->name = $name;
				if ($optionalUpdate->find(true)) {
					if ($updateValue == 1) {
						//No updates needed
					} elseif ($updateValue == 2) {
						//Apply the update
						$optionalUpdate->applyUpdate();
					} elseif ($updateValue == 3) {
						$optionalUpdate->status = 3;
						$optionalUpdate->update();
					}
				}
			}
		}

		$optionalUpdates = [];

		$optionalUpdate = new OptionalUpdate();
		$optionalUpdate->status = 1;
		$optionalUpdate->find();
		while ($optionalUpdate->fetch()) {
			$optionalUpdates[$optionalUpdate->name] = clone $optionalUpdate;
		}

		$interface->assign('optionalUpdates', $optionalUpdates);

		$this->display('optionalUpdates.tpl', 'Recommended Updates');

	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#system_admin', 'System Administration');
		$breadcrumbs[] = new Breadcrumb('', 'Optional Updates');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'system_admin';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Run Optional Updates');
	}
}