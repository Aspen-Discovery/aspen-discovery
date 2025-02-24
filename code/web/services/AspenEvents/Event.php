<?php

require_once ROOT_DIR . '/RecordDrivers/AspenEventRecordDriver.php';

class AspenEvents_Event extends Action {

	private $recordDriver;

	function launch() {
		global $interface;
		$id = urldecode($_REQUEST['id']);

		$this->recordDriver = new AspenEventRecordDriver($id);
		if (!$this->recordDriver->isValid()) {
			global $interface;
			$interface->assign('module', 'Error');
			$interface->assign('action', 'Handle404');
			require_once ROOT_DIR . "/services/Error/Handle404.php";
			$actionClass = new Error_Handle404();
			$actionClass->launch();
			die();
		}
		$interface->assign('recordDriver', $this->recordDriver);
		$interface->assign('eventsInLists', true);
		$interface->assign('isStaff', UserAccount::isStaff());

		// Display Page
		$this->display('event.tpl', $this->recordDriver->getTitle(), null, false);
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		if (!empty($this->lastSearch)) {
			$breadcrumbs[] = new Breadcrumb($this->lastSearch, 'Event Search Results');
		}
		$breadcrumbs[] = new Breadcrumb('', $this->recordDriver->getTitle());
		return $breadcrumbs;
	}
}