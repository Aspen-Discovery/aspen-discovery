<?php /** @noinspection PhpMissingFieldTypeInspection */

class CampaignMilestoneUsersProgress extends DataObject
{
	public $__table = 'ce_campaign_milestone_users_progress';
	public $id;
	public $userId;
	// REMOVE: public $ce_campaign_id;
    // REMOVE: public $ce_milestone_id;
    public $ce_campaign_milestone_id; // NEW: Points to junction table ID
	public $progress;
	public $rewardGiven;
	public $milestoneCompleteEmailSent;

	public static function getProgressByCampaignMilestoneId($campaignMilestoneId, $userId) : int {
		$campaignMilestoneProgress = new CampaignMilestoneUsersProgress();

		// $milestoneProgress->whereAdd('ce_milestone_id = ' . intval($milestoneId));
		// $milestoneProgress->whereAdd('ce_campaign_id = ' . intval($campaignId));
		$campaignMilestoneProgress->whereAdd('ce_campaign_milestone_id = ' . intval($campaignMilestoneId));
		$campaignMilestoneProgress->whereAdd('userId = ' . intval($userId));
		$campaignMilestoneProgress->find(true);

        return $campaignMilestoneProgress->progress ? $campaignMilestoneProgress->progress : 0;
    }


	public static function getRewardGivenForCampaignMilestone($campaignMilestoneId, $userId) {
		$campaignMilestoneProgress = new CampaignMilestoneUsersProgress();
		$campaignMilestoneProgress->ce_campaign_milestone_id = $campaignMilestoneId;
		$campaignMilestoneProgress->userId = $userId;

		if ($campaignMilestoneProgress->find(true)) {
			return $campaignMilestoneProgress->rewardGiven;
		}
		return false;
	} 
}