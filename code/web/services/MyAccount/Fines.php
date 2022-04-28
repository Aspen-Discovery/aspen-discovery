<?php

require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';

class MyAccount_Fines extends MyAccount
{
	function launch()
	{
		global $interface;
		global $configArray;

// TODO: get account profile -> ils instead of config.ini
		$ils = $configArray['Catalog']['ils'];
		$interface->assign('showDate', $ils == 'Koha' || $ils == 'Horizon' || $ils == 'CarlX' || $ils == 'Symphony');
		$interface->assign('showReason', true);

		$interface->setFinesRelatedTemplateVariables();

		$showSystem = false;

		if (UserAccount::isLoggedIn()) {
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
				$fines = $user->getFines();
				$useOutstanding = $user->getCatalogDriver()->showOutstandingFines();
				$interface->assign('showOutstanding', $useOutstanding);

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
								$finePaymentResult->message = translate(['text' => 'Your payment was processed successfully, thank you.', 'isPublicFacing'=> true]);
							} elseif ($payment->completed == 9) {
								$finePaymentResult->success = false;
								$finePaymentResult->message = translate(['text' => 'Your payment was processed, but failed to update the Library system. Library staff have been alerted to this problem.', 'isPublicFacing'=> true]);
							} else { // i.e., $payment->completed == 0
								$finePaymentResult->success = false;
								$finePaymentResult->message = translate(['text' => 'Your payment has not completed processing.', 'isPublicFacing'=> true]);
							}
						} else {
							$finePaymentResult->success = false;
							$finePaymentResult->message = translate(['text' => 'Your payment was processed, but did not match library records. Please contact the library with your receipt.', 'isPublicFacing'=> true]);
						}
						$interface->assign('finePaymentResult', $finePaymentResult);
					}
				}

				// FIS WorldPay data
				if($userLibrary->finePaymentType == 7) {
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

					if($worldPaySettings->find(true)){
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

				$interface->assign('finesToPay', $userLibrary->finesToPay);
				$interface->assign('userFines', $fines);

				$userAccountLabel = [];
				$fineTotalsVal = [];
				$outstandingTotalVal = [];
				// Get Account Labels, Add Up Totals
				foreach ($fines as $userId => $finesDetails) {
					$userAccountLabel[$userId] = $user->getUserReferredTo($userId)->getNameAndLibraryLabel();
					$total = $totalOutstanding = 0;
					foreach ($finesDetails as $fine) {
						$amount = $fine['amountVal'];
						if (is_numeric($amount)) $total += $amount;
						if ($useOutstanding && $fine['amountOutstandingVal']) {
							$outstanding = $fine['amountOutstandingVal'];
							if (is_numeric($outstanding)) $totalOutstanding += $outstanding;
						}
						if (!empty($fine['system'])){
							$showSystem = true;
						}
					}

					$fineTotalsVal[$userId] = $total;

					if ($useOutstanding) {
						$outstandingTotalVal[$userId] = $totalOutstanding;
					}
				}

				$interface->assign('userAccountLabel', $userAccountLabel);
				$interface->assign('fineTotalsVal', $fineTotalsVal);
				if ($useOutstanding) {
					$interface->assign('outstandingTotalVal', $outstandingTotalVal);
				}
			}
		}
		$interface->assign('showSystem', $showSystem);
		$this->display('fines.tpl', 'My Fines');
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'My Account');
		$breadcrumbs[] = new Breadcrumb('', 'My Fines');
		return $breadcrumbs;
	}
}
