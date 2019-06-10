<?php

require_once ROOT_DIR . "/Action.php";
require_once ROOT_DIR . '/sys/MaterialsRequest.php';
require_once ROOT_DIR . '/sys/MaterialsRequestStatus.php';
require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';

class MaterialsRequest_IlsRequests extends MyAccount
{

	function launch()
	{
		global $configArray;
		global $interface;

		//Get a list of all materials requests for the user
		if (UserAccount::isLoggedIn()){
			$user = UserAccount::getLoggedInUser();
			$catalogConnection = CatalogFactory::getCatalogConnectionInstance();

			if (isset($_REQUEST['submit'])){
				$catalogConnection->deleteMaterialsRequests($user);
			}
			$requestTemplate = $catalogConnection->getMaterialsRequestsPage($user);

			$interface->assign('pageTitleShort', 'My ' . translate('Materials_Request_alt'). 's' );

			$title = 'My '. translate('Materials_Request_alt') .'s';
			$this->display($requestTemplate, $title);
		}else{
			header('Location: ' . $configArray['Site']['path'] . '/MyAccount/Home?followupModule=MaterialsRequest&followupAction=MyRequests');
			exit;
		}
	}
}