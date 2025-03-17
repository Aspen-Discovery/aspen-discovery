<?php
require_once 'Action.php';

class ShareCampaigns extends Action {
    function launch() {
        global $interface;
        $rewardName = $_GET['rewardName'];
        $rewardImage = $_GET['rewardImage'];
        $rewardId = $_GET['rewardId'];

        $og_image = $rewardImage . '&id=' . $rewardId;
        $og_title = $rewardName;
        $og_type = "website";
        $og_description = "My Latest Reward";
        $twitter_card = "summary_large_image";
        $twitter_title = $rewardName;
        $twitter_image = $rewardImage . '&id=' . $rewardId;
        

        $interface->assign('rewardImage', $rewardImage);
        $interface->assign('rewardName', $rewardName);
        $interface->assign('og_image', $og_image);
        $interface->assign('og_title', $og_title);
        $interface->assign('og_description', $og_description);
        $interface->assign('twitter_card', $twitter_card);
        $interface->assign('twitter_title', $twitter_title);
        $interface->assign('twitter_image', $twitter_image);
        $interface->assign('og_type', $og_type);

        $this->display('/share-campaigns.tpl', 'Share Campaigns');
    }

    function getBreadcrumbs(): array {
        $breadcrumbs = [];
        if (UserAccount::isLoggedIn()) {
			$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'Your Account');
			$breadcrumbs[] = new Breadcrumb('/MyAccount/MyCampaigns', 'Your Campaigns');
		}
		return $breadcrumbs;
    }
}