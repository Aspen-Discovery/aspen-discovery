<?php


class XpressPay_Complete extends Action
{
	public function launch(){
		global $logger;
		$logger->log("Completing Xpress-pay Payment", Logger::LOG_ERROR);

		require_once ROOT_DIR . '/sys/Account/UserPayment.php';
		$result = UserPayment::completeXpressPayPayment($_REQUEST);

		header('Content-type: application/json');
		header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past

		$logger->log(print_r($result, true), Logger::LOG_ERROR);
		echo json_encode($result);
		die();
	}

	function getBreadcrumbs() : array
	{
		return [];
	}
}