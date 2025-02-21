<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Dashboard.php';
require_once ROOT_DIR . '/sys/Account/User.php';
require_once ROOT_DIR . '/Drivers/Koha.php';

class CommunityEngagement_Dashboard extends Admin_Dashboard {
	function launch() {
		global $interface;

		$campaignId = isset($_GET['campaign']) ? $_GET['campaign'] : null;
		$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : null;
		$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : null;

		$campaign = new Campaign();

		$campaigns = $campaign->getAllCampaigns();        
		$interface->assign('campaigns', $campaigns);
		$interface->assign('selectedCampaignId', $campaignId);
		$interface->assign('selectedDateFrom', $dateFrom);
		$interface->assign('selectedDateTo', $dateTo);

		if (isset($_GET['download_report']) && $_GET['download_report'] === 'true') {
			$_SESSION['downloadRequested'] = true;
			$reportData = $this->generatePatronEngagementReport($campaignId ?? null, $dateFrom ?? null, $dateTo ?? null);
			$this->outputCSV($reportData);
			unset($_GET['download_report']);
			unset($_SESSION['downloadRequested']);

		}
		$this->display('dashboard.tpl', 'Community Engagement Dashboard');
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

	function outputCSV($reportData) {
		if (empty($reportData)) {
			return;
		}
		header('Content-Type: text/csv');
		header('Content-Disposition: attachment; filename="patron_engagement_report.csv"');

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