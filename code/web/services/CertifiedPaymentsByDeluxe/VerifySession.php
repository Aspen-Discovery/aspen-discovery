<?php

require_once ROOT_DIR . '/sys/ECommerce/CertifiedPaymentsByDeluxeSetting.php';
require_once ROOT_DIR . '/sys/Account/UserPayment.php';

class CertifiedPaymentsByDeluxe_VerifySession extends Action {
	public function launch() {
		global $logger;
		global $configArray;
		global $library;
		$logger->log('Completing Session Verification Request for Certified Payments by Deluxe...', Logger::LOG_ERROR);

		if($_POST) {
			//$logger->log(print_r($_POST, true), Logger::LOG_ERROR);
			$payment = new UserPayment();
			$payment->deluxeRemittanceId = $_POST['remittance_id'];
			if($payment->find(true)) {
				$logger->log('Found user payment with matching remittance id.', Logger::LOG_ERROR);
				if($payment->completed || $payment->deluxeSecurityId) {
					$logger->log('Payment already completed or using expired security id. Try again.', Logger::LOG_ERROR);
					$deluxeSettings = new CertifiedPaymentsByDeluxeSetting();
					$deluxeSettings->id = $library->deluxeCertifiedPaymentsSettingId;
					if($deluxeSettings->find(true)) {
						echo http_build_query([
							'continue_processing' => false,
							'user_message' => 'This payment has already been processed or the session with the payment vendor is no longer valid.',
							'redirect_user_url' => ''
						]);
					}
				} else {
					$logger->log('Session verified.', Logger::LOG_ERROR);
					// store security id
					$payment->deluxeSecurityId = $_POST['security_id'];
					$payment->update();
					$deluxeSettings = new CertifiedPaymentsByDeluxeSetting();
					$deluxeSettings->id = $library->deluxeCertifiedPaymentsSettingId;
					if($deluxeSettings->find(true)) {
						$postParams = [
							'application_id' => $deluxeSettings->applicationId,
							'message_version' => "2.7",
							'remittance_id' => $payment->deluxeRemittanceId,
							'security_id' => $_POST['security_id'],
							'continue_processing' => true,
							'action_type' => 'PayNow',
							'redirect_user_url' => $configArray['Site']['url'] . '/MyAccount/Fines',
							'amount' => $payment->totalPaid,
							'tax_amount' => '',
						];

						// try to pre-populate payment profile with user data known
						require_once ROOT_DIR . '/sys/Account/User.php';
						$patron = new User();
						$patron->id = $payment->userId;
						if($patron->find(true)) {
							$postParams['billing_firstname'] = $patron->firstname;
							$postParams['billing_lastname'] = $patron->lastname;
						}
						echo http_build_query($postParams);
					}
				}
			} else {
				$logger->log('User payment not found with given remittance id.', Logger::LOG_ERROR);
				echo http_build_query([
					'continue_processing' => false,
					'user_message' => 'Could not find payment matching given remittance id.',
					'redirect_user_url' => ''
				]);
			}
		} else {
			$logger->log('Post data not found.', Logger::LOG_ERROR);
			echo http_build_query([
				'continue_processing' => false,
				'user_message' => 'POST data not provided by vendor.',
				'redirect_user_url' => ''
			]);
		}
	}

	function getBreadcrumbs(): array {
		return [];
	}
}