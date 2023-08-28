<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/CatalogConnection.php';

class MyAccount_ForgotBarcode extends Action {
	function launch($msg = null) {
		global $interface;
		global $library;

		$interface->assign('usernameLabel', str_replace('Your', '', $library->loginFormUsernameLabel ? $library->loginFormUsernameLabel : 'Name'));

		$result = [
			'success' => false,
			'message' => translate(['text' => 'We were unable to send a message at this time, please contact the library.', 'isPublicFacing' => true])
		];

		if ($library->twilioSettingId == -1) {
			$error = translate(['text' => 'The library is not configured to send text messages with Twilio at this time.', 'isPublicFacing' => true]);
			$interface->assign('error', $error);
			$this->display('forgotBarcode.tpl', 'Forgot Barcode', '');
		} else {
			$catalog = CatalogFactory::getCatalogConnectionInstance(null, null);
			if (isset($_REQUEST['submit'])) {
				if (isset($_REQUEST['phone'])) {
					$matchFound = false;
					$phone = $_REQUEST['phone'];
					$phone = preg_replace('/[^0-9]/', '', $phone);
					if (strlen($phone) >= 7 && strlen($phone) <= 11) {
						$result = $catalog->lookupAccountByPhoneNumber($phone);
					} else {
						$result = [
							'success' => false,
							'message' => translate([
								'text' => 'The phone number supplied was not valid.',
								'isPublicFacing' => true
							])
						];
					}

					if (!$result['success']) {
						$phone = $_REQUEST['phone'];
						if (substr($phone, 0, 1) == '+') {
							$phone = preg_replace('/[^0-9]/', '', $phone);
							if (strlen($phone) >= 10) {
								$phone = substr($phone, strlen($phone) - 10, 10);
								$result = $catalog->lookupAccountByPhoneNumber($phone);
							}
						}
					}
				} else {
					$result['message'] = translate([
						'text' => 'Please provide a phone number',
						'isPublicFacing' => true,
					]);
				}

				$interface->assign('result', $result);
				$this->display('forgotBarcodeResults.tpl', 'Forgot Barcode', '');
			} else {
				$this->display('forgotBarcode.tpl', 'Forgot Barcode', '');
			}
		}
	}

	function getBreadcrumbs(): array {
		return [];
	}
}