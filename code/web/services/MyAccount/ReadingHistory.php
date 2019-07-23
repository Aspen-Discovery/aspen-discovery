<?php

require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';
require_once ROOT_DIR . '/sys/Pager.php';

class ReadingHistory extends MyAccount
{
	function launch()
	{
		global $configArray;
		global $interface;
		global $library;
		$interface->assign('showRatings', $library->showRatings);

		global $offlineMode;
		if (!$offlineMode) {
			$interface->assign('offline', false);

			$user = UserAccount::getActiveUserObj();

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

				$interface->assign('historyActive', $patron->trackReadingHistory);
				//Check to see if there is an action to perform.
				if (!empty($_REQUEST['readingHistoryAction']) && $_REQUEST['readingHistoryAction'] != 'exportToExcel'){
					//Perform the requested action
					$selectedTitles = isset($_REQUEST['selected']) ? $_REQUEST['selected'] : array();
					$readingHistoryAction = $_REQUEST['readingHistoryAction'];
					$patron->doReadingHistoryAction($readingHistoryAction, $selectedTitles);

					//redirect back to the current location without the action.
					$newLocation = "{$configArray['Site']['path']}/MyAccount/ReadingHistory";
					if (isset($_REQUEST['page']) && $readingHistoryAction != 'deleteAll' && $readingHistoryAction != 'optOut'){
						$params[] = 'page=' . $_REQUEST['page'];
					}
					if (isset($_REQUEST['accountSort'])){
						$params[] = 'accountSort=' . $_REQUEST['accountSort'];
					}
					if (isset($_REQUEST['pageSize'])){
						$params[] = 'pageSize=' . $_REQUEST['pageSize'];
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
		}

		$this->display('readingHistory.tpl', 'Reading History');
	}
}