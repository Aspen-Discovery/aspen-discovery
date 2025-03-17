<?php
require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';
require_once ROOT_DIR . '/sys/CommunityEngagement/Campaign.php';
require_once ROOT_DIR . '/sys/CommunityEngagement/CampaignMilestone.php';
require_once ROOT_DIR . '/sys/CommunityEngagement/Milestone.php';
require_once ROOT_DIR . '/sys/CommunityEngagement/UserCompletedMilestone.php';
require_once ROOT_DIR . '/sys/CommunityEngagement/CampaignMilestoneProgressEntry.php';
require_once ROOT_DIR . '/sys/Account/User.php';

class MyCampaigns extends MyAccount {

	function launch() {
		global $interface;
		global $library;

        $campaign = new Campaign();
        //Get User
        $userId = $this->getUserId();
        $interface->assign('userId', $userId);

        $hasLinkedAccounts = UserAccount::hasLinkedUsers();
        $interface->assign('hasLinkedAccounts', $hasLinkedAccounts);

        $linkedCampaigns = $this->getLinkedUserCampaigns($userId);
        $interface->assign('linkedCampaigns', $linkedCampaigns);

		//Get Campaigns
		$campaignList = $this->getCampaigns();
		$interface->assign('campaignList', $campaignList);

		//Get past campaigns
		$pastCampaigns = $campaign->getPastCampaigns($userId);
		$interface->assign('pastCampaigns', $pastCampaigns);

        $url = $this->getBaseUrl();
        $interface->assign('url', $url);

		$this->display('../MyAccount/myCampaigns.tpl', 'My Campaigns');
	}

	function getUserId() {
		$user = UserAccount::getLoggedInUser();
		$userId = $user->id;
		return $userId;
	}
    //TODO:: MOVE TO CAMPAIGN.PHP
	function getCampaigns() {
		global $activeLanguage;

		$campaign = new Campaign();
		$campaignList = [];

		if (!UserAccount::isLoggedIn()) {
			return $campaignList;
		}
		$user = UserAccount::getLoggedInUser();
		$userId = $user->id;

		//Get active campaigns
		$activeCampaigns = Campaign::getActiveCampaignsList();

		//Get upcoming campaigns - those starting in the next month
		$upcomingCampaigns = Campaign::getUpcomingCampaigns();

		//Get campaigns
		$campaign->find();
		while ($campaign->fetch()) {
			$campaignId = $campaign->id;

			//Find out if user is enrolled in campaign
			$campaign->enrolled = $campaign->isUserEnrolled($userId);
			//Find out if campaign is active
			$campaign->isActive = isset($activeCampaigns[$campaignId]);

			//Find out if campaign in upcoming
			$campaign->isUpcoming = isset($upcomingCampaigns[$campaignId]);
			$campaign->textBlockTranslationDescription = $campaign->getTextBlockTranslation('description', $activeLanguage->code);
			if (empty($campaign->textBlockTranslationDescription)) {
				$campaign->textBlockTranslationDescription = "";
			}
			//Get campaign reward name
			$rewardDetails = $campaign->getRewardDetails();
			if ($rewardDetails) {
				$campaign->rewardName = $rewardDetails['name'];
				$campaign->rewardId = $rewardDetails['id'];
				$campaign->rewardType = $rewardDetails['rewardType'];
				$campaign->badgeImage = $rewardDetails['badgeImage'];
				$campaign->rewardExists = $rewardDetails['rewardExists'];
                $campaign->displayReward = $rewardDetails['displayReward'];
                $campaign->displayName = $rewardDetails['displayName'];
			}

				//Fetch milestones for this campaign
				$milestones = CampaignMilestone::getMilestoneByCampaign($campaignId);
				$completedMilestonesCount = 0;
				$numCampaignMilestones = 0;
				$milestoneProgressData = [];

				//Store progress for each milestone
				$campaign->milestoneProgress = [];


				foreach ($milestones as $milestone) {
					$milestoneId = $milestone->id;
					$numCampaignMilestones++;

					//Calculate milestone progress
					$milestoneProgress = CampaignMilestone::getMilestoneProgress($campaignId, $userId, $milestone->id);
					$progressData = CampaignMilestoneProgressEntry::getUserProgressDataByMilestoneId($userId, $milestoneId, $campaignId);

                    $milestone->progress = $milestoneProgress['progress'];
                    $milestone->extraProgress = $milestoneProgress['extraProgress'];
                    $milestone->completedGoals = $milestoneProgress['completed'];
                    $milestone->totalGoals = CampaignMilestone::getMilestoneGoalCountByCampaign($campaignId, $milestoneId);
                    $milestone->progressData = $progressData;
                    $milestone->rewardGiven = CampaignMilestoneUsersProgress::getRewardGivenForMilestone($milestone->id, $user->id, $campaign->id);
                }
                //Add completed milestones count to campaign object
                // $campaign->numCompletedMilestones = $completedMilestonesCount;
                $campaign->numCampaignMilestones = $numCampaignMilestones;

                $userCampaign = new UserCampaign();
                $userCampaign->userId = $userId;
                $userCampaign->campaignId = $campaignId;
                $userCampaign->find();
                while($userCampaign->fetch()) {
                    if ($userCampaign->optInToCampaignLeaderboard === null) {
                        $campaign->optInToCampaignLeaderboard = $user->optInToAllCampaignLeaderboards;
                    }else{
                        $campaign->optInToCampaignLeaderboard = $userCampaign->optInToCampaignLeaderboard;
                    }
                    if ($userCampaign->optInToCampaignEmailNotifications == null) {
                        $campaign->optInToCampaignEmailNotifications = $user->campaignNotificationsByEmail;
                    } else {
                        $campaign->optInToCampaignEmailNotifications = $userCampaign->optInToCampaignEmailNotifications;
                    }
                    $campaign->campaignRewardGiven = $userCampaign->rewardGiven;
                }
                $milestoneCompletionStatus = $userCampaign->checkMilestoneCompletionStatus();
                $campaign->numCompletedMilestones = count(array_filter($milestoneCompletionStatus));

				//Add milestones to campaign object
				$campaign->milestones = $milestones;

                //Add the campaign to the list
            $campaignList[] = clone $campaign;
        }
        return $campaignList;
    }

