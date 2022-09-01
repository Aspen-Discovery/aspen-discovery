<?php

require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';
require_once ROOT_DIR . '/sys/SearchEntry.php';

class SaveSearch extends MyAccount
{
	function launch()
	{
		$searchId = null;
		$todo = 'addSearch';
		if (isset($_REQUEST['delete']) && $_REQUEST['delete']) {
			$todo = 'deleteSearch';
			$searchId = $_REQUEST['delete'];
		}
		// If for some strange reason the user tries
		//    to do both, just focus on the save.
		if (isset($_REQUEST['save']) && $_REQUEST['save']) {
			$todo = 'addSearch';
			$searchId = $_REQUEST['save'];
		}

		$search = new SearchEntry();
		$search->id = $searchId;
		if ($search->find(true)) {
			// Found, make sure this is a search from this user
			if ($search->session_id == session_id() || $search->user_id == UserAccount::getActiveUserId()) {
				// Call whichever function is required below
				$this->$todo($search);
			}
		}

		// If we are in "edit history" mode, stay in Search History:
		if (isset($_REQUEST['mode']) && $_REQUEST['mode'] == 'history') {
			header("Location: /Search/History");
		} else {
			// If the ID wasn't found, or some other error occurred, nothing will
			//   have processed be now, let the error handling on the display
			//   screen take care of it.
			header("Location: /Search/Results?saved=$searchId");
		}
	}


	/**
	 * Add a search to the database
	 *
	 * @param SearchEntry $search
	 * @noinspection PhpUnusedPrivateMethodInspection
	 */
	private function addSearch($search)
	{
		if ($search->saved != 1) {
			$search->user_id = UserAccount::getActiveUserId();
			$search->saved = 1;
			$search->update();
		}
	}

	/**
	 * Delete a search from the database
	 *
	 * @param SearchEntry $search
	 * @noinspection PhpUnusedPrivateMethodInspection
	 */
	private function deleteSearch($search)
	{
		if ($search->saved != 0) {
			$search->saved = 0;
			$search->update();
		}
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'Your Account');
		$breadcrumbs[] = new Breadcrumb('', 'Saved Searches');
		return $breadcrumbs;
	}
}