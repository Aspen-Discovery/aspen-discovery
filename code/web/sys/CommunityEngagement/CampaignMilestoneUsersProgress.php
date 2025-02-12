<?php
class CampaignMilestoneUsersProgress extends DataObject
{
	public $__table = 'ce_campaign_milestone_users_progress';
	public $id;
	public $userId;
	public $ce_campaign_id;
	public $ce_milestone_id;
	public $progress;
	public $rewardGiven;

	public static function getProgressByMilestoneId($milestoneId, $campaignId, $userId) {
		$milestoneProgress = new Self();

		$milestoneProgress->whereAdd('ce_milestone_id = ' . intval($milestoneId));
		$milestoneProgress->whereAdd('ce_campaign_id = ' . intval($campaignId));
		$milestoneProgress->whereAdd('userId = ' . intval($userId));
		$milestoneProgress->find(true);

        return $milestoneProgress->progress ? $milestoneProgress->progress : 0;
    }


	public static function getRewardGivenForMilestone($milestoneId, $userId, $campaignId) {
		$progress = new CampaignMilestoneUsersProgress();
		$progress->ce_milestone_id = $milestoneId;
		$progress->userId = $userId;
		$progress->ce_campaign_id = $campaignId;

		if ($progress->find(true)) {
			return $progress->rewardGiven;
		}
		return false;
	} 
}