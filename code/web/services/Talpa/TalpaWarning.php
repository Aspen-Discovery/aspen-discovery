<?php

require_once ROOT_DIR . '/Action.php';

class TalpaWarning extends Action {
	function launch() {
		$this->display('talpa-warning.tpl', 'Unable to display results');
	}

	function getBreadcrumbs(): array {
		global $library;
		require_once ROOT_DIR . '/sys/Talpa/TalpaSettings.php';
		$searchSourceString ='Talpa Search';

		if ($library->talpaSettingsId != -1) {
			$talpaSettings = new TalpaSettings();
			$talpaSettings->id = $library->talpaSettingsId;
			if ($talpaSettings->find(true)) {
				$searchSourceString = $talpaSettings->talpaSearchSourceString;
			}
		}


		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('', $searchSourceString.' Error');
		return $breadcrumbs;
//		return parent::getResultsBreadcrumbs($_SESSION['talpaBreadcrumb']);
	}
}