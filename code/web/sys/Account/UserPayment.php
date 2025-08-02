<?php


class UserPayment extends DataObject {
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
	public $squareToken;
	public $stripeToken;
	public $aciToken;
	public $deluxeRemittanceId;
	public $deluxeSecurityId;
	public $ncrTransactionId;
	public $heyCentricPaymentReferenceNumber;
	public $requestingUrl;

	public static function getObjectStructure($context = '') {
		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id within the database',
			],
			'paymentType' => [
				'property' => 'paymentType',
				'type' => 'text',
				'label' => 'Payment Type',
				'description' => 'The system the payment was made with',
				'readOnly' => true,
			],
			'transactionDate' => [
				'property' => 'transactionDate',
				'type' => 'timestamp',
				'label' => 'Transaction Date',
				'description' => 'The date the payment was started',
				'readOnly' => true,
			],
			'transactionType' => [
				'property' => 'transactionType',
				'type' => 'text',
				'label' => 'Transaction Type',
				'description' => 'The kind of transaction this was',
				'readOnly' => true,
			],
			'user' => [
				'property' => 'user',
				'type' => 'text',
				'label' => 'User',
				'description' => 'The user who made the payment',
				'readOnly' => true,
			],
			'paidFromInstance' => [
				'property' => 'paidFromInstance',
				'type' => 'text',
				'label' => 'Paid From',
				'description' => 'The interface used when making the payment',
				'readOnly' => true,
			],
			'library' => [
				'property' => 'library',
				'type' => 'text',
				'label' => 'Library',
				'description' => 'The patron\'s home library',
				'readOnly' => true,
			],
			'orderId' => [
				'property' => 'orderId',
				'type' => 'text',
				'label' => 'Order ID',
				'description' => 'The ID of the order within the payment system',
				'readOnly' => true,
			],
			'transactionId' => [
				'property' => 'transactionId',
				'type' => 'text',
				'label' => 'Transaction ID',
				'description' => 'The ID of the transaction within the payment system (if different from order)',
				'readOnly' => true,
			],
			'totalPaid' => [
				'property' => 'totalPaid',
				'type' => 'currency',
				'label' => 'Total Paid',
				'description' => 'A list of fines paid as part of this transaction',
				'displayFormat' => '%0.2f',
				'readOnly' => true,
			],
			'finesPaid' => [
				'property' => 'finesPaid',
				'type' => 'text',
				'label' => 'Fines Paid',
				'description' => 'The ID of the order within the payment system',
				'readOnly' => true,
			],
			'completed' => [
				'property' => 'completed',
				'type' => 'checkbox',
				'label' => 'Completed?',
				'description' => 'Whether or not the payment has been completed',
				'readOnly' => true,
			],
			'cancelled' => [
				'property' => 'cancelled',
				'type' => 'checkbox',
				'label' => 'Cancelled?',
				'description' => 'Whether or not the user cancelled the payment',
				'readOnly' => true,
			],
			'declined' => [
				'property' => 'declined',
				'type' => 'checkbox',
				'label' => 'Declined?',
				'description' => 'Whether or not the payment was declined',
				'readOnly' => true,
			],
			'error' => [
				'property' => 'error',
				'type' => 'checkbox',
				'label' => 'Error?',
				'description' => 'Whether or not an error occurred during processing of the payment',
				'readOnly' => true,
			],
			'message' => [
				'property' => 'message',
				'type' => 'text',
				'label' => 'Message',
				'description' => 'A message returned by the payment system',
				'readOnly' => true,
			],
			'requestingUrl' => [
				'property' => 'requestingUrl',
				'type' => 'url',
				'label' => 'Requesting Url',
				'description' => 'Where the payment was requested from',
				'readOnly' => true,
			]
		];
	}

	/** @var User[] */
	private static $usersById = [];

	function __get($name) {
		if ($name == 'user') {
			if (empty($this->userId)) {
				return translate([
					'text' => 'Guest',
					'isPublicFacing' => true,
				]);
			}
			if (empty($this->_data['user'])) {
				if (!array_key_exists($this->userId, UserPayment::$usersById)) {
					$user = new User();
					$user->id = $this->userId;
					if ($user->find(true)) {
						UserPayment::$usersById[$this->userId] = $user;
					}
				}
				if (array_key_exists($this->userId, UserPayment::$usersById)) {
					$user = UserPayment::$usersById[$this->userId];
					if (!empty($user->displayName)) {
						$this->_data['user'] = $user->displayName . ' (' . $user->getBarcode() . ')';
					} else {
						$this->_data['user'] = $user->firstname . ' ' . $user->lastname . ' (' . $user->getBarcode() . ')';
					}
				} else {
					$this->_data['user'] = translate([
						'text' => 'Unknown',
						'isPublicFacing' => true,
					]);
				}

			}
		} elseif ($name == 'library') {
			if (empty($this->_data['library'])) {
				if (array_key_exists($this->userId, UserPayment::$usersById)) {
					if (UserPayment::$usersById[$this->userId]->getHomeLibrary() != null) {
						$this->_data['library'] = UserPayment::$usersById[$this->userId]->getHomeLibrary()->displayName;
					}else{
						$this->_data['library'] = 'None';
					}
				} else {
					$this->_data['library'] = 'Unknown';
				}
			}
		}
		return $this->_data[$name] ?? null;
	}

	public static function completeNCRPayment(): array {
		$paymentId = $_REQUEST['transid'];
		$success = false;
		$error = '';
		$message = '';

		require_once ROOT_DIR . '/sys/Account/UserPayment.php';
		$userPayment = new UserPayment();
		$userPayment->orderId = $paymentId;
		if ($userPayment->find(true)) {
			if ($userPayment->completed) {
				$success = true;
				$message = translate([
					'text' => 'Your payment has been completed. ',
					'isPublicFacing' => true,
				]);
			} else {
				$user = new User();
				$user->id = $userPayment->userId;
				if ($user->find(true)) {
					$userLibrary = $user->getHomeLibrary();

					require_once ROOT_DIR . '/sys/ECommerce/NCRPaymentsSetting.php';
					$NCRPaymentsSetting = new NCRPaymentsSetting();
					$NCRPaymentsSetting->id = $userLibrary->ncrSettingId;

					if ($NCRPaymentsSetting->find(true)) {
						$status = $_REQUEST['status'] ?? null;
						// Status = 0: Payment Cancelled
						if ($status === '0') {
							$error = translate([
								'text' => 'Payment was cancelled.',
								'isPublicFacing' => true,
							]);
							$userPayment->cancelled = true;
						}
						else {
							$clientKey = $NCRPaymentsSetting->clientKey;
							$transactionID = $userPayment->orderId;

							$url = "https://magic.collectorsolutions.com/magic-api/api/transaction/redirect/".$clientKey."/".$transactionID;

							$curlWrapper = new CurlWrapper();
							$curlWrapper->addCustomHeaders([
								'Accept: application/json',
								'Content-Type: application/json',
								'Accept-Charset: utf-8',
							], true);

							$hostedTransactionResultsResponse = $curlWrapper->curlGetPage($url);

							$jsonResponse = null;

							if ($hostedTransactionResultsResponse && $curlWrapper->getResponseCode() == 200) {
								$jsonResponse = json_decode($hostedTransactionResultsResponse);
							}
							ExternalRequestLogEntry::logRequest('fine_payment.completeNCROrder', 'GET', $url, $curlWrapper->getHeaders(), false, $curlWrapper->getResponseCode(), $hostedTransactionResultsResponse, []);

							if ($jsonResponse->status == "ok") {
								if($userPayment->transactionType == 'donation') {
									//Check to see if we have a donation for this payment
									require_once ROOT_DIR . '/sys/Donations/Donation.php';
									$donation = new Donation();
									$donation->paymentId = $userPayment->id;
									if ($donation->find(true)) {
										$success = true;
										$message = translate([
											'text' => 'Your donation payment has been completed. ',
											'isPublicFacing' => true,
										]);
										$userPayment->message .= "Donation payment completed";
										$userPayment->completed = true;
										if ($jsonResponse) {
											if($jsonResponse->approvalStatus == 2) {
												$netAmt = $jsonResponse->totalRemitted;
												$transactionId = $jsonResponse->transactionidentifier;
												$userPayment->message .= ", TransactionId = $transactionId, Net Amount = $netAmt. ";
											}
										}
										$userPayment->update();

										$donation->sendReceiptEmail();
									} else {
										$message = translate([
											'text' => 'Unable to locate donation with given payment id %1%',
											'isPublicFacing' => true,
											1 => $userPayment->id,
										]);
									}
								} else {
									if ($jsonResponse == null || !isset($jsonResponse->approvalStatus)) {
										$userPayment->error = true;
										$userPayment->message = 'Could not receive transaction response from NCR.  Please visit the library with your receipt to have the fine removed from your account.';
									} else {
										if ($jsonResponse->approvalStatus == 2) {
											$success = true;
											$user = new User();
											$user->id = $userPayment->userId;
											if ($user->find(true)) {
												$finePaymentCompleted = $user->completeFinePayment($userPayment);
												if ($finePaymentCompleted['success']) {
													$message = translate([
														'text' => 'Your payment has been completed. ',
														'isPublicFacing' => true,
													]);
													$netAmt = $jsonResponse->totalRemitted;
													$transactionId = $jsonResponse->transactionidentifier;

													$userPayment->completed = true;
													$userPayment->message .= "Payment completed, TransactionId = $transactionId, Net Amount = $netAmt. ";
												} else {
													$success = false;
													$userPayment->error = true;
													$userPayment->message .= $finePaymentCompleted['message'];
												}
											} else {
												$userPayment->error = true;
												$userPayment->message .= 'Could not find user to mark the fine paid in the ILS. ';
											}
										} else {
											$userPayment->error = true;
											$userPayment->message .= 'Payment processing failed. ' . $jsonResponse->errors;
										}
									}
								}
							} else {
								$userPayment->error = true;
								$userPayment->message = "Unknown result, processing payment.";
							}
							if (empty($userPayment->message)) {
								$error = 'Your payment has not been marked as complete within the system, please contact the library with your receipt to have the payment credited to your account.';
							} else {
								if ($userPayment->error) {
									$error = $userPayment->message;
								} else {
									if (empty($message)) {
										$message = $userPayment->message;
									}
								}
							}
						}
						$userPayment->update();
					} else {
						$error = 'Could not find settings for the user payment';
					}
				} else {
					$error = 'Incorrect User for the payment';
				}
			}
		} else {
			$error = 'Incorrect Payment ID provided';
		}

		return [
			'success' => $success,
			'message' => $success ? $message : $error,
		];
	}

	public static function completeComprisePayment($queryParams) {
		$success = false;
		$error = '';
		$message = '';
		if (empty($queryParams['INVNUM'])) {
			$error = 'No Payment ID was provided, could not complete the payment';
		} else {
			$paymentId = $queryParams['INVNUM'];
			$userPayment = new UserPayment();
			$userPayment->id = $paymentId;
			if ($userPayment->find(true)) {
				if ($userPayment->error || $userPayment->completed || $userPayment->cancelled) {
					if ($userPayment->error) {
						$userPayment->message .= "This payment had an error. ";
					}else if ($userPayment->completed) {
						$userPayment->message .= "This payment was already marked completed. ";
					}else if ($userPayment->cancelled) {
						$userPayment->message .= "This payment was already marked cancelled. ";
					}
					$userPayment->error = true;
				} else {
					$result = $queryParams['RESULT'];
					$message = $queryParams['RESPMSG'];
					$amountPaid = $queryParams['AMT'];
					$troutD = $queryParams['TROUTD'];
					$authCode = $queryParams['AUTHCODE'];
					$ccNumber = $queryParams['CCNUMBER'];
					$userPayment->transactionId = $troutD;
					if ($amountPaid != $userPayment->totalPaid) {
						$userPayment->message = "Payment amount did not match, was $userPayment->totalPaid, paid $amountPaid. ";
						$userPayment->totalPaid = $amountPaid;
					}

					if ($result == 0) {
						if($userPayment->transactionType == 'donation') {
							//Check to see if we have a donation for this payment
							require_once ROOT_DIR . '/sys/Donations/Donation.php';
							$donation = new Donation();
							$donation->paymentId = $userPayment->id;
							if ($donation->find(true)) {
								$success = true;
								$message = translate([
									'text' => 'Your donation payment has been completed. ',
									'isPublicFacing' => true,
								]);
								$userPayment->message .= "Donation payment completed, TROUTD = $troutD, AUTHCODE = $authCode, CCNUMBER = $ccNumber. ";
								$userPayment->completed = true;
								$userPayment->update();

								$donation->sendReceiptEmail();
							} else {
								$message = translate([
									'text' => 'Unable to locate donation with given payment id %1%',
									'isPublicFacing' => true,
									1 => $userPayment->id,
								]);
							}
						} else {
							$user = new User();
							$user->id = $userPayment->userId;
							if ($user->find(true)) {
								$finePaymentCompleted = $user->completeFinePayment($userPayment);
								if ($finePaymentCompleted['success']) {
									$success = true;
									$message = translate([
										'text' => 'Your payment has been completed. ',
										'isPublicFacing' => true,
									]);
									$userPayment->message .= "Payment completed, TROUTD = $troutD, AUTHCODE = $authCode, CCNUMBER = $ccNumber. ";
								} else {
									$userPayment->error = true;
									$userPayment->message .= $finePaymentCompleted['message'];
								}
							} else {
								$userPayment->error = true;
								$userPayment->message .= "Could not find user to mark the fine paid in the ILS. ";
							}
						}
						$userPayment->completed = true;
					} else {
						$userPayment->error = true;
					}
				}

				$userPayment->update();
				if ($userPayment->error) {
					$error = $userPayment->message;
				} else {
					$message = $userPayment->message;
				}
			} else {
				$error = 'Incorrect Payment ID provided';
				global $logger;
				$logger->log('Incorrect Payment ID provided', Logger::LOG_ERROR);
			}
		}
		$result = [
			'success' => $success,
			'message' => $success ? $message : $error,
		];

		return $result;
	}

	public static function completeProPayPayment($queryParams) {
		$paymentId = $_REQUEST['id'];
		$success = false;
		$error = '';
		$message = '';

		require_once ROOT_DIR . '/sys/Account/UserPayment.php';
		$userPayment = new UserPayment();
		$userPayment->id = $paymentId;
		if ($userPayment->find(true)) {
			if ($userPayment->completed == true) {
				$success = true;
				$message = translate([
					'text' => 'Your payment has been completed. ',
					'isPublicFacing' => true,
				]);
			} else {
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
						} else {
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
							'Authorization: ' . $authorization,
						], true);
						$hostedTransactionResultsResponse = $curlWrapper->curlGetPage($url);

						ExternalRequestLogEntry::logRequest('fine_payment.completeProPayPayment', 'GET', $url, $curlWrapper->getHeaders(),"", $curlWrapper->getResponseCode(), $hostedTransactionResultsResponse, []);
						
						$jsonResponse = null;
						if ($hostedTransactionResultsResponse && $curlWrapper->getResponseCode() == 200) {
							$jsonResponse = json_decode($hostedTransactionResultsResponse);
						}

						if ($proPayResult == 'Failure') {
							$userPayment->completed = true;
							$userPayment->error = true;
							$proPayMessage = $_REQUEST['message'];
							$userPayment->message = $proPayMessage;
							$userPayment->update();
							$error = $userPayment->message;
						} elseif ($proPayResult == 'Cancel') {
							$userPayment->completed = true;
							$userPayment->message = "Your payment has been cancelled";
							$userPayment->update();
							$error = $userPayment->message;
						} elseif ($proPayResult == 'Success') {
							if($userPayment->transactionType == 'donation') {
								//Check to see if we have a donation for this payment
								require_once ROOT_DIR . '/sys/Donations/Donation.php';
								$donation = new Donation();
								$donation->paymentId = $userPayment->id;
								if ($donation->find(true)) {
									$success = true;
									$message = translate([
										'text' => 'Your donation payment has been completed. ',
										'isPublicFacing' => true,
									]);
									$userPayment->message .= "Donation payment completed";
									$userPayment->completed = true;
									if ($jsonResponse) {
										if($jsonResponse->Result->ResultValue == 'SUCCESS') {
											$authCode = $jsonResponse->HostedTransaction->AuthCode;
											$netAmt = $jsonResponse->HostedTransaction->NetAmt;
											$transactionId = $jsonResponse->HostedTransaction->TransactionId;
											if (isset($jsonResponse->HostedTransaction->ObfuscatedAccountNumber)) {
												$ccNumber = $jsonResponse->HostedTransaction->ObfuscatedAccountNumber;
											} else {
												$ccNumber = 'Not provided';
											}
											$userPayment->message .= ", TransactionId = $transactionId, AuthCode = $authCode, CC Number = $ccNumber, Net Amount = $netAmt. ";
										}
									}
									$userPayment->update();

									$donation->sendReceiptEmail();
								} else {
									$message = translate([
										'text' => 'Unable to locate donation with given payment id %1%',
										'isPublicFacing' => true,
										1 => $userPayment->id,
									]);
								}
							} else {
								$userPayment->completed = true;
								if ($jsonResponse == null) {
									$userPayment->error = true;
									$userPayment->message = 'Could not receive transaction response from ProPay.  Please visit the library with your receipt to have the fine removed from your account.';
								} else {
									if ($jsonResponse->Result->ResultValue == 'SUCCESS') {
										$success = true;
										$amountPaid = $jsonResponse->HostedTransaction->GrossAmt;
										if ($amountPaid != (int)round($userPayment->totalPaid * 100)) {
											$userPayment->message = "Payment amount did not match, was $userPayment->totalPaid, paid $amountPaid. ";
											$userPayment->totalPaid = $amountPaid;
										}
										$user = new User();
										$user->id = $userPayment->userId;
										if ($user->find(true)) {
											$finePaymentCompleted = $user->completeFinePayment($userPayment);
											if ($finePaymentCompleted['success']) {
												$success = true;
												$message = translate([
													'text' => 'Your payment has been completed. ',
													'isPublicFacing' => true,
												]);
												$authCode = $jsonResponse->HostedTransaction->AuthCode;
												$netAmt = $jsonResponse->HostedTransaction->NetAmt;
												$transactionId = $jsonResponse->HostedTransaction->TransactionId;
												if (isset($jsonResponse->HostedTransaction->ObfuscatedAccountNumber)) {
													$ccNumber = $jsonResponse->HostedTransaction->ObfuscatedAccountNumber;
												} else {
													$ccNumber = 'Not provided';
												}
												$userPayment->message .= "Payment completed, TransactionId = $transactionId, AuthCode = $authCode, CC Number = $ccNumber, Net Amount = $netAmt. ";
											} else {
												$success = false;
												$userPayment->error = true;
												$userPayment->message .= $finePaymentCompleted['message'];
											}
										} else {
											$userPayment->error = true;
											$userPayment->message .= 'Could not find user to mark the fine paid in the ILS. ';
										}
									} else {
										$userPayment->error = true;
										$userPayment->message .= 'Payment processing failed. ' . $jsonResponse->Result->ResultMessage;
									}

									$userPayment->completed = true;
								}
							}
						} else {
							$userPayment->error = true;
							$userPayment->message = "Unknown result, processing payment " . $proPayResult;
						}
						if (empty($userPayment->message)) {
							$error = 'Your payment has not been marked as complete within the system, please contact the library with your receipt to have the payment credited to your account.';
						} else {
							if ($userPayment->error) {
								$error = $userPayment->message;
							} else {
								if (empty($message)) {
									$message = $userPayment->message;
								}
							}
						}
						$userPayment->update();
					} else {
						$error = 'Could not find settings for the user payment';
					}
				} else {
					$error = 'Incorrect User for the payment';
				}
			}
		} else {
			$error = 'Incorrect Payment ID provided';
		}
		$result = [
			'success' => $success,
			'message' => $success ? $message : $error,
		];

		return $result;
	}

	public static function completeXpressPayPayment($queryParams) {
		$success = false;
		$error = '';
		$message = '';
		if (empty($queryParams['l1'])) {
			$error = 'No Payment ID was provided, could not complete the payment';
		} else {
			$paymentId = $queryParams['l1'];
			$userPayment = new UserPayment();
			$userPayment->id = $paymentId;
			if ($userPayment->find(true)) {
				if ($userPayment->error || $userPayment->completed || $userPayment->cancelled) {
					$userPayment->error = true;
					$userPayment->message .= "This payment has already been completed. ";
				} else {
					$amountPaid = $queryParams['totalAmount'];
					$transactionId = $queryParams['transactionId'];
					$paymentType = $queryParams['paymentType']; // card or echeck

					/*					if ($amountPaid != $userPayment->totalPaid){
											$userPayment->message = "Payment amount did not match, was $userPayment->totalPaid, paid $amountPaid. ";
											$userPayment->totalPaid = $amountPaid;
										}*/

					if($userPayment->transactionType == 'donation') {
						//Check to see if we have a donation for this payment
						require_once ROOT_DIR . '/sys/Donations/Donation.php';
						$donation = new Donation();
						$donation->paymentId = $userPayment->id;
						if ($donation->find(true)) {
							$success = true;
							$message = translate([
								'text' => 'Your donation payment has been completed. ',
								'isPublicFacing' => true,
							]);
							$userPayment->message .= "Donation payment completed, TransactionId = $transactionId, TotalAmount = $amountPaid, PaymentType = $paymentType. ";
							$userPayment->completed = true;
							$userPayment->update();

							$donation->sendReceiptEmail();
						} else {
							$message = translate([
								'text' => 'Unable to locate donation with given payment id %1%',
								'isPublicFacing' => true,
								1 => $userPayment->id,
							]);
						}
					}else {
						$user = new User();
						$user->id = $userPayment->userId;
						if ($user->find(true)) {
							$finePaymentCompleted = $user->completeFinePayment($userPayment);
							if ($finePaymentCompleted['success']) {
								$success = true;
								$message = translate([
									'text' => 'Your payment has been completed. ',
									'isPublicFacing' => true,
								]);
								$userPayment->message .= "Payment completed, TransactionId = $transactionId, TotalAmount = $amountPaid, PaymentType = $paymentType. ";
							} else {
								$userPayment->error = true;
								$userPayment->message .= $finePaymentCompleted['message'];
							}
						} else {
							$userPayment->error = true;
							$userPayment->message .= "Could not find user to mark the fine paid in the ILS. ";
						}
					}
					$userPayment->completed = true;

				}

				$userPayment->update();
				if ($userPayment->error) {
					$error = $userPayment->message;
				} else {
					$message = $userPayment->message;
				}
			} else {
				$error = 'Incorrect Payment ID provided';
				global $logger;
				$logger->log('Incorrect Payment ID provided', Logger::LOG_ERROR);
			}
		}
		$result = [
			'success' => $success,
			'message' => $success ? $message : $error,
		];

		return $result;
	}

	public static function completeWorldPayPayment($queryParams) {
		$success = false;
		$error = '';
		$message = '';
		if (empty($queryParams['PaymentID'])) {
			$error = 'No Payment ID was provided, could not complete the payment';
		} else {
			$paymentId = $queryParams['PaymentID'];
			$userPayment = new UserPayment();
			$userPayment->id = $paymentId;
			if ($userPayment->find(true)) {
				if ($userPayment->error || $userPayment->completed || $userPayment->cancelled) {
					$userPayment->error = true;
					$userPayment->message .= "This payment has already been completed. ";
				} else {
					$success = true;
					$amountPaid = $queryParams['TotalPaymentAmountt'] ?? $queryParams['TransactionAmount'];
					$transactionId = $queryParams['TransactionID'] ?? $queryParams['FISTransactionNumber'];
					$userPayment->transactionId = $transactionId;
					if ($amountPaid != $userPayment->totalPaid) {
						$userPayment->message = "Payment amount did not match, was $userPayment->totalPaid, paid $amountPaid. ";
						$userPayment->totalPaid = $amountPaid;
					}

					if($userPayment->transactionType == 'donation') {
						//Check to see if we have a donation for this payment
						require_once ROOT_DIR . '/sys/Donations/Donation.php';
						$donation = new Donation();
						$donation->paymentId = $userPayment->id;
						if ($donation->find(true)) {
							$success = true;
							$message = translate([
								'text' => 'Your donation payment has been completed. ',
								'isPublicFacing' => true,
							]);
							$userPayment->message .= "Donation payment completed, PaymentId = $paymentId, TotalAmount = $amountPaid, TransactionId = $transactionId ";
							$userPayment->completed = true;
							$userPayment->update();

							$donation->sendReceiptEmail();
						} else {
							$message = translate([
								'text' => 'Unable to locate donation with given payment id %1%',
								'isPublicFacing' => true,
								1 => $userPayment->id,
							]);
						}
					} else {
						$user = new User();
						$user->id = $userPayment->userId;
						if ($user->find(true)) {
							$finePaymentCompleted = $user->completeFinePayment($userPayment);
							if ($finePaymentCompleted['success']) {
								$success = true;
								$message = translate([
									'text' => 'Your payment has been completed. ',
									'isPublicFacing' => true,
								]);
								$userPayment->message .= "Payment completed, PaymentId = $paymentId, TotalAmount = $amountPaid, TransactionId = $transactionId ";
							} else {
								$userPayment->error = true;
								$userPayment->message .= $finePaymentCompleted['message'];
							}
						} else {
							$userPayment->error = true;
							$userPayment->message .= "Could not find user to mark the fine paid in the ILS. ";
						}
					}
					$userPayment->completed = true;

				}

				$userPayment->update();
				if ($userPayment->error) {
					$error = $userPayment->message;
				} else {
					$message = $userPayment->message;
				}
			} else {
				$error = 'Incorrect Payment ID provided';
				global $logger;
				$logger->log('Incorrect Payment ID provided', Logger::LOG_ERROR);
			}
		}
		$result = [
			'success' => $success,
			'message' => $success ? $message : $error,
		];

		return $result;
	}

	public static function completePayPalPayflowPayment($queryParams) {
		$success = false;
		$error = '';
		$message = '';
		if (empty($queryParams['USER1'])) {
			$error = 'No Payment ID was provided, could not complete the payment';
		} else {
			$paymentId = $queryParams['USER1'];
			$userPayment = new UserPayment();
			$userPayment->id = $paymentId;
			if ($userPayment->find(true)) {
				if ($userPayment->error || $userPayment->completed || $userPayment->cancelled) {
					$userPayment->error = true;
					$userPayment->message .= 'This payment has already been completed. ';
				} else {
					if($queryParams['RESPMSG'] != "Approved") {
						$success = false;
						$userPayment->error = true;
						$userPayment->message = "Payment failed. Reason: " . $queryParams['RESPMSG'];
					} else {
						$success = true;
						$amountPaid = $queryParams['AMT'];
						$transactionId = $queryParams['PNREF'];
						$userPayment->transactionId = $transactionId;
						if ($amountPaid != $userPayment->totalPaid) {
							$userPayment->message = "Payment amount did not match, was $userPayment->totalPaid, paid $amountPaid. ";
							$userPayment->totalPaid = $amountPaid;
						}

						if($userPayment->transactionType == 'donation') {
							//Check to see if we have a donation for this payment
							require_once ROOT_DIR . '/sys/Donations/Donation.php';
							$donation = new Donation();
							$donation->paymentId = $userPayment->id;
							if ($donation->find(true)) {
								$success = true;
								$message = translate([
									'text' => 'Your donation payment has been completed. ',
									'isPublicFacing' => true,
								]);
								$userPayment->message .= "Donation payment completed, PaymentId = $paymentId, TotalAmount = $amountPaid, TransactionId = $transactionId ";
								$userPayment->completed = true;
								$userPayment->update();

								$donation->sendReceiptEmail();
							} else {
								$message = translate([
									'text' => 'Unable to locate donation with given payment id %1%',
									'isPublicFacing' => true,
									1 => $userPayment->id,
								]);
							}
						} else {
							$user = new User();
							$user->id = $userPayment->userId;
							if ($user->find(true)) {
								$finePaymentCompleted = $user->completeFinePayment($userPayment);
								if ($finePaymentCompleted['success']) {
									$success = true;
									$message = translate([
										'text' => 'Your payment has been completed. ',
										'isPublicFacing' => true,
									]);
									$userPayment->completed = true;
									$userPayment->message .= "Payment completed, PaymentId = $paymentId, TotalAmount = $amountPaid, TransactionId = $transactionId ";
								} else {
									$userPayment->error = true;
									$userPayment->message .= $finePaymentCompleted['message'];
								}
							} else {
								$userPayment->error = true;
								$userPayment->message .= 'Could not find user to mark the fine paid in the ILS. ';
							}
						}

					}
				}
				$userPayment->update();
				if ($userPayment->error) {
					$error = $userPayment->message;
				} else {
					$message = $userPayment->message;
				}
			} else {
				$error = 'Invalid Payment ID provided';
				global $logger;
				$logger->log('Invalid Payment ID provided', Logger::LOG_ERROR);
			}
		}
		$result = [
			'success' => $success,
			'message' => $success ? $message : $error,
		];

		return $result;
	}

	public static function completeInvoiceCloudPayment($payload): array {
		$success = false;
		$error = '';
		$message = '';
		if (empty($payload['BillerReference'])) {
			$error = translate([
				'text' => 'No Payment ID was provided, could not complete the payment',
				'isPublicFacing' => true,
			]);
		} else {
			$paymentId = $payload['BillerReference'];
			$userPayment = new UserPayment();
			$userPayment->id = $paymentId;
			if ($userPayment->find(true)) {
				if ($userPayment->error || $userPayment->completed || $userPayment->cancelled) {
					$userPayment->error = true;
					$userPayment->message .= translate([
						'text' => 'This payment has already been completed.',
						'isPublicFacing' => true
					]);
				} else {
					$amountPaid = $payload['PaymentAmount'];
					$transactionId = $payload['PaymentGUID'];
					$paymentType = $payload['PaymentTypeID'];

					if($userPayment->transactionType == 'donation') {
						//Check to see if we have a donation for this payment
						require_once ROOT_DIR . '/sys/Donations/Donation.php';
						$donation = new Donation();
						$donation->paymentId = $userPayment->id;
						if ($donation->find(true)) {
							$success = true;
							$message = translate([
								'text' => 'Your donation payment has been completed. ',
								'isPublicFacing' => true,
							]);
							$userPayment->message .= "Donation payment completed, TransactionId = $transactionId, TotalAmount = $amountPaid, PaymentType = $paymentType. ";
							$userPayment->completed = true;
							$userPayment->update();

							$donation->sendReceiptEmail();
						} else {
							$message = translate([
								'text' => 'Unable to locate donation with given payment id %1%',
								'isPublicFacing' => true,
								1 => $userPayment->id,
							]);
						}
					} else {
						$user = new User();
						$user->id = $userPayment->userId;
						if ($user->find(true)) {
							$completePayment = $user->completeFinePayment($userPayment);
							if ($completePayment['success']) {
								$success = true;
								$message = translate([
									'text' => 'Your payment has been completed. ',
									'isPublicFacing' => true,
								]);
								$userPayment->message .= "Payment completed, TransactionId = $transactionId, TotalAmount = $amountPaid, PaymentType = $paymentType. ";
							} else {
								$userPayment->error = true;
								$userPayment->message .= $completePayment['message'];
							}
						} else {
							$userPayment->error = true;
							$userPayment->message .= 'Could not find user to mark the fine paid in the ILS. ';
						}
					}
				}
			} else {
				$error = translate([
					'text' => 'Incorrect Payment ID provided',
					'isPublicFacing' => true,
				]);
				global $logger;
				$logger->log('Incorrect Payment ID provided from InvoiceCloud', Logger::LOG_ERROR);
			}
		}

		return [
			'success' => $success,
			'message' => $success ? $message : $error,
		];
	}

	public static function completeCertifiedPaymentsByDeluxePayment($payload): array {
		$success = false;
		$error = '';
		$message = '';

		$userPayment = new UserPayment();
		$userPayment->deluxeRemittanceId = $payload['remittance_id'];
		$userPayment->deluxeSecurityId = $payload['security_id'];
		if($userPayment->find(true)) {
			$userPayment->transactionId = $payload['transaction_id'];
			$userPayment->orderId = $payload['approval_code'];

			if($payload['transaction_status'] != 0 && $payload['fail_code'] != 0) {
				// transaction failed
				$userPayment->error = true;
				$message = 'Unable to process payment. ';
				$message = CertifiedPaymentsByDeluxeSetting::getFailedPaymentMessage($payload['fail_code'], $message);
				$userPayment->message = $message;
				$userPayment->update();
			} else {
				// transaction completed
				$userPayment->completed = 1;
				$userPayment->totalPaid = $payload['total_amount'];
				$userPayment->update();

				if($userPayment->transactionType == 'donation') {
					//Check to see if we have a donation for this payment
					require_once ROOT_DIR . '/sys/Donations/Donation.php';
					$donation = new Donation();
					$donation->paymentId = $userPayment->id;
					if ($donation->find(true)) {
						$success = true;
						$message = translate([
							'text' => 'Your donation payment has been completed. ',
							'isPublicFacing' => true,
						]);
						$userPayment->message .= 'Donation payment completed, TransactionId = ' . $payload['transaction_id'] . ', TotalAmount = ' . $payload['total_amount'] . '.';
						$userPayment->update();

						$donation->sendReceiptEmail();
					} else {
						$message = translate([
							'text' => 'Unable to locate donation with given payment id %1%',
							'isPublicFacing' => true,
							1 => $userPayment->id,
						]);
					}
				} else {
					$user = new User();
					$user->id = $userPayment->userId;
					if ($user->find(true)) {
						$completePayment = $user->completeFinePayment($userPayment);
						if ($completePayment['success']) {
							$success = true;
							$message = translate([
								'text' => 'Your payment has been completed. ',
								'isPublicFacing' => true,
							]);
							$userPayment->message .= 'Payment completed, TransactionId = ' . $payload['transaction_id'] . ', TotalAmount = ' . $payload['total_amount'] . '.';
						} else {
							$userPayment->error = true;
							$userPayment->message .= $completePayment['message'];
						}
					} else {
						$userPayment->error = true;
						$userPayment->message .= 'Could not find user to mark the fine paid in the ILS. ';
					}
				}
			}
		}

		return [
			'success' => $success,
			'message' => $success ? $message : $error,
		];
	}

	public static function completeSnapPayPayment(): array {
		$userPayment = new UserPayment();
		$payload = $_REQUEST;
		$userPayment->id = $payload['udf1'];
		if($userPayment->find(true)) {
			$userPayment->message = '';
			$userPayment->transactionId = $payload['paymenttransactionid'];
			if ($payload['transactionstatus'] != 'Y') { // SnapPay payment transaction failed
				if (!empty($payload['returnmessage']) && $payload['returnmessage'] == 'Action cancelled.') { // user canceled transaction ('x-ed out' of SnapPay Hosted Payment Page)
					$userPayment->cancelled = true;
				} else {
					$userPayment->error = true; // SnapPay payment transaction failed for reasons other than user cancellation
				}
				$userPayment->message = translate(
					[
						'text' => 'Unable to process payment. SnapPay server returned message: ',
						'isPublicFacing' => true,
					]
				);
				$userPayment->message .= translate(
					[
						'text' => $payload['returnmessage'],
						'isPublicFacing' => true,
					]
				);
				$userPayment->message .= translate(
					[
						'text' => '. Payment Reference ID: ',
						'isPublicFacing' => true,
					]
				);
				$userPayment->message .= $userPayment->id;
			} elseif ($userPayment->completed != 0) { // Check to see whether the payment was already processed (whether this is a double-payment that requires staff review)
				$userPayment->error = true;
				$userPayment->message = translate(
					[
						'text' => 'SnapPay Payment has already been processed for Payment Reference ID ',
						'isPublicFacing' => true,
					]
				);
				$userPayment->message .= $userPayment->id;
			} elseif ($userPayment->totalPaid != $payload['transactionamount']) { // Ensure SnapPay-reported transaction amount (which does not include convenience fee) equals Aspen-expected total paid
				$userPayment->error = true;
				$userPayment->message = translate(
					[
						'text' => 'SnapPay Payment does not equal Aspen expected payment for Payment Reference ID ',
						'isPublicFacing' => true,
					]
				);
				$userPayment->message .= $userPayment->id . " : " . $payload['transactionamount'] . " != " . $userPayment->totalPaid;
			} else {
				$userPayment->completed = true; //  Payment was processed successfully at SnapPay
				$userPayment->totalPaid = $payload['transactionamount'];
				if ($userPayment->transactionType == 'donation') { //Check to see if we have a donation for this payment
					require_once ROOT_DIR . '/sys/Donations/Donation.php';
					$donation = new Donation();
					$donation->paymentId = $userPayment->id;
					if ($donation->find(true)) {
						$userPayment->message = translate([
							'text' => 'Your donation payment has been completed. ',
							'isPublicFacing' => true,
						]);
						$userPayment->message .= translate(
							[
								'text' => 'Payment Reference ID: ',
								'isPublicFacing' => true,
							]
						);
						$userPayment->message .= $userPayment->id;
						$donation->sendReceiptEmail();
					} else {
						$userPayment->message = translate(
							[
								'text' => 'Unable to locate donation with Payment Reference ID ',
								'isPublicFacing' => true,
							]
						);
						$userPayment->message .= $userPayment->id;
					}
				} else { // Complete fee/fine payment (i.e., not a donation)
					$user = new User();
					$user->id = $userPayment->userId;
					if ($user->find(true)) {
						$completePayment = $user->completeFinePayment($userPayment);
						if ($completePayment['success']) {
							$userPayment->message .= translate(
								[
									'text' => 'Your payment has been completed. ',
									'isPublicFacing' => true,
								]
							);
							$userPayment->message .= translate(
								[
									'text' => 'Payment Reference ID: ',
									'isPublicFacing' => true,
								]
							);
							$userPayment->message .= $userPayment->id;
						} else {
							$userPayment->error = true;
							$userPayment->message .= translate(
								[
									'text' => $completePayment['message'],
									'isPublicFacing' => true,
								]
							);
						}
					} else {
						$userPayment->error = true;
						$userPayment->message .= translate(
							[
								'text' => 'Could not find user to mark the fine paid in the ILS. ',
								'isPublicFacing' => true,
							]
						);
					}
				}
			}
			$userPayment->update();
		}
		return $userPayment->toArray();
	}
	public function toArray($includeRuntimeProperties = true, $encryptFields = false): array {
		$return = parent::toArray($includeRuntimeProperties, $encryptFields);
		unset($return['userId']);
		return $return;
	}

	public function okToExport(array $selectedFilters): bool {
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

	public function getLinksForJSON(): array {
		$links = parent::getLinksForJSON();
		$user = new User();
		$user->id = $this->userId;
		if ($user->find(true)) {
			$links['user'] = $user->ils_barcode;
		}
		return $links;
	}

	public function loadEmbeddedLinksFromJSON($jsonData, $mappings, $overrideExisting = 'keepExisting') {
		parent::loadEmbeddedLinksFromJSON($jsonData, $mappings, $overrideExisting);
		if (isset($jsonData['user'])) {
			$username = $jsonData['user'];
			$user = new User();
			$user->ils_barcode = $username;
			if ($user->find(true)) {
				$this->userId = $user->id;
			}
		}
	}
}
