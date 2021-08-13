<?php


class UserPayment extends DataObject
{
	public $__table = 'user_payments';

	public $id;
	public $userId;
	public $paymentType;
	public $orderId;
	public $completed;
	public $cancelled;
	public $error;
	public $message;
	public $finesPaid;
	public $totalPaid;
	public $transactionDate;

	public static function completeComprisePayment($queryParams){
		$success = false;
		$error = '';
		$message = '';
		if (empty($queryParams['INVNUM'])) {
			$error = 'No Payment ID was provided, could not complete the payment';
		}else{
			$paymentId = $queryParams['INVNUM'];
			$userPayment = new UserPayment();
			$userPayment->id = $paymentId;
			if ($userPayment->find(true)){
				if ($userPayment->error || $userPayment->completed || $userPayment->cancelled){
					$userPayment->error = true;
					$userPayment->message .= "This payment has already been completed. ";
				}else{
					$result = $queryParams['RESULT'];
					$message = $queryParams['RESPMSG'];
					$amountPaid = $queryParams['AMT'];
					$troutD = $queryParams['TROUTD'];
					$authCode = $queryParams['AUTHCODE'];
					$ccNumber = $queryParams['CCNUMBER'];
					if ($amountPaid != $userPayment->totalPaid){
						$userPayment->message = "Payment amount did not match, was $userPayment->totalPaid, paid $amountPaid. ";
						$userPayment->totalPaid = $amountPaid;
					}
					$user = new User();
					$user->id = $userPayment->userId;

					if ($result == 0) {
						if ($user->find(true)){
							$finePaymentCompleted = $user->completeFinePayment($userPayment);
							if ($finePaymentCompleted['success']) {
								$success = true;
								$message = 'Your payment has been completed. ';
								$userPayment->message .= "Payment completed, TROUTD = $troutD, AUTHCODE = $authCode, CCNUMBER = $ccNumber. ";
							} else {
								$userPayment->error = true;
								$userPayment->message .= $finePaymentCompleted['message'];
							}
						}else{
							$userPayment->error = true;
							$userPayment->message .= "Could not find user to mark the fine paid in the ILS. ";
						}
						$userPayment->completed = true;
					}else{
						$userPayment->error = true;
					}
				}

				$userPayment->update();
				if ($userPayment->error){
					$error = $userPayment->message;
				}else{
					$message = $userPayment->message;
				}
			}else{
				$error = 'Incorrect Payment ID provided';
				global $logger;
				$logger->log('Incorrect Payment ID provided', Logger::LOG_ERROR);
			}
		}
		$result = [
			'success' => $success,
			'message' => $success ? $message : $error
		];

		return $result;
	}

