<?php


class UserPayment extends DataObject
{
	public $__table = 'user_payments';

	public $id;
	public $userId;
	public $paidFromInstance;
	public $paymentType;
	public $orderId;
	public $transactionId;
	public $completed;
	public $cancelled;
	public $error;
	public $message;
	public $finesPaid;
	public $totalPaid;
	public $transactionDate;
	public $transactionType;

	public static function getObjectStructure(){
		return [
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id within the database'),
			'paymentType' => ['property' => 'paymentType', 'type' => 'text', 'label' => 'Payment Type', 'description' => 'The system the payment was made with', 'readOnly' => true],
			'transactionDate' => ['property' => 'transactionDate', 'type' => 'timestamp', 'label' => 'Transaction Date', 'description' => 'The date the payment was started', 'readOnly' => true],
			'transactionType' => ['property' => 'transactionType', 'type' => 'text', 'label' => 'Transaction Type', 'description' => 'The kind of transaction this was', 'readOnly' => true],
			'user' => ['property' => 'user', 'type' => 'text', 'label' => 'User', 'description' => 'The user who made the payment', 'readOnly' => true],
			'paidFromInstance' => ['property' => 'paidFromInstance', 'type' => 'text', 'label' => 'Paid From', 'description' => 'The interface used when making the payment', 'readOnly' => true],
			'library' => ['property' => 'library', 'type' => 'text', 'label' => 'Library', 'description' => 'The patron\'s home library', 'readOnly' => true],
			'orderId' => ['property' => 'orderId', 'type' => 'text', 'label' => 'Order ID', 'description' => 'The ID of the order within the payment system', 'readOnly' => true],
			'transactionId' => ['property' => 'transactionId', 'type' => 'text', 'label' => 'Transaction ID', 'description' => 'The ID of the transaction within the payment system (if different from order)', 'readOnly' => true],
			'totalPaid' => ['property' => 'totalPaid', 'type' => 'currency', 'label' => 'Total Paid', 'description' => 'A list of fines paid as part of this transaction', 'displayFormat'=>'%0.2f', 'readOnly' => true],
			'finesPaid' => ['property' => 'finesPaid', 'type' => 'text', 'label' => 'Fines Paid', 'description' => 'The ID of the order within the payment system', 'readOnly' => true],
			'completed' => array('property' => 'completed', 'type' => 'checkbox', 'label' => 'Completed?', 'description' => 'Whether or not the payment has been completed', 'readOnly' => true),
			'cancelled' => array('property' => 'cancelled', 'type' => 'checkbox', 'label' => 'Cancelled?', 'description' => 'Whether or not the user cancelled the payment', 'readOnly' => true),
			'error' => array('property' => 'error', 'type' => 'checkbox', 'label' => 'Error?', 'description' => 'Whether or not an error occurred during processing of the payment', 'readOnly' => true),
			'message' => ['property' => 'message', 'type' => 'text', 'label' => 'Message', 'description' => 'A message returned by the payment system', 'readOnly' => true],
		];
	}

	/** @var User[] */
	private static $usersById = [];
	function __get($name){
		if ($name == 'user'){
			if(empty($this->userId)){
				return translate(['text' => 'Guest', 'isPublicFacing'=>true]);
			}
			if (empty($this->_data['user'])){
				if (!array_key_exists($this->userId, UserPayment::$usersById)){
					$user = new User();
					$user->id = $this->userId;
					if ($user->find(true)) {
						UserPayment::$usersById[$this->userId] = $user;
					}
				}
				if (array_key_exists($this->userId, UserPayment::$usersById)){
					$user = UserPayment::$usersById[$this->userId];
					if (!empty($user->displayName)) {
						$this->_data['user'] = $user->displayName . ' (' . $user->getBarcode() . ')';
					} else {
						$this->_data['user'] = $user->firstname . ' ' . $user->lastname . ' (' . $user->getBarcode() . ')';
					}
				}else{
					$this->_data['user'] = translate(['text' => 'Unknown', 'isPublicFacing'=>true]);
				}

			}
		}elseif ($name == 'library'){
			if (empty($this->_data['library'])){
				if (array_key_exists($this->userId, UserPayment::$usersById)){
					$this->_data['library'] = UserPayment::$usersById[$this->userId]->getHomeLibrary()->displayName;
				}else {
					$this->_data['library'] = 'Unknown';
				}
			}
		}
		return $this->_data[$name];
	}

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

