<?php
require_once ROOT_DIR . '/JSON_Action.php';
require_once ROOT_DIR . '/sys/CommunityEngagement/Campaign.php';
require_once ROOT_DIR . '/sys/CommunityEngagement/UserCampaign.php';
require_once ROOT_DIR . '/sys/CommunityEngagement/CampaignMilestoneUsersProgress.php';
require_once ROOT_DIR . '/sys/UserAccount.php';


class CommunityEngagement_AJAX extends JSON_Action {
	function launch($method = null) : void {
		global $enabledModules;
		if (!in_array('Community Engagement', $enabledModules)) {
			$this->outputEncodedResult(['error' => 'Community Engagement not enabled']);
			return;
		}
		parent::launch($method);
	}
	function campaignRewardGivenUpdate() {
		if (!UserAccount::userHasPermission(['View Community Engagement Dashboard'])){
			return ['error' => "You don't have permission to access this page"];
		}
		if (empty($_GET['userId']) || empty($_GET['campaignId'])) {
			return ['error' => "User ID and Campaign ID are required"];
		}
		$userId = $_GET['userId'];
		$campaignId = $_GET['campaignId'];
		$userCampaign = new UserCampaign();
		$userCampaign->userId = $userId;
		$userCampaign->campaignId = $campaignId;

		if ($userCampaign->find(true)) {
			$userCampaign->rewardGiven = 1;
			if ($userCampaign->update()) {
				echo json_encode(['success' => true]);
			} else {
				echo json_encode(['success' => false, 'message' => 'Failed to update reward status.']);
			}
		} else {
			echo json_encode(['success' => false, 'message' => 'User campaign record not found.']);
		}
		exit;
	}

	function milestoneRewardGivenUpdate() {
		if (!UserAccount::userHasPermission(['View Community Engagement Dashboard'])){
			return ['error' => "You don't have permission to access this page"];
		}
		if (empty($_GET['userId']) || empty($_GET['campaignId']) || empty($_GET['milestoneId'])) {
			return ['error' => "User ID, Campaign ID, and Milestone ID are required"];
		}
		ob_start();

		try {
			$userId = $_GET['userId'];
			$milestoneId = $_GET['milestoneId'];
			$campaignId = $_GET['campaignId'];

			$campaignMilestoneProgress = new CampaignMilestoneUsersProgress();
			$campaignMilestoneProgress->userId = $userId;
			$campaignMilestoneProgress->ce_milestone_id = $milestoneId;
			$campaignMilestoneProgress->ce_campaign_id = $campaignId;

			if ($campaignMilestoneProgress->find(true)) {
				$campaignMilestoneProgress->rewardGiven = 1;

				if ($campaignMilestoneProgress->update()) {
					ob_end_clean();
					echo json_encode(['success' => true]);
				} else {
					throw new Exception('Failed to update reward status');
				}
			} else {
				throw new Exception('Milestone progress record not found.');
			}

		} catch(Exception $e) {
			ob_end_clean();
			echo json_encode(['success' => false, 'message' => $e->getMessage()]);
		}
		exit;
	}

