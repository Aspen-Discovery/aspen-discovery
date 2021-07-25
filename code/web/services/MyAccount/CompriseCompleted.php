<?php
require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';

class CompriseCompleted extends MyAccount
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
				if ($userPayment->completed = true){
					$message = 'Your payment has been completed.';
				}else{
					$error = $userPayment->message;
				}
			}else{
				$error = 'Incorrect Payment ID provided';
			}
		}
		$interface->assign('error', $error);
		$interface->assign('message', $message);
		$this->display('paymentCompleted.tpl', 'Payment Completed');
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