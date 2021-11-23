<?php

require_once ROOT_DIR . '/JSON_Action.php';

class Donations_AJAX extends JSON_Action
{
	function launch($method = null)
	{
		$method = (isset($_GET['method']) && !is_array($_GET['method'])) ? $_GET['method'] : '';
		if (method_exists($this, $method)) {
				parent::launch($method);
		} else {
			echo json_encode(array('error' => 'invalid_method'));
		}
	}

	/** @noinspection PhpUnused */
	function createGenericOrder($paymentType = '') {
		$transactionDate = time();

		if(UserAccount::isLoggedIn()) {
			$user = UserAccount::getLoggedInUser();
			$patronId = $user->id;
			$patron = $user->getUserReferredTo($patronId);
			if($patron == false) {
				$isLoggedIn = false;
			} else {
				$isLoggedIn = true;
			}
		} else {
			$patronId = "guest";
		}

		if (empty($_REQUEST['donationAmount'])) {
			return ['success' => false, 'message' => translate(['text' => 'Please provide a donation amount value', 'isPublicFacing'=> true])];
		}

		$donationAmount = $_REQUEST['donationAmount'];
		$currencyCode = 'USD';
		$variables = new SystemVariables();
		if ($variables->find(true)) {
			$currencyCode = $variables->currencyCode;
		}

		$purchaseUnits['items'][] = [
			'custom_id' => $transactionDate,
			'name' => "",
			'description' => "",
			'unit_amount' => [
				'currency_code' => $currencyCode,
				'value' => round($donationAmount, 2),
			],
			'quantity' => 1
		];

		$purchaseUnits['amount'] = [
			'currency_code' => $currencyCode,
			'value' => round($donationAmount, 2),
			'breakdown' => [
				'item_total' => [
					'currency_code' => $currencyCode,
					'value' => round($donationAmount, 2),
				],
			]
		];

		$transactionType = $_REQUEST['transactionType'];

		require_once ROOT_DIR . '/sys/Account/UserPayment.php';
		$payment = new UserPayment();
		$payment->userId = $patronId;
		$payment->completed = 0;
		$payment->totalPaid = $donationAmount;
		$payment->paymentType = $paymentType;
		$payment->transactionDate = $transactionDate;
		$payment->transactionType = $transactionType;
		$paymentId = $payment->insert();
		$purchaseUnits['custom_id'] = $paymentId;

		return [$payment, $purchaseUnits];
	}
}