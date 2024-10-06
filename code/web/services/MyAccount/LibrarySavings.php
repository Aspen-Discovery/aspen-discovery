<?php

require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';
require_once ROOT_DIR . '/sys/Pager.php';

class LibrarySavings extends MyAccount {
	function launch() {
		global $interface;
		global $library;

		global $activeLanguage;
		$patron = UserAccount::getActiveUserObj();

		if (isset($_REQUEST['disableLibrarySavings'])){
			$patron->enableCostSavings = 0;
			$patron->update();
		}elseif (isset($_REQUEST['enableLibrarySavings'])){
			$patron->enableCostSavings = 1;
			$patron->update();
		}

		if ($patron->enableCostSavings) {
			$costSavingsExplanation = $library->getTextBlockTranslation('costSavingsExplanationEnabled', $activeLanguage->code);
			//Use a side effect of getting all checkouts to load current costs savings
			$patron->getCheckouts(false, 'all');

			require_once ROOT_DIR . '/sys/Utils/StringUtils.php';
			$interface->assign('currentCostSavings', StringUtils::formatCurrency($patron->currentCostSavings));
			$interface->assign('totalCostSavings', StringUtils::formatCurrency($patron->totalCostSavings));

			if ($patron->trackReadingHistory) {

				//We can show graphs of savings.
				//The first graph is savings per month over the course of a year (with the ability to choose which of the last 5 years to view).
				//The second graph is savings per year over the last 5 years.
				//Determine the earliest year we have data for.
				$readingHistoryStartTimestamp = $patron->getReadingHistoryStartDate();
				if ($readingHistoryStartTimestamp == null) {
					//No reading history has been recorded
					$interface->assign('showGraphs', false);
				}else{
					$interface->assign('showGraphs', true);
					//Get the year for the start date and find variables for displaying the year selection.
					$readingHistoryStartDate = date('Y', $readingHistoryStartTimestamp);
					$currentYear = date('Y', time());
					if ($readingHistoryStartDate < $currentYear - 5) {
						$readingHistoryStartDate = $currentYear - 5;
					}
					$yearsToShow = [];
					for ($i = $readingHistoryStartDate; $i <= $currentYear; $i++) {
						$yearsToShow[$i] = $i;
					}
					$interface->assign('yearsToShow', $yearsToShow);
					if (isset($_REQUEST['yearToShow']) && is_numeric($_REQUEST['yearToShow'])){
						$yearToShow = $_REQUEST['yearToShow'];
					}else{
						$yearToShow = $currentYear;
					}
					$interface->assign('yearToShow', $yearToShow);

					require_once ROOT_DIR . '/sys/SystemVariables.php';
					$currencyCode = 'USD';
					$variables = new SystemVariables();
					if ($variables->find(true)) {
						$currencyCode = $variables->currencyCode;
					}

					//Get data for each of the graphs
					$monthlySavingsColumnLabels = [
						'1' => translate(['text' => 'January', 'isPublicFacing' => true]),
						'2' => translate(['text' => 'February', 'isPublicFacing' => true]),
						'3' => translate(['text' => 'March', 'isPublicFacing' => true]),
						'4' => translate(['text' => 'April', 'isPublicFacing' => true]),
						'5' => translate(['text' => 'May', 'isPublicFacing' => true]),
						'6' => translate(['text' => 'June', 'isPublicFacing' => true]),
						'7' => translate(['text' => "July", 'isPublicFacing' => true]),
						'8' => translate(['text' => "August", 'isPublicFacing' => true]),
						'9' => translate(['text' => "September", 'isPublicFacing' => true]),
						'10' => translate(['text' => "October", 'isPublicFacing' => true]),
						'11' => translate(['text' => "November", 'isPublicFacing' => true]),
						'12' => translate(['text' => "December", 'isPublicFacing' => true])
					];
					$interface->assign('monthlySavingsColumnLabels', $monthlySavingsColumnLabels);
					$monthlyDataSeries = [
						"Cost Savings ($currencyCode)" => [
							'borderColor' => 'rgba(255, 99, 132, 1)',
							'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
							'data' => [],
						]
					];
					foreach ($monthlySavingsColumnLabels as $month => $displayName) {
						$monthlyDataSeries["Cost Savings ($currencyCode)"]['data'][$month] = $patron->getCostSavingsByMonth($month, $yearToShow);
					}
					$interface->assign('monthlyDataSeries', $monthlyDataSeries);

					$interface->assign('yearlySavingsColumnLabels', $yearsToShow);
					$yearlyDataSeries = [
						"Cost Savings ($currencyCode)" => [
							'borderColor' => 'rgba(255, 99, 132, 1)',
							'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
							'data' => [],
						]
					];
					foreach ($yearsToShow as $year) {
						$yearlyDataSeries["Cost Savings ($currencyCode)"]['data'][$year] = $patron->getCostSavingsByYear($year);
					}
					$interface->assign('yearlyDataSeries', $yearlyDataSeries);
				}
			}else{
				$interface->assign('showGraphs', false);
			}
		}else{
			$costSavingsExplanation = $library->getTextBlockTranslation('costSavingsExplanationDisabled', $activeLanguage->code);
		}

		$interface->assign('costSavingsExplanation', $costSavingsExplanation);


		$this->display('librarySavings.tpl', 'Library Savings');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'Your Account');
		$breadcrumbs[] = new Breadcrumb('', 'Your Library Savings');
		return $breadcrumbs;
	}
}