					if ($result == 0) {
						//Check to see if we have a donation for this payment
						require_once ROOT_DIR . '/sys/Donations/Donation.php';
						$donation = new Donation();
						$donation->paymentId = $userPayment->id;
						if($donation->find(true)) {
							$success = true;
							$message = 'Your donation payment has been completed. ';
							$userPayment->message .= "Donation payment completed, TROUTD = $troutD, AUTHCODE = $authCode, CCNUMBER = $ccNumber. ";
						} else {
							$user = new User();
							$user->id = $userPayment->userId;
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
									if ($amountPaid != (int)round($userPayment->totalPaid * 100)){
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

	public static function completeXpressPayPayment($queryParams){
		$success = false;
		$error = '';
		$message = '';
		if (empty($queryParams['l1'])) {
			$error = 'No Payment ID was provided, could not complete the payment';
		}else{
			$paymentId = $queryParams['l1'];
			$userPayment = new UserPayment();
			$userPayment->id = $paymentId;
			if ($userPayment->find(true)){
				if ($userPayment->error || $userPayment->completed || $userPayment->cancelled){
					$userPayment->error = true;
					$userPayment->message .= "This payment has already been completed. ";
				}else{
					$amountPaid = $queryParams['totalAmount'];
					$transactionId = $queryParams['transactionId'];
					$paymentType = $queryParams['paymentType']; // card or echeck
					if ($amountPaid != $userPayment->totalPaid){
						$userPayment->message = "Payment amount did not match, was $userPayment->totalPaid, paid $amountPaid. ";
						$userPayment->totalPaid = $amountPaid;
					}

					//Check to see if we have a donation for this payment
					require_once ROOT_DIR . '/sys/Donations/Donation.php';
					$donation = new Donation();
					$donation->paymentId = $userPayment->id;
					if($donation->find(true)) {
						$success = true;
						$message = 'Your donation payment has been completed. ';
						$userPayment->message .= "Donation payment completed, TransactionId = $transactionId, TotalAmount = $amountPaid, PaymentType = $paymentType. ";
					} else {
						$user = new User();
						$user->id = $userPayment->userId;
						if ($user->find(true)){
							$finePaymentCompleted = $user->completeFinePayment($userPayment);
							if ($finePaymentCompleted['success']) {
								$success = true;
								$message = 'Your payment has been completed. ';
								$userPayment->message .= "Payment completed, TransactionId = $transactionId, TotalAmount = $amountPaid, PaymentType = $paymentType. ";
							} else {
								$userPayment->error = true;
								$userPayment->message .= $finePaymentCompleted['message'];
							}
						}else{
							$userPayment->error = true;
							$userPayment->message .= "Could not find user to mark the fine paid in the ILS. ";
						}
					}
					$userPayment->completed = true;

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

	public static function completeWorldPayPayment($queryParams){
		$success = false;
		$error = '';
		$message = '';
		if (empty($queryParams['PaymentId'])) {
			$error = 'No Payment ID was provided, could not complete the payment';
		}else{
			$paymentId = $queryParams['PaymentId'];
			$userPayment = new UserPayment();
			$userPayment->id = $paymentId;
			if ($userPayment->find(true)){
				if ($userPayment->error || $userPayment->completed || $userPayment->cancelled){
					$userPayment->error = true;
					$userPayment->message .= "This payment has already been completed. ";
				}else{
					$amountPaid = $queryParams['TransactionAmount'];
					$transactionId = $queryParams['AuthorizationCode'];
					$paymentType = $queryParams['PaymentMethodCode'];
					if ($amountPaid != $userPayment->totalPaid){
						$userPayment->message = "Payment amount did not match, was $userPayment->totalPaid, paid $amountPaid. ";
						$userPayment->totalPaid = $amountPaid;
					}

					//Check to see if we have a donation for this payment
					require_once ROOT_DIR . '/sys/Donations/Donation.php';
					$donation = new Donation();
					$donation->paymentId = $userPayment->id;
					if($donation->find(true)) {
						$success = true;
						$message = 'Your donation payment has been completed. ';
						$userPayment->message .= "Donation payment completed, TransactionId = $transactionId, TotalAmount = $amountPaid, PaymentType = $paymentType. ";
					} else {
						$user = new User();
						$user->id = $userPayment->userId;
						if ($user->find(true)){
							$finePaymentCompleted = $user->completeFinePayment($userPayment);
							if ($finePaymentCompleted['success']) {
								$success = true;
								$message = 'Your payment has been completed. ';
								$userPayment->message .= "Payment completed, TransactionId = $transactionId, TotalAmount = $amountPaid, PaymentType = $paymentType. ";
							} else {
								$userPayment->error = true;
								$userPayment->message .= $finePaymentCompleted['message'];
							}
						}else{
							$userPayment->error = true;
							$userPayment->message .= "Could not find user to mark the fine paid in the ILS. ";
						}
					}
					$userPayment->completed = true;

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

	public function toArray($includeRuntimeProperties = true, $encryptFields = false): array
	{
		$return =  parent::toArray($includeRuntimeProperties, $encryptFields);
		unset($return['userId']);
		return $return;
	}

	public function okToExport(array $selectedFilters) : bool{
		$okToExport = parent::okToExport($selectedFilters);
		$user = new User();
		$user->id = $this->userId;
		if ($user->find(true)) {
			if ($user->homeLocationId == 0 || in_array($user->homeLocationId, $selectedFilters['locations'])) {
				$okToExport = true;
			}
		}
		return $okToExport;
	}

	public function getLinksForJSON(): array
	{
		$links =  parent::getLinksForJSON();
		$user = new User();
		$user->id = $this->userId;
		if ($user->find(true)){
			$links['user'] = $user->cat_username;
		}
		return $links;
	}

	public function loadEmbeddedLinksFromJSON($jsonData, $mappings, $overrideExisting = 'keepExisting')
	{
		parent::loadEmbeddedLinksFromJSON($jsonData, $mappings, $overrideExisting);
		if (isset($jsonData['user'])){
			$username = $jsonData['user'];
			$user = new User();
			$user->cat_username = $username;
			if ($user->find(true)){
				$this->userId = $user->id;
			}
		}
	}
}