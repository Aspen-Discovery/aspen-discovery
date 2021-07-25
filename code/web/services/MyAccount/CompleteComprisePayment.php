<?php
require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';

class CompleteComprisePayment extends MyAccount
{
	public function launch(){
		global $interface;
		$success = false;
		$error = '';
		$message = '';
		if (empty($_REQUEST['INVNUM'])) {
			$error = 'No Payment ID was provided, could not complete the payment';
		}else{
			$paymentId = $_REQUEST['INVNUM'];
			require_once ROOT_DIR . '/sys/Account/UserPayment.php';
			$userPayment = new UserPayment();
			$userPayment->id = $paymentId;
			if ($userPayment->find(true)){
				if ($userPayment->error || $userPayment->completed || $userPayment->cancelled){
					$userPayment->error = true;
					$userPayment->message .= "This payment has already been completed. ";
				}else{
					$result = $_REQUEST['RESULT'];
					$message = $_REQUEST['RESPMSG'];
					$amountPaid = $_REQUEST['AMT'];
					$troutD = $_REQUEST['TROUTD'];
					$authCode = $_REQUEST['AUTHCODE'];
					$ccNumber = $_REQUEST['CCNUMBER'];
					if ($amountPaid != $userPayment->totalPaid){
						$userPayment->message = "Payment amount did not match, was $userPayment->totalPaid, paid $amountPaid. ";
						$userPayment->totalPaid = $amountPaid;
					}
					$user = new User();
					$user->id = $userPayment->userId;

					if ($result == 0) {
						if ($user->find(true)){
							//Make sure the payment is for the active user or one of the users the primary user is linked to
							$userIsValid = false;
							if ($user->id == UserAccount::getActiveUserId()){
								$userIsValid = true;
							}else{
								$activeUser = UserAccount::getActiveUserObj();
								foreach ($activeUser->getLinkedUsers() as $linkedUser){
									if ($linkedUser->id == $user->id){
										$userIsValid = true;
									}
								}
							}
							if ($userIsValid) {
								$finePaymentCompleted = $user->completeFinePayment($userPayment);
								if ($finePaymentCompleted['success']) {
									$message = 'Your payment has been completed. ';
									$userPayment->message .= "Payment completed, TROUTD = $troutD, AUTHCODE = $authCode, CCNUMBER = $ccNumber. ";
								} else {
									$userPayment->error = true;
									$userPayment->message .= $finePaymentCompleted['message'];
								}
							}else{
								$userPayment->error = true;
								$userPayment->message .= 'Incorrect user was found, could not update the ILS. ';
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
				//TODO: log that an error occurred.
			}
		}
		$interface->assign('error', $error);
		$interface->assign('message', $message);
		header('Content-type: application/json');
		header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
		$result = json_encode([
			'success' => $success,
			'message' => $success ? $message : $error
		]);
		echo $result;
		die();
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'My Account');
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Fines', 'My Fines');
		$breadcrumbs[] = new Breadcrumb('', 'Payment Completed');
		return $breadcrumbs;
	}
}