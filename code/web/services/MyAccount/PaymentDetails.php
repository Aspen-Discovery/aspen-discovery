<?php

require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';
require_once ROOT_DIR . '/sys/Pager.php';

class PaymentDetails extends MyAccount {
	function launch() {
		global $interface;
		global $library;


		//Get Payment history for the user
		require_once ROOT_DIR . '/services/API/UserAPI.php';
		$api = new UserAPI('internal');
		$paymentId = $_REQUEST['paymentId'];
		$paymentDetails = $api->getPaymentDetails($paymentId);
		$interface->assign('success', $paymentDetails['success']);
		if ($paymentDetails['success']) {
			$interface->assign('paymentDetails', $paymentDetails['payment']);
		}else{
			$interface->assign('message', $paymentDetails['message']);
		}

		$this->display('paymentDetails.tpl', 'Payment Details');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'Your Account');
		$breadcrumbs[] = new Breadcrumb('/MyAccount/PaymentHistory', 'Payment History');
		$breadcrumbs[] = new Breadcrumb('', 'Payment Details');
		return $breadcrumbs;
	}
}