<?php
require_once ROOT_DIR . '/JSON_Action.php';

class Omeka_AJAX extends JSON_Action {
	function launch($method = null): void {
		$this->checkRequiredModule('Omeka');
		parent::launch($method);
	}

	/** @noinspection PhpUnused */
	function getStaffView(): array {
		global $interface;
		if (!$interface->getVariable('showStaffView')) {
			return $this->failureResult(null, 'Staff View is not available.');
		}

		$id = $_REQUEST['id'];
		require_once ROOT_DIR . '/RecordDrivers/OmekaRecordDriver.php';
		$recordDriver = new OmekaRecordDriver($id);
		if (!$recordDriver->isValid()) {
			return $this->failureResult(null, translate([
				'text' => 'Could not find that record',
				'isPublicFacing' => true,
			]));
		}
		$interface->assign('recordDriver', $recordDriver);
		return [
			'success' => true,
			'staffView' => $interface->fetch($recordDriver->getStaffView()),
		];
	}

	/** @noinspection PhpUnused */
	function getLargeCover(): array {
		global $interface;

		$id = $_REQUEST['id'];
		if (!is_numeric($id)) {
			return $this->failureResult(null, 'Invalid record id.');
		}
		$interface->assign('id', $id);

		return [
			'title' => translate([
				'text' => 'Cover Image',
				'isPublicFacing' => true,
			]),
			'modalBody' => $interface->fetch('Omeka/largeCover.tpl'),
			'modalButtons' => '',
		];
	}
}
