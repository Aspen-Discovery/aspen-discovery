<?php
require_once ROOT_DIR . '/JSON_Action.php';
require_once ROOT_DIR . '/sys/CommunityEngagement/Campaign.php';
require_once ROOT_DIR . '/sys/CommunityEngagement/UserCampaign.php';
require_once ROOT_DIR . '/sys/CommunityEngagement/CampaignMilestoneUsersProgress.php';
require_once ROOT_DIR . '/sys/UserAccount.php';


class CommunityEngagement_AJAX extends JSON_Action {
	function launch($method = null) : void {
		$this->checkRequiredModule('Community Engagement');
		parent::launch($method);
	}

	/** @noinspection PhpUnused */
	function campaignRewardGivenUpdate() : array {
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
				return ['success' => true];
			} else {
				return ['success' => false, 'message' => 'Failed to update reward status.'];
			}
		} else {
			return ['success' => false, 'message' => 'User campaign record not found.'];
		}
	}

	/** @noinspection PhpUnused */
	function milestoneRewardGivenUpdate() : array {
		$this->checkRequiredPermission(['View Community Engagement Dashboard']);
		$this->checkRequiredParameters(['userId', 'campaignId', 'milestoneId']);

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
					return ['success' => true];
				} else {
					return ['success' => false, 'message' => 'Failed to update reward status'];
				}
			} else {
				return ['success' => false, 'message' => 'Milestone progress record not found'];
			}

		} catch(Exception $e) {
			return ['success' => false, 'message' => $e->getMessage()];
		}
	}

	/** @noinspection PhpUnused */
	function filterCampaigns() : array {
		$this->checkRequiredPermission(['View Community Engagement Dashboard']);

		global $library;

		$campaignId = isset($_REQUEST['campaignId']) ? intval($_REQUEST['campaignId']) : 0;
		$userId = isset($_REQUEST['userId']) ? intval($_REQUEST['userId']) : 0;
		$filterType = $_REQUEST['filterType'] ?? '';
	
		$response = [];
		if ($filterType === 'campaign') {
			if ($campaignId > 0) {
	
				$campaign = Campaign::getCampaignById($campaignId);
				if ($campaign) {
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
					$html .= '<div class="dashboardValue">' . htmlspecialchars($campaign->getCompletedUsersCount()) . '</div>';
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
					$html = '';
					$html .= "<button class='btn btn-primary btn-sm' onclick='AspenDiscovery.CommunityEngagement.refreshCurrentUserStats($userId); return false;' style='margin: 5px 0;'>Refresh Campaign Progress</button>";

					foreach ($allEligibleCampaigns as $campaign) {
						$html .= '<div class="dashboardCategory" style="border: 1px solid #3174AF; padding: 15px; margin-bottom: 20px;">';

						$html .= "<h5><a href=\"/CommunityEngagement/CampaignTable?id={$campaign->id}\">" . htmlspecialchars($campaign->name) . "</a></h5>";

						$html .= "<table class='table table-bordered table-sm'>";
						$html .= "<thead><tr>
									<th>Milestone</th>
									<th>Progress</th>
									<th>Status</th>
									<th>Reward</th>
								</tr></thead><tbody>";

						if (!empty($campaign->milestones)) {
							foreach ($campaign->milestones as $milestone) {
								$completed = (int)($milestone->completedGoals ?? 0);
								$total = (int)($milestone->totalGoals ?? 0);
								$progressBeyondLimit = $milestone->progressBeyondOneHundredPercent ?? false;
								if (!$progressBeyondLimit && $completed > $total) {
									$completed = $total;
								}

								$progress = "$completed / $total";
								$percentage = $total > 0 ? round(($completed / $total) * 100) : 0;
								$isComplete = $milestone->milestoneComplete == 1;
								$rewardGiven = $milestone->rewardGiven == 1;

								if ($milestone->rewardType == 1 && $milestone->awardAutomatically && $isComplete && !$rewardGiven) {
									$milestone->rewardGiven = 1;
									$rewardGiven = 1;
								}

								$progressData = $milestone->progressData;

								$html .= "<tr><td>" . htmlspecialchars($milestone->name) . "</td>";

								// Progress bar
								$html .= "<td>
									{$progress}
									<div class='progress' style='width:100%; border:1px solid black; border-radius:4px;height:20px;'>
										<div class='progress-bar' role='progressbar' aria-valuenow='{$percentage}' aria-valuemin='0' aria-valuemax='100' style='width: {$percentage}%; background-color: blue; color: white; text-align: center;'>
											{$percentage}%
										</div>
									</div>";

								if (!empty($progressData)) {
									$goalCount = 0;

									foreach ($progressData as $progressDataItem) {
										if ($goalCount < $total || $milestone->progressBeyondOneHundredPercent) {
											$html .= "<div style='padding:10px;'>";
											if (isset($progressDataItem['title'])) {
												$html .= htmlspecialchars($progressDataItem['title']);
											}
											$html .= "</div>";
											$goalCount++;
										}
									}
								}
								$html .= "</td>";

								// Status and manual progress
								$html .= "<td>";
								if ($isComplete) {
									$html .= "Complete";
									if ($milestone->milestoneType === 'manual' && $milestone->progressBeyondOneHundredPercent) {
										if ($campaign->enrolled) {
											$html .= "<br><button class='btn btn-primary btn-sm' onclick='AspenDiscovery.CommunityEngagement.adminManuallyProgressMilestone($milestone->id, $userId, $campaign->id); return false;'>Add Progress</button>";
										} else {
											$html .= "<br><button class='btn btn-secondary btn-sm' disabled>Add Progress</button>";
										}
									}
								} else {
									$html .= "Incomplete";
									if ($milestone->milestoneType === 'manual') {
										if ($campaign->enrolled) {
											$html .= "<br><button class='btn btn-primary btn-sm' onclick='AspenDiscovery.CommunityEngagement.adminManuallyProgressMilestone($milestone->id, $userId, $campaign->id); return false;'>Add Progress</button>";
										} else {
											$html .= "<br><button class='btn btn-secondary btn-sm' disabled>Add Progress</button>";
										}
									}
								}
								$html .= "</td>";

								// Reward button
								$html .= "<td>";
								$canGiveReward = $isComplete && !$rewardGiven;

								if ($rewardGiven) {
									$html .= "Reward Given";
								} else {
									$disabled = $canGiveReward ? '' : 'disabled';
									$tooltip = !$canGiveReward ? 'title="Milestone not complete or reward already given."' : '';
									$onclick = $canGiveReward
										? "onclick='AspenDiscovery.CommunityEngagement.adminMilestoneRewardGiven({$userId}, {$campaign->id}, {$milestone->id}); return false;'"
										: '';

									$html .= "<button class='btn btn-primary btn-sm' {$disabled} {$tooltip} {$onclick}>Give Reward</button>";
								}
								$html .= "</td></tr>";
							}
						} else {
							$html .= "<tr><td colspan='4'>No milestones defined for this campaign.</td></tr>";
						}

						$html .= "</tbody></table>";

						$extraCreditActivities = CampaignExtraCredit::getExtraCreditByCampaign($campaign->id, $userId);

						if (!empty($extraCreditActivities)) {
							$html .= "<h6>Extra Credit Activities</h6>";
							$html .= "<table class='table table-bordered table-sm'>";
							$html .= "<thead><tr>
										<th>Activity</th>
										<th>Progress</th>
										<th>Status</th>
										<th>Reward</th>
									</tr></thead><tbody>";

							foreach ($extraCreditActivities as $activity) {
								$completed = (int)($activity->completedGoals ?? 0);
								$total = (int)($activity->totalGoals ?? 0);

								if ($completed > $total) {
									$completed = $total;
								}

								$progress = "$completed / $total";
								$percentage = $total > 0 ? round(($completed / $total) * 100) : 0;
								$isComplete = $percentage >= 100;
								$rewardGiven = $activity->rewardGiven ?? false;

								if ($activity->rewardType == 1 && $activity->awardAutomatically && $isComplete && !$rewardGiven) {
									$rewardGiven = true;
								}

								$html .= "<tr>";
								$html .= "<td>" . htmlspecialchars($activity->name) . "<br><small>" . htmlspecialchars($activity->rewardDescription ?? '') . "</small></td>";

								$html .= "<td>
											{$progress}
											<div class='progress' style='width:100%; border:1px solid black; border-radius:4px;height:20px;'>
												<div class='progress-bar' role='progressbar' aria-valuenow='{$percentage}' aria-valuemin='0' aria-valuemax='100' style='width: {$percentage}%; background-color: green; color: white; text-align: center;'>
													{$percentage}%
												</div>
											</div>
										</td>";

								$html .= "<td>";
								if ($isComplete) {
									$html .= "Complete";
								} else {
									$html .= "Incomplete";
									if ($campaign->enrolled) {
										$html .= "<br><button class='btn btn-primary btn-sm' onclick='AspenDiscovery.CommunityEngagement.adminManuallyProgressExtraCredit({$activity->id}, {$userId}, {$campaign->id}); return false;'>Add Progress</button>";
									} else {
										$html .= "<br><button class='btn btn-secondary btn-sm' disabled>Add Progress</button>";
									}
								}
								$html .= "</td>";

								$html .= "<td>";
								if ($rewardGiven) {
									$html .= "Reward Given";
								} else {
									$canGiveReward = $isComplete && !$rewardGiven;
									$disabled = $canGiveReward ? '' : 'disabled';
									$tooltip = !$canGiveReward ? 'title="Activity not complete or reward already given."' : '';
									$onclick = $canGiveReward
										? "onclick='AspenDiscovery.CommunityEngagement.adminExtraCreditRewardGiven({$userId}, {$campaign->id}, {$activity->id}); return false;'"
										: '';

									$html .= "<button class='btn btn-primary btn-sm' {$disabled} {$tooltip} {$onclick}>Give Reward</button>";
								}
								$html .= "</td>";

								$html .= "</tr>";
							}

							$html .= "</tbody></table>";
						}

						// Campaign complete / reward section
						$campaignComplete = $campaign->isComplete == 1;
						$campaignRewardGiven = $campaign->campaignRewardGiven == 1;
						$html .= "<p><strong>Campaign Complete:</strong> " . ($campaignComplete ? "Yes" : "No") . "</p>";
						$html .= "<p><strong>Reward Given:</strong> " . ($campaignRewardGiven ? "Yes" : "No") . "</p>";

						if ($campaign->rewardType == 1 && $campaign->awardAutomatically == 1 && $campaignComplete) {
							$html .= "<p>Rewarded Automatically</p>";
						} elseif (!$campaignRewardGiven) {
							$html .= "<button class='btn btn-primary' style='margin-right: 5px;' onclick='AspenDiscovery.CommunityEngagement.adminCampaignRewardGiven({$userId}, {$campaign->id}); return false;'>Give Campaign Reward</button>";
						}

						// Enrollment buttons
						if (($campaign->isActive || $campaign->isUpcoming) && $library->allowAdminToEnrollUsersInAdminView && $campaign->canEnroll) {
							if ($campaign->enrolled) {
								$html .= "<button type='button' class='btn btn-danger' onclick='AspenDiscovery.CommunityEngagement.adminUnenroll({$campaign->id}, {$userId}); return false;'>Unenroll</button>";
							} else {
								$html .= "<button type='button' class='btn btn-success' onclick='AspenDiscovery.CommunityEngagement.adminEnrollPatron({$campaign->id}, {$userId}, {$userEmailOptInSetting}); return false;'>Enroll</button>";
							}
						}

						$html .= "</div>"; // end campaign box
					}
					$response['html'] = $html;
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
	
		return $response;
	}

	/** @noinspection PhpUnused */
	public function filterLeaderboardCampaigns() : array {
		$this->checkRequiredPermission(['View Community Engagement Dashboard']);

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
			return $response;
		} catch (Exception $e) {
			global $logger;
			$logger->log('Error filterLeaderboardCampaigns: ' . $e->getMessage());
			return [
				'success' => false,
				'message' => 'Error retrieving campaign information'
			];
		}
	}

	/** @noinspection PhpUnused */
	public function filterBranchLeaderboardCampaigns() : array {
		$this->checkRequiredPermission(['View Community Engagement Dashboard']);

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
				$branchLeaderboard = $campaign->getLeaderboardByBranchForCampaign($campaignId);
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
			
			return $response;
		} catch (Exception $e) {
			global $logger;
			$logger->log('Error filterBranchLeaderboardCampaigns: ' . $e->getMessage());
			return [
				'success' => false,
				'message' => 'Error retrieving campaign information'
			];
		}
	}

	/** @noinspection PhpUnused */
	public function manuallyProgressUserMilestone($milestoneId = null, $userId = null, $campaignId = null) : array {
		if ($milestoneId == null || $userId == null || $campaignId == null) {
			$this->checkRequiredParameters(['milestoneId', 'userId', 'campaignId']);
		}
		//TODO: This should check that the user is logged in or that there are permissions to progress the milestone

		require_once ROOT_DIR . '/sys/CommunityEngagement/Campaign.php';
		require_once ROOT_DIR . '/sys/CommunityEngagement/Milestone.php';
		require_once ROOT_DIR . '/sys/CommunityEngagement/UserCampaign.php';
		require_once ROOT_DIR . '/sys/CommunityEngagement/CampaignMilestone.php';

		$milestoneId = $milestoneId ?? $_GET['milestoneId'] ?? null;
		$userId = $userId ?? $_GET['userId'] ?? null;
		$campaignId = $campaignId ?? $_GET['campaignId'] ?? null;

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
		
		return [
			'title' => translate([
					'text' => 'Progress Added',
					'isPublicFacing' => true,
				]),
			'success' => true,
			'message' => translate([
				'text' => 'Progress added successfully!',
				'isPublicFacing' => true,
			]),
		];
	}

	/** @noinspection PhpUnused */
	public function campaignLeaderboardOptIn() : array {
		$this->requireLoggedInUser();
		$this->checkRequiredParameters(['userId', 'campaignId']);

		$userId = $_GET['userId'];
		$campaignId = $_GET['campaignId'];

		$userCampaign = new UserCampaign();
		$userCampaign->userId = $userId;
		$userCampaign->campaignId = $campaignId;
		$userCampaign->find(['userId' => $userId, 'campaignId' => $campaignId]);


		$userCampaign->optInToCampaignLeaderboard = 1;
		$userCampaign->update();

		return [
			'success' => true,
			'title' => translate([
					'text' => 'Joined Leaderboard',
					'isPublicFacing' => true,
				]),
			'message' => translate([
				'text' => 'You have successfully joined the leaderboard for this campaign',
				'isPublicFacing' => true,
			]),
		];
	}

	/** @noinspection PhpUnused */
	public function campaignLeaderboardOptOut() : array {
		$this->requireLoggedInUser();
		$this->checkRequiredParameters(['userId', 'campaignId']);

		$userId = $_GET['userId'];
		$campaignId = $_GET['campaignId'];

		$userCampaign = new UserCampaign();
		$userCampaign->userId = $userId;
		$userCampaign->campaignId = $campaignId;

		$userCampaign->find(['userId' => $userId, 'campaignId' => $campaignId]);


		$userCampaign->optInToCampaignLeaderboard = 0;
		$userCampaign->update();

		return [
			'success' => true,
			'title' => translate([
					'text' => 'Opted Out of Leaderboard',
					'isPublicFacing' => true,
				]),
			'message' => translate([
				'text' => 'You have successfully opted out of the leaderboard for this campaign',
				'isPublicFacing' => true,
			]),
		];

	}

	/** @noinspection PhpUnused */
	public function getCampaignEmailOptInForm() : array {
		require_once ROOT_DIR . '/sys/CommunityEngagement/UserCampaign.php';
		require_once ROOT_DIR . '/sys/Account/User.php';
		require_once ROOT_DIR . '/sys/CommunityEngagement/Campaign.php';
		global $interface;

		$this->checkRequiredParameters(['campaignId', 'userId']);

		$campaignId = $_GET['campaignId'];
		$userId = $_GET['userId'];

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

	/** @noinspection PhpUnused */
	public function saveCampaignEmailOptInToggle() : array {
		$this->checkRequiredParameters(['campaignId', 'userId']);

		require_once ROOT_DIR . '/sys/CommunityEngagement/UserCampaign.php';
		global $interface;

		$campaignId = $_GET['campaignId'] ?? null;
		$userId = $_GET['userId'] ?? null;
		$optIn = $_GET['optIn'] ?? null;

		if ($optIn === null) {
			return [
				'success' => false,
				'title' => translate([
					'text' => 'Error',
					'isPublicFacing' => true
				]),
				'message' => translate([
					'text' => 'Opt in information is missing',
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

		$success = false;
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

	/** @noinspection PhpUnused */
	private function sendEnrollmentEmail($user, $campaignId) : void {
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

	/** @noinspection PhpUnused */
	public function saveLeaderboardChanges() : array {
		header('Content-Type: application/json');
		ob_start();
		$data = json_decode(file_get_contents('php://input'), true);

		$html = $data['html'];
		$css = $data['css'];
		$templateName = $data['templateName'];

		if (empty($html) || empty($templateName) || empty($css)) {
			ob_end_clean();
			return [
				'success' => false,
				'title' => translate([
					'text' => 'Error',
					'isPublicFacing' => true,
				]),
				'message' => translate([
					'text' => 'Invalid html, css or template name',
					'isPublicFacing' => true,
				]),
			];
		}

		$this->saveLeaderboardToDatabase($templateName, $html, $css);
		$leaderboardData = $this->getLeaderboardData();

		if (empty($leaderboardData) || empty($leaderboardData['html']) || empty($leaderboardData['css'])) {
			return [
				'success' => false,
				'title' => translate(['text' => 'Error', 'isPublicFacing' => true]),
				'message' => translate(['text' => 'Failed to retrieve updated leaderboard data', 'isPublicFacing' => true]),
			];
		}

		ob_end_clean();
		return [
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
		];
	}

	/** @noinspection PhpReturnValueOfMethodIsNeverUsedInspection */
	private function saveLeaderboardToDatabase($templateName, $html, $css) : bool|array {
		require_once ROOT_DIR . '/sys/WebBuilder/GrapesTemplate.php';
		global $logger;

		$this->requireLoggedInUser();

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

	/** @noinspection PhpUnused */
	public function getLeaderboardData() : ?array {
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

	/** @noinspection PhpUnused */
	public function resetLeaderboardDisplay() : array {
		require_once ROOT_DIR . '/sys/WebBuilder/GrapesTemplate.php';
		$grapesTemplate = new GrapesTemplate();
		$grapesTemplate->templateName = 'leaderboard_template';
		if ($grapesTemplate->find(true)) {
			$grapesTemplate->delete();
			return [
				'success' => true,
				'title' => translate([
					'text' => 'Success',
					'isPublicFacing' => true
				]),
				'message' => translate([
					'text' => 'The leaderboard template has been successfully reset.',
					'isPublicFacing' => true
				])
			];
		}else {
			return [
				'success' => false,
				'title' => translate([
					'text' => 'Error',
					'isPublicFacing' => true
				]),
				'message' => translate([
					'text' => 'No leaderboard template to reset.',
					'isPublicFacing' => true
				])
			];
		}
	}

	/** @noinspection PhpUnused */
	public function campaignEmailOptIn() : array {
		$this->checkRequiredParameters(['campaignId', 'userId']);

		$userId = $_GET['userId'];
		$campaignId = $_GET['campaignId'];

		$userCampaign = new UserCampaign();
		$userCampaign->userId = $userId;
		$userCampaign->campaignId = $campaignId;

		$userCampaign->find(['userId' => $userId, 'campaignId' => $campaignId]);


		$userCampaign->optInToCampaignEmailNotifications = 1;
		$userCampaign->update();

		return [
			'success' => true,
			'title' => translate([
				'text' => 'Success',
				'isPublicFacing' => true,
			]),
			'message' => translate([
				'text' => 'You have successfully opted in to notification emails for this campaign',
				'isPublicFacing' => true,
			]),
		];
	}

	/** @noinspection PhpUnused */
	public function campaignEmailOptOut() : array {
		$this->checkRequiredParameters(['campaignId', 'userId']);

		$userId = $_GET['userId'];
		$campaignId = $_GET['campaignId'];

		$userCampaign = new UserCampaign();
		$userCampaign->userId = $userId;
		$userCampaign->campaignId = $campaignId;

		$userCampaign->find(['userId' => $userId, 'campaignId' => $campaignId]);

		$userCampaign->optInToCampaignEmailNotifications = 0;
		$userCampaign->update();

		return [
			'success' => true,
			'title' => translate([
				'text' => 'Success',
				'isPublicFacing' => true,
			]),
			'message' => translate([
				'text' => 'You have successfully opted out of email notifications for this campaign',
				'isPublicFacing' => true,
			]),
		];
	}

	/** @noinspection PhpUnused */
	public function fetchLibraryUsers($enrolledOnly = false) : array {
		$this->checkRequiredPermission(['View Community Engagement Dashboard']);

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

	/** @noinspection PhpUnused */
	public function getLibraryUsers() : array {
		$this->checkRequiredPermission(['View Community Engagement Dashboard']);

		try {
			$users = $this->fetchLibraryUsers();

			return [
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
			];

		} catch (Exception $e){
			return [
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
			];
		}
	}


	/** @noinspection PhpUnused */
	public function addUserByBarcode() : array {
		$this->checkRequiredPermission(['View Community Engagement Dashboard']);
		$this->checkRequiredParameters(['barcode']);

		$barcode = $_POST['barcode'] ?? '';
		
		require_once ROOT_DIR . '/sys/Account/User.php';
		require_once ROOT_DIR . '/CatalogFactory.php';
		global $library;
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

	/** @noinspection PhpUnused */
	public function addProgressToExtraCreditActivities($extraCreditActivityId = null, $userId = null, $campaignId = null) : array {
		require_once ROOT_DIR . '/sys/CommunityEngagement/Campaign.php';
		require_once ROOT_DIR . '/sys/CommunityEngagement/ExtraCredit.php';
		require_once ROOT_DIR . '/sys/CommunityEngagement/UserCampaign.php';
		require_once ROOT_DIR . '/sys/CommunityEngagement/CampaignExtraCreditActivityUsersProgress.php';

		$this->checkRequiredParameters(['extraCreditActivityId', 'userId', 'campaignId']);

		$extraCreditActivityId = $extraCreditActivityId ?? $_GET['extraCreditActivityId'] ?? null;
		$userId = $userId ?? $_GET['userId'] ?? null;
		$campaignId = $campaignId ?? $_GET['campaignId'] ?? null;

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

		return [
			'title' => translate([
					'text' => 'Progress Added',
					'isPublicFacing' => true,
				]),
			'success' => true,
			'message' => translate([
				'text' => 'Progress added successfully!',
				'isPublicFacing' => true,
			]),
		];
 
	}

	/** @noinspection PhpUnused */
	function extraCreditRewardGivenUpdate() : array {
		$this->checkRequiredParameters(['extraCreditActivityId', 'userId', 'campaignId']);

		ob_start();

		try {
			$userId = $_GET['userId'];
			$extraCreditActivityId = $_GET['extraCreditActivityId'];
			$campaignId = $_GET['campaignId'];

			$campaignExtraCreditActivityUsersProgress = new CampaignExtraCreditActivityUsersProgress();
			$campaignExtraCreditActivityUsersProgress->userId = $userId;
			$campaignExtraCreditActivityUsersProgress->extraCreditId = $extraCreditActivityId;
			$campaignExtraCreditActivityUsersProgress->campaignId = $campaignId;

			if ($campaignExtraCreditActivityUsersProgress->find(true)) {
				$campaignExtraCreditActivityUsersProgress->rewardGiven = 1;

				if ($campaignExtraCreditActivityUsersProgress->update()) {
					ob_end_clean();
					return [
						'success' => true,
						'title' => translate([
							'text' => 'Reward Given',
							'isPublicFacing' => true,
						]),
						'message' => translate([
							'text' => 'Reward Status Updated',
							'isPublicFacing' => true,
						]),
					];
				} else {
					return ['success' => false, 'message' => 'Failed to update reward status'];
				}
			} else {
				return ['success' => false, 'message' => 'Milestone progress record not found.'];
			}

		} catch(Exception $e) {
			return ['success' => false, 'message' => $e->getMessage()];
		}
	}
}