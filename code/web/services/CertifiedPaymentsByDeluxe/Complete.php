<?php

class CertifiedPaymentsByDeluxe_Complete extends Action {
	public function launch() {
		global $logger;
		$success = false;
		$message = '';
		$error = '';
		$logger->log('Completing Session Notification Request for Certified Payments by Deluxe...', Logger::LOG_ERROR);
		$logger->log(print_r($_POST, true), Logger::LOG_ERROR);

		$result = [
			'success' => $success,
			'message' => $success ? $message : $error,
		];

		header('Content-type: application/json');
		header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past

		echo json_encode($result);
		die();
	}

	function getBreadcrumbs(): array {
		return [];
	}
}