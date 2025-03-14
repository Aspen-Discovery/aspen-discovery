<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Dashboard.php';
require_once ROOT_DIR . '/sys/Account/User.php';
require_once ROOT_DIR . '/Drivers/Koha.php';
require_once ROOT_DIR . '/sys/CommunityEngagement/Campaign.php';
require_once ROOT_DIR . '/sys/CommunityEngagement/UserCampaign.php';


class CommunityEngagement_Dashboard extends Admin_Dashboard {
	function launch() {
		global $interface;
		$this->loadDates();

		$campaignId = isset($_GET['campaign']) ? $_GET['campaign'] : null;
		$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : null;
		$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : null;

		$campaign = new Campaign();

		$campaigns = $campaign->getAllCampaigns();        
		$interface->assign('campaigns', $campaigns);
		$interface->assign('selectedCampaignId', $campaignId);
		$interface->assign('selectedDateFrom', $dateFrom);
		$interface->assign('selectedDateTo', $dateTo);

		$enrolledUsersThisMonth = $this->getUserStats($this->thisMonth, $this->thisYear);
		$interface->assign('enrolledUsersThisMonth', $enrolledUsersThisMonth);
		$enrolledUsersLastMonth = $this->getUserStats($this->lastMonth, $this->lastMonthYear);
		$interface->assign('enrolledUsersLastMonth', $enrolledUsersLastMonth);
		$enrolledUsersThisYear = $this->getUserStats(null, $this->thisYear);
		$interface->assign('enrolledUsersThisYear', $enrolledUsersThisYear);
		$enrolledUsersLastYear = $this->getUserStats(null, $this->lastYear);
		$interface->assign('enrolledUsersLastYear', $enrolledUsersLastYear);
		$enrolledUsersAllTime = $this->getUserStats(null, null);
		$interface->assign('enrolledUsersAllTime', $enrolledUsersAllTime);

		
		$campaignStatsThisMonth = $this->getCampaignStats($this->thisMonth, $this->thisYear);
		$interface->assign('campaignStatsThisMonth', $campaignStatsThisMonth);
		$campaignStatsLastMonth = $this->getCampaignStats($this->lastMonth, $this->lastMonthYear);
		$interface->assign('campaignStatsLastMonth', $campaignStatsLastMonth);
		$campaignStatsThisYear = $this->getCampaignStats(null, $this->thisYear);
		$interface->assign('campaignStatsThisYear', $campaignStatsThisYear);
		$campaignStatsLastYear = $this->getCampaignStats(null, $this->lastYear);
		$interface->assign('campaignStatsLastYear', $campaignStatsLastYear);
		$campaignStatsAllTime = $this->getCampaignStats(null, null);
		$interface->assign('campaignStatsAllTime', $campaignStatsAllTime);


		if (isset($_GET['download_report']) && $_GET['download_report'] === 'true') {
			$_SESSION['downloadRequested'] = true;
			$reportData = $this->generatePatronEngagementReport($campaignId ?? null, $dateFrom ?? null, $dateTo ?? null);
			$this->outputCSV($reportData, $campaignId);
			unset($_GET['download_report']);
			unset($_SESSION['downloadRequested']);

		}
		$this->display('dashboard.tpl', 'Community Engagement Dashboard');
	}

	/**
	 * @param string|null $month
	 * @param string|null $year
	 * @return int
	 */
	public function getUserStats($month, $year): int {
		$userCampaign = new UserCampaign();
		if ($month) {
			$userCampaign->whereAdd('MONTH(enrollmentDate) = ' . (int)$month);
		}
		if ($year) {
			$userCampaign->whereAdd('YEAR(enrollmentDate) = ' . (int)$year);
		}

		$userCampaign->find();

		$userIds = [];

		while ($userCampaign->fetch()) {
			$userIds[] = $userCampaign->userId;
		}

		$uniqueUserIds = array_unique($userIds);
		return count($uniqueUserIds);
	}

	/**
	 * @param string|null $month
	 * @param string|null $year
	 * @return int
	 */
	public function getCampaignStats($month, $year): array {
		global $interface;
		$campaignStats = [];
		
		$campaign = new Campaign();
		$campaigns = $campaign->getCampaigns();
		foreach ($campaigns as $campaign) {
			$campaignStats[$campaign->id] = [
				'id' => $campaign->id,
				'campaignName' => $campaign->name,
				'enrolledUsers' => 0
			];
			$userCampaign = new UserCampaign();
			$userCampaign->whereAdd("campaignId = " . (int)$campaign->id);

			if ($month) {
				$userCampaign->whereAdd("MONTH(enrollmentDate) = " . (int)$month);
			}
			if ($year) {
				$userCampaign->whereAdd("YEAR(enrollmentDate) = " . (int)$year);
			}

			$userCampaign->find();

			$userIds = 0;
			while ($userCampaign->fetch()) {
				$userIds++;
			}

			$campaignStats[$campaign->id]['enrolledUsers'] = $userIds;
		}
		$interface->assign('campaignStats', $campaignStats);
		return $campaignStats;
	}

