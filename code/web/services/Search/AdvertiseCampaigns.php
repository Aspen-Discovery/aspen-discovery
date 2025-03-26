<?php
require_once 'Action.php';
require_once ROOT_DIR . '/sys/CommunityEngagement/Campaign.php';

class AdvertiseCampaigns extends Action {
    function launch() {
        global $interface;
        global $activeLanguageCode;
        $campaignId = $_GET['campaignId'];
        $interface->assign('campaignId', $campaignId);

        $campaign = new Campaign();
        // $campaignList = $campaign->getCampaigns();
        $campaign->id = $campaignId;
        if ($campaign->find(true)) {
            $campaignName = $campaign->name;
            $campaignDescription = $campaign->getTextBlockTranslation('description', $activeLanguageCode);
            $campaignReward = new Reward();
            $campaignReward->id = $campaign->campaignReward;
            if ($campaignReward->find(true)) {
                $campaignRewardName = $campaignReward->name;
                $campaignRewardImage = $campaignReward->getDisplayUrl();
                $campaignRewardType = $campaignReward->rewardType;
                $campaignRewardExists = !empty($campaignReward->badgeImage);
            }
            $campaignMilestones = CampaignMilestone::getMilestoneByCampaign($campaignId);

        }

        $interface->assign('campaignName', $campaignName);
        $interface->assign('campaignDescription', $campaignDescription);
        $interface->assign('campaignRewardName', $campaignRewardName);
        $interface->assign('campaignMilestones', $campaignMilestones);
        $interface->assign('campaignRewardImage', $campaignRewardImage);
        $interface->assign('campaignRewardType', $campaignRewardType);
        $interface->assign('campaignRewardExists', $campaignRewardExists);

        $this->display('/advertise-campaigns.tpl', 'Advertise Campaigns');
    }

    function getBreadcrumbs(): array {
        $breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#communityEngagement', 'Community Engagement');
		return $breadcrumbs;
    }
}