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
		global $enabledModules;

		$campaign = new Campaign();
		//Get User
		$userId = $this->getUserId();
		$interface->assign('userId', $userId);

		$hasLinkedAccounts = UserAccount::hasLinkedUsers();
		$interface->assign('hasLinkedAccounts', $hasLinkedAccounts);

		$linkedCampaigns = $campaign->getLinkedUserCampaigns($userId);
		$linkedCampaigns = $this->filterRemovedCampaigns($linkedCampaigns);
		$interface->assign('linkedCampaigns', $linkedCampaigns);

		$webBuilderEnabled = array_key_exists('Web Builder', $enabledModules);
		$interface->assign('webBuilderEnabled', $webBuilderEnabled);
		//Get Campaigns
		$campaignList = $campaign->getCampaigns();
		$campaignList = $this->filterRemovedCampaignsList($campaignList, $userId);
		$interface->assign('campaignList', $campaignList);

		//Get past campaigns
		$pastCampaigns = $campaign->getPastCampaigns($userId);
		$pastCampaigns = $this->filterRemovedCampaignsList($pastCampaigns, $userId);
		$interface->assign('pastCampaigns', $pastCampaigns);

		$campaignLeaderboardDisplay = $this->userLeaderboardButtonDisplay();
		$interface->assign('campaignLeaderboardDisplay', $campaignLeaderboardDisplay);

		$useCampaignLeaderboards = $this->useCampaignLeaderboards();
		$interface->assign('useCampaignLeaderboards', $useCampaignLeaderboards);

		$url = $this->getBaseUrl();
		$interface->assign('url', $url);

		$userCanAdvertise = $this->userCanAdvertise();
		$interface->assign('userCanAdvertise', $userCanAdvertise);

		$displayCampaignLeaderboard = $library->displayCampaignLeaderboard;
		$interface->assign('displayCampaignLeaderboard', $displayCampaignLeaderboard);

		$user = UserAccount::getLoggedInUser();
		$homeLibrary = $user->getHomeLibrary();
		if (!empty($homeLibrary)) {
			$displayPlaceholderImage = $homeLibrary->displayDigitalRewardOnlyWhenAwarded;
			$placeHolderImage = $homeLibrary->digitalRewardPlaceholderImage;
		} else {
			$displayPlaceholderImage = $library->displayDigitalRewardOnlyWhenAwarded;
			$placeHolderImage = $library->digitalRewardPlaceholderImage;
		}
		$interface->assign('displayPlaceholderImage', $displayPlaceholderImage);
		$interface->assign('placeholderImage', $placeHolderImage);

		$this->display('../MyAccount/myCampaigns.tpl', 'My Campaigns');
	}

	private function filterRemovedCampaigns($userCampaigns) {
		require_once ROOT_DIR . '/sys/CommunityEngagement/UserRemovedCampaign.php';
		foreach ($userCampaigns as &$userCampaign) {
			$userId = $userCampaign['linkedUserId'];
			$removedCampaignIds = UserRemovedCampaign::getRemovedCampaignIds($userId);

			if (!empty($removedCampaignIds)) {
				$userCampaign['campaigns'] = array_filter($userCampaign['campaigns'], function($campaign) use ($removedCampaignIds) {
					return !in_array($campaign['campaignId'], $removedCampaignIds);
				});

				$userCampaign['campaigns'] = array_values($userCampaign['campaigns']);
			}
		}
		return $userCampaigns;
	}

	private function filterRemovedCampaignsList($campaigns, $userId) {
		require_once ROOT_DIR . '/sys/CommunityEngagement/UserRemovedCampaign.php';

		$removedCampaignIds = UserRemovedCampaign::getRemovedCampaignIds($userId);

		if (empty($removedCampaignIds)) {
			return $campaigns;
		}

		return array_filter($campaigns, function($campaign) use ($removedCampaignIds) {
			return !in_array($campaign->id, $removedCampaignIds);
		});
	}

	function getUserId() {
		$user = UserAccount::getLoggedInUser();
		$userId = $user->id;
		return $userId;
	}

	function userLeaderboardButtonDisplay() {
		global $library;
		$user = UserAccount::getLoggedInUser();
		if ($user->getHomeLibrary() != null){
			$userLibrary = $user->getHomeLibrary();
			$campaignLeaderboardDisplay = $userLibrary->campaignLeaderboardDisplay;
		} else {
			$campaignLeaderboardDisplay = $library->campaignLeaderboardDisplay;
		}
		return $campaignLeaderboardDisplay;
	}

	function useCampaignLeaderboards() {
		global $library;
		$user = UserAccount::getLoggedInUser();
		if ($user->getHomeLibrary() != null) {
			$userLibrary = $user->getHomeLibrary();
			$useCampaignLeaderboards = $userLibrary->displayCampaignLeaderboard;
		} else {
			$useCampaignLeaderboards = $library->displayCampaignLeaderboard;
		}
		return $useCampaignLeaderboards;
	}

	public function getBaseUrl(): string {
		global $configArray;
		return $configArray['Site']['url'];
	}

	public function userCanAdvertise() {
		$user = UserAccount::getActiveUserObj();
		if ($user->isAspenAdminUser() || $user->isUserAdmin()){
			return true;
		}
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