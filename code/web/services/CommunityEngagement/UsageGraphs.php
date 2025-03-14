<?php
require_once ROOT_DIR . '/services/Admin/Admin.php';


class CommunityEngagement_UsageGraphs extends Admin_Admin {
    function launch() {
		global $interface;
		$title = "Community Engagement Usage Graph";
		$stat = $_REQUEST['stat'] ?? 'enrollments';
		$campaignId = $_REQUEST['campaignId'] ?? null;
	
		

		$interface->assign('graphTitle', $title);
		$interface->assign('section', 'Community Engagement');
		$interface->assign('showCSVExportButton', false);
		$this->assignGraphSpecificTitle($campaignId);
		$this->getAndSetInterfaceDataSeries($stat, $campaignId);

		$interface->assign('stat', $stat);
		$this->display('../Admin/usage-graph.tpl', $title);
	}

	private function getAndSetInterfaceDataSeries($stat, $campaignId) {
		global $interface;
		$dataSeries = [];
		$columnLabels = [];
		$userCampaign = new UserCampaign();

		if ($campaignId) {
			$userCampaign->whereAdd("campaignId = '$campaignId'");
		}

		$userCampaign->find();
		$userCampaignData = [];

		$dataSeries['Enrolled Users'] = [
			'borderColor' => 'rgba(54, 162, 235, 1)',  // Different color for this series
			'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
			'data' => [],
		];

		while ($userCampaign->fetch()) {
			$enrollmentDate = $userCampaign->enrollmentDate;
			$year = date('Y', strtotime($enrollmentDate));
			$month = date('m', strtotime($enrollmentDate));

			$curPeriod = "{$month}-{$year}";

			if (!in_array($curPeriod, $columnLabels)) {
				$columnLabels[] = $curPeriod;
			}

			if (!isset($dataSeries['Enrolled Users']['data'][$curPeriod])) {
				$dataSeries['Enrolled Users']['data'][$curPeriod] = 0;
			}
			$dataSeries['Enrolled Users']['data'][$curPeriod]++;
		}
		$interface->assign('dataSeries', $dataSeries);
		$interface->assign('columnLabels', $columnLabels);
	}

	
	
	

	private function assignGraphSpecificTitle($campaignId) {
		global $interface;
		if ($campaignId != null) {
			$campaign = new Campaign();
			$campaign->id = $campaignId;
			// $campaign->find();
			if ($campaign->find(true)) {
				$title = 'Community Engagement Usage for ' . $campaign->name;
			} else {
			 	$title = "Community Engagement Usage";
			}
		} else {
			$title = "Community Engagement Usage";
		}
		$interface->assign('graphTitle', $title);
	}

    function canView(): bool {
		return UserAccount::userHasPermission([
			'View Community Engagement Dashboard',
		]);
	}

	function getActiveAdminSection(): string {
		return 'communityEngagement';
	}

	
	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#communityEngagement', 'Community Engagement');
		$breadcrumbs[] = new Breadcrumb('/CommunityEngagement/Dashboard', 'Community Engagement Dashboard');
		$breadcrumbs[] = new Breadcrumb('', 'Usage Graph');
		return $breadcrumbs;
	}
}