	function filterCampaigns() {
		if (!UserAccount::userHasPermission(['View Community Engagement Dashboard'])){
			return ['error' => "You don't have permission to access this page"];
		}

		global $library;
		global $interface;

		$campaignId = isset($_REQUEST['campaignId']) ? intval($_REQUEST['campaignId']) : 0;
		$userId = isset($_REQUEST['userId']) ? intval($_REQUEST['userId']) : 0;
		$filterType = isset($_REQUEST['filterType']) ? $_REQUEST['filterType'] : '';
	
		$response = [];
		if ($filterType === 'campaign') {
			if ($campaignId > 0) {
	
				$campaign = Campaign::getCampaignById($campaignId);
				if ($campaign) {
					$campaign->completedUsersCount = $campaign->getCompletedUsersCount();
					$html = '<div class="dashboardCategory row" style="border: 1px solid #3174AF; padding: 0 10px 10px 10px; margin-bottom: 10px;">';
					$html .= '<div class="col-sm-12">';
					$html .= '<h5 style="font-weight:bold;">';
					$html .= '<a href="/CommunityEngagement/CampaignTable?id=' . htmlspecialchars($campaignId) . '">';
					$html .= htmlspecialchars($campaign->name);
					$html .= '</a>';
					$html .= '</h5>';
					$html .= '<div style="border-bottom: 2px solid #3174AF; padding: 10px; margin-bottom: 10px;">';
					$html .= '<div class="dashboardLabel">Number of Patrons Enrolled:</div>';
					$html .= '<div class="dashboardValue">' . htmlspecialchars($campaign->currentEnrollments) . '</div>';
					$html .= '<div class="dashboardLabel">Total Number of Enrollments:</div>';
					$html .= '<div class="dashboardValue">' . htmlspecialchars($campaign->enrollmentCounter) . '</div>';
					$html .= '<div class="dashboardLabel">Total Number of Unenrollments:</div>';
					$html .= '<div class="dashboardValue">' . htmlspecialchars($campaign->unenrollmentCounter) . '</div>';
					$html .= '<div class="dashboardLabel">Number of Users Who Have Completed the Campaign:</div>';
					$html .= '<div class="dashboardValue">' . htmlspecialchars($campaign->completedUsersCount) . '</div>';
					$html .= '</div>';
					$html .= '</div>';
					$html .= '</div>';
	
					$response['html'] = $html;
					$response['success'] = true;
				} else {
					$response['message'] = 'Campaign not found';
				}
			} else {
				// Get all campaigns if no specific campaign is selected
				$allCampaigns = Campaign::getAllCampaigns();
				if (!empty($allCampaigns)) {
					$html = '';
					foreach ($allCampaigns as $campaign) {
						$campaign->completedUsersCount = $campaign->getCompletedUsersCount();
						$html .= '<div class="dashboardCategory row" style="border: 1px solid #3174AF; padding: 0 10px 10px 10px; margin-bottom: 10px;">';
						$html .= '<div class="col-sm-12">';
						$html .= '<h5 style="font-weight:bold;">';
						$html .= '<a href="/CommunityEngagement/CampaignTable?id=' . htmlspecialchars($campaign->id) . '">';
						$html .= htmlspecialchars($campaign->name);
						$html .= '</a>';
						$html .= '</h5>';
						$html .= '<div style="border-bottom: 2px solid #3174AF; padding: 10px; margin-bottom: 10px;">';
						$html .= '<div class="dashboardLabel">Number of Patrons Enrolled:</div>';
						$html .= '<div class="dashboardValue">' . htmlspecialchars($campaign->currentEnrollments) . '</div>';
						$html .= '<div class="dashboardLabel">Total Number of Enrollments:</div>';
						$html .= '<div class="dashboardValue">' . htmlspecialchars($campaign->enrollmentCounter) . '</div>';
						$html .= '<div class="dashboardLabel">Total Number of Unenrollments:</div>';
						$html .= '<div class="dashboardValue">' . htmlspecialchars($campaign->unenrollmentCounter) . '</div>';
						$html .= '<div class="dashboardLabel">Number of Users Who Have Completed the Campaign:</div>';
						$html .= '<div class="dashboardValue">' . htmlspecialchars($campaign->completedUsersCount) . '</div>';
						$html .= '</div>';
						$html .= '</div>';
						$html .= '</div>';
					}
					$response['html'] = $html;
					$response['success'] = true;
				} else {
					$response['message'] = 'No campaigns found';
				}
			}
		} elseif ($filterType === 'user') {
			if ($userId > 0) {
				$campaign = new Campaign();
				$allEligibleCampaigns = $campaign->getCampaigns($userId, true);
				$user = new User();
				$user->id = $userId;
				if ($user->find(true)) {
					$userEmailOptInSetting = $user->campaignNotificationsByEmail;
				} else {
					$userEmailOptInSetting = 0;
				}
				if (!empty($allEligibleCampaigns)) {
					$campaignDisplayData = [];
					foreach ($allEligibleCampaigns as $campaign) {
						$isRemoved = $this->userRemovedCampaignCheck($campaign->id, $userId);

						$milestoneData = [];

						if (!empty($campaign->milestones)) {
							foreach ($campaign->milestones as $milestone) {
								$completed = (int)($milestone->completedGoals ?? 0);
								$total = (int)($milestone->totalGoals ?? 0);
								if (!$milestone->progressBeyondOneHundredPercent && $completed > $total) {
									$completed = $total;
								}

								$percentage = $total > 0 ? round(($completed / $total) * 100) : 0;
								$isComplete = $milestone->milestoneComplete == 1;
								$rewardGiven = $milestone->rewardGiven == 1;

								$milestoneData[] = [
									'id' => $milestone->id,
									'name' => $milestone->name,
									'completed' => $completed,
									'total' => $total,
									'percentage' => $percentage,
									'isComplete' => $isComplete,
									'rewardGiven' => $rewardGiven,
									'milestoneType' => $milestone->milestoneType,
									'progressBeyondLimit' => $milestone->progressBeyondOneHundredPercent,
								];
							}
						}

						$extraCreditActivities = CampaignExtraCredit::getExtraCreditByCampaign($campaign->id, $userId);

						$campaignDisplayData[] = [
							'campaign' => $campaign,
							'milestones' => $milestoneData,
							'extraCredit' => $extraCreditActivities,
							'isRemoved' => $isRemoved,
						];
					}

					$interface->assign('campaigns', $campaignDisplayData);
					$interface->assign('userId', $userId);
					$interface->assign('userEmailOptInSetting', $userEmailOptInSetting);

					$response['html'] = $interface->fetch('CommunityEngagement/adminUserCampaigns.tpl');
					$response['success'] = true;
				} else {
					$response['message'] = 'No campaigns found for this user.';
				}
			} else {
				$response['html'] = '<div class="alert alert-info" style="margin: 10px 0;">Please select a user.</div>';
				$response['success'] = true;
			}
		} else {
			$response['message'] = 'Invalid filter type.';
		}
	
		header('Content-Type: application/json');
		echo json_encode($response);
		exit;
	}

	private function userRemovedCampaignCheck($campaignId, $userId) {
		require_once ROOT_DIR . '/sys/CommunityEngagement/UserRemovedCampaign.php';

		$removed = new UserRemovedCampaign();
		$removed->campaignId = $campaignId;
		$removed->userId = $userId;

		return $removed->find(true);
	}

	public function restoreCampaignForUser() {

		if (!UserAccount::userHasPermission('View Community Engagement Admin View')) {
			return [
				'success' => false,
				'title' => translate([
					'text' => 'Error',
					'isPublicFacing' => true,
				]),
				'message' => translate([
					'text' => 'You do not have the correct permissions to carry out this action',
					'isPublicFacing' => true,
				]),
			];
		}

		$userId = $_REQUEST['userId'] ?? null;
		$campaignId = $_REQUEST['campaignId'] ?? null;

		if (!$userId) {
			return [
				'success' => false,
				'title' => translate([
					'text' => 'Error',
					'isPublicFacing' => true,
				]),
				'message' => translate([
					'text' => 'No User ID ',
					'isPublicFacing' => true,
				]),
			];
		}

		if (!$campaignId) {
			return [
				'success' => false,
				'title' => translate([
					'text' => 'Error',
					'isPublicFacing' => true,
				]),
				'message' => translate([
					'text' => 'No Campaign ID ',
					'isPublicFacing' => true,
				]),
			];
		}
		require_once ROOT_DIR . '/sys/CommunityEngagement/UserRemovedCampaign.php';

		$removed = new UserRemovedCampaign();
		$removed->campaignId = $campaignId;
		$removed->userId = $userId;
		if ($removed->find(true)) {
			$removed->delete();
			return [
				'success' => true,
				'title' => translate([
					'text' => 'Campaign Restored',
					'isPublicFacing' => true,
				]),
				'message' => translate([
					'text' => 'The campaign has been restored for this user.',
					'isPublicFacing' => true,
				]),
			];
		} else {
			return [
				'success' => false,
				'title' => translate([
					'text' => 'Error',
					'isPublicFacing' => true,
				]),
				'message' => translate([
					'text' => 'Unable to restore the campaign for this user.',
					'isPublicFacing' => true,
				]),
			];
		}
	}

