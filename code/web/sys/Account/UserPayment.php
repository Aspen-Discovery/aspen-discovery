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
}