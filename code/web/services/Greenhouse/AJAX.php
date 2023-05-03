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

	/** @noinspection PhpUnused */
	function getScheduleUpdateForm() {
		global $interface;
		if (isset($_REQUEST['siteId'])) {
			require_once ROOT_DIR . '/sys/Greenhouse/AspenSite.php';
			$siteToUpdate = new AspenSite();
			$siteToUpdate->id = $_REQUEST['siteId'];
			if($siteToUpdate->find(true)) {
				require_once ROOT_DIR . '/sys/Development/AspenRelease.php';
				$releases = AspenRelease::getReleasesList();
				$eligibleReleases = [];
				$siteCurrentRelease = explode(" ", $siteToUpdate->version);
				$siteCurrentRelease = $siteCurrentRelease[0];
				foreach($releases as $release) {
					if(version_compare($release['version'], $siteCurrentRelease, '>=')) {
						$eligibleReleases[$release['version']] = $release;
					}
				}
				$interface->assign('releases', $eligibleReleases);
				$interface->assign('siteToUpdate', $siteToUpdate);
				return [
					'title' => translate([
						'text' => 'Schedule Update for %1%',
						1 => $siteToUpdate->name,
						'isAdminFacing' => true,
					]),
					'modalBody' => $interface->fetch('Greenhouse/scheduleUpdateForm.tpl'),
					'modalButtons' => '<span class="btn btn-primary" onclick="$(\'#scheduleUpdateForm\').submit();">' . translate(['text' => 'Schedule', 'isAdminFacing' => true])  .'</span>',
				];
			}
		} else {
			return [
				'success' => false,
				'message' => 'You must provide a valid id for the site to update',
			];
		}
	}

	/** @noinspection PhpUnused */
	function getBatchScheduleUpdateForm() {
		global $interface;
		require_once ROOT_DIR . '/sys/Development/AspenRelease.php';
		$releases = AspenRelease::getReleasesList();
		require_once ROOT_DIR . '/sys/Greenhouse/AspenSite.php';
		$sites = new AspenSite();
		if(isset($_REQUEST['implementationStatus'])) {
			$sites->whereAdd('implementationStatus = ' . $_REQUEST['implementationStatus']);
		} else {
			$sites->whereAdd('implementationStatus != 4');
		}

		if(isset($_REQUEST['siteType'])) {
			$sites->whereAdd('siteType = ' . $_REQUEST['siteType']);
		}

		$sites->orderBy('implementationStatus ASC, timezone, name ASC');
		$sites->find();
		$allBatchUpdateSites = [];
		$eligibleReleases = [];
		while ($sites->fetch()) {
			if(!$sites->optOutBatchUpdates) {
				$currentRelease = explode(' ', $sites->version);
				$currentRelease = $currentRelease[0];
				foreach($releases as $release) {
					if(version_compare($release['version'], $currentRelease, '>=')) {
						$eligibleReleases[$release['version']] = $release;
					} else {
						unset($eligibleReleases[$release['version']]);
					}
				}
				$allBatchUpdateSites[] = $sites->id;
			}
		}

		$allBatchUpdateSites = implode(',', $allBatchUpdateSites);

		$interface->assign('releases', $eligibleReleases);
		$interface->assign('allBatchUpdateSites', $allBatchUpdateSites);

		return [
			'title' => translate([
				'text' => 'Schedule Batch Update',
				'isAdminFacing' => true,
			]),
			'modalBody' => $interface->fetch('Greenhouse/batchScheduleUpdateForm.tpl'),
			'modalButtons' => '<span class="btn btn-primary" onclick="$(\'#scheduleUpdateForm\').submit();">' . translate(['text' => 'Schedule', 'isAdminFacing' => true])  .'</span>',
		];
	}

	/** @noinspection PhpUnused */
	function getSelectedScheduleUpdateForm() {
		$sitesToUpdate = $_REQUEST['sitesToUpdate'];
		$sitesArray = explode(",", $sitesToUpdate);
		global $interface;
		require_once ROOT_DIR . '/sys/Development/AspenRelease.php';
		$releases = AspenRelease::getReleasesList();
		$eligibleReleases = [];
		foreach($sitesArray as $site) {
			require_once ROOT_DIR . '/sys/Greenhouse/AspenSite.php';
			$aspenSite = new AspenSite();
			$aspenSite->id = $site;
			if($aspenSite->find(true)) {
				$currentRelease = explode(' ', $aspenSite->version);
				$currentRelease = $currentRelease[0];
				foreach($releases as $release) {
					if(version_compare($release['version'], $currentRelease, '>=')) {
						$eligibleReleases[$release['version']] = $release;
					} else {
						unset($eligibleReleases[$release['version']]);
					}
				}
			}
		}

		$interface->assign('releases', $eligibleReleases);
		$interface->assign('allBatchUpdateSites', $sitesToUpdate);

		return [
			'title' => translate([
				'text' => 'Schedule Update for Selected Sites',
				'isAdminFacing' => true,
			]),
			'modalBody' => $interface->fetch('Greenhouse/batchScheduleUpdateForm.tpl'),
			'modalButtons' => '<span class="btn btn-primary" onclick="$(\'#scheduleUpdateForm\').submit();">' . translate(['text' => 'Schedule', 'isAdminFacing' => true])  .'</span>',
		];
	}

	/** @noinspection PhpUnused */
	function scheduleUpdate() {
		if (isset($_REQUEST['siteToUpdate'])) {
			require_once ROOT_DIR . '/sys/Greenhouse/AspenSite.php';
			$site = new AspenSite();
			$site->id = $_REQUEST['siteToUpdate'];
			if($site->find(true)) {
				$runType = $_REQUEST['updateType'] ?? 'patch'; // grab run type, if none is provided, assume patch
				$runUpdateOn = $_REQUEST['runUpdateOn'] ?? null;
				if(empty($_REQUEST['runUpdateOn']) || is_null($runUpdateOn)) {
					$now = new DateTime();
					$runUpdateOn = $now->format('Y-m-d H:i');
				}
				require_once ROOT_DIR . '/sys/Updates/ScheduledUpdate.php';
				$scheduledUpdate = new ScheduledUpdate();
				$scheduledUpdate->updateType = $runType;
				$scheduledUpdate->dateScheduled = $runUpdateOn;
				$scheduledUpdate->siteId = $_REQUEST['siteToUpdate'];
				$scheduledUpdate->updateToVersion = $_REQUEST['updateToVersion'];
				$scheduledUpdate->status = 'pending';
				$scheduledUpdate->insert();

				require_once ROOT_DIR . '/sys/CurlWrapper.php';
				$curl = new CurlWrapper();
				$body = [
					'runType' => $scheduledUpdate->updateType,
					'dateScheduled' => $scheduledUpdate->dateScheduled,
					'updateToVersion' => $scheduledUpdate->updateToVersion,
					'status' => $scheduledUpdate->status,
					'greenhouseId' => $scheduledUpdate->id,
				];
				$response = $curl->curlPostPage($site->baseUrl . '/API/GreenhouseAPI?method=addScheduledUpdate', $body);
				if($response['success']) {
					// update scheduled
					return [
						'success' => true,
						'title' => translate([
							'text' => 'Schedule Update for %1%',
							1 => $site->name,
							'isAdminFacing' => true,
						]),
						'message' => translate([
							'text' => 'Update successfully scheduled for %1%',
							1 => $site->name,
							'isAdminFacing' => true,
						]),
					];
				} else {
					// unable to schedule update
					$scheduledUpdate->notes = $response['message'];
					$scheduledUpdate->update();
					return [
						'success' => false,
						'title' => translate([
							'text' => 'Error',
							'isAdminFacing' => true,
						]),
						'message' => translate([
							'text' => 'Unable to schedule an update for %1%. See notes for details',
							1 => $site->name,
							'isAdminFacing' => true,
						]),
					];
				}
			} else {
				// no site found with that id
				return [
					'success' => false,
					'title' => translate([
						'text' => 'Error',
						'isAdminFacing' => true,
					]),
					'message' => translate([
						'text' => 'Could not find a valid site with given id %1%',
						1 => $_REQUEST['siteToUpdate'],
						'isAdminFacing' => true,
					]),
				];
			}
		} elseif (isset($_REQUEST['sitesToUpdate'])) {
			$sitesToUpdate = explode(",", $_REQUEST['sitesToUpdate']);
			$numSitesUpdated = 0;
			$numSites = count($sitesToUpdate);
			$runType = $_REQUEST['updateType'] ?? 'patch'; // grab run type, if none is provided, assume patch
			$runUpdateOn = $_REQUEST['runUpdateOn'] ?? null;
			if(empty($_REQUEST['runUpdateOn']) || is_null($runUpdateOn)) {
				$now = new DateTime();
				$runUpdateOn = $now->format('Y-m-d H:i');
			}
			foreach($sitesToUpdate as $site){
				require_once ROOT_DIR . '/sys/Greenhouse/AspenSite.php';
				$siteToUpdate = new AspenSite();
				$siteToUpdate->id = $site;
				if($siteToUpdate->find(true)) {
					require_once ROOT_DIR . '/sys/Updates/ScheduledUpdate.php';
					$scheduledUpdate = new ScheduledUpdate();
					$scheduledUpdate->updateType = $runType;
					$scheduledUpdate->dateScheduled = $runUpdateOn;
					$scheduledUpdate->siteId = $_REQUEST['siteToUpdate'];
					$scheduledUpdate->updateToVersion = $_REQUEST['updateToVersion'];
					$scheduledUpdate->status = 'pending';
					$scheduledUpdate->insert();

					require_once ROOT_DIR . '/sys/CurlWrapper.php';
					$curl = new CurlWrapper();
					$body = [
						'runType' => $scheduledUpdate->updateType,
						'dateScheduled' => $scheduledUpdate->dateScheduled,
						'updateToVersion' => $scheduledUpdate->updateToVersion,
						'status' => $scheduledUpdate->status,
						'greenhouseId' => $scheduledUpdate->id,
					];
					$response = $curl->curlPostPage($siteToUpdate->baseUrl . '/API/GreenhouseAPI?method=addScheduledUpdate', $body);
					if($response['success']) {
						// update scheduled
						$numSitesUpdated++;
					} else {
						$scheduledUpdate->notes = $response['message'];
						$scheduledUpdate->update();
					}
				}
			}
			return [
				'success' => true,
				'title' => translate([
					'text' => 'Schedule Batch Update',
					'isAdminFacing' => true,
				]),
				'message' => translate([
					'text' => 'Successfully scheduled updates for %1% of %2% sites.',
					1 => $numSitesUpdated,
					2 => $numSites,
					'isAdminFacing' => true,
				]),
			];
		} else {
			return false;
		}
	}

	/** @noinspection PhpUnused */
	function showScheduledUpdateDetails(): array {
		global $interface;
		$viewMoreBtn = '';
		$user = UserAccount::getLoggedInUser();
		if (!isset($_REQUEST['id'])) {
			$interface->assign('error', translate([
				'text' => 'Please provide an id of the materials request to view.',
				'isAdminFacing' => true,
			]));
		} elseif (empty($user)) {
			$interface->assign('error', translate([
				'text' => 'Please log in to view details.',
				'isAdminFacing' => true,
			]));
		} else {
			$id = $_REQUEST['id'];
			if($id) {
				require_once ROOT_DIR . '/sys/Updates/ScheduledUpdate.php';
				$scheduledUpdate = new ScheduledUpdate();
				$scheduledUpdate->id = $id;
				if($scheduledUpdate->find(true)) {
					$updateStatus = 'pending';
					if($scheduledUpdate->status) {
						$updateStatus = $scheduledUpdate->status;
					}
					$interface->assign('updateStatus', $updateStatus);

					$updateTo = null;
					if($scheduledUpdate->updateToVersion) {
						$updateTo = $scheduledUpdate->updateToVersion;
					}
					$interface->assign('updateTo', $updateTo);

					$updateType = null;
					if($scheduledUpdate->updateType) {
						$updateType = $scheduledUpdate->updateType;
					}
					$interface->assign('updateType', $updateType);

					$updateScheduled = null;
					if($scheduledUpdate->dateScheduled) {
						$updateScheduled = $scheduledUpdate->dateScheduled;
					}
					$interface->assign('updateScheduled', $updateScheduled);

					$updateRan = null;
					if($scheduledUpdate->dateRun) {
						$updateRan = $scheduledUpdate->dateRun;
					}
					$interface->assign('updateRan', $updateRan);

					$updateNotes = '';
					if($scheduledUpdate->notes) {
						$updateNotes = $scheduledUpdate->notes;
					}
					$interface->assign('updateNotes', $updateNotes);
				} else {
					$interface->assign('error', translate([
						'text' => 'Sorry, we couldn\'t find a scheduled update for that id.',
						'isAdminFacing' => true,
					]));
				}

				$viewMoreBtn = "<a class='btn btn-primary' href='/Admin/ScheduledUpdates?objectAction=edit&id=$id'>" . translate(['text' => 'View Details', 'isAdminFacing' => true]) . "</a>";
			}
		}

		return [
			'title' => translate([
				'text' => 'Scheduled Update Details',
				'isAdminFacing' => true,
			]),
			'modalBody' => $interface->fetch('Greenhouse/ajaxScheduledUpdateDetails.tpl'),
			'modalButtons' => $viewMoreBtn,
		];
	}

	function getBreadcrumbs(): array {
		return [];
	}
}