	public function filterLeaderboardCampaigns() {
		if (!UserAccount::userHasPermission(['View Community Engagement Dashboard'])){
			return ['error' => "You don't have permission to access this page"];
		}
		require_once ROOT_DIR . '/sys/CommunityEngagement/Campaign.php';
		$campaignId = $_GET['campaignId'] ?? null;
		$response = [];
		$campaign = new Campaign();
		$html = '';
		try {
			if ($campaignId) { 
				$campaign->id = $campaignId;
				if ($campaign->find(true)) {
					$campaignName = $campaign->name;
				}
				$leaderboard = $campaign->getLeaderboardByCampaign($campaignId);
				if ($leaderboard) {
					$html .='<table class="leaderboard-table" style="width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 16px;"><thead><tr><th>User</th><th>Rank</th><th>Completed Milestones</th></tr></thead><tbody>';
					foreach ($leaderboard as $entry) {
						$html .= "<tr><td>{$entry['user']}</td><td>{$entry['rankDisplayed']}</td><td>{$entry['completedMilestones']}</td></tr>";
					}
					$html .= '</tbody></table>';
					$response['html'] = $html;
					$response['campaignName'] = $campaignName;
					$response['success'] = true;
				} else {
					$response['success'] = false;
					$response['campaignName'] = $campaignName;
					$response['html'] = 'There are currently no users to display.';
				}
			
			} else {
				$leaderboard = $campaign->getOverallLeaderboard();
				if ($leaderboard) {
					$html .='<table class="leaderboard-table" style="width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 16px;"><thead><tr><th>User</th><th>Rank</th><th>Completed Milestones</th></tr></thead><tbody>';
					foreach ($leaderboard as $entry) {
						$html .= "<tr><td>{$entry['user']}</td><td>{$entry['rankDisplayed']}</td><td>{$entry['completedMilestones']}</td></tr>";
					}
					$html .= '</tbody></table>';
					$response['html'] = $html;
					$response['campaignName'] = 'All Campaigns';
					$response['success'] = true;
				} else {
					$response['success'] = false;
					$response['campaignName'] = 'All Campaigns';
					$response['html'] = 'There are currently no users to display.';
				}
			
			}
			header('Content-Type: application/json');
			echo json_encode($response);
			exit;
		} catch (Exception $e) {
			error_log('Error: ' . $e->getMessage());
			echo json_encode([
				'success' => false,
				'message' => 'Error retrieving campaign information'
			]);
		}
	}

	public function filterBranchLeaderboardCampaigns() {
		if (!UserAccount::userHasPermission(['View Community Engagement Dashboard'])){
			return ['error' => "You don't have permission to access this page"];
		}

		require_once ROOT_DIR . '/sys/CommunityEngagement/Campaign.php';
		$campaignId = $_GET['campaignId'] ?? null;
		$response = [];
		$campaign = new Campaign();
		$html = '';

		try {
			if ($campaignId) {
				$campaign->id = $campaignId;
				if ($campaign->find(true)) {
					$campaignName = $campaign->name;
				}
				$branchLeaderboard = $campaign->getLeaderboardByBranchForCampaign($campaign);
				if ($branchLeaderboard) {
					$html .='<table class="leaderboard-table" style="width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 16px;"><thead><tr><th>Branch</th><th>Rank</th><th>Completed Milestones</th></tr></thead><tbody>';
					foreach ($branchLeaderboard as $entry) {
						$html .= "<tr><td>{$entry['branch']}</td><td>{$entry['rankDisplayed']}</td><td>{$entry['completedMilestones']}</td></tr>";
					}
					$html .= '</tbody></table>';
					$response['html'] = $html;
					$response['campaignName'] = $campaignName;
					$response['success'] = true;
				} else {
					$response['success'] = false;
					$response['campaignName'] = $campaignName;
					$response['html'] = 'There are currently no users enrolled in this campaign.';
				}
			} else {
				$branchLeaderboard = $campaign->getOverallLeaderboardByBranch();
				if ($branchLeaderboard){
					$html .='<table class="leaderboard-table" style="width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 16px;"><thead><tr><th>Branch</th><th>Rank</th><th>Completed Milestones</th></tr></thead><tbody>';
					foreach ($branchLeaderboard as $entry) {
						$html .= "<tr><td>{$entry['branch']}</td><td>{$entry['rankDisplayed']}</td><td>{$entry['completedMilestones']}</td></tr>";
					}
					$html .= '</tbody></table>';
					$response['html'] = $html;
					$response['campaignName'] = 'All Campaigns';
					$response['success'] = true;
				} else {
					$response['success'] = false;
					$response['message'] = 'No leaderboard data found.';
				}
			}
			
		header('Content-Type: application/json');
		echo json_encode($response);
		exit;
		} catch (Exception $e) {
			error_log('Error: ' . $e->getMessage());
			echo json_encode([
				'success' => false,
				'message' => 'Error retrieving campaign information'
			]);
		}
	}