	function generatePatronEngagementReport($campaignId, $dateFrom, $dateTo) {
		$userDetails = [];

		$userCampaign = new UserCampaign();

		if ($campaignId) {
			$userCampaign->whereAdd("campaignId = '$campaignId'");
		}
		if ($dateFrom) {
			$userCampaign->whereAdd("enrollmentDate >= '$dateFrom'");
		}
		if ($dateTo) {
			$userCampaign->whereAdd("enrollmentDate <= '$dateTo'");
		}
		
		$userCampaign->find();
		$userCampaignData = [];

		while($userCampaign->fetch()) {
			$userId = $userCampaign->userId;

			if(!isset($userCampaignData[$userId])) {
				$userCampaignData[$userId] = [
					'userId' => $userId,
					'campaigns' => [],
				];

				$user = new User();
				$user->id = $userId;
				if ($user->find(true)) {
					$user->loadContactInformation();
					$userCampaignData[$userId]['dateOfBirth'] = $user->dateOfBirth ?? 'N/A';
					$userCampaignData[$userId]['homeBranch'] = $user->getHomeLibrary() ->displayName ?? 'N/A';
					$userCampaignData[$userId]['zip'] = $user->_zip ?? 'N/A';
				}
			}

			$campaign = new Campaign();
			$campaign->id = $userCampaign->campaignId;
			if ($campaign->find(true)) {
				$userCampaignData[$userId]['campaigns'][] = [
					'campaignName' => $campaign->name,
					'isCompleted' => ($userCampaign->completed === 'completed'),
				];
			}
		}
		if (empty($userCampaignData)) {
			return [
				[
					'userId' => 'N/A',
					'dateOfBirth' => 'N/A',
					'homeBranch' => 'N/A',
					'zip' => 'N/A',
					'campaigns' => ['No users are enrolled in this campaign']
				]
			];
		}

		foreach ($userCampaignData as $userId => $userData) {
			if(!empty($userData['campaigns'])) {
				$userDetails[] = [
					'userId' => $userData['userId'],
					'dateOfBirth' => $userData['dateOfBirth'],
					'zip' =>$userData['zip'],
					'homeBranch' => $userData['homeBranch'],
					'campaigns' => $userData['campaigns'],
				];
			}
		}
		return $userDetails;
	}

	function noData() {
		echo '
		<div class="modal" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title">Campaign Notice</h5>
					</div>
					<div class="modal-body">
						<p>No users are enrolled in this campaign withing this enrollment period.</p>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" onclick="this.closest(\'.modal\').style.display=\'none\'">Close</button>
					</div>
				</div>
			</div>
		</div>
		';
	}

	function outputCSV($reportData, $campaignId = null) {
		if (empty($reportData) || (count($reportData) === 1 && isset($reportData[0]['campaigns']) && $reportData[0]['campaigns'] === ['No users are enrolled in this campaign'])) {
			$this->noData();
			return;
		}

		$filename = $this->getCampaignFilename($campaignId);

		header('Content-Type: text/csv');
		header('Content-Disposition: attachment; filename="' . $filename . '"');

		$output = fopen('php://output', 'w');
		fputcsv($output, ['Date of Birth', 'Home Branch', 'ZIP Code', 'Enrolled Campaigns']);
		foreach ($reportData as $row) {
			if (isset($row['campaigns']) && $row['campaigns'] === ['No users are enrolled in this campaign']) {
				$csvRow = [
					'N/A',
					'N/A',
					'N/A',
					'No users are enrolled in this campaign',
				];
			} else {
				$campaignInfo = [];
	
				foreach ($row['campaigns'] as $campaign) {
					$status = isset($campaign['isCompleted']) && $campaign['isCompleted'] ? 'Complete' : 'Incomplete';
					$campaignInfo[] = "{$campaign['campaignName']} ({$status})";
				}
	
				$campaignText = empty($campaignInfo) ? 'N/A' : implode(', ', $campaignInfo);

				$csvRow = [
					$row['dateOfBirth'] ?? 'N/A',
					$row['homeBranch'] ?? 'N/A',
					$row['zip'] ?? 'N/A',
					$campaignText
				];
			}
			fputcsv($output, $csvRow);
		}
		fclose($output);
		exit;
	}

	private function getCampaignFilename($campaignId): string {
		if ($campaignId) {
			$campaign = new Campaign();
			$campaign->id = $campaignId;
			if ($campaign->find(true)) {
				$campaignName = preg_replace('/[^A-Za-z0-9_-]/', '_', $campaign->name);
				return "patron_engagement_report_{$campaignName}.csv";
			}
		}
		return "patron_engagement_report_all_campaigns.csv";
	}

	function canView(): bool {
		return UserAccount::userHasPermission([
			'View Community Engagement Dashboard',
		]);
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#communityEngagement', 'Community Engagement');
		$breadcrumbs[] = new Breadcrumb('/CommunityEngagement/Dashboard', 'Community Engagement Dashboard');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'communityEngagement';
	}
}