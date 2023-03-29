<?php

class CertifiedPaymentsByDeluxe_Complete extends Action {
	public function launch() {
		global $logger;
		$success = false;
		$message = '';
		$error = '';
		$logger->log('Completing Session Notification Request for Certified Payments by Deluxe...', Logger::LOG_ERROR);
		$logger->log(print_r($_POST, true), Logger::LOG_ERROR);

		require_once ROOT_DIR . '/sys/Account/UserPayment.php';
		$result = UserPayment::completeCertifiedPaymentsByDeluxePayment($_POST);

		if($result['success']) {
			$logger->log('User payment processed successfully.', Logger::LOG_ERROR);
			echo http_build_query([
				'success' => true,
			]);
			die();
		} else {
			$logger->log('Unable to process user payment. ' . $result['message'], Logger::LOG_ERROR);
			echo http_build_query([
				'success' => false,
				'user_message' => $result['message'],
			]);
			die();
		}
	}

	function getBreadcrumbs(): array {
		return [];
	}
}