	public function manuallyProgressUserMilestone($milestoneId = null, $userId = null, $campaignId = null) {
		require_once ROOT_DIR . '/sys/CommunityEngagement/Campaign.php';
		require_once ROOT_DIR . '/sys/CommunityEngagement/Milestone.php';
		require_once ROOT_DIR . '/sys/CommunityEngagement/UserCampaign.php';
		require_once ROOT_DIR . '/sys/CommunityEngagement/CampaignMilestone.php';

		$milestoneId = $milestoneId ?? $_GET['milestoneId'] ?? null;
		$userId = $userId ?? $_GET['userId'] ?? null;
		$campaignId = $campaignId ?? $_GET['campaignId'] ?? null;

		if (!isset($milestoneId) || $milestoneId <=0) {
			echo json_encode([
				'success' => false,
				'title' => translate([
					'text' => 'Error',
					'isPublicFacing' => true,
				]),
				'message' => translate([
					'text' => 'Invalid milestone ID.',
					'isPublicFacing' => true,
				]),
			]);
			exit;
		}

		if (!isset($userId)) {
			echo json_encode([
				'success' => false,
				'title' => translate([
					'text' => 'Error',
					'isPublicFacing' => true,
				]),
				'message' => translate([
					'text' => 'Invalid user ID.',
					'isPublicFacing' => true,
				]),
			]);
			exit;
		}

		if (!isset($campaignId)){
			echo json_encode([
				'success' => false,
				'title' => translate([
					'text' => 'Error',
					'isPublicFacing' => true,
				]),
				'message' => translate([
					'text' => 'Invalid campaign ID.',
					'isPublicFacing' => true,
				]),
			]);
			exit;
		}

		$campaignMilestone = new CampaignMilestone();
		$campaignMilestone->campaignId = $campaignId;
		$campaignMilestone->milestoneId = $milestoneId;
		$campaignMilestone->addCampaignMilestoneProgressEntry(null, $userId, null);

		$userCampaign = new UserCampaign();
		$userCampaign->userId = $userId;
		$userCampaign->campaignId = $campaignId;
		if ($userCampaign->find(true)) {
			$userCampaign->checkAndHandleCampaignCompletion($userId, $campaignId);
		}
		
		echo json_encode([
			'title' => translate([
					'text' => 'Progress Added',
					'isPublicFacing' => true,
				]),
			'success' => true,
			'message' => translate([
				'text' => 'Progress added successfully!',
				'isPublicFacing' => true,
			]),
		]);
		exit;
 
	}

	public function campaignLeaderboardOptIn() {
		if (!UserAccount::isLoggedIn()) {
			echo json_encode([
				'success' => false,
				'title' => translate([
					'text' => 'Error',
					'isPublicFacing' => true,
				]),
				'message' => translate([
					'text' => 'User not logged in.',
					'isPublicFacing' => true,
				]),
			]);
			exit;
		}

		$userId = $_GET['userId'];
		$campaignId = $_GET['campaignId'];

		if (empty($campaignId)) {
			echo json_encode([
				'success' => false,
				'title' => translate([
					'text' => 'Error',
					'isPublicFacing' => true,
				]),
				'message' => translate([
					'text' => 'Invalid Campaign ID',
					'isPublicFacing' => true,
				]),
			]);
			exit;
		}

		$userCampaign = new UserCampaign();
		$userCampaign->userId = $userId;
		$userCampaign->campaignId = $campaignId;
		$userCampaign->find(['userId' => $userId, 'campaignId' => $campaignId]);


		$userCampaign->optInToCampaignLeaderboard = 1;
		$userCampaign->update();

		echo json_encode([
			'success' => true,
			'title' => translate([
					'text' => 'Joined Leaderboard',
					'isPublicFacing' => true,
				]),
			'message' => translate([
				'text' => 'You have successfully joined the leaderboard for this campaign',
				'isPublicFacing' => true,
			]),
		]);
		exit;
	}

	public function campaignLeaderboardOptOut() {

		if (!UserAccount::isLoggedIn()) {
			echo json_encode([
				'success' => false,
				'title' => translate([
					'text' => 'Error',
					'isPublicFacing' => true,
				]),
				'message' => translate([
					'text' => 'User not logged in.',
					'isPublicFacing' => true,
				]),
			]);
			exit;
		}

		$userId = $_GET['userId'];
		$campaignId = $_GET['campaignId'];

		if (empty($campaignId)) {
			echo json_encode([
				'success' => false,
				'title' => translate([
					'text' => 'Error',
					'isPublicFacing' => true,
				]),
				'message' => translate([
					'text' => 'Invalid Campaign ID',
					'isPublicFacing' => true,
				]),
			]);
			exit;
		}

		$userCampaign = new UserCampaign();
		$userCampaign->userId = $userId;
		$userCampaign->campaignId = $campaignId;

		$userCampaign->find(['userId' => $userId, 'campaignId' => $campaignId]);


		$userCampaign->optInToCampaignLeaderboard = 0;
		$userCampaign->update();

		echo json_encode([
			'success' => true,
			'title' => translate([
					'text' => 'Opted Out of Leaderboard',
					'isPublicFacing' => true,
				]),
			'message' => translate([
				'text' => 'You have successfully opted out of the leaderboard for this campaign',
				'isPublicFacing' => true,
			]),
		]);
		exit;

	}

