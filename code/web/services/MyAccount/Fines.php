<?php

use Random\RandomException;

require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';

class MyAccount_Fines extends MyAccount {
	/**
	* @throws RandomException
	*/
	function launch() : void {
		global $interface;
		global $configArray;

		$showSystem = false;

		if (UserAccount::isLoggedIn()) {
			$user = UserAccount::getActiveUserObj();
			$interface->assign('showDate', $user->showDateInFines());

			$interface->setFinesRelatedTemplateVariables();

			global $offlineMode;
			if (!$offlineMode) {
				$currencyCode = 'USD';
				$systemVariables = SystemVariables::getSystemVariables();

				if (!empty($systemVariables->currencyCode)) {
					$currencyCode = $systemVariables->currencyCode;
				}
				$interface->assign('currencyCode', $currencyCode);

				// Get My Fines
				$user = UserAccount::getLoggedInUser();
				$interface->assign('profile', $user);
				$paymentLibrary = $user->getHomeLibrary();

				$systemVariables = SystemVariables::getSystemVariables();
				if ($systemVariables->libraryToUseForPayments == 1) {
					global $library;
					$paymentLibrary = $library;
				}

				$fines = $user->getFines();
				$credits = $user->getCredits();
				$useOutstanding = $user->getCatalogDriver()->showOutstandingFines();
				$interface->assign('showOutstanding', $useOutstanding);
				$interface->assign('supportsCredits', $user->supportsCredits());

				//PayPal
				if ($paymentLibrary->finePaymentType == 2) {
					require_once ROOT_DIR . '/sys/ECommerce/PayPalSetting.php';
					$settings = new PayPalSetting();
					$settings->id = $paymentLibrary->payPalSettingId;
					if ($settings->find(true)) {
						$interface->assign('payPalClientId', $settings->clientId);
						$interface->assign('showPayLater', $settings->showPayLater);
					}
				}

				// MSB payment result message
				if ($paymentLibrary->finePaymentType == 3) {
					if (!empty($_REQUEST['id'])) {
						require_once ROOT_DIR . '/sys/Account/UserPayment.php';
						$payment = new UserPayment();
						$payment->id = $_REQUEST['id'];
						$finePaymentResult = new stdClass();
						if ($payment->find(true)) {
							if ($payment->completed == 1) {
								$finePaymentResult->success = true;
								$finePaymentResult->message = translate([
									'text' => 'Your payment was processed successfully, thank you.',
									'isPublicFacing' => true,
								]);
							} elseif ($payment->completed == 9) {
								$finePaymentResult->success = false;
								$finePaymentResult->message = translate([
									'text' => 'Your payment was processed, but failed to update the Library system. Library staff have been alerted to this problem.',
									'isPublicFacing' => true,
								]);
							} else { // i.e., $payment->completed == 0
								$finePaymentResult->success = false;
								$finePaymentResult->message = translate([
									'text' => 'Your payment has not completed processing.',
									'isPublicFacing' => true,
								]);
							}
						} else {
							$finePaymentResult->success = false;
							$finePaymentResult->message = translate([
								'text' => 'Your payment was processed, but did not match library records. Please contact the library with your receipt.',
								'isPublicFacing' => true,
							]);
						}
						$interface->assign('finePaymentResult', $finePaymentResult);
					}
				}

				// FIS WorldPay data
				if ($paymentLibrary->finePaymentType == 7) {
					$aspenUrl = $configArray['Site']['url'];
					$interface->assign('aspenUrl', $aspenUrl);

					require_once ROOT_DIR . '/sys/ECommerce/WorldPaySetting.php';
					$worldPaySettings = new WorldPaySetting();
					$worldPaySettings->id = $paymentLibrary->worldPaySettingId;

					$merchantCode = 0;
					$settleCode = 0;
					$paymentSite = "";
					$useLineItems = 0;

					if ($worldPaySettings->find(true)) {
						$merchantCode = $worldPaySettings->merchantCode;
						$settleCode = $worldPaySettings->settleCode;
						$paymentSite = $worldPaySettings->paymentSite;
						$useLineItems = $worldPaySettings->useLineItems;
					}

					$interface->assign('settleCode', $settleCode);
					$interface->assign('merchantCode', $merchantCode);
					$interface->assign('paymentSite', $paymentSite);
					$interface->assign('useLineItems', $useLineItems);
				}

				// ACI Speedpay data
				if ($paymentLibrary->finePaymentType == 8) {
					$aspenUrl = $configArray['Site']['url'];
					$interface->assign('aspenUrl', $aspenUrl);

					require_once ROOT_DIR . '/sys/ECommerce/ACISpeedpaySetting.php';
					$aciSpeedpaySettings = new ACISpeedpaySetting();
					$aciSpeedpaySettings->id = $paymentLibrary->aciSpeedpaySettingId;

					if ($aciSpeedpaySettings->find(true)) {
						$baseUrl = 'https://api.acispeedpay.com';
						$sdkUrl = 'cds.officialpayments.com';
						$billerAccountId = $user->ils_barcode;

						if ($aciSpeedpaySettings->sandboxMode == 1) {
							$baseUrl = 'https://sandbox-api.acispeedpay.com';
							$sdkUrl = 'sandbox-cds.officialpayments.com';
						}

						$apiAuthKey = $aciSpeedpaySettings->apiAuthKey;
						$billerId = $aciSpeedpaySettings->billerId;

						$interface->assign('billerId', $billerId);
						$interface->assign('aciHost', $baseUrl);
						$interface->assign('sdkUrl', $sdkUrl);
						$interface->assign('sdkAuthKey', $aciSpeedpaySettings->sdkApiAuthKey);
						$interface->assign('sdkClientId', $aciSpeedpaySettings->sdkClientId);
						$interface->assign('sdkClientSecret', $aciSpeedpaySettings->sdkClientSecret);
						$interface->assign('billerAccountId', $billerAccountId);

						require_once ROOT_DIR . '/sys/CurlWrapper.php';
						$serviceAccountAuthorization = new CurlWrapper();
						$serviceAccountAuthorization->addCustomHeaders([
							"X-Auth-Key: $aciSpeedpaySettings->sdkApiAuthKey",
							"Content-Type: application/x-www-form-urlencoded",
							"Accept: application/json",
						], true);

						$postParams = [
							'grant_type' => 'client_credentials',
							'client_id' => $aciSpeedpaySettings->sdkClientId,
							'client_secret' => $aciSpeedpaySettings->sdkClientSecret,
							'scope' => 'token_exchange',
							'biller_id' => $aciSpeedpaySettings->billerId,
							'account_number' => $billerAccountId,
						];

						$url = $baseUrl . "/auth/v1/auth/token";
						$accessTokenResults = $serviceAccountAuthorization->curlPostPage($url, $postParams);
						$accessTokenResults = json_decode($accessTokenResults, true);
						$accessToken = "";
						if (empty($accessTokenResults['access_token'])) {
							$interface->assign('aciError', 'Unable to authenticate with ACI, please try again in a few minutes.');
						} else {
							$accessToken = $accessTokenResults['access_token'];
						}
						$interface->assign('accessToken', $accessToken);

						$aciManifest = "https://cds.officialpayments.com/js-sdk/1.5.0/manifest.json";
						$aciManifest = file_get_contents($aciManifest);
						$aciManifest = json_decode($aciManifest, true);
						$sriHash = "";
						if (empty($aciManifest['speedpay.js']['integrity'])) {
							$interface->assign('aciError', 'Unable to authenticate with ACI, please try again in a few minutes.');
						} else {
							$sriHash = $aciManifest['speedpay.js']['integrity'];
						}
						$interface->assign('sriHash', $sriHash);
					}
				}

				// Certified Payments by Deluxe
				if ($paymentLibrary->finePaymentType == 10) {
					require_once ROOT_DIR . '/sys/ECommerce/CertifiedPaymentsByDeluxeSetting.php';
					$deluxeSettings = new CertifiedPaymentsByDeluxeSetting();
					$deluxeSettings->id = $paymentLibrary->deluxeCertifiedPaymentsSettingId;
					if ($deluxeSettings->find(true)) {
						// connection URL to payment portal
						$url = 'https://www.velocitypayment.com/vrelay/verify.do';
						if ($deluxeSettings->sandboxMode == 1 || $deluxeSettings->sandboxMode == "1") {
							$url = 'https://demo.velocitypayment.com/vrelay/verify.do';
						}
						$interface->assign('deluxeAPIConnectionUrl', $url);

						// generate remittance id
						$uid = random_bytes(12);
						$interface->assign('deluxeRemittanceId', bin2hex($uid));

						// application id from deluxe
						$interface->assign('deluxeApplicationId', $deluxeSettings->applicationId);
					}
				}

				// Square
				if ($paymentLibrary->finePaymentType == 12) {
					require_once ROOT_DIR . '/sys/ECommerce/SquareSetting.php';
					$squareSetting = new SquareSetting();
					$squareSetting->id = $paymentLibrary->squareSettingId;
					if ($squareSetting->find(true)) {
						$cdnUrl = 'https://web.squarecdn.com/v1/square.js';
						if ($squareSetting->sandboxMode == 1 || $squareSetting->sandboxMode == '1') {
							$cdnUrl = 'https://sandbox.web.squarecdn.com/v1/square.js';
						}
						$interface->assign('squareCdnUrl', $cdnUrl);
						$interface->assign('squareApplicationId', $squareSetting->applicationId);
						$interface->assign('squareAccessToken', $squareSetting->accessToken);
						$interface->assign('squareLocationId', $squareSetting->locationId);

						//require_once ROOT_DIR . '/sys/CurlWrapper.php';
						//$serviceAccountAuthorization = new CurlWrapper();
					}
				}

				// Stripe
				if ($paymentLibrary->finePaymentType == 13) {
					require_once ROOT_DIR . '/sys/ECommerce/StripeSetting.php';
					$stripeSetting = new StripeSetting();
					$stripeSetting->id = $paymentLibrary->stripeSettingId;
					if ($stripeSetting->find(true)) {
						$interface->assign('stripePublicKey', $stripeSetting->stripePublicKey);
					}
				}

				// SnapPay
				if ($paymentLibrary->finePaymentType == 15) {
					// Set SNapPay URL for production vs. sandbox
					require_once ROOT_DIR . '/sys/ECommerce/SnapPaySetting.php';
					$snapPaySetting = new SnapPaySetting();
					$snapPaySetting->id = $paymentLibrary->snapPaySettingId;
					if ($snapPaySetting->find(true)) {
						$paymentRequestUrl = "https://www.snappayglobal.com/Interop/HostedPaymentPage";
						if ($snapPaySetting->sandboxMode == 1 || $snapPaySetting->sandboxMode == '1') {
							$paymentRequestUrl = "https://stage.snappayglobal.com/Interop/HostedPaymentPage";
						}
						$interface->assign('paymentRequestUrl', $paymentRequestUrl);
					}
					// Catch incoming payment results
					if (!empty($_REQUEST['s'])) {
						require_once ROOT_DIR . '/sys/Account/UserPayment.php';
						require_once ROOT_DIR . '/sys/Utils/EncryptionUtils.php';
						// Decrypt the token to get the user_payments.id
						$paymentId = EncryptionUtils::decryptField($_REQUEST['s']);
						if ($paymentId) {
							$payment = new UserPayment();
							$payment->id = $paymentId;
							$finePaymentResult = new stdClass();
							if ($payment->find(true)) {
								if ($payment->completed == 1) {
									$finePaymentResult->success = true;
								} else { // i.e., $payment->completed == 0
									$finePaymentResult->success = false;
								}
								$finePaymentResult->message = translate([
									'text' => $payment->message,
									'isPublicFacing' => true,
								]);
								$interface->assign('finePaymentResult', $finePaymentResult);
							}
						}
					}
				}

				// HeyCentric result message
				if ($paymentLibrary->finePaymentType == 16) {
					$rc = $_REQUEST['Rc'] ?? null;
					$finePaymentResult = (object)[];

					if (!empty($rc)) {
						$pmt = $_REQUEST['Pmt'] ?? null;
						if ($rc == 'A') {
							$recNo = $_REQUEST['RecNo'];
							$finePaymentResult->success = true;
							$finePaymentResult->message ="Payment successful: $pmt GBP paid (HeyCentric payment reference number:" .  ($recNo ? $recNo  : " none specified") . ")";
						}
						if ($rc == 'D') {
							$finePaymentResult->success = false;
							$finePaymentResult->message = "Your payment of $pmt GBP was declined";
						}
						if ($rc == 'C') {
							$finePaymentResult->success = false;
							$finePaymentResult->message = "Your payment of $pmt GBP was cancelled before it could be executed";
						}
					}
					if (!empty((array)$finePaymentResult)) {
						$interface->assign('finePaymentResult', $finePaymentResult);
					}
				}

				$interface->assign('finesToPay', $paymentLibrary->finesToPay);
				$interface->assign('userFines', $fines);
				$interface->assign('userCredits', $credits);

				$termsOfService = null;
				$convenienceFee = 0.00;
				try {
					$termsOfService = $paymentLibrary->eCommerceTerms;
					$convenienceFee = $paymentLibrary->eCommerceFee;
				} catch (Exception $e) {
					// fields don't exist;
				}
				$interface->assign('termsOfService', $termsOfService);
				$interface->assign('convenienceFee', $convenienceFee);

				$userAccountLabel = [];
				$fineTotalsVal = [];
				$creditTotalsVal = [];
				$outstandingTotalVal = [];
				$creditOutstandingTotalVal = [];
				$grandTotalVal = [];
				$outstandingGrandTotalVal = [];
				$creditOutstandingGrandTotalVal = [];
				// Get Account Labels, Add Up Totals
				foreach ($fines as $userId => $finesDetails) {
					$userAccountLabel[$userId] = $user->getUserReferredTo($userId)->getNameAndLibraryLabel();
					$total = $totalOutstanding = 0;
					foreach ($finesDetails as $fine) {
						$amount = $fine['amountVal'];
						if (is_numeric($amount)) {
							$total += $amount;
						}
						if ($useOutstanding && $fine['amountOutstandingVal']) {
							$outstanding = $fine['amountOutstandingVal'];
							if (is_numeric($outstanding)) {
								$totalOutstanding += $outstanding;
							}
						}
						if (!empty($fine['system'])) {
							$showSystem = true;
						}
					}

					$fineTotalsVal[$userId] = $total;
					$grandTotalVal[$userId] = $total;
					$grandTotalVal[$userId] += $convenienceFee;

					if ($useOutstanding) {
						$outstandingTotalVal[$userId] = $totalOutstanding;
						$outstandingGrandTotalVal[$userId] = $totalOutstanding;
						$outstandingGrandTotalVal[$userId] += $convenienceFee;
					}
				}

				foreach ($credits as $userId => $creditsDetails) {
					if (!isset($userAccountLabel[$userId])) {
						$userAccountLabel[$userId] = $user->getUserReferredTo($userId)->getNameAndLibraryLabel();
					}
					$total = $totalOutstanding = 0;
					foreach ($creditsDetails as $credit) {
						$amount = $credit['amountVal'];
						if (is_numeric($amount)) {
							$total += $amount;
						}
						if ($useOutstanding && $credit['amountOutstandingVal']) {
							$outstanding = $credit['amountOutstandingVal'];
							if (is_numeric($outstanding)) {
								$totalOutstanding += $outstanding;
							}
						}
						if (!empty($credit['system'])) {
							$showSystem = true;
						}
					}

					$creditTotalsVal[$userId] = $total;
					if ($useOutstanding) {
						$creditOutstandingTotalVal[$userId] = $totalOutstanding;
						$creditOutstandingGrandTotalVal[$userId] = $totalOutstanding + $convenienceFee;
					}
				}


				$interface->assign('userAccountLabel', $userAccountLabel);
				$interface->assign('fineTotalsVal', $fineTotalsVal);
				$interface->assign('creditTotalsVal', $creditTotalsVal);
				if ($useOutstanding) {
					$interface->assign('outstandingTotalVal', $outstandingTotalVal);
					$interface->assign('creditOutstandingTotalVal', $creditOutstandingTotalVal);
					$interface->assign('outstandingGrandTotalVal', $outstandingGrandTotalVal);
					$interface->assign('creditOutstandingGrandTotalVal', $creditOutstandingGrandTotalVal);
				}
				$interface->assign('grandTotalVal', $grandTotalVal);

				$overPayWarning = translate([
					'text' => 'You cannot pay more than the outstanding fine amount.',
					'isPublicFacing' => true,
				]);
				$interface->assign('overPayWarning', $overPayWarning);
			}
		}
		$interface->assign('showSystem', $showSystem);
		$this->display('fines.tpl', 'My Fines');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'Your Account');
		$breadcrumbs[] = new Breadcrumb('', 'My Fines');
		return $breadcrumbs;
	}
}