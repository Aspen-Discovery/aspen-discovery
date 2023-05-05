<?php
require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';

class MyAccount_PayflowCancelled extends Action {
	function launch() {
		global $interface;
		$userId = $_GET['USER2'];

		global $logger;
		$logger->log('PayPal Payflow Payment Cancelled', Logger::LOG_ERROR);

		require_once ROOT_DIR . '/sys/Account/UserPayment.php';
		$userPayment = new UserPayment();
		$userPayment->id = $_GET['USER1'];
		if ($userPayment->find(true)) {
			$userPayment->cancelled = true;
			$userPayment->update();
		}

		echo $interface->fetch('MyAccount/paypalPayflowCancelled.tpl');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/', 'Home');
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'My Account');
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Fines', 'Fines');
		$breadcrumbs[] = new Breadcrumb('', 'Payment Cancelled');
		return $breadcrumbs;
	}
}