	public function getCampaignEmailOptInForm() {
		require_once ROOT_DIR . '/sys/CommunityEngagement/UserCampaign.php';
		require_once ROOT_DIR . '/sys/Account/User.php';
		require_once ROOT_DIR . '/sys/CommunityEngagement/Campaign.php';
		global $interface;

		$campaignId = $_GET['campaignId'];
		$userId = $_GET['userId'];


		if (!$campaignId || !$userId) {
			return [
				'success' => false,
				'title' => translate([
					'text' => 'Error',
					'isPublicFacing' => true,
				]),
				'message' => translate([
					'text' => 'Campaign or User information is missing.',
					'isPublicFacing' => true
				]),
			];
		}

		$user = new User();
		$user->id = $userId;
		if(!$user->find(true)) {
			return [
				'success' => false,
				'title' => translate([
					'text' => 'Error',
					'isPublicFacing' => true
				]),
				'message' => translate([
					'text' => 'User not found',
					'isPublicFacing' => true
				])
				];
		}

		$optInToAllCampaignEmails = $user->campaignNotificationsByEmail;

		$userCampaign = new UserCampaign();
		$userCampaign->userId = $userId;
		$userCampaign->campaignId = $campaignId;

		$campaign = new Campaign();
		$campaign->id = $campaignId;
		if ($campaign->find(true)) {
			$campaignName = $campaign->name;
		}

		$optInToCampaignSpecificEmails = null;
		if ($userCampaign->find(true)) {
			$optInToCampaignSpecificEmails = $userCampaign->optInToCampaignEmailNotifications;
		}

		$isOptedIn = ($optInToCampaignSpecificEmails !== null) ? $optInToCampaignSpecificEmails : $optInToAllCampaignEmails;
		$sliderState = $isOptedIn ? ' checked' : '';

		if (!empty($user->email)) {
			$emailReminder = translate([
				'text' => 'Emails will be sent to: ' . $user->email,
				'isPublicFacing' => true,
			]);
		} else {
			$emailReminder = translate([
				'text' => 'Please update your email address in your contact information.',
				'isPublicFacing' => true,
			]);
		}

		$interface->assign('campaignId', $campaignId);
		$interface->assign('userId', $userId);
		$interface->assign('user', $user);
		$interface->assign('campaignName', $campaignName ?? '');
		$interface->assign('isOptedIn', $isOptedIn);
		$interface->assign('emailReminder', $emailReminder);
		$interface->assign('sliderState', $sliderState);

		return [
			'success' => true,
			'title' => translate([
				'text' => 'Campaign Notification Options',
				'isPublicFacing' => true
			]),
			'modalBody' => $interface->fetch('CommunityEngagement/campaignEmailOptInForm.tpl'),
			'modalButtons' => "<button type='button' class='tool btn btn-primary' onclick='AspenDiscovery.CommunityEngagement.handleCampaignEnrollment($campaignId, $userId, $(\"#emailOptInSlider\").prop(\"checked\") ? 1 : 0)'>" . translate([
				'text' => 'Submit',
				'isPublicFacing' => true,
				]) . "</button>",
		];
	}

	public function saveCampaignEmailOptInToggle() {
		require_once ROOT_DIR . '/sys/CommunityEngagement/UserCampaign.php';
		global $interface;

		$campaignId = $_GET['campaignId'] ?? null;
		$userId = $_GET['userId'] ?? null;
		$optIn = $_GET['optIn'] ?? null;

		if (!$campaignId || !$userId || $optIn === null) {
			return [
				'success' => false,
				'title' => translate([
					'text' => 'Error',
					'isPublicFacing' => true
				]),
				'message' => translate([
					'text' => 'Campaign, user or opt in information is missing',
					'isPublicFacing' => true,
				]),
			];
		}
		$campaign = new Campaign();
		$campaign->id = $campaignId;
		if ($campaign->find(true)) {
			$campaignName = $campaign->name;
		}

		$userCampaign = new UserCampaign();
		$userCampaign->userId = $userId;
		$userCampaign->campaignId = $campaignId;

		if ($userCampaign->find(true)) {
			$userCampaign->optInToCampaignEmailNotifications = (int)$optIn;
			$success = $userCampaign->update();
		}
		if ($success) {
			if ($userCampaign->optInToCampaignEmailNotifications == 1) {

				$user = new User();
				$user->id = $userId;
				if ($user->find(true) && !empty($user->email)) {
					$this->sendEnrollmentEmail($user, $campaignId);
				}

			}
			$userCampaign->checkAndHandleCampaignCompletion($userId, $campaignId);
			$interface->assign('campaignName', $campaignName);

	
			return [
				'success' => true,
				'title' => translate([
					'text' => 'Success',
					'isPublicFacing' => true,
				]),
				'message' => $interface->fetch('CommunityEngagement/saveCampaignEmailOptInForm.tpl')
			];
		} else {
			return [
				'success' => false,
				'title' => translate([
					'text' => 'Error',
					'isPublicFacing' => false,
				]),
				'message' => translate([
					'text' => 'Failed to update your campaign notification preferences.',
					'isPublicFacing' => true,
				])
			];
		}
	}

	private function sendEnrollmentEmail($user, $campaignId) {
		require_once ROOT_DIR . '/sys/Email/EmailTemplate.php';
		require_once ROOT_DIR . '/sys/CommunityEngagement/Campaign.php';

		global $logger;

		$emailTemplate = EmailTemplate::getActiveTemplate('campaignEnroll');
		if(!$emailTemplate) {
			return;
		}

		$campaign = new Campaign();
		$campaign->id = $campaignId;

		if (!$campaign->find(true)) {
			$logger->log("Campaign with ID $campaignId not found.", Logger::LOG_ERROR);
			return;
		}

		$parameters = $campaign->getCampaignEmailParameters($user, $campaignId);
		if (empty($parameters)) {
			return;
		}
		
		try {
			$emailTemplate->sendEmail($user->email, $parameters);

		} catch (Exception $e) {
			$logger->log("Exception while sending email to {$user->email}: " . $e->getMessage(), Logger::LOG_ERROR);
		}
	}

