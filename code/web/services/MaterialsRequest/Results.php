<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequest.php';
require_once ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequestStatus.php';

/**
 * MaterialsRequest Submission processing, processes a new request for the user and
 * displays a success/fail message to the user.
 */
class MaterialsRequest_Results extends Action {

	function launch() {
		global $interface;
		global $library;

		$maxActiveRequests = $library->maxOpenRequests;
		$maxRequestsPerYear = $library->maxRequestsPerYear;
		$accountPageLink = '/MaterialsRequest/MyRequests';
		$interface->assign('accountPageLink', $accountPageLink);
		$interface->assign('maxActiveRequests', $maxActiveRequests);
		$interface->assign('maxRequestsPerYear', $maxRequestsPerYear);

		if($_REQUEST['success'] && $_REQUEST['id']) {
			$materialsRequest = new MaterialsRequest();
			$materialsRequest->id = $_REQUEST['id'];
			if($materialsRequest->find(true)) {
				$materialsRequestCounts = new MaterialsRequest();
				$materialsRequestCounts->createdBy = UserAccount::getActiveUserId();
				$statusQuery = new MaterialsRequestStatus();
				$homeLibrary = Library::getPatronHomeLibrary();
				if (is_null($homeLibrary)) {
					$homeLibrary = $library;
				}
				$statusQuery->libraryId = $homeLibrary->libraryId;
				$statusQuery->isOpen = 1;
				$materialsRequestCounts->joinAdd($statusQuery, 'INNER', 'status', 'status', 'id');
				$openRequests = $materialsRequestCounts->count();

				$materialsRequestCounts = new MaterialsRequest();
				$materialsRequestCounts->createdBy = UserAccount::getActiveUserId();
				$materialsRequestCounts->whereAdd('dateCreated >= unix_timestamp(now() - interval 1 year)');
				$statusQuery = new MaterialsRequestStatus();
				$statusQuery->isPatronCancel = 0;
				$materialsRequestCounts->joinAdd($statusQuery, 'INNER', 'status', 'status', 'id');
				$requestsThisYear = $materialsRequestCounts->count();

				$interface->assign('success', true);
				$interface->assign('materialsRequest', $materialsRequest);
				// Update Request Counts on success
				$interface->assign('requestsThisYear', $requestsThisYear);
				$interface->assign('openRequests', $openRequests);

				require_once ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequestUsage.php';
				MaterialsRequestUsage::incrementStat($materialsRequest->status, $homeLibrary->libraryId);

				$materialsRequest->sendStatusChangeEmail();
				$materialsRequest->sendStaffNewMaterialsRequestEmail();
			}
		}

		$sidebar = '';
		if (UserAccount::isLoggedIn()) {
			$sidebar = 'Search/home-sidebar.tpl';
		}

		$this->display('submission-result.tpl', 'Submission Result', $sidebar);
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'Your Account');
		$breadcrumbs[] = new Breadcrumb('/MaterialsRequest/MyRequests', 'My Materials Requests');
		return $breadcrumbs;
	}
}