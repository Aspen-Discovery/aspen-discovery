<?php

require_once ROOT_DIR . '/Drivers/CarlX.php';

class Nashville extends CarlX {

	public function __construct($accountProfile)
	{
		parent::__construct($accountProfile);
	}

	public function completeFeePaidViaSIP($patronId, $pmtAmount, $feeId, $transId){
			$mysip = new sip2();
			$mysip->hostname = $this->accountProfile->sipHost;
			$mysip->port = $this->accountProfile->sipPort;

			$success = false;
			$message = 'Failed to connect to complete requested action.';
			if ($mysip->connect()) {
				//send selfcheck status message
				$in = $mysip->msgSCStatus();
				$msg_result = $mysip->get_message($in);
				// Make sure the response is 98 as expected
				if (preg_match("/^98/", $msg_result)) {
					$result = $mysip->parseACSStatusResponse($msg_result);

					//  Use result to populate SIP2 settings
					// These settings don't seem to apply to the CarlX Sandbox. pascal 7-12-2016
					if (isset($result['variable']['AO'][0])){
						$mysip->AO = $result['variable']['AO'][0]; /* set AO to value returned */
					}else{
						$mysip->AO = 'NASH';
					}
					if (isset($result['variable']['AN'][0])) {
						$mysip->AN = $result['variable']['AN'][0]; /* set AN to value returned */
					}else{
						$mysip->AN = '';
					}

					$in = $mysip->msgFeePaid('','', $pmtAmount, '', $feeId, $transId);
					//print_r($in . '<br/>');
					$msg_result = $mysip->get_message($in);
					//print_r($msg_result);

					if (preg_match("/^30/", $msg_result)) {
						$result = $mysip->parseFeePaidResponse($msg_result);
						$success = ($result['fixed']['Ok'] == 1);
						$message = $result['variable']['AF'][0];
						$patronId = $result['variable']['AA'][0];
						$transId = $result['variable']['BK'][0];
						if (!$success) {
							$message += "<li>Payment unsuccessful. Transaction ID $transId ; Fee ID $feeId. $message</li>";
						}
					}
				}
			}else{
				$message = "Could not connect to circulation server, please try again later.";
			}

			return array(
				'patronId' => $patronId,
				'transId' => $transId,
				'feeId'  => $feeId,
				'success' => $success,
				'message' => $message
			);
		}

	public function completeFinePayment(User $patron, UserPayment $payment)
	{
		global $logger;
		$result = [
			'success' => false,
			'message' => 'Unknown error completing fine payment'
		];
/*
		$creditType = 'payment';

		$accountLinesPaid = explode(',', $payment->finesPaid);
		$partialPayments = [];
		$fullyPaidTotal = $payment->totalPaid;
		foreach ($accountLinesPaid as $index => $accountLinePaid){
			if (strpos($accountLinePaid, '|')){
				//Partial Payments are in the form of fineId|paymentAmount
				$accountLineInfo = explode('|', $accountLinePaid);
				$partialPayments[] = $accountLineInfo;
				$fullyPaidTotal -= $accountLineInfo[1];
				unset($accountLinesPaid[$index]);
			}else{
				$accountLinesPaid[$index] = (int)$accountLinePaid;
			}
		}

		//Process everything that has been fully paid
		$allPaymentsSucceed = true;

		$apiUrl = $this->getWebServiceURL() . "/api/v1/patrons/{$patron->username}/account/credits";
		if (count($accountLinesPaid) > 0) {
			$postVariables = [
				'account_lines_ids' => $accountLinesPaid,
				'amount' => (float)$fullyPaidTotal,
				'credit_type' => $creditType,
				'payment_type' => $payment->paymentType,
				'description' => 'Paid Online via Aspen Discovery',
				'note' => $payment->paymentType,
			];

			$response = $this->apiCurlWrapper->curlPostBodyData($apiUrl, $postVariables);

			$response = completeFeePaidViaSIP($patronId, $pmtAmount, $feeId, $transId);

			if ($this->apiCurlWrapper->getResponseCode() != 200) {
				if (strlen($response) > 0) {
					$jsonResponse = json_decode($response);
					if ($jsonResponse) {
						$result['message'] = $jsonResponse->errors[0]->message;
					} else {
						$result['message'] = $response;
					}
				} else {
					$result['message'] = "Error {$this->apiCurlWrapper->getResponseCode()} updating your payment, please visit the library with your receipt.";
					$logger->log("Unable to authenticate with Koha while completing fine payment response code: {$this->apiCurlWrapper->getResponseCode()}", Logger::LOG_ERROR);
				}
				$allPaymentsSucceed = false;
			}
		}
		if (count($partialPayments) > 0){
			foreach ($partialPayments as $paymentInfo){
				$postVariables = [
					'account_lines_ids' => [(int)$paymentInfo[0]],
					'amount' => (float)$paymentInfo[1],
					'credit_type' => $creditType,
					'payment_type' => $payment->paymentType,
					'description' => 'Paid Online via Aspen Discovery',
					'note' => $payment->paymentType,
				];

				$response = $this->apiCurlWrapper->curlPostBodyData($apiUrl, $postVariables);
				if ($this->apiCurlWrapper->getResponseCode() != 200) {
					if (!isset($result['message'])) {$result['message'] = '';}
					if (strlen($response) > 0) {
						$jsonResponse = json_decode($response);
						if ($jsonResponse) {
							$result['message'] .= $jsonResponse->errors[0]['message'];
						} else {
							$result['message'] .= $response;
						}
					} else {
						$result['message'] .= "Error {$this->apiCurlWrapper->getResponseCode()} updating your payment, please visit the library with your receipt.";
						$logger->log("Error {$this->apiCurlWrapper->getResponseCode()} updating your payment", Logger::LOG_ERROR);
					}
					$allPaymentsSucceed = false;
				}
			}
		}
		if ($allPaymentsSucceed){
			$result = [
				'success' => true,
				'message' => 'Your fines have been paid successfully, thank you.'
			];
		}

		global $memCache;
		$memCache->delete('koha_summary_' . $patron->id);
		return $result;
*/
	}

	public function canPayFine($system){
		$canPayFine = false;
		if ($system == 'NPL') {
			$canPayFine = true;
		}
		return $canPayFine;
	}

	public function getFineSystem($branchId){
		if (($branchId >= 30 && $branchId <= 178 && $branchId != 42 && $branchId != 171) || ($branchId >= 180 && $branchId <= 212 && $branchId != 185 && $branchId != 187)) {
			return "MNPS";
		} else {
			return "NPL";
		}
	}

	function getSelfRegTemplate($reason){
		if ($reason == 'duplicate_email'){
			return 'Emails/nashville-self-registration-denied-duplicate_email.tpl';
		}elseif ($reason == 'duplicate_name+birthdate') {
			return 'Emails/nashville-self-registration-denied-duplicate_name+birthdate.tpl';
		}elseif ($reason == 'success') {
			return 'Emails/nashville-self-registration.tpl';
		}else{
			return;
		}
	}

}
