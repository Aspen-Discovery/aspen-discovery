<?php

require_once ROOT_DIR . "/Action.php";

class eRENEW extends Action {
	public function launch() {
		global $interface;

		require_once ROOT_DIR . '/sys/Enrichment/QuipuECardSetting.php';
		$quipuECardSettings = new QuipuECardSetting();
		if ($quipuECardSettings->find(true)) {
			$interface->assign('eCardSettings', $quipuECardSettings);
			$user = UserAccount::getActiveUserObj();
			if (!empty($user) && $user->hasIlsConnection()) {
				$interface->assign('patronId', $user->unique_ils_id);
			}
		} else {
			$interface->assign('eCardSettings', null);
		}
		global $library;
		//$interface->assign('selfRegistrationFormMessage', $library->selfRegistrationFormMessage);

		$this->display('quipuERenew.tpl', 'Renew Your Library Card', '');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('', 'Renew Your Library Card');
		return $breadcrumbs;
	}
}