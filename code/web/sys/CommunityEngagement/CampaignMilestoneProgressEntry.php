<?php /** @noinspection PhpMissingFieldTypeInspection */
require_once ROOT_DIR . '/sys/CommunityEngagement/Milestone.php';

class CampaignMilestoneProgressEntry extends DataObject
{
	public $__table = 'ce_campaign_milestone_progress_entries';
	public $id;
	public $userId;
	// REMOVE: public $ce_campaign_id;
    // REMOVE: public $ce_milestone_id;
    public $ce_campaign_milestone_id; // NEW
	public $ce_campaign_milestone_users_progress_id;
	public $tableName;
	public $processed;
	public $object;
	public $timestamp;

	/**
	 * Initializes a new CampaignMilestoneProgressEntry object by setting its ce_milestone_id to the provided milestone object
	 *
	 * @param CampaignMilestone $campaignMilestone The campaign milestone associated with this progress entry.
	 * @param mixed $args Optional arguments to further configure the progress entry. Expects the following structure:
	 * 
	 *  [
	 *     "object"                         => An object param, may be of any of the allowed milestone types e.g. 'Checkout', 'Hold', etc.
	 *     "userId"                         => A user id param
	 *     "campaignMilestoneUsersProgress" => A CampaignMilestoneUsersProgress object param to be associated with this progress entry
	 *  ]
	 * 
	 * @return void
	 */
	public function initialize(CampaignMilestone $campaignMilestone, $args = null) : void
	{

		// $this->ce_milestone_id = $campaignMilestone->milestoneId;
		// $this->ce_campaign_id = $campaignMilestone->campaignId;
		$this->ce_campaign_milestone_id = $campaignMilestone->id;

		if (!$args)
			return;

		$this->userId = $args['userId'];
		$this->ce_campaign_milestone_users_progress_id = $args['campaignMilestoneUsersProgress']->id;
		$this->processed = 0;
		if($args['object']){
			$this->tableName = $args['object']->__table;
			$this->object = json_encode($args['object']);
		}
		$this->insert();
	}

	//TODO: Update all instances of getUserProgressDataByMilestoneId($userId, $milestoneId, $campaignId) to this
	public static function getUserProgressDataByCampaignMilestoneId($userId, $campaignMilestoneId) {
		$campaignMilestoneProgressEntry = new self();
		$campaignMilestoneProgressEntry->whereAdd("userId = " . $userId);
		$campaignMilestoneProgressEntry->whereAdd("ce_campaign_milestone_id = " . $campaignMilestoneId);


		$results = [];
		if ($campaignMilestoneProgressEntry->find()) {
			$decodedObject = [];
			while ($campaignMilestoneProgressEntry->fetch()) {
				$decodedObject = json_decode($campaignMilestoneProgressEntry->object, true);
				if ($decodedObject) {
					$results[$campaignMilestoneProgressEntry->id] = $decodedObject;
				}
			}
		}
		return $results;
	}
}