<?php
require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';
require_once ROOT_DIR . '/sys/ECommerce/ProPaySetting.php';

class Complete extends MyAccount
{
	public function launch(){
		global $interface;
		$error = '';
		$message = '';
		if (empty($_REQUEST['id'])) {
			$error = 'No Payment ID was provided, could not cancel the payment';
		}else{
			require_once ROOT_DIR . '/sys/Account/UserPayment.php';
			$result = UserPayment::completeProPayPayment($_REQUEST);
			if ($result['success']){
				$message = $result['message'];
			}else {
				$error = $result['message'];
			}
		}
		$interface->assign('error', $error);
		$interface->assign('message', $message);
		$this->display('paymentCompleted.tpl', 'Payment Completed');
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'Your Account');
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Fines', 'Your Fines');
		$breadcrumbs[] = new Breadcrumb('', 'Payment Completed');
		return $breadcrumbs;
	}
}