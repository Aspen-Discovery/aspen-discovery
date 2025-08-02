<?php

require_once(ROOT_DIR . '/services/Admin/Admin.php');
require_once ROOT_DIR . "/sys/MaterialsRequests/MaterialsRequest.php";

class MaterialsRequest_Update extends Admin_Admin {

	function launch() {
		global $configArray;
		global $interface;

		//Load the materials request to determine if it can be edited
		$materialsRequest = new MaterialsRequest();
		$materialsRequest->id = $_REQUEST['id'];
		if (!$materialsRequest->find(true)) {
			$materialsRequest = null;
			$requestUser = false;
		} else {
			$requestUser = new User();
			$requestUser->id = $materialsRequest->createdBy;
			if ($requestUser->find(true)) {
				$interface->assign('requestUser', $requestUser);
			} else {
				$requestUser = false;
			}
		}

		//Make sure that the user is logged in
		$user = UserAccount::getLoggedInUser();
		if (!UserAccount::isLoggedIn()) {
			$interface->assign('error', 'Sorry, you must be logged in to update a materials request.');
		} else {
			header('Location: /MaterialsRequest/ManageRequests');
			$processForm = true;
			//Make sure that the user is valid
			if ($materialsRequest == null) {
				$user->updateMessage = translate([
					'text' => 'Sorry, we could not find a request with that id.',
					'isPublicFacing' => true
				]);
				$user->updateMessageIsError = true;
				$user->update();
				$processForm = false;
			} elseif (UserAccount::userHasPermission('Manage Library Materials Requests') && $requestUser && ($user->getHomeLibrary() == null || ($requestUser->getHomeLibrary()->libraryId == $user->getHomeLibrary()->libraryId))) {
				//Ok to process because they are an admin for the user's home library
				$processForm = true;
			} elseif ($user->id != $materialsRequest->createdBy) {
				$user->updateMessage = translate([
					'text' => 'Sorry, you do not have permission to update this materials request.',
					'isPublicFacing' => true
				]);
				$user->updateMessageIsError = true;
				$user->update();
				$processForm = false;
			}
			if ($processForm) {
				//Materials request can be submitted.
				$materialsRequest->format = empty($_REQUEST['format']) ? '' : strip_tags($_REQUEST['format']);
				if (empty($materialsRequest->format)) {
					$user->updateMessage = translate([
						'text' => 'There was an error updating the materials request. No format was specified.',
						'isPublicFacing' => true
					]);
					$user->updateMessageIsError = true;
					$user->update();
				} else {
					$materialsRequest->phone = isset($_REQUEST['phone']) ? strip_tags($_REQUEST['phone']) : '';
					$materialsRequest->email = isset($_REQUEST['email']) ? strip_tags($_REQUEST['email']) : '';
					$materialsRequest->title = isset($_REQUEST['title']) ? strip_tags($_REQUEST['title']) : '';
					$materialsRequest->season = isset($_REQUEST['season']) ? strip_tags($_REQUEST['season']) : '';
					$materialsRequest->magazineTitle = isset($_REQUEST['magazineTitle']) ? strip_tags($_REQUEST['magazineTitle']) : '';
					$materialsRequest->magazineDate = isset($_REQUEST['magazineDate']) ? strip_tags($_REQUEST['magazineDate']) : '';
					$materialsRequest->magazineVolume = isset($_REQUEST['magazineVolume']) ? strip_tags($_REQUEST['magazineVolume']) : '';
					$materialsRequest->magazineNumber = isset($_REQUEST['magazineNumber']) ? strip_tags($_REQUEST['magazineNumber']) : '';
					$materialsRequest->magazinePageNumbers = isset($_REQUEST['magazinePageNumbers']) ? strip_tags($_REQUEST['magazinePageNumbers']) : '';
					$materialsRequest->author = empty($_REQUEST['author']) ? '' : strip_tags($_REQUEST['author']);
					$materialsRequest->ageLevel = isset($_REQUEST['ageLevel']) ? strip_tags($_REQUEST['ageLevel']) : '';
					$materialsRequest->bookType = isset($_REQUEST['bookType']) ? strip_tags($_REQUEST['bookType']) : '';
					$materialsRequest->isbn = isset($_REQUEST['isbn']) ? strip_tags($_REQUEST['isbn']) : '';
					$materialsRequest->upc = isset($_REQUEST['upc']) ? strip_tags($_REQUEST['upc']) : '';
					$materialsRequest->issn = isset($_REQUEST['issn']) ? strip_tags($_REQUEST['issn']) : '';
					$materialsRequest->oclcNumber = isset($_REQUEST['oclcNumber']) ? strip_tags($_REQUEST['oclcNumber']) : '';
					$materialsRequest->publisher = empty($_REQUEST['publisher']) ? '' : strip_tags($_REQUEST['publisher']);
					$materialsRequest->publicationYear = empty($_REQUEST['publicationYear']) ? '' : strip_tags($_REQUEST['publicationYear']);
					$materialsRequest->about = empty($_REQUEST['about']) ? '' : strip_tags($_REQUEST['about']);
					$materialsRequest->comments = empty($_REQUEST['comments']) ? '' : strip_tags($_REQUEST['comments']);
					$materialsRequest->staffComments = empty($_REQUEST['staffComments']) ? '' : strip_tags($_REQUEST['staffComments']);
					$materialsRequest->placeHoldWhenAvailable = empty($_REQUEST['placeHoldWhenAvailable']) ? 0 : $_REQUEST['placeHoldWhenAvailable'];
					$materialsRequest->holdPickupLocation = empty($_REQUEST['holdPickupLocation']) ? '' : $_REQUEST['holdPickupLocation'];
					$materialsRequest->bookmobileStop = empty($_REQUEST['bookmobileStop']) ? '' : $_REQUEST['bookmobileStop'];
					$materialsRequest->illItem = empty($_REQUEST['illItem']) ? 0 : $_REQUEST['illItem'];
					$materialsRequest->emailSent = empty($_REQUEST['emailSent']) ? 0 : $_REQUEST['emailSent'];
					$statusChanged = false;
					if (!empty($_REQUEST['status'])) {
						if ($materialsRequest->status != $_REQUEST['status']) {
							$materialsRequest->status = $_REQUEST['status'];
							$statusChanged = true;
						}
					}
					$assigneeChanged = false;
					if (!empty($_REQUEST['assignedTo'])) {
						if ($materialsRequest->assignedTo != $_REQUEST['assignedTo']) {
							$materialsRequest->assignedTo = $_REQUEST['assignedTo'];
							$assigneeChanged = true;
						}
					}

					$materialsRequest->libraryId = $requestUser->getHomeLibrary()->libraryId;

					$formatObject = $materialsRequest->getFormatObjectByFormat();
					if (!empty($formatObject->id)) {
						$materialsRequest->formatId = $formatObject->id;
					}

					if (isset($_REQUEST['ebookFormat']) && $formatObject->hasSpecialFieldOption('Ebook format')) {
						$materialsRequest->subFormat = strip_tags($_REQUEST['ebookFormat']);

					} elseif (isset($_REQUEST['eaudioFormat']) && $formatObject->hasSpecialFieldOption('Eaudio format')) {
						$materialsRequest->subFormat = strip_tags($_REQUEST['eaudioFormat']);

					}
					if (isset($_REQUEST['abridged'])) {
						if ($_REQUEST['abridged'] == 'abridged') {
							$materialsRequest->abridged = 1;
						} elseif ($_REQUEST['abridged'] == 'unabridged') {
							$materialsRequest->abridged = 0;
						} else {
							$materialsRequest->abridged = 2; //Not applicable
						}
					}
					$materialsRequest->dateUpdated = time();

					if ($materialsRequest->update()) {
						$user->updateMessage = translate([
							'text' => 'The request for %1% by %2% was updated successfully.',
							'isPublicFacing' => true,
							1 => $materialsRequest->title,
							2 => $materialsRequest->author
						]);
						$user->updateMessageIsError = false;
						$user->update();
						if ($statusChanged) {
							//Send an email as needed
							$materialsRequest->sendStatusChangeEmail();
							require_once ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequestUsage.php';
							MaterialsRequestUsage::incrementStat($materialsRequest->status, $materialsRequest->libraryId);
						}
						if ($assigneeChanged) {
							//Send an email as needed
							$materialsRequest->sendStaffNewMaterialsRequestAssignedEmail();
						}
					} else {
						$user->updateMessage = translate([
							'text' => 'There was an error updating the materials request.',
							'isPublicFacing' => true
						]);
						$user->updateMessageIsError = true;
						$user->update();
					}
				}
			}
		}

		//Get a list of formats to show
		$availableFormats = MaterialsRequest::getFormats(false);
		$interface->assign('availableFormats', $availableFormats);

		$this->display('update-result.tpl', 'Update Result');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MaterialsRequest/ManageRequests', 'Manage Materials Requests');
		$breadcrumbs[] = new Breadcrumb('', 'Update Materials Request');
		return $breadcrumbs;
	}


	function getActiveAdminSection(): string {
		return 'materials_request';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Manage Library Materials Requests');
	}
}