<?php
require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';

class MyAccount_SnapPayComplete extends MyAccount {
	public function launch() {
		global $interface;
		$error = '';
		$message = '';
		$cancelled = 0;
		if (empty($_REQUEST['transid'])) {
			$error = 'No Transaction ID was provided, could not cancel the payment';
		} else {
			$paymentId = $_REQUEST['transid'];
			require_once ROOT_DIR . '/sys/Account/UserPayment.php';
			$result = UserPayment::completeSnapPayPayment($_REQUEST);
			if ($result['success']) {
				$message = $result['message'];
				$cancelled = 0;
			} else {
				$error = $result['message'];
				$cancelled = 1;
			}
		}
		$interface->assign('error', $error);
		$interface->assign('message', $message);
		if ($cancelled){
			$this->display('paymentCancelled.tpl');
		}else {
			$this->display('paymentCompleted.tpl');
		}
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'Your Account');
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Fines', 'Your Fines');
		$breadcrumbs[] = new Breadcrumb('', 'Payment Completed');
		return $breadcrumbs;
	}
}