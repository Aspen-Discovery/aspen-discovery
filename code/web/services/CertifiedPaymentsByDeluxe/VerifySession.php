<?php

class CertifiedPaymentsByDeluxe_VerifySession extends Action {
	public function launch() {
		global $logger;
		global $configArray;
		global $library;
		$result = '';
		$logger->log('Completing Session Verification Request for Certified Payments by Deluxe...', Logger::LOG_ERROR);

		if($_POST) {
			//$logger->log(print_r($_POST, true), Logger::LOG_ERROR);
			require_once ROOT_DIR . '/sys/Account/UserPayment.php';
			$payment = new UserPayment();
			$payment->deluxeRemittanceId = $_POST['remittance_id'];
			if($payment->find(true)) {
				if($payment->completed || $payment->deluxeSecurityId) {
					require_once ROOT_DIR . '/sys/ECommerce/CertifiedPaymentsByDeluxeSetting.php';
					$deluxeSettings = new CertifiedPaymentsByDeluxeSetting();
					$deluxeSettings->id = $library->deluxeCertifiedPaymentsSettingId;
					if($deluxeSettings->find(true)) {
						$url = 'https://www.velocitypayment.com/vrelay/verify.do';
						if ($deluxeSettings->sandboxMode == 1 || $deluxeSettings->sandboxMode == '1') {
							$url = 'https://demo.velocitypayment.com/vrelay/verify.do';
						}
						$postParams = [
							'continue_processing' => false,
							'user_message' => 'This payment has already been processed or the session with the payment vendor is no longer valid.',
							'redirect_user_url' => ''
						];
						$invalidSessionReturn = new CurlWrapper();
						$invalidSessionReturn->setOption(CURLOPT_RETURNTRANSFER, true);
						return $invalidSessionReturn->curlPostPage($url, $postParams);
					}
				} else {
					$success = true;
					// store security id
					$payment->deluxeSecurityId = $_POST['security_id'];
					$payment->update();
					$deluxeSettings = new CertifiedPaymentsByDeluxeSetting();
					$deluxeSettings->id = $library->deluxeCertifiedPaymentsSettingId;
					if($deluxeSettings->find(true)) {
						$url = 'https://www.velocitypayment.com/vrelay/verify.do';
						if ($deluxeSettings->sandboxMode == 1 || $deluxeSettings->sandboxMode == '1') {
							$url = 'https://demo.velocitypayment.com/vrelay/verify.do';
						}

						$postParams = [
							'continue_processing' => true,
							'action_type' => 'PayNow',
							'redirect_user_url' => $configArray['Site']['url'] . '/MyAccount/Fines',
							'amount' => $payment->totalPaid,
							'tax_amount' => '',
						];

						// try to populate payment profile with user data known
						require_once ROOT_DIR . '/sys/Account/User.php';
						$patron = new User();
						$patron->id = $payment->userId;
						if($patron->find(true)) {
							$postParams['billing_firstname'] = $patron->firstname;
							$postParams['billing_lastname'] = $patron->lastname;
						}

						$validSessionReturn = new CurlWrapper();
						$validSessionReturn->setOption(CURLOPT_RETURNTRANSFER, true);
						return $validSessionReturn->curlPostPage($url, $postParams);
					}
				}
			}
		}
	}

	function getBreadcrumbs(): array {
		return [];
	}
}