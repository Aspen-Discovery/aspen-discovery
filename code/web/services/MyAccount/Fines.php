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
		$interface->assign('showReason', $ils != 'Koha');

		$interface->setFinesRelatedTemplateVariables();

		if (UserAccount::isLoggedIn()) {
			global $offlineMode;
			if (!$offlineMode) {
				// Get My Fines
				$user = UserAccount::getLoggedInUser();
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
				$fineTotalsFormatted = [];
				$fineTotalsVal = [];
				$outstandingTotal = [];
				$outstandingTotalVal = [];
				// Get Account Labels, Add Up Totals
				foreach ($fines as $userId => $finesDetails) {
					$userAccountLabel[$userId] = $user->getUserReferredTo($userId)->getNameAndLibraryLabel();
					$total = $totalOutstanding = 0;
					foreach ($finesDetails as $fine) {
						if (!empty($fine['amount']) && $fine['amount'][0] == '-') {
							$amount = -ltrim($fine['amount'], '-' . $this->currency_symbol);
						} else {
							$amount = ltrim($fine['amount'], $this->currency_symbol);
						}
						if (is_numeric($amount)) $total += $amount;
						if ($useOutstanding && $fine['amountOutstanding']) {
							$outstanding = ltrim($fine['amountOutstanding'], $this->currency_symbol);
							if (is_numeric($outstanding)) $totalOutstanding += $outstanding;
						}
					}

					$fineTotalsVal[$userId] = $total;
					$fineTotalsFormatted[$userId] = $this->currency_symbol . number_format($total, 2);

					if ($useOutstanding) {
						$outstandingTotalVal[$userId] = $totalOutstanding;
						$outstandingTotal[$userId] = $this->currency_symbol . number_format($totalOutstanding, 2);
					}
				}

				$interface->assign('userAccountLabel', $userAccountLabel);
				$interface->assign('fineTotalsFormatted', $fineTotalsFormatted);
				$interface->assign('fineTotalsVal', $fineTotalsVal);
				if ($useOutstanding) {
					$interface->assign('outstandingTotal', $outstandingTotal);
					$interface->assign('outstandingTotalVal', $outstandingTotalVal);
				}
			}
		}
		$this->display('fines.tpl', 'My Fines');
	}

}
