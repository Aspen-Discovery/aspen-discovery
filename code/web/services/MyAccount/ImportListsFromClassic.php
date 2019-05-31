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

		$interface->setPageTitle('Import Results');
		$interface->assign('sidebar', 'Search/home-sidebar.tpl');
		$interface->setTemplate('listImportResults.tpl');

		$interface->display('layout.tpl');
	}

}