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

        $linkedCampaigns = $campaign->getLinkedUserCampaigns($userId);
        $interface->assign('linkedCampaigns', $linkedCampaigns);

		//Get Campaigns
		$campaignList = $campaign->getCampaigns();
		$interface->assign('campaignList', $campaignList);

		//Get past campaigns
		$pastCampaigns = $campaign->getPastCampaigns($userId);
		$interface->assign('pastCampaigns', $pastCampaigns);

        $campaignLeaderboardDisplay = $this->userLeaderboardButtonDisplay();
        $interface->assign('campaignLeaderboardDisplay', $campaignLeaderboardDisplay);

        $url = $this->getBaseUrl();
        $interface->assign('url', $url);

        $userCanAdvertise = $this->userCanAdvertise();
        $interface->assign('userCanAdvertise', $userCanAdvertise);

		$this->display('../MyAccount/myCampaigns.tpl', 'My Campaigns');
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