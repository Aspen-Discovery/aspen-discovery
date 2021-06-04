<?php

require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';
class ImportListsFromClassic extends MyAccount{

	/**
	 * Process parameters and display the page.
	 *
	 * @return void
	 * @access public
	 */
	public function launch()
	{
		global $interface;
		$user = UserAccount::getLoggedInUser();

		//Import Lists from the ILS
		$results = $user->importListsFromIls();
		$interface->assign('importResults', $results);

		//Reload all lists for the user
		$listList = $user->getLists();
		$interface->assign('listList', $listList);

		$this->display('listImportResults.tpl', 'Import Results');
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'My Account');
		$breadcrumbs[] = new Breadcrumb('', 'Import Lists');
		return $breadcrumbs;
	}
}