<?php

require_once ROOT_DIR . "/Action.php";
require_once ROOT_DIR . '/sys/MaterialsRequest.php';
require_once ROOT_DIR . '/sys/MaterialsRequestStatus.php';
require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';

class MaterialsRequest_MyRequests extends MyAccount {

	function launch() {
		global $interface;

		$showOpen = true;
		if (isset($_REQUEST['requestsToShow']) && $_REQUEST['requestsToShow'] == 'allRequests') {
			$showOpen = false;
		}
		$interface->assign('showOpen', $showOpen);

		$homeLibrary = Library::getPatronHomeLibrary();
		if (is_null($homeLibrary)) {
			/** Admin User */ global $library;
			$homeLibrary = $library;
		}

		$maxActiveRequests = isset($homeLibrary) ? $homeLibrary->maxOpenRequests : 5;
		$maxRequestsPerYear = isset($homeLibrary) ? $homeLibrary->maxRequestsPerYear : 60;
		$interface->assign('maxActiveRequests', $maxActiveRequests);
		$interface->assign('maxRequestsPerYear', $maxRequestsPerYear);

		$defaultStatus = new MaterialsRequestStatus();
		$defaultStatus->isDefault = 1;
		$defaultStatus->libraryId = $homeLibrary->libraryId;
		$defaultStatus->find(true);
		$interface->assign('defaultStatus', $defaultStatus->id);

		//Get a list of all materials requests for the user
		$allRequests = [];
		if (UserAccount::isLoggedIn()) {
			$materialsRequests = new MaterialsRequest();
			$materialsRequests->createdBy = UserAccount::getActiveUserId();
			$materialsRequests->whereAdd('dateCreated >= unix_timestamp(now() - interval 1 year)');

			$statusQueryNotCancelled = new MaterialsRequestStatus();
			$statusQueryNotCancelled->libraryId = $homeLibrary->libraryId;
			$statusQueryNotCancelled->isPatronCancel = 0;
			$materialsRequests->joinAdd($statusQueryNotCancelled, 'INNER', 'status', 'status', 'id');

			$requestsThisYear = $materialsRequests->count();
			$interface->assign('requestsThisYear', $requestsThisYear);

			$statusQuery = new MaterialsRequestStatus();
			$statusQuery->libraryId = $homeLibrary->libraryId;
			$statusQuery->isOpen = 1;

			$materialsRequests = new MaterialsRequest();
			$materialsRequests->createdBy = UserAccount::getActiveUserId();
			$materialsRequests->joinAdd($statusQuery, 'INNER', 'status', 'status', 'id');
			$openRequests = $materialsRequests->count();
			$interface->assign('openRequests', $openRequests);

			$formats = MaterialsRequest::getFormats(true);

			$materialsRequests = new MaterialsRequest();
			$materialsRequests->createdBy = UserAccount::getActiveUserId();
			$materialsRequests->orderBy('title, dateCreated');

			$statusQuery = new MaterialsRequestStatus();
			if ($showOpen) {
				$statusQuery->libraryId = $homeLibrary->libraryId;
				$statusQuery->isOpen = 1;
			}
			$materialsRequests->joinAdd($statusQuery, 'INNER', 'status', 'status', 'id');
			$materialsRequests->selectAdd();
			$materialsRequests->selectAdd('materials_request.*, description as statusLabel');
			$materialsRequests->find();
			while ($materialsRequests->fetch()) {
				if (array_key_exists($materialsRequests->format, $formats)) {
					$materialsRequests->format = $formats[$materialsRequests->format];
				}
				$allRequests[] = clone $materialsRequests;
			}
		} else {
			header('Location: /MyAccount/Home?followupModule=MaterialsRequest&followupAction=MyRequests');
			exit;
		}
		$interface->assign('allRequests', $allRequests);

		$this->display('myMaterialRequests.tpl', 'My Materials Requests');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'Your Account');
		$breadcrumbs[] = new Breadcrumb('/MaterialsRequest/MyRequests', 'My Materials Requests');
		return $breadcrumbs;
	}
}