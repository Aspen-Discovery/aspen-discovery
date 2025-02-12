<?php
require_once ROOT_DIR . '/sys/CommunityEngagement/Campaign.php';
class CommunityEngagement_Leaderboard extends Action {
    function launch() {
        global $interface;
        global $library;

        $campaign = new Campaign();
        $campaigns = $campaign->getAllCampaigns();
        $interface->assign('campaigns', $campaigns);

        $user = userAccount::getActiveUserObj();
        if ($user->getHomeLibrary() != null) {
            $campaignLeaderboardDisplay = $user->getHomeLibrary()->campaignLeaderboardDisplay;
        } else {
            $campaignLeaderboardDisplay = $library->campaignLeaderboardDisplay;
        }
        $interface->assign('campaignLeaderboardDisplay', $campaignLeaderboardDisplay);
        $this->display('leaderboard.tpl', 'Leaderboard');
    }
    //TODO:: Insert breadcrumbs
    function getBreadcrumbs(): array {
        $breadcrumbs = [];
        return $breadcrumbs;
    }
}