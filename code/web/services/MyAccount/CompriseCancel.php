<?php
require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';

class CompriseCancel extends MyAccount
{
	public function launch(){
		global $interface;
		$error = '';
		$message = '';
		if (empty($_REQUEST['payment'])) {
			$error = 'No Payment ID was provided, could not cancel the payment';
		}else{
			$paymentId = $_REQUEST['payment'];
			require_once ROOT_DIR . '/sys/Account/UserPayment.php';
			$userPayment = new UserPayment();
			$userPayment->id = $paymentId;
			if ($userPayment->find(true)){
				$userPayment->cancelled = true;
				$userPayment->update();
				$message = 'Your payment has been cancelled.';
			}else{
				$error = 'Incorrect Payment ID provided';
			}
		}
		$interface->assign('error', $error);
		$interface->assign('message', $message);
		$this->display('paymentCancelled.tpl', 'Payment Cancelled');
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'Your Account');
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Fines', 'Your Fines');
		$breadcrumbs[] = new Breadcrumb('', 'Payment Cancelled');
		return $breadcrumbs;
	}
}