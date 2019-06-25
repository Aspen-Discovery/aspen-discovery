<?php

require_once ROOT_DIR . "/Action.php";
require_once ROOT_DIR . "/sys/MaterialsRequest.php";

class MaterialsRequest_NewRequestIls extends Action
{

	function launch()
	{
		global $configArray;

		if (!UserAccount::isLoggedIn()) {
			header('Location: ' . $configArray['Site']['path'] . '/MyAccount/Home?followupModule=MaterialsRequest&followupAction=NewRequestIls');
			exit;
		} else {
			$catalogConnection = CatalogFactory::getCatalogConnectionInstance();
			if (isset($_REQUEST['submit'])){
				$user = UserAccount::getLoggedInUser();
				$result = $catalogConnection->processMaterialsRequestForm($user);
				if ($result['success']){
					header('Location: ' . $configArray['Site']['path'] . '/MaterialsRequest/IlsRequests');
					exit;
				}else{
					global $interface;
					$interface->assign('errors', [$result['message']]);
				}
			}
			$requestForm = $catalogConnection->getNewMaterialsRequestForm();

			$this->display($requestForm, 'Materials Request');
		}
	}
}