	public function saveLeaderboardChanges() {
		header('Content-Type: application/json');
		ob_start();
		$data = json_decode(file_get_contents('php://input'), true);

		$html = $data['html'];
		$css = $data['css'];
		$templateName = $data['templateName'];

		if (empty($html) || empty($templateName) || empty($css)) {
			ob_end_clean();
				echo json_encode([
					'success' => false, 
					'title' => translate([
						'text' => 'Error',
						'isPublicFacing' => true,
					]),
					'message' => translate([
						'text' => 'Invalid html, css or template name',
						'isPublicFacing' => true,
					]),
				]);
				return;
		}

		$this->saveLeaderboardToDatabase($templateName, $html, $css);
		$leaderboardData = $this->getLeaderboardData();

		if (empty($leaderboardData) || empty($leaderboardData['html']) || empty($leaderboardData['css'])) {
			echo json_encode([
				'success' => false,
				'title' => translate(['text' => 'Error', 'isPublicFacing' => true]),
				'message' => translate(['text' => 'Failed to retrieve updated leaderboard data', 'isPublicFacing' => true]),
			]);
		exit;
	}
	

		ob_end_clean();
		if ($leaderboardData) {
			echo json_encode([
				'success' => true,
				'title' => translate([
					'text' => 'Success',
					'isPublicFacing' => true,
				]),
				'message' => translate([
					'text' => 'Leaderboard changes saved successfully',
					'isPublicFacing' => true,
				]),
				'updatedHTML' => $leaderboardData['html'],
				'updatedCSS' => $leaderboardData['css']
			]);
			exit;
		}
	}

	private function saveLeaderboardToDatabase($templateName, $html, $css) {
		require_once ROOT_DIR . '/sys/WebBuilder/GrapesTemplate.php';
		global $logger;

		$activeUser = UserAccount::getActiveUserObj();

		if (!$activeUser) {
			return [
				'success' => false,
				'title' => translate([
					'text' =>'Error',
					'isPublicFacing' => true,
				]),
				'message' => translate([
					'text' => 'You must be logged in to make changes to the leaderboard.',
					'isPublicFacing' => true
				])
			];
		}
		$userIsAspenAdmin = UserAccount::getActiveUserObj()->isAspenAdminUser();
		$userIsAdmin = UserAccount::getActiveUserObj()->isUserAdmin();

		if (!$userIsAspenAdmin || !$userIsAdmin) {
			return [
				'success' => false,
				'title' => translate([
					'text' =>'Error',
					'isPublicFacing' => true,
				]),
				'message' => translate([
					'text' => 'You do not have the correct permissions to make changes to the leaderboard.',
					'isPublicFacing' => true
				])
			];
		}

		$grapesTemplate = new GrapesTemplate();
		$grapesTemplate->templateName = $templateName;
		
		if ($grapesTemplate->find(true)) {

			$grapesTemplate->htmlData = $html;
			$grapesTemplate->templateContent = $html;
			$grapesTemplate->cssData = $css;
			$success = $grapesTemplate->update();
		} else {

			$grapesTemplate = new GrapesTemplate();
			$grapesTemplate->htmlData = $html;
			$grapesTemplate->templateName = $templateName;
			$grapesTemplate->templateContent = $html;
			$grapesTemplate->cssData = $css;
			$success = $grapesTemplate->insert();
		}

		if (!$success) {
			$logger->log("Failed to save template: " . print_r($grapesTemplate->getLastError(), true), LOGGER::LOG_ERROR);
		}
		return $success;
	}

	public function getLeaderboardData() {
		require_once ROOT_DIR . '/sys/WebBuilder/GrapesTemplate.php';

		$grapesTemplate = new GrapesTemplate();
		$grapesTemplate->templateName = 'leaderboard_template';

		if ($grapesTemplate->find(true)) {
			return [
				"html" => $grapesTemplate->htmlData,
				"css" => $grapesTemplate->cssData
			];
		}
		return null;
	}

	public function resetLeaderboardDisplay() {
		require_once ROOT_DIR . '/sys/WebBuilder/GrapesTemplate.php';
		$grapesTemplate = new GrapesTemplate();
		$grapesTemplate->templateName = 'leaderboard_template';
		if ($grapesTemplate->find(true)) {
			$grapesTemplate->delete();
			echo json_encode([
				'success' => true,
				'title' => translate([
					'text' => 'Success',
					'isPublicFacing' => true
				]),
				'message' => translate([
					'text' => 'The leaderboard template has been successfully reset.',
					'isPublicFacing' => true
				])
			]);
		}else {
			echo json_encode([
				'success' => false,
				'title' => translate([
					'text' => 'Error',
					'isPublicFacing' => true
				]),
				'message' => translate([
					'text' => 'No leaderboard template to reset.',
					'isPublicFacing' => true
				])
			]);
		}
		exit;
	}

	public function campaignEmailOptIn() {
		$userId = $_GET['userId'];
		$campaignId = $_GET['campaignId'];

		if (empty($campaignId)) {
			echo json_encode([
				'success' => false,
				'title' => translate([
					'text' => 'Error',
					'isPublicFacing' => true,
				]),
				'message' => translate([
					'text' => 'Invalid Campaign ID',
					'isPublicFacing' => true,
				]),
			]);
			exit;
		}
		if (empty($userId)) {
			echo json_encode([
				'success' => false,
				'title' => translate([
					'text' => 'Error',
					'isPublicFacing' => true,
				]),
				'message' => translate([
					'text' => 'Invalid User ID',
					'isPublicFacing' => true,
				]),
			]);
			exit;
		}

		$userCampaign = new UserCampaign();
		$userCampaign->userId = $userId;
		$userCampaign->campaignId = $campaignId;

		$userCampaign->find(['userId' => $userId, 'campaignId' => $campaignId]);


		$userCampaign->optInToCampaignEmailNotifications = 1;
		$userCampaign->update();

		echo json_encode([
			'success' => true,
			'title' => translate([
				'text' => 'Success',
				'isPublicFacing' => true,
			]),
			'message' => translate([
				'text' => 'You have successfully opted in to notification emails for this campaign',
				'isPublicFacing' => true,
			]),
		]);
		exit;
	}

