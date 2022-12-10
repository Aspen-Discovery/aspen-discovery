<?php
require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';

class MyAccount_InvoiceCloudCompleted extends MyAccount {
	public function launch() {
		global $interface;
		$error = '';
		$message = '';
		if (empty($_REQUEST['payment'])) {
			$error = 'No Payment ID was provided, could not cancel the payment';
		} else {
			$paymentId = $_REQUEST['payment'];
			require_once ROOT_DIR . '/sys/Account/UserPayment.php';
			$userPayment = new UserPayment();
			$userPayment->id = $paymentId;
			if ($userPayment->find(true)) {
				if ($userPayment->completed == true) {
					$message = 'Your payment has been completed.';
				} else {
					if (empty($userPayment->message)) {
						$error = 'Your payment has not been marked as complete within the system, please contact the library with your receipt to have the payment credited to your account.';
					} else {
						$error = $userPayment->message;
					}
				}
			} else {
				$error = 'Incorrect Payment ID provided';
			}
		}
		$interface->assign('error', $error);
		$interface->assign('message', $message);
		$this->display('paymentCompleted.tpl', 'Payment Completed');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'Your Account');
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Fines', 'Your Fines');
		$breadcrumbs[] = new Breadcrumb('', 'Payment Completed');
		return $breadcrumbs;
	}
}