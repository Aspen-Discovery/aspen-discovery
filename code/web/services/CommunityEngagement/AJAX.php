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
                    $html .='<thead><tr><th>User</th><th>Rank</th><th>Completed Milestones</th></tr></thead><tbody>';
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
                    $response['html'] = 'There are currently no users enrolled in this campaign.';
                }
              
            } else {
                $leaderboard = $campaign->getOverallLeaderboard();
                if ($leaderboard) {
                    $html .='<thead><tr><th>User</th><th>Rank</th><th>Completed Milestones</th></tr></thead><tbody>';
                    foreach ($leaderboard as $entry) {
                        $html .= "<tr><td>{$entry['user']}</td><td>{$entry['rankDisplayed']}</td><td>{$entry['completedMilestones']}</td></tr>";
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
                    $html .='<thead><tr><th>Branch</th><th>Rank</th><th>Completed Milestones</th></tr></thead><tbody>';
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
                    $html .='<thead><tr><th>Branch</th><th>Rank</th><th>Completed Milestones</th></tr></thead><tbody>';
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

        echo json_encode([
            'success' => true,
            'message' => translate([
                'text' => 'Progress added successfully!',
                'isPublicFacing' => true,
            ]),
        ]);
        exit;
 
    }

    public function campaignLeaderboardOptIn() {
        //TODO: CHANGE SO THAT WE USE THE PASSED IN USER NOT LOGGED IN
        if (!UserAccount::isLoggedIn()) {
            echo json_encode([
                'success' => false,
                'message' => translate([
                    'text' => 'User not logged in.',
                    'isPublicFacing' => true,
                ]),
            ]);
            exit;
        }

        $user = UserAccount::getLoggedInUser();
        $userId = $user->id;
        $campaignId = $_GET['campaignId'];

        if (empty($campaignId)) {
            echo json_encode([
                'success' => false,
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

        $userCampaign->optInToCampaignLeaderboard = 1;
        $userCampaign->update();

        echo json_encode([
            'success' => true,
            'message' => translate([
                'text' => 'You have successfully joined the leaderboard for this campaign',
                'isPublicFacing' => true,
            ]),
        ]);
        exit;
    }

    public function campaignLeaderboardOptOut() {
        //TODO: CHANGE SO THAT WE USE THE PASSED IN USER NOT LOGGED IN

        if (!UserAccount::isLoggedIn()) {
            echo json_encode([
                'success' => false,
                'message' => translate([
                    'text' => 'User not logged in.',
                    'isPublicFacing' => true,
                ]),
            ]);
            exit;
        }

        $user = UserAccount::getLoggedInUser();
        $userId = $user->id;
        $campaignId = $_GET['campaignId'];

        if (empty($campaignId)) {
            echo json_encode([
                'success' => false,
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

        $userCampaign->optInToCampaignLeaderboard = 0;
        $userCampaign->update();

        echo json_encode([
            'success' => true,
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

        $optInToCampaignSpecificEmails = null;
        if ($userCampaign->find(true)) {
            $optInToCampaignSpecificEmails = $userCampaign->optInToCampaignEmailNotifications;
        }

        $isOptedIn = ($optInToCampaignSpecificEmails !== null) ? $optInToCampaignSpecificEmails : $optInToAllCampaignEmails;

        if ($isOptedIn) {
            $buttonExplanationText = translate([
                'text' => 'Opt Out of Email Notifications for This Campaign',
                'isPublicFacing' => true
            ]);
            $action = "AspenDiscovery.CommunityEngagement.toggleCampaignEmailOptIn($campaignId, $userId, 0)";
        } else {
            $buttonExplanationText = translate([
                'text' => 'Opt In to Email Notifications for This Campaign',
                'isPublicFacing' => true
            ]);
            $action = "AspenDiscovery.CommunityEngagement.toggleCampaignEmailOptIn($campaignId, $userId, 1)";
        }

        return [
            'success' => true,
            'title' => translate([
                'text' => 'Campaign Notification Options',
                'isPublicFacing' => true
            ]),
            'modalBody' => translate([
                'text' => $buttonExplanationText,
                'isPublicFacing' => true,
            ]),
            'modalButtons' => "<button type='button' class='tool btn btn-primary' onclick='$action'>" . translate([
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
        $userCampaign->userid = $userId;
        $userCampaign->campaignId = $campaignId;

        if ($userCampaign->find(true)) {
            $userCampaign->optInToCampaignEmailNotifications = (int)$optIn;
            $success = $userCampaign->update();
        } else {
            $userCampaign->optInToCampaignEmailNotifications = (int)$optIn;
            $success = $userCampaign->insert();
        }

        if ($success) {
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
       

}