<?php
require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';

class CompleteComprisePayment extends MyAccount
{
	public function launch(){
		global $logger;
		$logger->log("Starting CompleteComprisePayment method", Logger::LOG_ERROR);

		require_once ROOT_DIR . '/sys/Account/UserPayment.php';
		$result = UserPayment::completeComprisePayment($_REQUEST);

		header('Content-type: application/json');
		header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past

		global $logger;
		$logger->log(print_r($result, true), Logger::LOG_ERROR);
		echo json_encode($result);
		die();
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