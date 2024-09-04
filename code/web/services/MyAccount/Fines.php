<?php

require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';

class MyAccount_Fines extends MyAccount {
	function launch() {
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
				$userLibrary = $user->getHomeLibrary();

				$systemVariables = SystemVariables::getSystemVariables();
				if ($systemVariables->libraryToUseForPayments == 1) {
					global $library;
					$userLibrary = $library;
				}

				$fines = $user->getFines();
				$useOutstanding = $user->getCatalogDriver()->showOutstandingFines();
				$interface->assign('showOutstanding', $useOutstanding);

				//PayPal
				if ($userLibrary->finePaymentType == 2) {
					require_once ROOT_DIR . '/sys/ECommerce/PayPalSetting.php';
					$settings = new PayPalSetting();
					$settings->id = $userLibrary->payPalSettingId;
					if ($settings->find(true)) {
						$interface->assign('payPalClientId', $settings->clientId);
						$interface->assign('showPayLater', $settings->showPayLater);
					}
				}

				// MSB payment result message
				if ($userLibrary->finePaymentType == 3) {
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
				if ($userLibrary->finePaymentType == 7) {
					$aspenUrl = $configArray['Site']['url'];
					$interface->assign('aspenUrl', $aspenUrl);

					global $library;
					require_once ROOT_DIR . '/sys/ECommerce/WorldPaySetting.php';
					$worldPaySettings = new WorldPaySetting();
					$worldPaySettings->id = $library->worldPaySettingId;

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
				if ($userLibrary->finePaymentType == 8) {
					$aspenUrl = $configArray['Site']['url'];
					$interface->assign('aspenUrl', $aspenUrl);

					global $library;
					require_once ROOT_DIR . '/sys/ECommerce/ACISpeedpaySetting.php';
					$aciSpeedpaySettings = new ACISpeedpaySetting();
					$aciSpeedpaySettings->id = $library->aciSpeedpaySettingId;

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
				if($userLibrary->finePaymentType == 10) {
					global $library;
					require_once ROOT_DIR . '/sys/ECommerce/CertifiedPaymentsByDeluxeSetting.php';
					$deluxeSettings = new CertifiedPaymentsByDeluxeSetting();
					$deluxeSettings->id = $library->deluxeCertifiedPaymentsSettingId;
					if($deluxeSettings->find(true)) {
						// connection URL to payment portal
						$url = 'https://www.velocitypayment.com/vrelay/verify.do';
						if($deluxeSettings->sandboxMode == 1 || $deluxeSettings->sandboxMode == "1") {
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
				if($userLibrary->finePaymentType == 12) {
					global $library;
					require_once ROOT_DIR . '/sys/ECommerce/SquareSetting.php';
					$squareSetting = new SquareSetting();
					$squareSetting->id = $library->squareSettingId;
					if($squareSetting->find(true)) {
						$cdnUrl = 'https://web.squarecdn.com/v1/square.js';
						if($squareSetting->sandboxMode == 1 || $squareSetting->sandboxMode == '1') {
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
				if($userLibrary->finePaymentType == 13) {
					global $library;
					require_once ROOT_DIR . '/sys/ECommerce/StripeSetting.php';
					$stripeSetting = new StripeSetting();
					$stripeSetting->id = $library->stripeSettingId;
					if($stripeSetting->find(true)) {
						//$baseUrl = 'https://api.stripe.com';
						$interface->assign('stripePublicKey', $stripeSetting->stripePublicKey);
						$interface->assign('stripeSecretKey', $stripeSetting->stripeSecretKey);
					}
				}

				// SnapPay
				if($userLibrary->finePaymentType == 15) {
					global $library;
					require_once ROOT_DIR . '/sys/ECommerce/SnapPaySetting.php';
					$snapPaySetting = new SnapPaySetting();
					$snapPaySetting->id = $library->snapPaySettingId;
					if($snapPaySetting->find(true)) {
						$paymentRequestUrl = "https://www.snappayglobal.com/Interop/HostedPaymentPage";
						if ($snapPaySetting->sandboxMode == 1 || $snapPaySetting->sandboxMode == '1') {
							$paymentRequestUrl = "https://stage.snappayglobal.com/Interop/HostedPaymentPage";
						}
						$interface->assign('paymentRequestUrl', $paymentRequestUrl);
					}
				}

				$interface->assign('finesToPay', $userLibrary->finesToPay);
				$interface->assign('userFines', $fines);

				$termsOfService = null;
				$convenienceFee = 0.00;
				try {
					$termsOfService = $userLibrary->eCommerceTerms;
					$convenienceFee = $userLibrary->eCommerceFee;
				} catch (Exception $e) {
					// fields don't exist;
				}
				$interface->assign('termsOfService', $termsOfService);
				$interface->assign('convenienceFee', $convenienceFee);

				$userAccountLabel = [];
				$fineTotalsVal = [];
				$outstandingTotalVal = [];
				$grandTotalVal = [];
				$outstandingGrandTotalVal = [];
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


				$interface->assign('userAccountLabel', $userAccountLabel);
				$interface->assign('fineTotalsVal', $fineTotalsVal);
				if ($useOutstanding) {
					$interface->assign('outstandingTotalVal', $outstandingTotalVal);
					$interface->assign('outstandingGrandTotalVal', $outstandingGrandTotalVal);
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