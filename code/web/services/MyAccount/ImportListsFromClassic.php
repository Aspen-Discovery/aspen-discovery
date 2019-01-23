<?php
/**
 * Imports Lists for a user from prior catalog (Millennium WebPAC, Encore, Etc).
 *
 * @category VuFind-Plus
 * @author Mark Noble <mark@marmot.org>
 * Date: 2/26/14
 * Time: 10:35 PM
 */

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
		$interface->assign('sidebar', 'MyAccount/account-sidebar.tpl');
		$interface->setTemplate('listImportResults.tpl');

		$interface->display('layout.tpl');
	}

}