	public static function completeProPayPayment($queryParams){
		$paymentId = $_REQUEST['id'];
		$success = false;
		$error = '';
		$message = '';

		require_once ROOT_DIR . '/sys/Account/UserPayment.php';
		$userPayment = new UserPayment();
		$userPayment->id = $paymentId;
		if ($userPayment->find(true)){
			if ($userPayment->completed == true){
				$success = true;
				$message = 'Your payment has been completed.';
			}else{
				$user = new User();
				$user->id = $userPayment->userId;
				if ($user->find(true)) {
					$userLibrary = $user->getHomeLibrary();

					$proPaySetting = new ProPaySetting();
					$proPaySetting->id = $userLibrary->proPaySettingId;

					if ($proPaySetting->find(true)) {
						$proPayResult = $_REQUEST['result'];

						//Get results from ProPay
						$proPayHostedTransactionIdentifier = $userPayment->orderId;
						if ($proPaySetting->useTestSystem) {
							$url = 'https://xmltestapi.propay.com/protectpay/HostedTransactionResults/';
						}else{
							$url = 'https://api.propay.com/protectpay/HostedTransactionResults/';
						}
						$url .= $proPayHostedTransactionIdentifier;

						$curlWrapper = new CurlWrapper();
						$authorization = $proPaySetting->billerAccountId . ':' . $proPaySetting->authenticationToken;
						$authorization = 'Basic ' . base64_encode($authorization);
						$curlWrapper->addCustomHeaders([
							'User-Agent: Aspen Discovery',
							'Accept: application/json',
							'Cache-Control: no-cache',
							'Content-Type: application/json',
							'Accept-Encoding: gzip, deflate',
							'Authorization: ' . $authorization
						], true);
						$hostedTransactionResultsResponse = $curlWrapper->curlGetPage($url);
						$jsonResponse = null;
						if ($hostedTransactionResultsResponse && $curlWrapper->getResponseCode() == 200){
							$jsonResponse = json_decode($hostedTransactionResultsResponse);
						}

						if ($proPayResult == 'Failure') {
							$userPayment->completed = true;
							$userPayment->error = true;
							$proPayMessage = $_REQUEST['message'];
							$userPayment->message = $proPayMessage;
							$userPayment->update();
							$error = $userPayment->message;
						} else if ($proPayResult == 'Cancel') {
							$userPayment->completed = true;
							$userPayment->message = "Your payment has been cancelled";
							$userPayment->update();
							$error = $userPayment->message;
						} else if ($proPayResult == 'Success') {
							$userPayment->completed = true;
							if ($jsonResponse == null){
								$userPayment->error = true;
								$userPayment->message = 'Could not receive transaction response from ProPay.  Please visit the library with your receipt to have the fine removed from your account.';
							}else{
								if ($jsonResponse->Result->ResultValue == 'SUCCESS'){
									$success = true;
									$amountPaid = $jsonResponse->HostedTransaction->GrossAmt;
									if ($amountPaid != (int)($userPayment->totalPaid * 100)){
										$userPayment->message = "Payment amount did not match, was $userPayment->totalPaid, paid $amountPaid. ";
										$userPayment->totalPaid = $amountPaid;
									}
									$user = new User();
									$user->id = $userPayment->userId;
									if ($user->find(true)){
										$finePaymentCompleted = $user->completeFinePayment($userPayment);
										if ($finePaymentCompleted['success']) {
											$success = true;
											$message = 'Your payment has been completed. ';
											$authCode = $jsonResponse->HostedTransaction->AuthCode;
											$netAmt = $jsonResponse->HostedTransaction->NetAmt;
											$transactionId = $jsonResponse->HostedTransaction->TransactionId;
											if (isset($jsonResponse->HostedTransaction->ObfuscatedAccountNumber)) {
												$ccNumber = $jsonResponse->HostedTransaction->ObfuscatedAccountNumber;
											}else{
												$ccNumber = "Not provided";
											}
											$userPayment->message .= "Payment completed, TransactionId = $transactionId, AuthCode = $authCode, CC Number = $ccNumber, Net Amount = $netAmt. ";
										} else {
											$success = false;
											$userPayment->error = true;
											$userPayment->message .= $finePaymentCompleted['message'];
										}
									}else{
										$userPayment->error = true;
										$userPayment->message .= "Could not find user to mark the fine paid in the ILS. ";
									}
								}else{
									$userPayment->error = true;
									$userPayment->message .= "Payment processing failed. " . $jsonResponse->Result->ResultMessage;
								}

								$userPayment->completed = true;
							}
						}else{
							$userPayment->error = true;
							$userPayment->message = "Unknown result, processing payment " . $proPayResult;
						}
						if (empty($userPayment->message)) {
							$error = 'Your payment has not been marked as complete within the system, please contact the library with your receipt to have the payment credited to your account.';
						} else {
							if ($userPayment->error){
								$error = $userPayment->message;
							}else{
								if (empty($message)) {
									$message = $userPayment->message;
								}
							}
						}
						$userPayment->update();
					}else{
						$error = 'Could not find settings for the user payment';
					}
				}else{
					$error = 'Incorrect User for the payment';
				}
			}
		}else{
			$error = 'Incorrect Payment ID provided';
		}
		$result = [
			'success' => $success,
			'message' => $success ? $message : $error
		];

		return $result;
	}
}