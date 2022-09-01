<?php


class XpressPay_Complete extends Action
{
	public function launch(){
		global $interface;
		$error = '';
		$message = '';

		if ($_SERVER['REQUEST_METHOD'] === 'GET') {
			if (empty($_REQUEST['l1'])) {
				$error = 'No Payment ID was provided, could not complete the payment';
			}else{
				require_once ROOT_DIR . '/sys/Account/UserPayment.php';
				$result = UserPayment::completeXpressPayPayment($_REQUEST);
				if ($result['success']){
					$message = $result['message'];
				}else {
					$error = $result['message'];
				}
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