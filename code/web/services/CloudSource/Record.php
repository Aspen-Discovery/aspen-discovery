<?php

require_once ROOT_DIR . '/RecordDrivers/CloudSourceRecordDriver.php';

class CloudSource_Record extends Action {
	private $recordDriver;

	function launch() {
		global $interface;
		$id = urldecode($_REQUEST['id']);

		$this->recordDriver = new CloudSourceRecordDriver($id);
		if (!$this->recordDriver->isValid()) {
			global $interface;
			$interface->assign('module', 'Error');
			$interface->assign('action', 'Handle404');
			require_once ROOT_DIR . "/services/Error/Handle404.php";
			$actionClass = new Error_Handle404();
			$actionClass->launch();
			die();
		}
		$appliedTheme = $interface->getAppliedTheme();
		if ($appliedTheme != null && !empty($appliedTheme->articlesDBImage)) {
			$image = '/files/origional/' . $appliedTheme->articlesDBImage;
		} else {
			$image = '/interface/themes/responsive/images/cloudsource.png';
		}
		$interface->assign('image', $image);

		$record = $this->recordDriver->getRecordViewData();

		$interface->assign('record', $record);

		$patronUrl = (new CloudSourceRecordDriver($id))->getPatronURL(true);
		$interface->assign('directLinkUrl', $this->recordDriver->getAbsoluteUrl());

		$interface->assign('patronUrl', $patronUrl);

		$interface->assign('recordDriver', $this->recordDriver);

		$interface->assign('isStaff', UserAccount::isStaff());

		// Display Page
		$this->display('record-view.tpl', $this->recordDriver->getTitle(), null, false);
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		if (!empty($this->lastSearch)) {
			$breadcrumbs[] = new Breadcrumb($this->lastSearch, 'Articles & Databases Search Results');
		}
		$breadcrumbs[] = new Breadcrumb('', $this->recordDriver->getTitle());
		return $breadcrumbs;
	}
}