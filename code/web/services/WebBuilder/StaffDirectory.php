<?php


class StaffDirectory extends Action {
	function launch() {
		global $interface;
		global $library;

		$_SESSION['returnToModule'] = 'WebBuilder';
		$_SESSION['returnToAction'] = 'StaffDirectory';

		require_once ROOT_DIR . '/sys/WebBuilder/StaffMember.php';
		$staffMember = new StaffMember();
		$staffMember->orderBy('CASE WHEN displayOrder = 0 THEN 1 ELSE 0 END, displayOrder ASC, name ASC, id ASC');
		$staffMember->libraryId = $library->libraryId;
		$staffMember->find();
		$staffMembers = [];
		$hasPhotos = false;
		while ($staffMember->fetch()) {
			$staffMembers[] = clone $staffMember;
			if (!empty($staffMember->photo)) {
				$hasPhotos = true;
			}
		}

		$interface->assign('hasPhotos', $hasPhotos);
		$interface->assign('staffMembers', $staffMembers);

		$this->display('staffDirectory.tpl', 'Staff Directory', '');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/', 'Home');
		$breadcrumbs[] = new Breadcrumb('', 'Staff Directory', true);
		return $breadcrumbs;
	}
}