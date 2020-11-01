<?php

require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';

class MyAccount_Fines extends MyAccount
{
	private $currency_symbol = '$';

	function launch()
	{
		global $interface;
		global $configArray;

		$ils = $configArray['Catalog']['ils'];
		$interface->assign('showDate', $ils == 'Koha' || $ils == 'Horizon' || $ils == 'CarlX' || $ils == 'Symphony');
		$interface->assign('showReason', true);

		$interface->setFinesRelatedTemplateVariables();

		if (UserAccount::isLoggedIn()) {
			global $offlineMode;
			if (!$offlineMode) {
				$currencyCode = 'USD';
				$variables = new SystemVariables();
				if ($variables->find(true)){
					$currencyCode = $variables->currencyCode;
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
					$clientId = $userLibrary->payPalClientId;
					$interface->assign('payPalClientId', $clientId);
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
		$this->display('fines.tpl', 'My Fines');
	}

	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'My Account');
		$breadcrumbs[] = new Breadcrumb('', 'My Fines');
		return $breadcrumbs;
	}
}
