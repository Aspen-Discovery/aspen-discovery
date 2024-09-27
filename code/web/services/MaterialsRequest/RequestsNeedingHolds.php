<?php

require_once ROOT_DIR . '/Action.php';
require_once(ROOT_DIR . '/services/Admin/ObjectEditor.php');
require_once(ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequest.php');
require_once(ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequestStatus.php');
require_once(ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequestHoldCandidate.php');

class RequestsNeedingHolds extends ObjectEditor {

	function getAllObjects($page, $recordsPerPage): array {
		$list = [];

		$object = new MaterialsRequest();
		$object->readyForHolds = 1;
		$object->holdsCreated = 0;
		//TODO: Filter by assignee as well?

		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$object->find();
		while ($object->fetch()) {
			$list[$object->id] = clone $object;
		}

		return $list;
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MaterialsRequest/ManageRequests', 'Manage Materials Requests');
		$breadcrumbs[] = new Breadcrumb('', 'Requests Needing Holds');
		return $breadcrumbs;
	}

	function canView() : bool {
		return UserAccount::userHasPermission('Place Holds For Materials Requests');
	}

	function getActiveAdminSection(): string {
		return 'materials_request';
	}

	function getObjectType(): string {
		return 'MaterialsRequest';
	}

	function getToolName(): string {
		return 'RequestsNeedingHolds';
	}

	function getPageTitle(): string {
		return 'Requests Needing Holds';
	}

	function getObjectStructure($context = ''): array {
		return MaterialsRequest::getObjectStructure('requestsNeedingHolds');
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getDefaultSort(): string {
		return 'title';
	}

	function canEdit(DataObject $object) : bool {
		return false;
	}

	function canDelete() : bool {
		return false;
	}

	function canBatchEdit() : bool {
		return false;
	}

	function canAddNew() : bool {
		return false;
	}

	protected function showHistoryLinks() : bool {
		return false;
	}

	function customListActions() : array {
		return [
			[
				'label' => 'Place Holds for selected',
				'action' => 'placeSelectedHolds',
				'onclick' => "AspenDiscovery.showMessage('" . translate(['text'=>'Placing Holds', 'isAdminFacing'=>true]) . ", ". translate(['text'=>'Placing holds on the selected title(s)', 'isAdminFacing'=>true]) . ")"
			],
		];
	}

	public function getCustomListPanel() : string {
		global $interface;
		//Load status information
		$materialsRequestStatus = new MaterialsRequestStatus();
		$materialsRequestStatus->orderBy('holdFailed DESC, holdPlacedSuccessfully DESC, description ASC');
		$homeLibrary = Library::getPatronHomeLibrary();
		$user = UserAccount::getLoggedInUser();
		if (is_null($homeLibrary)) {
			//User does not have a home library, this is likely an admin account.  Use the active library
			global $library;
			$homeLibrary = $library;
		}

		$materialsRequestStatus->libraryId = $homeLibrary->libraryId;
		$materialsRequestStatus->find();

		$availableStatuses = [];
		while ($materialsRequestStatus->fetch()) {
			$availableStatuses[$materialsRequestStatus->id] = $materialsRequestStatus->description;
		}
		$interface->assign('availableStatuses', $availableStatuses);
		return 'MaterialsRequest/changeRequestStatusPanel.tpl';
	}

	/** @noinspection PhpUnused */
	function placeSelectedHolds() : void {
		global $interface;
		if (!empty($_REQUEST['selectedObject'])) {
			$updateMessage = '';
			$selectedRequestIds = $_REQUEST['selectedObject'];
			$updateMessageIsError = false;
			foreach ($selectedRequestIds as $requestId => $value) {
				require_once ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequest.php';
				require_once ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequestStatus.php';
				$materialsRequest = new MaterialsRequest();
				$materialsRequest->id = $requestId;
				if ($materialsRequest->find(true)) {
					$requestUser = $materialsRequest->getCreatedByUser();
					if ($requestUser !== false) {
						$result = $requestUser->placeHoldForRequest($materialsRequest);
						if (!$result['success']) {
							$updateMessage .= $result['message'] . '<br/>';
							$materialsRequest->holdFailureMessage = $result['message'];
							$updateMessageIsError = true;
						}else{
							$materialsRequest->holdFailureMessage = '';
							$materialsRequest->holdsCreated = 1;
							//Set the status to the correct status showing the hold has been placed
							$successStatus = new MaterialsRequestStatus();
							$successStatus->libraryId = $materialsRequest->libraryId;
							$successStatus->holdPlacedSuccessfully = 1;
							if ($successStatus->find(true)) {
								if ($materialsRequest->status != $successStatus->id) {
									$materialsRequest->status = $successStatus->id;
									$materialsRequest->dateUpdated = time();

									$materialsRequest->sendStatusChangeEmail();
								}
							}
						}
					}else{
						$updateMessageIsError = true;
						$materialsRequest->holdFailureMessage = 'Could not find user for request number ' . $requestId . '<br>';
						$updateMessage .= $materialsRequest->holdFailureMessage . '<br>';
					}
					$materialsRequest->update();
				}else{
					$updateMessage .= 'Could not find request for id ' . $requestId . '<br>';
					$updateMessageIsError = true;
				}
			}
			$interface->assign('updateMessage', $updateMessage);
			$interface->assign('updateMessageIsError', $updateMessageIsError);
		}else{
			$interface->assign('updateMessage', 'Please select one or more requests to place holds for.');
			$interface->assign('updateMessageIsError', true);
		}
		$objectStructure = $this->getObjectStructure();
		$this->viewExistingObjects($objectStructure);
	}

	function updateRequestStatus()  : void {
		global $interface;
		$newStatus = $_REQUEST['newStatus'];
		if ($newStatus == 'unselected') {
			$interface->assign('updateMessage', 'Please select a new status');
			$interface->assign('updateMessageIsError', true);
		}else {
			require_once ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequestStatus.php';
			$newStatusObject = new MaterialsRequestStatus();
			$newStatusObject->id = $newStatus;
			if ($newStatusObject->find(true)) {
				if (!empty($_REQUEST['selectedObject'])) {
					$updateMessage = '';
					$selectedRequestIds = $_REQUEST['selectedObject'];
					$updateMessageIsError = false;
					foreach ($selectedRequestIds as $requestId => $value) {
						require_once ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequest.php';
						$materialsRequest = new MaterialsRequest();
						$materialsRequest->id = $requestId;
						if ($materialsRequest->find(true)) {
							if ($materialsRequest->status != $newStatus) {
								if ($newStatusObject->holdFailed || $newStatusObject->holdPlacedSuccessfully) {
									$materialsRequest->holdsCreated = 1;
								}
								$materialsRequest->status = $newStatus;
								$materialsRequest->dateUpdated = time();
								$materialsRequest->sendStatusChangeEmail();
								$materialsRequest->update();
							}
						}
					}
				}
			}
		}
		$objectStructure = $this->getObjectStructure();
		$this->viewExistingObjects($objectStructure);
	}
}