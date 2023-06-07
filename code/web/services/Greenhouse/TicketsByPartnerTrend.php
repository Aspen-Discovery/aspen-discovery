<?php
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/Support/TicketSeverityFeed.php';
require_once ROOT_DIR . '/sys/Support/TicketTrendByPartner.php';
require_once ROOT_DIR . '/sys/Greenhouse/AspenSite.php';

class TicketsByPartnerTrend extends Admin_Admin {
	function launch() {
		global $interface;
		$title = 'Trend of Open Tickets by Partner';

		$dataSeries = [];
		$columnLabels = [];
		require_once ROOT_DIR . '/sys/Utils/GraphingUtils.php';

		$aspenSite = new AspenSite();
		$aspenSite->siteType = "0";
		$aspenSite->whereAdd('implementationStatus <> 0 AND implementationStatus <> 4');
		$aspenSite->orderBy('name ASC');
		$partnerNames = [];
		$aspenSite->find();
		while ($aspenSite->fetch()) {
			$partnerNames[$aspenSite->id] = $aspenSite->name;
 			$dataSeries[$aspenSite->name] = GraphingUtils::getDataSeriesArray(count($dataSeries));
		}
		$dataSeries['Not Set'] = GraphingUtils::getDataSeriesArray(count($dataSeries));

		$ticketTrend = new TicketTrendByPartner();
		$ticketTrend->orderBy('year, month, day');
		$ticketTrend->find();
		while ($ticketTrend->fetch()) {
			$curPeriod = "{$ticketTrend->month}-{$ticketTrend->day}-{$ticketTrend->year}";
			if (!in_array($curPeriod, $columnLabels)) {
				$columnLabels[] = $curPeriod;
			}

			if ($ticketTrend->requestingPartner == null) {
				$partnerName = 'Not Set';
			} else {
				if (isset($partnerNames[$ticketTrend->requestingPartner])) {
					$partnerName = $partnerNames[$ticketTrend->requestingPartner];
				}else{
					$partnerName ="Unknown $ticketTrend->requestingPartner";
					if (!array_key_exists($partnerName, $dataSeries)) {
						$dataSeries[$partnerName] = GraphingUtils::getDataSeriesArray(count($dataSeries));
					}
				}
			}

			if (!array_key_exists($partnerName, $dataSeries)) {
				//echo("Queue not set properly for ticket '" . $tickets->queue . "'");
			} else {
				//Populate all periods with 0's
				if (!array_key_exists($curPeriod, $dataSeries[$partnerName]['data'])) {
					foreach ($dataSeries as $queue => $data) {
						$dataSeries[$queue]['data'][$curPeriod] = 0;
					}
				}
				$dataSeries[$partnerName]['data'][$curPeriod] = $ticketTrend->count;
			}
		}

		$interface->assign('columnLabels', $columnLabels);
		$interface->assign('dataSeries', $dataSeries);
		$interface->assign('translateDataSeries', true);
		$interface->assign('translateColumnLabels', false);

		$interface->assign('graphTitle', $title);

		$this->display('../Admin/usage-graph.tpl', $title);
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/Home', 'Greenhouse Home');
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/BugsBySeverityTrend', 'Bugs by Severity Trend');
		return $breadcrumbs;
	}

	function canView() {
		if (UserAccount::isLoggedIn()) {
			if (UserAccount::getActiveUserObj()->source == 'admin' && UserAccount::getActiveUserObj()->cat_username == 'aspen_admin') {
				return true;
			}
		}
		return false;
	}

	function getActiveAdminSection(): string {
		return 'greenhouse';
	}

	public function display($mainContentTemplate, $pageTitle, $sidebarTemplate = 'Development/development-sidebar.tpl', $translateTitle = true) {
		parent::display($mainContentTemplate, $pageTitle, $sidebarTemplate, $translateTitle);
	}
}