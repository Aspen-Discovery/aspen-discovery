<?php

require_once ROOT_DIR . "/Action.php";
require_once ROOT_DIR . '/sys/MaterialsRequest.php';
require_once ROOT_DIR . '/sys/MaterialsRequestStatus.php';
require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';

class MaterialsRequest_IlsRequests extends MyAccount {

	function launch() {
		global $interface;

		//Get a list of all materials requests for the user
		if (UserAccount::isLoggedIn()) {
			$user = UserAccount::getActiveUserObj();
			$linkedUsers = $user->getLinkedUsers();
			$patronId = empty($_REQUEST['patronId']) ? $user->id : $_REQUEST['patronId'];
			$interface->assign('patronId', $patronId);

			$patron = $user->getUserReferredTo($patronId);
			if (count($linkedUsers) > 0) {
				array_unshift($linkedUsers, $user);
				$interface->assign('linkedUsers', $linkedUsers);
			}
			$interface->assign('selectedUser', $patronId); // needs to be set even when there is only one user so that the patronId hidden input gets a value in the reading history form.

			$catalogConnection = CatalogFactory::getCatalogConnectionInstance();

			if (isset($_REQUEST['submit'])) {
				$catalogConnection->deleteMaterialsRequests($patron);
			}
			$requestTemplate = $catalogConnection->getMaterialsRequestsPage($patron);

			$title = 'My Materials Requests';
			$this->display($requestTemplate, $title);
		} else {
			header('Location: /MyAccount/Home?followupModule=MaterialsRequest&followupAction=MyRequests');
			exit;
		}
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'Your Account');
		$breadcrumbs[] = new Breadcrumb('/MaterialsRequest/IlsRequests', 'My Materials Requests');
		return $breadcrumbs;
	}
}