<?php

require_once ROOT_DIR . "/Action.php";
require_once ROOT_DIR . "/sys/MaterialsRequest.php";

class MaterialsRequest_NewRequestIls extends Action
{

	function launch()
	{
		global $interface;
		global $library;

		if (!UserAccount::isLoggedIn()) {
			header('Location: /MyAccount/Home?followupModule=MaterialsRequest&followupAction=NewRequestIls');
			exit;
		} else {
			$user = UserAccount::getActiveUserObj();
			$patronId = empty($_REQUEST['patronId']) ?  $user->id : $_REQUEST['patronId'];
			$patron = $user->getUserReferredTo($patronId);
			$interface->assign('patronId', $patronId);

			$interface->assign('newMaterialsRequestSummary', $library->newMaterialsRequestSummary);

			$catalogConnection = CatalogFactory::getCatalogConnectionInstance();
			if (isset($_REQUEST['submit'])){
				$result = $catalogConnection->processMaterialsRequestForm($patron);
				if ($result['success']){
					header('Location: /MaterialsRequest/IlsRequests?patronId=' . $patronId);
					exit;
				}else{
					global $interface;
					$interface->assign('errors', [$result['message']]);
				}
			}
			$requestForm = $catalogConnection->getNewMaterialsRequestForm($patron);

			$this->display($requestForm, 'Materials Request');
		}
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'My Account');
		$breadcrumbs[] = new Breadcrumb('', 'New Materials Request');
		return $breadcrumbs;
	}
}