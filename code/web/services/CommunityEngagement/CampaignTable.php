<?php

require_once ROOT_DIR . '/sys/CommunityEngagement/Campaign.php';
require_once ROOT_DIR . '/sys/CommunityEngagement/UserCampaign.php';
require_once ROOT_DIR . '/sys/CommunityEngagement/Reward.php';
require_once ROOT_DIR . '/services/Admin/Dashboard.php';
require_once ROOT_DIR . '/sys/CommunityEngagement/CampaignMilestone.php';
require_once ROOT_DIR . '/sys/CommunityEngagement/CampaignExtraCredit.php';
require_once ROOT_DIR . '/sys/CommunityEngagement/CampaignExtraCreditActivityUsersProgress.php';


class CommunityEngagement_CampaignTable extends Admin_Dashboard {
	
	function launch() {
		global $interface;

		$campaignId = isset($_GET['id']) ? intval($_GET['id']) : 0;

		if ($campaignId > 0) {
			$campaign = Campaign::getCampaignById($campaignId);

			if ($campaign) {
				$campaignReward = new Reward();
				$campaignReward->id = $campaign->campaignReward;
				if ($campaignReward->find(true)) {
					$campaign->rewardName = $campaignReward->name;
					$campaign->awardAutomatically = $campaignReward->awardAutomatically;
					$campaign->rewardType = $campaignReward->rewardType;
				}
				$interface->assign('campaign', $campaign);
				
				//Retrieve milestones for the campaign
				$campaignMilestones = CampaignMilestone::getCampaignMilestoneByCampaign($campaignId);

				$extraCreditActivities = CampaignExtraCredit::getExtraCreditByCampaign($campaignId);
				//Get users for campaign
				$users = $campaign->getUsersForCampaign();

				$userCampaigns = [];
				foreach ($users as $user) {
					$userCampaign = new UserCampaign();
					$userCampaign->campaignId = $campaignId;
					$userCampaign->userId = $user->id;

					if ($userCampaign->find(true)) {
						$isCampaignComplete = $userCampaign->checkCompletionStatus();
						$userCampaigns[$campaign->id][$user->id] = [
							'rewardGiven' => (int)$userCampaign->rewardGiven,
							'isCampaignComplete' =>$isCampaignComplete,
							'milestones' => []
						];
						//Get milestone completion status
						$milestoneCompletionStatus = $userCampaign->checkCampaignMilestoneCompletionStatus();

                        foreach ($campaignMilestones as $campaignMilestone) {
                            $milestoneComplete = $milestoneCompletionStatus[$campaignMilestone->id] ?? false;
                            $userProgress = CampaignMilestoneUsersProgress::getProgressByCampaignMilestoneId($campaignMilestone->id, $user->id);
                            $totalGoals = CampaignMilestone::getCampaignMilestoneGoalCountByCampaign($campaignMilestone->id);
                            $milestoneRewardGiven = CampaignMilestoneUsersProgress::getRewardGivenForCampaignMilestone($campaignMilestone->id, $user->id);
                            $milestoneType = $campaignMilestone->milestoneType;
							$milestoneAwardAutomatically = $campaignMilestone->awardAutomatically;

                            //Calculate percentage progress
                            $percentageProgress = $totalGoals > 0 ? ($userProgress / $totalGoals) * 100 : 0;
                            //Add milestone data for each user
                            $userCampaigns[$campaign->id][$user->id]['milestones'][$campaignMilestone->id] = [
                                'milestoneComplete' => $milestoneComplete,
                                'userProgress' => $userProgress,
                                'goal' => $totalGoals,
                                'milestoneRewardGiven' =>$milestoneRewardGiven,
                                'percentageProgress' => round($percentageProgress, 2),
                                'milestoneType' => $milestoneType,
								'milestoneAwardAutomatically' => $milestoneAwardAutomatically,
                            ];
                        }
						$userExtraCredit = $this->getUserExtraCreditData($campaignId, $user, $extraCreditActivities);
						$userCampaigns[$campaign->id][$user->id]['extraCreditActivities'] = $userExtraCredit;
                    }
                }
                $interface->assign('userCampaigns', $userCampaigns);
                $interface->assign('milestones', $campaignMilestones);
                $interface->assign('users', $users);
				$interface->assign('extraCreditActivities', $extraCreditActivities);

			} else {
				$interface->assign('error', 'Campaign not found.');
			}
		} else {
			$interface->assign('error', 'Invalid campaign ID.');
		}
		$this->display('campaignTable.tpl', 'Campaign Table');
	}

	function canView(): bool {
		return UserAccount::userHasPermission([
			'View Community Engagement Dashboard',
		]);
	}

	function getActiveAdminSection(): string
	{
		return 'communityEngagement';
	}

	function getBreadcrumbs(): array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#communityEngagement', 'Community Engagement');
		$breadcrumbs[] = new Breadcrumb('/CommunityEngagement/AdminView', 'Community Engagement Admin View');
		return $breadcrumbs;
	}

	private function getUserExtraCreditData($campaignId, $user, $extraCreditActivities) {
		$userCampaign = new UserCampaign();
		$userCampaign->campaignId = $campaignId;
		$userCampaign->userId = $user->id;
		$userCampaign->find(true);

		$extraCreditCompletionStatus = $userCampaign->checkExtraCreditActivityCompletionStatus();

		$result = [];
		foreach ($extraCreditActivities as $activity) {
			$extraCreditComplete = $extraCreditCompletionStatus[$activity->id] ?? false;
			$extraCreditUsersprogress = CampaignExtraCreditActivityUsersProgress::getProgressByExtraCreditId($activity->id, $campaignId, $user->id);
			$totalActivityGoals = CampaignExtraCredit::getExtraCreditGoalCountByCampaign($campaignId, $activity->id);
			$extraCreditActivityRewardGiven = CampaignExtraCreditActivityUsersProgress::getRewardGivenForExtraCreditActivity($activity->id, $user->id, $campaignId);
			$percentageProgressExtraCredit = ($totalActivityGoals > 0) ? ($extraCreditUsersprogress / $totalActivityGoals) * 100 : 0;

			$result[$activity->id] = [
				'percentageProgress' => round($percentageProgressExtraCredit, 2),
				'extraCreditRewardGiven' => $extraCreditActivityRewardGiven,
				'extraCreditComplete' => $extraCreditComplete,
			];
		}
		return $result;
	}

}