<?php

require_once ROOT_DIR . "/Action.php";
require_once ROOT_DIR . '/CatalogConnection.php';

class MyAccount_CompletePinReset extends Action
{
	private $pinExpired = false;
	function setPinExpired($flag){
		$this->pinExpired = $flag;
	}
	function launch(){
		global $interface;

		$tokenValid = false;
		if (isset($_REQUEST['token'])) {
			require_once ROOT_DIR . '/sys/Account/PinResetToken.php';
			$pinResetToken = new PinResetToken();
			$pinResetToken->token = $_REQUEST['token'];
			if ($pinResetToken->find(true)){
				//Token should only be valid for 1 hour.
				if ((time() - $pinResetToken->dateIssued) < 60 * 60){
					$tokenValid = true;
				}else{
					$interface->assign('error', translate(['text' => 'Token has expired.', 'isPublicFacing' => true]));
				}
			}else{
				$interface->assign('error', translate(['text' => 'Token not found.', 'isPublicFacing' => true]));
			}
		}else{
			$interface->assign('error', translate(['text' => 'No PIN Reset token provided.', 'isPublicFacing' => true]));
		}

		$interface->assign('pinExpired', $this->pinExpired);
		$catalog = CatalogFactory::getCatalogConnectionInstance(null, null);
		$pinValidationRules = $catalog->getPasswordPinValidationRules();
		$interface->assign('pinValidationRules', $pinValidationRules);
		if ((isset($_REQUEST['update']) || (isset($_REQUEST['pin1']) && isset($_REQUEST['pin2']))) && $tokenValid) {
			$userToResetPinFor = new User();
			$userToResetPinFor->id = $pinResetToken->userId;
			if ($userToResetPinFor->find(true)){
				$pin1 = $_REQUEST['pin1'];
				$pin2 = $_REQUEST['pin2'];
				if ($pin1 != $pin2){
					$interface->assign('error', translate(['text' => 'The provided PINs do not match.', 'isPublicFacing' => true]));
				}else {
					$result = $catalog->driver->updatePin($userToResetPinFor, $userToResetPinFor->getPasswordOrPin(), $pin1);
					$interface->assign('result', $result);
					if (!$result['success']){
						$interface->assign('error', $result['message']);
					//}else{
						//We were successful!
						//TODO: Try to log the patron in automatically
					}
				}
			}else{
				$interface->assign('error', translate(['text' => 'User for PIN Reset was not valid.', 'isPublicFacing' => true]));
			}
		}

		$interface->assign('tokenValid', $tokenValid);
		$interface->assign('token', $pinResetToken->token);
		$this->display('pinResetWithToken.tpl', 'Reset My Pin', false);
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'My Account');
		$breadcrumbs[] = new Breadcrumb('', 'Reset PIN');
		return $breadcrumbs;
	}
}