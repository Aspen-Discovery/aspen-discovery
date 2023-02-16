<?php

require_once ROOT_DIR . '/Action.php';

global $configArray;

class Greenhouse_AJAX extends Action {

	function launch() {
		if (UserAccount::isLoggedIn()) {
			if (UserAccount::getActiveUserObj()->source == 'admin' && UserAccount::getActiveUserObj()->cat_username == 'aspen_admin') {
				global $timer;
				$method = (isset($_GET['method']) && !is_array($_GET['method'])) ? $_GET['method'] : '';
				$timer->logTime("Starting method $method");
				if (method_exists($this, $method)) {
					// Methods intend to return JSON data
					if ($method == 'downloadMarc') {
						echo $this->$method();
					} else {
						header('Content-type: application/json');
						header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
						header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
						echo json_encode($this->$method());
					}
				} else {
					$output = json_encode(['error' => 'invalid_method']);
					echo $output;
				}
				return;
			}
		}
		global $interface;
		$interface->assign('module', 'Error');
		$interface->assign('action', 'Handle404');
		require_once ROOT_DIR . "/services/Error/Handle404.php";
		$actionClass = new Error_Handle404();
		$actionClass->launch();
	}


	/** @noinspection PhpUnused */
	function mergeBarcode() {
		$barcode = $_REQUEST['barcode'];

		$result = [
			'success' => false,
			'oldUserId' => '',
			'newUserId' => '',
			'message' => "Finished Processing $barcode",
			'numUsersUpdated' => 0,
		];

		$catalog = CatalogFactory::getCatalogConnectionInstance();

		$userToMerge = new User();
		$barcodeField = $userToMerge->getBarcodeField();
		$userToMerge->$barcodeField = $barcode;
		$allUsersForBarcode = $userToMerge->fetchAll();
		if (count($allUsersForBarcode) < 2) {
			$result['message'] = 'User is already unique';
		} else {
			$userStillExists = false;
			$loginResult = $catalog->findNewUser($barcode);
			if ($loginResult instanceof User) {
				//The internal ILS ID has changed
				$newUser = $loginResult;
				$userStillExists = true;
			}
			if ($userStillExists) {
				/** @var User $oldUser */
				foreach ($allUsersForBarcode as $oldUser) {
					if ($oldUser->username != $newUser->username) {
						//$result['oldUser'] = $oldUser;
						$result['oldUserId'] .= $oldUser->username;
						//$result['newUser'] = $newUser;
						$result['newUserId'] .= $newUser->username;

						//Merge the records
						$mergeResults = [
							'numUsersUpdated' => 0,
							'numUsersMerged' => 0,
							'numUnmappedUsers' => 0,
							'numListsMoved' => 0,
							'numReadingHistoryEntriesMoved' => 0,
							'numRolesMoved' => 0,
							'numNotInterestedMoved' => 0,
							'numLinkedPrimaryUsersMoved' => 0,
							'numLinkedUsersMoved' => 0,
							'numSavedSearchesMoved' => 0,
							'numSystemMessageDismissalsMoved' => 0,
							'numPlacardDismissalsMoved' => 0,
							'numMaterialsRequestsMoved' => 0,
							'numMaterialsRequestsAssignmentsMoved' => 0,
							'numUserMessagesMoved' => 0,
							'numUserPaymentsMoved' => 0,
							'numRatingsReviewsMoved' => 0,
							'errors' => [],
						];

						require_once ROOT_DIR . '/sys/Utils/UserUtils.php';
						UserUtils::mergeUsers($oldUser, $newUser, $mergeResults);

						if (!empty($mergeResults['numListsMoved'])) {
							$result['message'] .= "<br/>Moved {$mergeResults['numListsMoved']} lists";
						}
						if (!empty($mergeResults['numReadingHistoryEntriesMoved'])) {
							$result['message'] .= "<br/>Moved {$mergeResults['numReadingHistoryEntriesMoved']} reading history entries";
						}
						if (!empty($mergeResults['numRolesMoved'])) {
							$result['message'] .= "<br/>Moved {$mergeResults['numRolesMoved']} roles";
						}
						if (!empty($mergeResults['numNotInterestedMoved'])) {
							$result['message'] .= "<br/>Moved {$mergeResults['numNotInterestedMoved']} not interested titles";
						}
						if (!empty($mergeResults['numLinkedPrimaryUsersMoved'])) {
							$result['message'] .= "<br/>Moved {$mergeResults['numLinkedPrimaryUsersMoved']} linked primary users";
						}
						if (!empty($mergeResults['numLinkedUsersMoved'])) {
							$result['message'] .= "<br/>Moved {$mergeResults['numLinkedUsersMoved']} lined users";
						}
						if (!empty($mergeResults['numSavedSearchesMoved'])) {
							$result['message'] .= "<br/>Moved {$mergeResults['numSavedSearchesMoved']} saved searches";
						}
						if (!empty($mergeResults['numSystemMessageDismissalsMoved'])) {
							$result['message'] .= "<br/>Moved {$mergeResults['numSystemMessageDismissalsMoved']} system message dismissals";
						}
						if (!empty($mergeResults['numPlacardDismissalsMoved'])) {
							$result['message'] .= "<br/>Moved {$mergeResults['numPlacardDismissalsMoved']} placard dismissals";
						}
						if (!empty($mergeResults['numMaterialsRequestsMoved'])) {
							$result['message'] .= "<br/>Moved {$mergeResults['numMaterialsRequestsMoved']} materials requests";
						}
						if (!empty($mergeResults['numMaterialsRequestsAssignmentsMoved'])) {
							$result['message'] .= "<br/>Moved {$mergeResults['numMaterialsRequestsAssignmentsMoved']} material request assignments";
						}
						if (!empty($mergeResults['numUserMessagesMoved'])) {
							$result['message'] .= "<br/>Moved {$mergeResults['numUserMessagesMoved']} user messages";
						}
						if (!empty($mergeResults['numUserPaymentsMoved'])) {
							$result['message'] .= "<br/>Moved {$mergeResults['numUserPaymentsMoved']} user payments";
						}
						if (!empty($mergeResults['numRatingsReviewsMoved'])) {
							$result['message'] .= "<br/>Moved {$mergeResults['numRatingsReviewsMoved']} ratings & reviews";
						}
						if (!empty($mergeResults['errors'])) {
							$result['message'] .= "<br/>" . implode("<br/>", $mergeResults['errors']);
						}

						$result['success'] = true;
						break;
					} else {
						//This is the correct user, skip updating it
					}
				}
			} else {
				$result['message'] = "User no longer exists in the ILS";
				//Make sure we aren't loading reading history for the deleted user(s)
				/** @var User $oldUser */
				foreach ($allUsersForBarcode as $oldUser) {
					if ($oldUser->trackReadingHistory) {
						$oldUser->trackReadingHistory = 0;
						$oldUser->update();
						$result['message'] .= "<br/>Disabled reading history for $oldUser->username";
					}
				}
				//TODO: cleanup the database and remove the old users.
			}
		}

		return $result;
	}

	function getBreadcrumbs(): array {
		return [];
	}
}