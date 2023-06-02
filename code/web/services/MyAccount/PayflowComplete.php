<?php
require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';

class MyAccount_PayflowComplete extends Action {
	function launch() {
		global $interface;
		$interface->resetActiveTheme($_GET['USER2']);

		global $activeLanguage;
		if (isset($_GET['USER3'])) {
			$language = new Language();
			$language->code = $_GET['USER3'];
			if ($language->find(true)) {
				$activeLanguage = $language;
			}
		}

		global $logger;
		$error = "";
		$logger->log('Completing PayPal Payflow Payment with PayflowComplete', Logger::LOG_ERROR);

		require_once ROOT_DIR . '/sys/Account/UserPayment.php';
		$result = UserPayment::completePayPalPayflowPayment($_GET);

		$status = $_GET['RESPMSG'];
		if($status !== 'Approved') {
			$logger->log('Error Completing PayPal Payflow Payment with PayflowComplete', Logger::LOG_ERROR);
			$error = 'Payment failed. Reason: ' . $_GET['RESPMSG'];
		} else {
			$logger->log('Completed PayPal Payflow Payment Successfully with PayflowComplete', Logger::LOG_ERROR);
		}

		$interface->assign('error', $error);
		$interface->assign('message', $result['message'] ?? '');
		echo $interface->fetch('MyAccount/paypalPayflowCompleted.tpl');
	}

	function getBreadcrumbs(): array {
		return [];
	}
}