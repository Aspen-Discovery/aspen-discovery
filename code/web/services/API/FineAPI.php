<?php
require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/CatalogConnection.php';

class FineAPI extends Action {
	private $catalog;

	function launch() {
		//Make sure the user can access the API based on the IP address
		if (!IPAddress::allowAPIAccessForClientIP()) {
			$this->forbidAPIAccess();
		}

		header('Content-type: application/json');
		header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past

		$method = (isset($_GET['method']) && !is_array($_GET['method'])) ? $_GET['method'] : '';
		if (method_exists($this, $method)) {
			$result = [
				'result' => $this->$method(),
			];
			$output = json_encode($result);
			require_once ROOT_DIR . '/sys/SystemLogging/APIUsage.php';
			APIUsage::incrementStat('FineAPI', $method);
		} else {
			$output = json_encode(['error' => 'invalid_method']);
		}
		echo $output;
	}

	function getBreadcrumbs(): array {
		return [];
	}

	function isValidJSON($str): bool {
		json_decode($str);
		return json_last_error() == JSON_ERROR_NONE;
	}

	private function MSBConfirmation() {
		global $logger;
		global $serverName;
		require_once ROOT_DIR . '/sys/Email/Mailer.php';
		$success = false;
		$message = "MSB Unknown error.";
		$mailer = new Mailer();
		$level = Logger::LOG_ERROR;
		$systemVariables = SystemVariables::getSystemVariables();
		$json_params = file_get_contents("php://input");
		if (strlen($json_params) > 0 && $this->isValidJSON($json_params)) {
			$msb = json_decode($json_params, true);
			if ($msb["ResponseCode"] != "Success") {
				// 2021 01 20: MSB reports they will only use the post back link when the operation is successful
				$success = false;
				$message = 'MSB Payment ' . $msb["PaymentReference"] . 'failed with MSB payment ResponseCode' . $msb["ResponseCode"];
			} else {
				//Retrieve the order information from Aspen db
				require_once ROOT_DIR . '/sys/Account/UserPayment.php';
				$payment = new UserPayment();
				$payment->id = $msb["PaymentReference"];
				if ($payment->find(true)) {
					$payment->orderId = $msb["TransactionID"];
					$payment->update();
					if ($payment->completed != 0) {
						$success = false;
						$message = "MSB Payment has already been processed for Payment Reference ID $payment->id";
					} else {
						// Ensure MSB-reported transaction amount (which does not include convenience fee) equals Aspen-expected total paid
						if ($payment->totalPaid != $msb["TransactionAmount"]) {
							$success = false;
							$message = "MSB Payment does not equal Aspen expected payment for Payment Reference ID $payment->id : " . $msb['TransactionAmount'] . " != $payment->totalPaid";
						} else {
							$user = new User();
							$user->id = $payment->userId;
							if ($user->find(true)) {
								return $user->completeFinePayment($payment);
							} else {
								$success = false;
								$message = 'MSB Payment ' . $msb["PaymentReference"] . 'failed with Invalid Patron';
							}
						}
					}
				} else {
					$success = false;
					$message = "MSB Payment not found in Aspen for Payment Reference ID $payment->id .";
				}
			}
		} else {
			$success = false;
			$message = "MSB Payment: processor payload not received";
		}
		$logger->log($message, $level);
		if (!empty($systemVariables->errorEmail)) {
			$mailer->send($systemVariables->errorEmail, "$serverName Error with MSB Payment", $message);
		}
		return [
			'success' => $success,
			'message' => $message,
		];
	}

}