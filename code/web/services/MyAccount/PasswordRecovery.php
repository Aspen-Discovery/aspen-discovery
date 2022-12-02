<?php

/**
 * Class PasswordRecovery
 *
 * Triggered by an emailed Password Reset action from the ILS, prompts the user
 */
class PasswordRecovery extends Action {
	function launch() {
		global $interface;

		$catalog = CatalogFactory::getCatalogConnectionInstance(null, null);
		//Get the unique key
		$error = null;
		if (isset($_REQUEST['submit'])) {
			$this->display($catalog->processPasswordRecovery(), 'Recover ' . $interface->getVariable('passwordLabel'), '');
		} else {
			$this->display($catalog->getPasswordRecoveryTemplate(), 'Recover ' . $interface->getVariable('passwordLabel'), '');
		}
	}

	function getBreadcrumbs(): array {
		global $interface;
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('', 'Recover ' . $interface->getVariable('passwordLabel'));
		return $breadcrumbs;
	}
}