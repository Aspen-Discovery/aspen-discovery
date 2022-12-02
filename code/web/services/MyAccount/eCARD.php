<?php

require_once ROOT_DIR . "/Action.php";

class eCARD extends Action {
	public function launch() {
		global $interface;

		require_once ROOT_DIR . '/sys/Enrichment/QuipuECardSetting.php';
		$quipuECardSettings = new QuipuECardSetting();
		if ($quipuECardSettings->find(true)) {
			$interface->assign('eCardSettings', $quipuECardSettings);
		} else {
			$interface->assign('eCardSettings', null);
		}
		global $library;
		$interface->assign('selfRegistrationFormMessage', $library->selfRegistrationFormMessage);

		$this->display('quipuECard.tpl', 'Register for a Library Card', '');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('', 'Register for a Library Card');
		return $breadcrumbs;
	}
}