	public function campaignEmailOptOut() {

		$userId = $_GET['userId'];
		$campaignId = $_GET['campaignId'];

		if (empty($campaignId)) {
			echo json_encode([
				'success' => false,
				'title' => translate([
					'text' => 'Error',
					'isPublicFacing' => true,
				]),
				'message' => translate([
					'text' => 'Invalid Campaign ID',
					'isPublicFacing' => true,
				]),
			]);
			exit;
		}

		if (empty($userId)) {
			echo json_encode([
				'success' => false,
				'title' => translate([
					'text' => 'Error',
					'isPublicFacing' => true,
				]),
				'message' => translate([
					'text' => 'Invalid User ID',
					'isPublicFacing' => true,
				]),
			]);
			exit;
		}

		$userCampaign = new UserCampaign();
		$userCampaign->userId = $userId;
		$userCampaign->campaignId = $campaignId;

		$userCampaign->find(['userId' => $userId, 'campaignId' => $campaignId]);

		$userCampaign->optInToCampaignEmailNotifications = 0;
		$userCampaign->update();

		echo json_encode([
			'success' => true,
			'title' => translate([
				'text' => 'Success',
				'isPublicFacing' => true,
			]),
			'message' => translate([
				'text' => 'You have successfully opted out of email notifications for this campaign',
				'isPublicFacing' => true,
			]),
		]);
		exit;
	}

	public function fetchLibraryUsers($enrolledOnly = false) {
		if (!UserAccount::userHasPermission(['View Community Engagement Dashboard'])){
			return [];
		}

		global $library;

		require_once ROOT_DIR . '/sys/Account/User.php';
		require_once ROOT_DIR . '/sys/CommunityEngagement/Campaign.php';

		$libraryId = $library->libraryId;
		$user = new User();

		if ($library->displayOnlyUsersForLocationInUserAdmin) {
			$user->whereAdd('homeLocationId = ' . $libraryId);
		}

		$users = array();

		if($user->find()) {
			while ($user->fetch()) {
				$users[] = array(
					'id' => $user->id,
					'displayName' => $user->displayName,
					'ils_barcode' => $user->ils_barcode,
				);
			} 
		}

		usort($users, function ($a, $b) {
			$aName = trim((string)$a['displayName']);
			$bName = trim((string)$b['displayName']);

			if ($aName === '' && $bName === '') {
				return 0;
			}

			if ($aName === '') {
				return 1;
			}
			if ($bName === '') {
				return -1;
			}

			return strcasecmp($bName, $aName);
		});

		return $users;
	}

	public function getLibraryUsers() {
		if (!UserAccount::userHasPermission(['View Community Engagement Dashboard'])){
			return ['error' => "You don't have permission to access this page"];
		}

		global $library;
		try {
			$users = $this->fetchLibraryUsers();

			 echo json_encode([
				'success' => true, 
				'users' => $users, 
				'title' => translate([
					'text' => 'Users Loaded',
					'isPublicFacing' => true,
				]),
				'message' => translate([
					'text' => count($users) . ' users found',
					'isPublicFacing' => true,
				]),
			]);

		} catch (Exception $e){
			echo json_encode([
				'success' => false,
				'users' => [],
				'title' => translate([
					'text' => 'Error', 
					'isPublicFacing' => true,
				]),
				'message' => translate([
					'text' => 'Error loading users: ' . $e->getMessage(),
					'isPublicFacing' => true,
				]),
			]);
		}
		exit;
	}


	public function addUserByBarcode() {
		if (!UserAccount::userHasPermission(['View Community Engagement Dashboard'])){
			return ['error' => "You don't have permission to access this page"];
		}

		$barcode = $_POST['barcode'] ?? '';
		
		if (empty($barcode)) {
			return ['success' => false, 'title' => 'Error','message' => 'Barcode is required'];
		}
		
		require_once ROOT_DIR . '/sys/Account/User.php';
		require_once ROOT_DIR . '/CatalogFactory.php';
		global $library;
		global $logger;
		$accountProfile = new AccountProfile();
		$accountProfile->id = $library->accountProfileId;
		$accountProfile->find(true);
		$user = new User();
		
		// Check if user already exists
		$user->ils_barcode = $barcode;
		if ($user->find(true)) {
			return [
				'success' => false,
				'title' => translate([
					'text' => 'Error',
					'isPublicFacing' => true
				]), 
				'message' => translate([
					'text' => 'User already exists',
					'isPublicFacing' => true
				])
			];
		}
		
		// Try to load from ILS
		$catalog = CatalogFactory::getCatalogConnectionInstance(null, null);
		if (method_exists($catalog, 'findNewUser')) {
			$newUser = $catalog->findNewUser($barcode, '');
		} else {
			return [
				'success' => false, 
				'title' => translate([
					'text' => 'Error',
					'isPublicFacing' => true
				]), 
				'message' => translate([
					'text' => 'Your ILS does not currently support this function',
					'isPublicFacing' => true
				])
			];
		}
		
		if ($newUser && !($newUser instanceof AspenError)) {
			$newUser->getDisplayName();
			$newUser->update();
			
			return [
				'success' => true, 
				'title' => translate([
					'text' => 'User Added',
					'isPublicFacing' => true
				]), 
				'message' => translate([
					'text' => 'User Added to Aspen',
					'isPublicFacing' => true
				])
			];
		}
		
		return [
			'success' => false,
			'title' => translate([
				'text' => 'Error',
				'isPublicFacing' => true
			]), 
			'message' => translate([
				'text' => 'User not found in ILS or could not be loaded',
				'isPublicFacing' => true
			])
		];
	}

