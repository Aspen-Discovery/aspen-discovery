<?php
require_once ROOT_DIR . "/sys/Donations/Donation.php";
require_once ROOT_DIR . "/sys/ECommerce/DonationsSetting.php";
require_once ROOT_DIR . "/sys/Account/UserPayment.php";

class Donations_DonationCompleted extends Action {
	public function launch() {
		global $interface;
		$error = '';
		$message = '';
		if (empty($_REQUEST['payment'])) {
			$error = 'No Payment ID was provided, could not complete the payment';
		} else {
			$paymentId = $_REQUEST['payment'];
			require_once ROOT_DIR . '/sys/Account/UserPayment.php';
			$userPayment = new UserPayment();
			require_once ROOT_DIR . '/sys/Donations/Donation.php';
			$donation = new Donation();
			$userPayment->id = $paymentId;
			if ($userPayment->find(true)) {
				$donation->paymentId = $userPayment->id;
				if ($donation->find(true)) {
					if ($userPayment->completed == true) {
						$message = 'Your payment has been completed.';
						$donation->sendReceiptEmail();
					} else {
						if (empty($userPayment->message)) {
							$error = 'Your payment has not been marked as complete within the system, please contact the library with your receipt to have the payment credited to your account.';
						} else {
							$error = $userPayment->message;
							$donation->delete();
						}
					}
				} else {
					$error = 'Incorrect Donation ID provided';
				}
			} else {
				$error = 'Incorrect Payment ID provided';
			}
		}
		$interface->assign('error', $error);
		$interface->assign('message', $message);
		$this->display('donationCompleted.tpl', 'Payment Completed', '', false);
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/', 'Home');
		$breadcrumbs[] = new Breadcrumb('/Donations/NewDonation', 'Donations');
		$breadcrumbs[] = new Breadcrumb('', 'Donation Completed', true);
		return $breadcrumbs;
	}
}