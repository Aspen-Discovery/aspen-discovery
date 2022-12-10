<?php
require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';
require_once ROOT_DIR . '/sys/ECommerce/InvoiceCloudSetting.php';

class InvoiceCloud_Complete extends MyAccount {
	public function launch() {
		global $interface;
		$error = '';
		$message = '';
		global $logger;
		$logger->log('Completing InvoiceCloud Payment', Logger::LOG_ERROR);
		$logger->log(print_r($_POST, true), Logger::LOG_ERROR);
		$logger->log(print_r($_REQUEST, true), Logger::LOG_ERROR);
		if (empty($_REQUEST['payment'])) {
			$error = 'No Payment ID was provided, could not complete the payment';
		} else {
			require_once ROOT_DIR . '/sys/Account/UserPayment.php';

			$result = UserPayment::completeInvoiceCloudPayment($_POST);
			if ($result['success']) {
				$message = $result['message'];
			} else {
				$error = $result['message'];
			}
			$logger->log(print_r($result, true), Logger::LOG_ERROR);
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