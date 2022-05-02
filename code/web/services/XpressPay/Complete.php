<?php


class XpressPay_Complete extends Action
{
	public function launch(){
		global $interface;
		$error = '';
		$message = '';
		if (empty($_REQUEST['l1'])) {
			$error = 'No Payment ID was provided, could not cancel the payment';
		}else{
			require_once ROOT_DIR . '/sys/Account/UserPayment.php';
			$result = UserPayment::completeXpressPayPayment($_POST);
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
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'My Account');
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Fines', 'My Fines');
		$breadcrumbs[] = new Breadcrumb('', 'Payment Completed');
		return $breadcrumbs;
	}
}