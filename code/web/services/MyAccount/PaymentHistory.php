<?php

require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';
require_once ROOT_DIR . '/sys/Pager.php';

class PaymentHistory extends MyAccount {
	function launch() {
		global $interface;
		global $library;


		//Get Payment history for the user
		require_once ROOT_DIR . '/services/API/UserAPI.php';
		$api = new UserAPI('internal');
		$paymentHistory = $api->getPaymentHistory();
		if ($paymentHistory['success']) {
			$interface->assign('explanationText', $paymentHistory['explanationText']);
			$interface->assign('paymentHistory', $paymentHistory['paymentHistory']);
		}


		$this->display('paymentHistory.tpl', 'Payment History');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'Your Account');
		$breadcrumbs[] = new Breadcrumb('', 'Your Payment History');
		return $breadcrumbs;
	}
}