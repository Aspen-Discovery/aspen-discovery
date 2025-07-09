<?php
require_once ROOT_DIR . '/JSON_Action.php';
require_once ROOT_DIR . '/sys/CommunityEngagement/Campaign.php';
require_once ROOT_DIR . '/sys/CommunityEngagement/UserCampaign.php';
require_once ROOT_DIR . '/sys/CommunityEngagement/CampaignMilestoneUsersProgress.php';
require_once ROOT_DIR . '/sys/UserAccount.php';


class CommunityEngagement_AJAX extends JSON_Action {
	function campaignRewardGivenUpdate() {
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
					$html .= "<h2 class=\"dashboardCategoryLabel\"><a href=\"/CommunityEngagement/CampaignTable?id={$campaignId}\">" . htmlspecialchars($campaign->name) . "</a></h2>";
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
						$html .= "<h2 class=\"dashboardCategoryLabel\"><a href=\"/CommunityEngagement/CampaignTable?id={$campaignId}\">" . htmlspecialchars($campaign->name) . "</a></h2>";
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
				// Fetch user campaigns
				$userCampaigns = Campaign::getUserEnrolledCampaigns($userId);
	
				if (!empty($userCampaigns)) {
					$html = '';
					foreach ($userCampaigns as $campaign) {
						$campaign->completedUsersCount = $campaign->getCompletedUsersCount();
						$html .= '<div class="dashboardCategory row" style="border: 1px solid #3174AF; padding: 0 10px 10px 10px; margin-bottom: 10px;">';
						$html .= '<div class="col-sm-12">';
						$html .= "<h5 style=\"font-weight:bold;\"><a href=\"/CommunityEngagement/CampaignTable?id={$campaign->id}\">" . htmlspecialchars($campaign->name) . "</a></h5>";
						$html .= '<div style="border-bottom: 2px solid #3174AF; padding: 10px; margin-bottom: 10px;">';
						$html .= '<div class="dashboardLabel">Number of Patrons Enrolled: </div>';
						$html .= '<div class="dashboardValue">' . htmlspecialchars($campaign->currentEnrollments) . '</div>';
						$html .= '<div class="dashboardLabel">Number of Enrollments: </div>';
						$html .= '<div class="dashboardValue">' . htmlspecialchars($campaign->enrollmentCounter) . '</div>';
						$html .= '<div class="dashboardLabel">Number of UnEnrollments: </div>';
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
					$response['message'] = 'User not found.';
				}
	
			} else {
				// Get all users in campaigns if no specific user is selected
				$userCampaigns = Campaign::getAllCampaignsWithEnrolledUsers();
				if (!empty($userCampaigns)) {
					$html = '';
					foreach ($userCampaigns as $campaign) {
						$campaign->completedUsersCount = $campaign->getCompletedUsersCount();
						$html .= '<div class="dashboardCategory row" style="border: 1px solid #3174AF; padding: 0 10px 10px 10px; margin-bottom: 10px;">';
						$html .= '<div class="col-sm-12">';
						$html .= "<h5 style=\"font-weight:bold;\"><a href=\"/CommunityEngagement/CampaignTable?id={$campaign->id}\">" . htmlspecialchars($user->name) . "</a></h5>";
						$html .= '<div style="border-bottom: 2px solid #3174AF; padding: 10px; margin-bottom: 10px;">';
						$html .= '<div class="dashboardLabel">Number of Patrons Enrolled: </div>';
						$html .= '<div class="dashboardValue">' . htmlspecialchars($campaign->currentEnrollments) . '</div>';
						$html .= '<div class="dashboardLabel">Number of Enrollments: </div>';
						$html .= '<div class="dashboardValue">' . htmlspecialchars($campaign->enrollmentCounter) . '</div>';
						$html .= '<div class="dashboardLabel">Number of UnEnrollments: </div>';
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
					$response['message'] = 'No users found';
				}
			}
		} else {
			$response['message'] = 'Invalid filter type.';
		}
	
		header('Content-Type: application/json');
		echo json_encode($response);
		exit;
	}
	

	public function filterLeaderboardCampaigns() {
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

	public function manuallyProgressUserMilestone() {
		require_once ROOT_DIR . '/sys/CommunityEngagement/Campaign.php';
		require_once ROOT_DIR . '/sys/CommunityEngagement/Milestone.php';
		require_once ROOT_DIR . '/sys/CommunityEngagement/UserCampaign.php';
		require_once ROOT_DIR . '/sys/CommunityEngagement/CampaignMilestoneUsersProgress.php';

		$milestoneId = $_GET['milestoneId'] ?? null;
		$userId = $_GET['userId'] ?? null;
		$campaignId = $_GET['campaignId'] ?? null;

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


		$campaignMilestoneUsersProgress = new CampaignMilestoneUsersProgress();
		$campaignMilestoneUsersProgress->ce_milestone_id = $milestoneId;
		$campaignMilestoneUsersProgress->userId = $userId;
		$campaignMilestoneUsersProgress->ce_campaign_id = $campaignId;


		if ($campaignMilestoneUsersProgress->find(true)) {
			$campaignMilestoneUsersProgress->progress++;
			$campaignMilestoneUsersProgress->update();
		} else {
			$campaignMilestoneUsersProgress->progress++;
			$campaignMilestoneUsersProgress->insert();
		}

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


		return [
			'success' => true,
			'title' => translate([
				'text' => 'Campaign Notification Options',
				'isPublicFacing' => true
			]),
			'modalBody' => translate([
				'text' => 'Opt in to campaign email updates for ' .$campaignName . ':',
				'isPublicFacing' => true,
			]) . '<label class="switch"><input type="checkbox" id="emailOptInSlider"' . $sliderState . '><span class="slider"></span></label><br>' . $emailReminder,
			'modalButtons' => "<button type='button' class='tool btn btn-primary' onclick='AspenDiscovery.CommunityEngagement.handleCampaignEnrollment($campaignId, $userId, $(\"#emailOptInSlider\").prop(\"checked\") ? 1 : 0)'>" . translate([
				'text' => 'Submit',
				'isPublicFacing' => true,
				]) . "</button>",
		];
	}

	public function saveCampaignEmailOptInToggle() {
		require_once ROOT_DIR . '/sys/CommunityEngagement/UserCampaign.php';

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


		$userCampaign = new UserCampaign();
		$userCampaign->userId = $userId;
		$userCampaign->campaignId = $campaignId;

		if ($userCampaign->find(true)) {
			$userCampaign->optInToCampaignEmailNotifications = (int)$optIn;
			$success = $userCampaign->update();
		}
		if ($success) {
			if ($userCampaign->optInToCampaignEmailNotifications == 1) {
				$campaign = new Campaign();
				$campaign->id = $campaignId;
				if ($campaign->find(true)) {
					$campaignName = $campaign->name;
				}

				$user = new User();
				$user->id = $userId;
				if ($user->find(true) && !empty($user->email)) {
					$this->sendEnrollmentEmail($user, $campaignId);
				}

			}
			$userCampaign->checkAndHandleCampaignCompletion($userId, $campaignId);

	
			return [
				'success' => true,
				'title' => translate([
					'text' => 'Success',
					'isPublicFacing' => true,
				]),
				'message' => translate([
					'text' => 'You have updated your campaign notification preferences.',
					'isPublicFacing' => true,
				])
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
	

}