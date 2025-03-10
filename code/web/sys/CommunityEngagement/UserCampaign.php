<?php
	require_once ROOT_DIR . '/sys/CommunityEngagement/CampaignMilestone.php';
	require_once ROOT_DIR . '/sys/CommunityEngagement/Campaign.php';
	require_once ROOT_DIR . '/sys/CommunityEngagement/CampaignMilestoneUsersProgress.php';

class UserCampaign extends DataObject {
    public $__table = 'ce_user_campaign';
    public $id;
    public $userId;
    public $campaignId;
    public $enrollmentDate;
    public $unenerollmentDate;
    public $completed;
    public $rewardGiven;
    public $optInToCampaignLeaderboard;
    public $optInToCampaignEmailNotifications;
    public $campaignCompleteEmailSent;

	public static function getObjectStructure($context = ''): array {
		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'userId' => [
				'property' => 'userId',
				'type' => 'label',
				'label' => 'User Id',
				'description' => 'The unique id of the user',
			],
			'campaignId' => [
				'property' => 'campaignId',
				'type' => 'label',
				'label' => 'Campaign Id',
				'description' => 'The unique id of the campaign',
			],
			'enrollmentDate' => [
				'property' => 'enrollmentDate',
				'type' => 'date',
				'label' => 'Enrollment Date',
				'description' => 'The Date of Enrollment',
			],
			'unenrollmentDate' => [
				'property' => 'unenrollmentDate',
				'type' => 'date',
				'label' => 'Unenrollment Date',
				'description' => 'The Date of Unenrollment',
			],
			'completed' => [
				'property' => 'completed',
				'type' => 'checkbox',
				'label' => 'Campaign Complete',
				'description' => 'Whether or not the campaign is complete',
                'default' => false,
            ],
            'rewardGiven' => [
                'property' => 'rewardGiven',
                'type' => 'checkbox',
                'label' => 'Reward Given',
                'description' => 'Whether or not the reward for completing the campaign has been given',
                'default' => false,
            ],
            'optInToCampaignLeaderboard' => [
                'property' => 'optInToCampaignLeaderboard',
                'type' => 'checkbox',
                'label' => 'Opt In To Campaign Leaderboard',
                'description' => 'Whether or not to opt into the being displayed on the leaderboard for this campaign',
            ],
            'optInToCampaignEmailNotifications' => [
                'property' => 'optInToCampaignEmailNotifications',
                'type' => 'checkbox',
                'label' => 'Opt In To Campaign Notification Emails',
                'description' => 'Whether or not to opt in to email notifications for this campaign',
            ],
        ];
    }

	//Check if the user has completed the campaign
	public function checkCompletionStatus() {
		//Get milestones for campaign
		$milestones = CampaignMilestone::getMilestoneByCampaign($this->campaignId);
		$isComplete = true;

		foreach ($milestones as $milestone) {
			$userProgress = CampaignMilestoneUsersProgress::getProgressByMilestoneId($milestone->id, $this->campaignId, $this->userId);
			$goal = CampaignMilestone::getMilestoneGoalCountByCampaign($this->campaignId, $milestone->id);

			if ($userProgress < $goal) {
				$isComplete = false;
				break;
			}
		}
		return $isComplete;
	}

	public function checkMilestoneCompletionStatus() {
		$milestones = CampaignMilestone::getMilestoneByCampaign($this->campaignId);
		$milestoneCompletionStatus = [];

		foreach ($milestones as $milestone) {
			//User's progress for this milestone
			$userProgress = CampaignMilestoneUsersProgress::getProgressByMilestoneId($milestone->id, $this->campaignId, $this->userId);

			//Goal for this milestone
			$goal = CampaignMilestone::getMilestoneGoalCountByCampaign($this->campaignId, $milestone->id);

			//Check if milestone is complete
			$isMilestoneComplete = ($userProgress >= $goal);

			//Add to array
			$milestoneCompletionStatus[$milestone->id] = $isMilestoneComplete;
		}

		return $milestoneCompletionStatus;
	}

     /**
     * Calculate the total number of completed milestones for a user
     * @param int $userId
     * @return int
     */
    public function calculateUserCompletedMilestones($userId) {
        $userCampaign = new UserCampaign();
        $userEnrolledCampaigns = [];
        $userCampaign->whereAdd("userId = '$userId'");
        $userCampaign->find();
        while ($userCampaign->fetch()) {
            $userEnrolledCampaigns[] = clone $userCampaign;
        }
        $totalCompletedMilestones = 0;
        foreach ($userEnrolledCampaigns as $userEnrolledCampaign) {
            $milestoneCompletionStatus = $userEnrolledCampaign->checkMilestoneCompletionStatus();
            $completedMilestones = array_filter($milestoneCompletionStatus, function($status) {
                return $status === true;
            });
            //Add the completed milestones count to the total 
            $totalCompletedMilestones += count($completedMilestones);
            return $totalCompletedMilestones;
        }
    }
    /**
     * Calculate the user's rank based on completed milestones
     * @param int $userId
     * @return int
     */
    public function getUserRank($userId) {
        $campaign = new Campaign();
        $allUsers = $campaign->getAllUsersInCampaigns();
        $userCompletedMilestones = [];
        foreach ($allUsers as $user) {
            $totalCompletedMilestones = $this->calculateUserCompletedMilestones($userId);
            $userCompletedMilestones[$user] = $totalCompletedMilestones;
        }
        arsort($userCompletedMilestones);
        $rank = 1;
        $previousCompletedMilestones = null;
        $userRank = null;
        foreach ($userCompletedMilestones as $userId => $completedMilestones) {
            if ($completedMilestones != $previousCompletedMilestones) {
                $rank = $rank;
            }
            if ($user == $userId) {
                $userRank = $rank;
                break;
            }
            $previousCompletedMilestones = $completedMilestones;
            $rank++;
        }
        return $userRank;
    }

    public function checkAndHandleCampaignCompletion($userId, $campaignId) {
        require_once ROOT_DIR . '/sys/Email/EmailTemplate.php';
        global $logger;

        $userCampaign = new UserCampaign();
        $userCampaign->userId = $userId;
        $userCampaign->campaignId = $campaignId;

        if ($userCampaign->find(true)) {
            if ($userCampaign->optInToCampaignEmailNotifications == 1) {
                $user = new User();
                $user->id = $userId;
                if (!$user->find(true)) {
                    return;
                }

                $campaign = new Campaign();
                $campaign->id = $campaignId;
                if (!$campaign->find(true)) {
                    return;
                }
                $campaignName = $campaign->name;
                if ($userCampaign->completed == 1 && $userCampaign->campaignCompleteEmailSent == 0) {
                    $emailTemplate = EmailTemplate::getActiveTemplate('campaignComplete');
                    if ($emailTemplate) {
                        $parameters = [
                            'user' => $user,
                            'campaignName' => $campaignName,
                            'library' => $user->getHomeLibrary(),
                        ];
                        try {
                            $emailTemplate->sendEmail($user->email, $parameters);
                            $userCampaign->campaignCompleteEmailSent = 1;
                            $userCampaign->update();
                        } catch (Exception $e) {
                            $logger->log("Exception while sending email to {$user->email}: " . $e->getMessage(), Logger::LOG_ERROR);
                        }
                    }
                }
                $milestone = new CampaignMilestone();
                $milestone->campaignId = $campaignId;
                $milestones = [];

                if ($milestone->find()) {
                    while ($milestone->fetch()) {
                        $milestones[] = clone $milestone;
                    }
                }

                foreach ($milestones as $milestone) {
                    $milestoneProgress = new CampaignMilestoneUsersProgress();
                    $milestoneProgress->userId = $userId;
                    $milestoneProgress->ce_milestone_id = $milestone->id;
                    $milestoneProgress->ce_campaign_id = $campaignId;

                    if ($milestoneProgress->find(true)) {
                        if ($milestoneProgress >= $milestone->goal && !$milestoneProgress->milestoneCompleteEmailSent){
                            $emailTemplate = EmailTemplate::getActiveTemplate('milestoneComplete');

                            if ($emailTemplate) {
                                $parameters = [
                                    'user' => $user,
                                    'campaignName' => $campaign->name,
                                    'milestoneName' => $milestone->name,
                                    'library' => $user->getHomeLibrary(),
                                ];

                                try {
                                    $emailTemplate->sendEmail($user->email, $parameters);
                                    $milestoneProgress->milestoneCompleteEmailSent = 1;
                                    $milestoneProgress->update();
                                } catch (Exception $e) {
                                    $logger->log("Error sending milestone email to {$user->email}: " . $e->getMessage(), Logger::LOG_ERROR);
                                }
                            }
                        }
                    }
                }
            }
        }
    }

}