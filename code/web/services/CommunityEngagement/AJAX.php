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
	
}