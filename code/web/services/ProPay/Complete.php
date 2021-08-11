<?php
require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';
require_once ROOT_DIR . '/sys/ECommerce/ProPaySetting.php';

class Complete extends MyAccount
{
	public function launch(){
		global $interface;
		$success = false;
		$error = '';
		$message = '';
		if (empty($_REQUEST['id'])) {
			$error = 'No Payment ID was provided, could not cancel the payment';
		}else{
			$paymentId = $_REQUEST['id'];
			require_once ROOT_DIR . '/sys/Account/UserPayment.php';
			$userPayment = new UserPayment();
			$userPayment->id = $paymentId;
			if ($userPayment->find(true)){
				if ($userPayment->completed == true){
					$message = 'Your payment has been completed.';
				}else{
					$user = new User();
					$user->id = $userPayment->userId;
					if ($user->find(true)) {
						$userLibrary = $user->getHomeLibrary();

						$proPaySetting = new ProPaySetting();
						$proPaySetting->id = $userLibrary->proPaySettingId;

						if ($proPaySetting->find(true)) {
							$proPayResult = $_REQUEST['result'];

							//Get results from ProPay
							$proPayHostedTransactionIdentifier = $userPayment->orderId;
							if ($proPaySetting->useTestSystem) {
								$url = 'https://xmltestapi.propay.com/protectpay/HostedTransactionResults/';
							}else{
								$url = 'https://api.propay.com/protectpay/HostedTransactionResults/';
							}
							$url .= $proPayHostedTransactionIdentifier;

							$curlWrapper = new CurlWrapper();
							$authorization = $proPaySetting->billerAccountId . ':' . $proPaySetting->authenticationToken;
							$authorization = 'Basic ' . base64_encode($authorization);
							$curlWrapper->addCustomHeaders([
								'User-Agent: Aspen Discovery',
								'Accept: application/json',
								'Cache-Control: no-cache',
								'Content-Type: application/json',
								'Accept-Encoding: gzip, deflate',
								'Authorization: ' . $authorization
							], true);
							$hostedTransactionResultsResponse = $curlWrapper->curlGetPage($url);
							if ($hostedTransactionResultsResponse && $curlWrapper->getResponseCode() == 200){
								$jsonResponse = json_decode($hostedTransactionResultsResponse);
								
							}

							if ($proPayResult == 'Failure') {
								$userPayment->completed = true;
								$userPayment->error = true;
								$proPayMessage = $_REQUEST['message'];
								$userPayment->message = $proPayMessage;
								$userPayment->update();
								$error = $userPayment->message;
							} else {
								$userPayment->completed = true;
							}
							if (empty($userPayment->message)) {
								$error = 'Your payment has not been marked as complete within the system, please contact the library with your receipt to have the payment credited to your account.';
							} else {

							}
						}else{
							$error = 'Could not find settings for the user payment';
						}
					}else{
						$error = 'Incorrect User for the payment';
					}
				}
			}else{
				$error = 'Incorrect Payment ID provided';
			}
		}
		$interface->assign('error', $error);
		$interface->assign('message', $message);
		$this->display('../MyAccount/paymentCompleted.tpl', 'Payment Completed');
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'My Account');
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Fines', 'My Fines');
		$breadcrumbs[] = new Breadcrumb('', 'Payment Completed');
		return $breadcrumbs;
	}
}