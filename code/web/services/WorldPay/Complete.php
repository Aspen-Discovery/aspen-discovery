<?php


class WorldPay_Complete extends Action {
	public function launch() {
		global $logger;
		$logger->log("Completing FIS WorldPay Payment", Logger::LOG_ERROR);

		require_once ROOT_DIR . '/sys/Account/UserPayment.php';
		$result = UserPayment::completeWorldPayPayment($_POST);

		// Log the WorldPay response
		require_once ROOT_DIR . '/sys/SystemLogging/ExternalRequestLogEntry.php';
		$responseHeaders = [
			'Content-type: application/json',
			'Cache-Control: no-cache, must-revalidate',
			'Expires: Mon, 26 Jul 1997 05:00:00 GMT'
		];
		ExternalRequestLogEntry::logRequest('fine_payment.worldpay_response', 'POST', $_SERVER['REQUEST_URI'], $responseHeaders, json_encode($_POST), '200', json_encode($result), []);

		header('Content-type: application/json');
		header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past

		$logger->log(print_r($result, true), Logger::LOG_ERROR);
		echo json_encode($result);
		die();
	}

	function getBreadcrumbs(): array {
		return [];
	}
}