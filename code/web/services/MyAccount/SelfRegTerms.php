<?php

require_once ROOT_DIR . "/Action.php";
class SelfRegTerms extends Action {
	function launch($msg = null) {
		global $interface;
		global $library;

		$catalog = CatalogFactory::getCatalogConnectionInstance();
		$selfRegTerms = $catalog->getSelfRegistrationTerms();
		if ($selfRegTerms != null) {
			$interface->assign('tosBody', $selfRegTerms->terms);
			$interface->assign('tosDenialBody', $selfRegTerms->redirect);
			$this->display('selfRegistrationTerms.tpl', 'Terms of Service', '');
		}
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('', 'Terms of Service');
		return $breadcrumbs;
	}
}