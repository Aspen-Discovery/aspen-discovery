<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Dashboard.php';
require_once ROOT_DIR . '/sys/CommunityEngagement/CampaignData.php';
require_once ROOT_DIR . '/sys/CommunityEngagement/Campaign.php';
require_once ROOT_DIR . '/sys/CommunityEngagement/UserCampaign.php';
require_once ROOT_DIR . '/sys/CommunityEngagement/UserCompletedMilestone.php';
require_once ROOT_DIR . '/sys/CommunityEngagement/CampaignMilestone.php';
require_once ROOT_DIR . '/services/CommunityEngagement/AJAX.php';



class CommunityEngagement_AdminView extends Admin_Dashboard {
	function launch() {
		global $interface;
		global $library;

		$campaign = new Campaign();
		$userCampaign = new UserCampaign();

		$campaigns = $campaign->getAllCampaigns();
		$interface->assign('campaigns', $campaigns);

		$campaignsEndingThisMonth = $campaign->getCampaignsEndingThisMonth();
		$interface->assign('campaignsEndingThisMonth', $campaignsEndingThisMonth);
		
		$activeCampaigns = $campaign->getActiveCampaignsList();
		$interface->assign('activeCampaigns', $activeCampaigns);

		$upcomingCampaigns = $campaign->getUpcomingCampaigns();
		$interface->assign('upcomingCampaigns', $upcomingCampaigns);

		$userCampaigns = [];
		$campaignMilestonesMap = [];
		$userCampaignMilestones = [];

		foreach ($campaigns as $campaign) {
			$campaignMilestones = CampaignMilestone::getCampaignMilestoneByCampaign($campaign->id);
			$extraCreditActivities = CampaignExtraCredit::getExtraCreditByCampaign($campaign->id);
			$campaignMilestonesMap[$campaign->id] = $campaignMilestones;

			$users = $campaign->getUsersForCampaign();
			foreach ($users as $user) {
				$userCampaign = new UserCampaign();
				$userCampaign->userId = $user->id;
				$userCampaign->campaignId = $campaign->id;
			
			if ($userCampaign->find(true)) {
				$isCampaignComplete = $userCampaign->checkCompletionStatus();
				if (!isset($userCampaigns[$campaign->id][$user->id])) {
					$userCampaigns[$campaign->id][$user->id] = [];
				}

				$userCampaigns[$campaign->id][$user->id]['rewardGiven']= (int)$userCampaign->rewardGiven;

				$userCampaigns[$campaign->id][$user->id]['isCampaignComplete'] = $isCampaignComplete;

				$campaignMilestoneCompletionStatus = $userCampaign->checkCampaignMilestoneCompletionStatus();
				foreach ($campaignMilestones as $campaignMilestone) {
					$milestoneComplete = $campaignMilestoneCompletionStatus[$campaignMilestone->id] ?? false;
					$userProgress = CampaignMilestoneUsersProgress::getProgressByCampaignMilestoneId($campaignMilestone->id, $user->id);
					$totalGoals = CampaignMilestone::getCampaignMilestoneGoalCountByCampaign($campaignMilestone->id);
					$milestoneRewardGiven = CampaignMilestoneUsersProgress::getRewardGivenForCampaignMilestone($campaignMilestone->id, $user->id);
					$userCampaigns[$campaign->id][$user->id]['milestones'][$campaignMilestone->id] = [
						'milestoneComplete' => $milestoneComplete, 
						'userProgress' => $userProgress,
						'goal' => $totalGoals,
						'milestoneRewardGiven' => $milestoneRewardGiven,
					];

				}
			}
			}
			//Count how many users have completed the campaign
			$campaign->completedUsersCount = $campaign->getCompletedUsersCount();
		}
		$interface->assign('userCampaigns', $userCampaigns);
		$interface->assign('campaignMilestones', $campaignMilestonesMap);
		$interface->assign('library', $library);
		$this->display('adminView.tpl', 'Admin View');
	}

   
	function getBreadcrumbs(): array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#communityEngagement', 'Community Engagement');
		$breadcrumbs[] = new Breadcrumb('/CommunityEngagement/AdminView', 'Community Engagement Admin View');
		return $breadcrumbs;
	}

	function canView(): bool {
		return UserAccount::userHasPermission([
			'View Community Engagement Admin View',
		]);
	}

	function getActiveAdminSection(): string
	{
		return 'communityEngagement';
	}
}