	public function addProgressToExtraCreditActivities($extraCreditActivityId = null, $userId = null, $campaignId = null) {
		require_once ROOT_DIR . '/sys/CommunityEngagement/Campaign.php';
		require_once ROOT_DIR . '/sys/CommunityEngagement/ExtraCredit.php';
		require_once ROOT_DIR . '/sys/CommunityEngagement/UserCampaign.php';
		require_once ROOT_DIR . '/sys/CommunityEngagement/CampaignExtraCreditActivityUsersProgress.php';

		$extraCreditActivityId = $extraCreditActivityId ?? $_GET['extraCreditActivityId'] ?? null;
		$userId = $userId ?? $_GET['userId'] ?? null;
		$campaignId = $campaignId ?? $_GET['campaignId'] ?? null;


		if (!isset($extraCreditActivityId) || $extraCreditActivityId <= 0) {
			echo json_encode([
				'success' => false,
				'title' => translate([
					'text' => 'Error',
					'isPublicFacing' => true,
				]),
				'message' => translate([
					'text' => 'Invalid Extra Credit Activity ID.',
					'isPublicFacing' => true,
				]),
			]);
			exit;
		}

		if (!isset($userId)) {
			echo json_encode([
				'success' => false,
				'title' => translate([
					'text' => 'Error',
					'isPublicFacing' => true,
				]),
				'message' => translate([
					'text' => 'Invalid user ID.',
					'isPublicFacing' => true,
				]),
			]);
			exit;
		}

		if (!isset($campaignId)){
			echo json_encode([
				'success' => false,
				'title' => translate([
					'text' => 'Error',
					'isPublicFacing' => true,
				]),
				'message' => translate([
					'text' => 'Invalid campaign ID.',
					'isPublicFacing' => true,
				]),
			]);
			exit;
		}

		$campaignExtraCreditActivityUsersProgress = new CampaignExtraCreditActivityUsersProgress();
		$campaignExtraCreditActivityUsersProgress->extraCreditId = $extraCreditActivityId;
		$campaignExtraCreditActivityUsersProgress->userId = $userId;
		$campaignExtraCreditActivityUsersProgress->campaignId = $campaignId;

		if ($campaignExtraCreditActivityUsersProgress->find(true)) {
			$campaignExtraCreditActivityUsersProgress->progress++;
			$campaignExtraCreditActivityUsersProgress->update();
		} else {
			$campaignExtraCreditActivityUsersProgress->progress++;
			$campaignExtraCreditActivityUsersProgress->insert();
		}

		echo json_encode([
			'title' => translate([
					'text' => 'Progress Added',
					'isPublicFacing' => true,
				]),
			'success' => true,
			'message' => translate([
				'text' => 'Progress added successfully!',
				'isPublicFacing' => true,
			]),
		]);
		exit;
 
	}

	function extraCreditRewardGivenUpdate() {
		ob_start();

		try {
			$userId = $_GET['userId'];
			$extraCreditActivityId = $_GET['extraCreditActivityId'];
			$campaignId = $_GET['campaignId'];

			if (!isset($extraCreditActivityId) || $extraCreditActivityId <= 0) {
				echo json_encode([
					'success' => false,
					'title' => translate([
						'text' => 'Error',
						'isPublicFacing' => true,
					]),
					'message' => translate([
						'text' => 'Invalid Extra Credit Activity ID.',
						'isPublicFacing' => true,
					]),
				]);
				exit;
			}
	
			if (!isset($userId)) {
				echo json_encode([
					'success' => false,
					'title' => translate([
						'text' => 'Error',
						'isPublicFacing' => true,
					]),
					'message' => translate([
						'text' => 'Invalid user ID.',
						'isPublicFacing' => true,
					]),
				]);
				exit;
			}
	
			if (!isset($campaignId)){
				echo json_encode([
					'success' => false,
					'title' => translate([
						'text' => 'Error',
						'isPublicFacing' => true,
					]),
					'message' => translate([
						'text' => 'Invalid campaign ID.',
						'isPublicFacing' => true,
					]),
				]);
				exit;
			}

			$campaignExtraCreditActivityUsersProgress = new CampaignExtraCreditActivityUsersProgress();
			$campaignExtraCreditActivityUsersProgress->userId = $userId;
			$campaignExtraCreditActivityUsersProgress->extraCreditId = $extraCreditActivityId;
			$campaignExtraCreditActivityUsersProgress->campaignId = $campaignId;

			if ($campaignExtraCreditActivityUsersProgress->find(true)) {
				$campaignExtraCreditActivityUsersProgress->rewardGiven = 1;

				if ($campaignExtraCreditActivityUsersProgress->update()) {
					ob_end_clean();
					echo json_encode([
						'success' => true,
						'title' => translate([
							'text' => 'Reward Given',
							'isPublicFacing' => true,
						]),
						'message' => translate([
							'text' => 'Reward Status Updated',
							'isPublicFacing' => true,
						]),
					]);
				} else {
					throw new Exception('Failed to update reward status');
				}
			} else {
				throw new Exception('Milestone progress record not found.');
			}

		} catch(Exception $e) {
			ob_end_clean();
			echo json_encode(['success' => false, 'message' => $e->getMessage()]);
		}
		exit;
	}

	public function searchUsers() {
		$query = $_REQUEST['query'] ?? '';

		$response = [
			'success' => false,
			'message' => 'Sorry, you don\'t have permissions to search users'
		];

		if (!UserAccount::userHasPermission('View Community Engagement Admin View')) {
			return $response;
		}

		if (strlen($query) < 2) {
			$response['message'] = 'Query too short';
			return $response;
		}

		require_once ROOT_DIR . '/sys/Account/User.php';
		$user = new User();

		$escapedQuery = addslashes($query);

		$user->whereAdd("displayName LIKE '%$escapedQuery%' OR ils_barcode LIKE '%$escapedQuery%'");

		$user->limit(0, 25);

		$matches = [];
		if ($user->find()) {
			while ($user->fetch()) {
				$matches[] = [
					'id' => $user->id,
					'displayName' => $user->displayName,
					'ils_barcode' => $user->ils_barcode
				];
			}
		}
		return [
			'success' => true,
			'users' => $matches
		];
	}
}