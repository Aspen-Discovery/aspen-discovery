<?php

require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';
require_once ROOT_DIR . '/sys/Pager.php';

class ReadingHistory extends MyAccount
{
	function launch()
	{
		global $interface;
		global $library;

		if (!$library->enableReadingHistory){
			//User shouldn't get here
			$module = 'Error';
			$action = 'Handle404';
			$interface->assign('module','Error');
			$interface->assign('action','Handle404');
			require_once ROOT_DIR . "/services/Error/Handle404.php";
			$actionClass = new Error_Handle404();
			$actionClass->launch();
			die();
		}
		$interface->assign('showRatings', $library->getGroupedWorkDisplaySettings()->showRatings);

		global $offlineMode;
		if (!$offlineMode) {
			$interface->assign('offline', false);
		}
		$user = UserAccount::getLoggedInUser();
		$interface->assign('profile', $user);

		// Get My Transactions
		if ($user) {
			$linkedUsers = $user->getLinkedUsers();
			if (count($linkedUsers) > 0) {
				array_unshift($linkedUsers, $user);
				$interface->assign('linkedUsers', $linkedUsers);
			}
			$patronId = empty($_REQUEST['patronId']) ?  $user->id : $_REQUEST['patronId'];

			$patron = $user->getUserReferredTo($patronId);

			$interface->assign('selectedUser', $patronId); // needs to be set even when there is only one user so that the patronId hidden input gets a value in the reading history form.

			if (isset($_REQUEST['page']) && is_numeric($_REQUEST['page'])){
				$interface->assign('page', $_REQUEST['page']);
			}else{
				$interface->assign('page', 1);
			}
			if (isset($_REQUEST['readingHistoryFilter'])){
				$interface->assign('readingHistoryFilter', strip_tags($_REQUEST['readingHistoryFilter']));
			}else{
				$interface->assign('readingHistoryFilter', '');
			}
			$interface->assign('historyActive', $patron->trackReadingHistory);
			//Check to see if there is an action to perform.
			if (!empty($_REQUEST['readingHistoryAction'])){
				//Perform the requested action
				$selectedTitles = isset($_REQUEST['selected']) ? $_REQUEST['selected'] : array();
				$readingHistoryAction = $_REQUEST['readingHistoryAction'];
				$patron->doReadingHistoryAction($readingHistoryAction, $selectedTitles);

				//redirect back to the current location without the action.
				$newLocation = "/MyAccount/ReadingHistory";
				if (isset($_REQUEST['page']) && $readingHistoryAction != 'deleteAll' && $readingHistoryAction != 'optOut'){
					$params[] = 'page=' . $_REQUEST['page'];
				}
				if (isset($_REQUEST['patronId'])){
					$params[] = 'patronId=' . $_REQUEST['patronId'];
				}
				if (!empty($params)){
					$additionalParams = implode('&', $params);
					$newLocation .= '?' . $additionalParams;
				}
				header("Location: $newLocation");
				die();
			}
		}

		$this->display('readingHistory.tpl', 'Reading History');
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'My Account');
		$breadcrumbs[] = new Breadcrumb('', 'My Reading History');
		return $breadcrumbs;
	}
}