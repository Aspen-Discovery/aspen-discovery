<?php

require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';
class Fines extends MyAccount
{
	private $currency_symbol = '$';

	function launch()
	{
		global $interface,
		       $configArray;

		$ils = $configArray['Catalog']['ils'];
		$interface->assign('showDate', $ils == 'Koha' || $ils == 'Horizon' || $ils == 'CarlX' || $ils == 'Symphony');
		$interface->assign('showReason', $ils != 'Koha');

		$interface->setFinesRelatedTemplateVariables();

		if (UserAccount::isLoggedIn()) {
			global $offlineMode;
			if (!$offlineMode) {
				// Get My Fines
				$user = UserAccount::getLoggedInUser();
				$fines = $user->getMyFines();
                $useOutstanding = $user->getCatalogDriver()->showOutstandingFines();
                $interface->assign('showOutstanding', $useOutstanding);

				$showFinePayments = $configArray['Catalog']['showFinePayments'];
				$interface->assign('showFinePayments', $showFinePayments);

				$interface->assign('userFines', $fines);

                $userAccountLabel = [];
                $fineTotalFormatted = [];
				$fineTotalVal = [];
                $outstandingTotal = [];
				// Get Account Labels, Add Up Totals
				foreach ($fines as $userId => $finesDetails) {
					$userAccountLabel[$userId] = $user->getUserReferredTo($userId)->getNameAndLibraryLabel();
					$total                     = $totalOutstanding = 0;
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
						$outstandingTotal[$userId] = $this->currency_symbol . number_format($totalOutstanding, 2);
					}
				}

				$interface->assign('userAccountLabel', $userAccountLabel);
				$interface->assign('fineTotalsFormatted', $fineTotalsFormatted);
				$interface->assign('fineTotalsVal', $fineTotalsVal);
				if ($useOutstanding) $interface->assign('outstandingTotal', $outstandingTotal);
			}
		}
		$this->display('fines.tpl', 'My Fines');
	}

}