    function getLinkedUserCampaigns($userId) {
        if (empty($userId)){
            throw new InvalidArgumentException("User ID is required");
        }
        $user = new User();
        $user->id = $userId;

        if (!$user->find(true)) {
            throw new RuntimeException("User not found.");
        }

        $linkedUsers = $user->getLinkedUsers();
        if (empty($linkedUsers)) {
            return [];
        }

        $groupedLinkedCampaigns = [];

        foreach ($linkedUsers as $linkedUser) {
            $eligibleCampaigns = [];
            $campaign = new Campaign();

            if ($campaign->find()) {
                while ($campaign->fetch()) {
                    $userCampaign = new UserCampaign();
                    $userCampaign->userId = $linkedUser->id;
                    $userCampaign->campaignId = $campaign->id;
    
                    $isEnrolled = $userCampaign->find(true);
                    $campaignReward = null;
                    $rewardDetails = $campaign->getRewardDetails();
                    if ($rewardDetails != null) {
                        $campaignReward = [
                            'rewardName' => $rewardDetails['name'],
                            'rewardType' => $rewardDetails['rewardType'], 
                            'badgeImage' => $rewardDetails['badgeImage'],
                            'rewardExists' => $rewardDetails['rewardExists'],
                            'displayName' => $rewardDetails['displayName'],
                        ];
                    }

                    $startDate = $campaign->startDate;
                    $endDate = $campaign->endDate;

                    $milestones = CampaignMilestone::getMilestoneByCampaign($campaign->id);
                    $numCampaignMilestones = count($milestones);
                    $numCompletedMilestones = 0;
                    $milestoneRewards = [];

                    foreach ($milestones as $milestone) {
                        $milestoneProgress = CampaignMilestone::getMilestoneProgress($campaign->id, $linkedUser->id, $milestone->id);
                        $completedGoals = $milestoneProgress['completed'];
                        $totalGoals = CampaignMilestone::getMilestoneGoalCountByCampaign($campaign->id, $milestone->id);

                        if ($milestoneProgress['progress'] == 100) {
                            $numCompletedMilestones++;
                        }


                        $milestoneRewards[] = [
                            'id' => $milestone->id,
                            'milestoneName' => $milestone->name,
                            'rewardName' => $milestone->rewardName, 
                            'rewardType' => $milestone->rewardType, 
                            'displayName' => $milestone->displayName,
                            'badgeImage' => $milestone->rewardImage,
                            'rewardExists' => $milestone->rewardExists,
                            'progress' => $milestoneProgress['progress'],
                            'extraProgress' => $milestoneProgress['extraProgress'],
                            'completedGoals' => $completedGoals,
                            'totalGoals' => $totalGoals,
                            'progressData' => $milestoneProgress['data'],
                            'progressBeyondOneHundredPercent' => $milestone->progressBeyondOneHundredPercent,
                            'allowPatronProgressInput' => $milestone->allowPatronProgressInput
                        ];
                    }


                    $eligibleCampaigns[] = [
                        'campaignId' => $campaign->id,
                        'campaignName' => $campaign->name,
                        'isEnrolled' => $isEnrolled,
                        'campaignReward' => $campaignReward,
                        'milestones' => $milestoneRewards,
                        'numCompletedMilestones' => $numCompletedMilestones,
                        'numCampaignMilestones' => $numCampaignMilestones,
                        'startDate' => $startDate,
                        'endDate' => $endDate
                    ];
                }
            }

            $groupedLinkedCampaigns[] = [
                'linkedUserName' => $linkedUser->displayName, 
                'linkedUserId' => $linkedUser->id,
                'campaigns' => $eligibleCampaigns
            ];
        }
       return $groupedLinkedCampaigns;
    }

    public function getBaseUrl(): string {
        global $configArray;
        return $configArray['Site']['url'];
    }



    //TODO:: Write a function that uses the milestone id for each progress bar to use the ce_milestone_progress_entries table and 
    //get information about which books were checked out and count towards the milestone. 
    function getBreadcrumbs(): array
    {
        $breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'Your Account');
		$breadcrumbs[] = new Breadcrumb('', 'Campaigns');
		return $breadcrumbs;
	}
}