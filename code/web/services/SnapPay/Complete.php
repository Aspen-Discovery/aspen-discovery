<?php

class SnapPay_Complete extends Action {
	public function launch() {
		global $interface;
		global $logger;
		global $library;
		$error = true;
		$message = '';
		$emailNotifications = 0; // emailNotifications 0 = Do not send email; 1 = Email errors; 2 = Email all transactions
		require_once ROOT_DIR . '/sys/ECommerce/SnapPaySetting.php';
		$snapPaySetting = new SnapPaySetting();
		$snapPaySetting->id = $library->snapPaySettingId;
		if ($snapPaySetting->find(true)) {
			// determine whether SnapPay settings have email notifications enabled
			$emailNotifications = $snapPaySetting->emailNotifications;
		}
		if ($emailNotifications !== 0) {
			global $serverName;
			require_once ROOT_DIR . '/sys/Email/Mailer.php';
			$mailer = new Mailer();
			$emailNotificationsAddresses = $snapPaySetting->emailNotificationsAddresses;
		}
		if (empty($_REQUEST['udf1'])) {
			$error = true;
			$message = 'No Transaction ID was provided from SnapPay.';
		} else {
			$paymentId = $_REQUEST['udf1'];
			$hppHMACParamValue = '';
			$params = explode(',', $_REQUEST['hpphmacresponseparameters']);
			foreach ($params as $param) {
				if ($param != 'nonce' && $param != 'timestamp') {
					$hppHMACParamValue .= $_REQUEST[$param];
				}
			}
			$validated = $this->validateSnapPayHMAC($_REQUEST['signature'], $hppHMACParamValue);
			if ($validated != 'Valid signature.') {
				$error = true;
				$message = "Invalid signature returned from SnapPay for Payment Reference ID $paymentId.";
			} else {
				require_once ROOT_DIR . '/sys/Account/UserPayment.php';
				$result = UserPayment::completeSnapPayPayment();
				$message = $result['message'];
				if ($result['error'] === true) {
					$error = true;
				} else {
					$error = false;
				}
			}
		}
		if ($error === true && $result['cancelled'] === true) {
			$interface->assign('error', $message);
			$logger->log($message, Logger::LOG_ERROR);
			if ($emailNotifications === 2) { // emailNotifications 0 = Do not send email; 1 = Email errors; 2 = Email all transactions
				$mailer->send($emailNotificationsAddresses, "$serverName Error with SnapPay Payment", $message);
			}
			$this->display('paymentResult.tpl', 'Payment Cancelled');
		} elseif ($error === true) {
			$interface->assign('error', $message);
			$logger->log($message, Logger::LOG_ERROR);
			if ($emailNotifications > 0) { // emailNotifications 0 = Do not send email; 1 = Email errors; 2 = Email all transactions
				$mailer->send($emailNotificationsAddresses, "$serverName Error with SnapPay Payment", $message);
			}
			$this->display('paymentResult.tpl', 'Payment Error');
		} else {
			if (empty($message)) {
				$message = "SnapPay Payment completed with no message for Payment Reference ID $paymentId.";
			}
			$interface->assign('message', $message);
			$logger->log($message, Logger::LOG_DEBUG);
			if ($emailNotifications === 2) { // emailNotifications 0 = Do not send email; 1 = Email errors; 2 = Email all transactions
				$mailer->send($emailNotificationsAddresses, "$serverName SnapPay Payment", $message);
			}
			$this->display('paymentResult.tpl', 'Payment Completed');
		}
	}

	function validateSnapPayHMAC(string $signatureFromSnapPay, $hppHMACParamValue): string {
		global $library;
		require_once ROOT_DIR . '/sys/ECommerce/SnapPaySetting.php';
		$snapPaySetting = new SnapPaySetting();
		$snapPaySetting->id = $library->snapPaySettingId;
		if ($snapPaySetting->find(true)) {
			$hmacHeader = '';
			try {
				$secretkey = $snapPaySetting->apiAuthenticationCode;
				// Retrieve the actual signature, nonce, and timestamp from the response
				$rawAuthzHeader = mb_convert_encoding(base64_decode($signatureFromSnapPay), 'ISO-8859-1');
				$authorizationHeaderArray = explode(':', $rawAuthzHeader);
				if ($authorizationHeaderArray) {
					$incomingBase64Signature = $authorizationHeaderArray[0];
					$nonce = $authorizationHeaderArray[1];
					$timestamp = $authorizationHeaderArray[2];
					// Concatenate hppHMACParamValue, nonce, and timestamp
					$data = sprintf("%s%s%s", $hppHMACParamValue, $nonce, $timestamp);
					$signature = mb_convert_encoding($data, 'UTF-8');
					// Decode the secret key from Base64
					$secretKeyByteArray = base64_decode($secretkey);
					// Compute the HMAC SHA-256 hash
					$hmac = hash_hmac('sha256', $signature, $secretKeyByteArray, true);
					$convertedInputString = base64_encode($hmac);
					// Compare the computed signature with the incoming signature
					$isValid = hash_equals($incomingBase64Signature, $convertedInputString);
					$hmacHeader = $isValid ? "Valid signature." : "Invalid signature.";
				}
			} catch (Exception $e) {
				$hmacHeader = $e->getMessage();
			}
			return $hmacHeader;
		}
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'Your Account');
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Fines', 'Your Fines');
		$breadcrumbs[] = new Breadcrumb('', 'Payment Completed');
		return $breadcrumbs;
	}
}
