<?php
require_once ROOT_DIR . '/sys/CommunityEngagement/Reward.php';
require_once ROOT_DIR . '/sys/CommunityEngagement/Milestone.php';
require_once ROOT_DIR . '/sys/CommunityEngagement/CampaignMilestoneUsersProgress.php';

class CampaignMilestone extends DataObject {
	public $__table = 'ce_campaign_milestones';
	public $id;
	public $campaignId;
	public $milestoneId;
	public $goal;
	public $reward;

	public function getNumericColumnNames(): array {
		return [
			'campaignId',
			'milestoneId',
		];
	}

	static function getObjectStructure($context = '') {
		require_once ROOT_DIR . '/sys/CommunityEngagement/Milestone.php';
		$milestone = new Milestone();
		$availableMilestones = [];
		$milestone->orderBy('name');
		$milestone->find();
		while ($milestone->fetch()) {
			$availableMilestones[$milestone->id] = $milestone->name;
		}
		$goalRange = range(1, 100);
		$rewardList = Reward::getRewardList();

		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'campaignId' => [
				'property' => 'campaignId',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id of the campaign',
			],
			'milestoneId' => [
				'property' => 'milestoneId',
				'type' => 'enum',
				'label' => 'Milestone Criteria',
				'values' => $availableMilestones,
				'description' => 'The milestone to be added to the campaign',
			],
			'goal' => [
				'property' => 'goal',
				'type' => 'enum',
				'label' => 'Goal',
				'description' => 'The numerical goal for this milestone',
				'values' => array_combine($goalRange, $goalRange),
				'required' => true,
			],
			'reward' => [
				'property' => 'reward',
				'type' => 'enum',
				'label' => 'Reward',
				'description' => 'The reward given for achieving the milestone',
				'values' => $rewardList,
			],
		];
	}


	public static function getMilestoneByCampaign($campaignId) {
	  $milestones = [];
	  $campaignMilestone = new CampaignMilestone();
	  $campaignMilestone->whereAdd('campaignId = ' . $campaignId);
	  $campaignMilestone->find();

	  $milestoneIds = [];
	  $rewardMapping = [];
	  while ($campaignMilestone->fetch()) {
		$milestoneIds[] = $campaignMilestone->milestoneId;
		$rewardMapping[$campaignMilestone->milestoneId] = $campaignMilestone->reward;
	  }

	  if (!empty($milestoneIds)) {
		$milestone = new Milestone();
		$milestone->whereAddIn('id', $milestoneIds, true);
		$milestone->find();

		while ($milestone->fetch()) {
		  $milestoneObj = clone $milestone;

		  //Fetch reward name
		  $rewardId = $rewardMapping[$milestone->id] ?? null;
		  if ($rewardId) {
			$reward = new Reward();
			$reward->id = $rewardId;
			if ($reward->find(true)) {
				$milestoneObj->rewardName = $reward->name;
				$milestoneObj->displayName = $reward->displayName;
				$milestoneObj->rewardType = $reward->rewardType;
				$milestoneObj->rewardId = $reward->id;
				$milestoneObj->rewardImage = $reward->getDisplayUrl();
				if (!empty($reward->badgeImage)) {
					$milestoneObj->rewardExists = true;
				} else {
					$milestoneObj->rewardExists = false;
				}
			}
		  }
		  $milestones[] = $milestoneObj;
		}
	  }
	  return $milestones;
	}

	public static function getMilestoneGoalCountByCampaign($campaignId, $milestoneId) {

		$campaignMilestone = new CampaignMilestone();
		$campaignMilestone->whereAdd('campaignId = ' . $campaignId);
		$campaignMilestone->whereAdd('milestoneId = ' . $milestoneId);
		$campaignMilestone->find(true);

		return $campaignMilestone->goal;
	}

   public static function getMilestoneProgress($campaignId, $userId, $milestoneId) {
		$campaignMilestoneUsersProgress = new CampaignMilestoneUsersProgress();
		$campaignMilestone = new CampaignMilestone();

		//Get goal total
		$goal = $campaignMilestone->getMilestoneGoalCountByCampaign($campaignId, $milestoneId);

		//Number of completed goals for this milestone
		$userCompletedGoalCount = $campaignMilestoneUsersProgress->getProgressByMilestoneId($milestoneId, $campaignId, $userId);

        if ($goal > 0) {
           $extraProgress = 0;
            $progress = ($userCompletedGoalCount / $goal ) * 100;

           if ($progress > 100){
             $progress = 100;
             $extraProgress = ($userCompletedGoalCount /$goal) * 100;
           }
        } else {
            $progress = 0;
            $extraProgress =0;
        }
        return [
            'progress' => round($progress, 2),
            'extraProgress' => round($extraProgress, 2),
            'completed' => $userCompletedGoalCount
        ];
   }

	/**
	 * Gets a list of milestones for a given object and table name that are related to
	 * a patron enrolled in an active campaign and of type $tableName
	 *
	 * @param object $object The object to check
	 * @param string $tableName The table name to check for
	 * @param int $userId The user id of the patron
	 * @return CampaignMilestone|false Returns a Milestone object if one is found, false otherwise
	 */
	public static function getCampaignMilestonesToUpdate($object, $tableName, $userId)
	{

		# Bail if not the table we want
		if ($object->__table != $tableName)
			return false;

		# Bail if no active campaigns exist
		$activeCampaigns = Campaign::getActiveCampaignsList();
		if (!count($activeCampaigns))
			return false;

		# Bail if this object does not relate to a patron enrolled in an active campaign
		$userCampaigns = new UserCampaign();
		$userCampaigns->whereAdd("campaignId IN (" . implode(",", array_keys($activeCampaigns)) . ")");
		$userCampaigns->userId = $userId;
		if (!$userCampaigns->find())
			return false;

		# Bail if no user active campaigns' milestones are of type $tableName
		$userActiveCampaigns = [];
		while ($userCampaigns->fetch()) {
			array_push($userActiveCampaigns, $userCampaigns->campaignId);
		}
		$campaignMilestone = new CampaignMilestone();
		$campaignMilestone->milestoneType = $tableName;
		$campaignMilestone->joinAdd(new Milestone(), 'LEFT', 'milestones', 'milestoneId', 'id');
		$campaignMilestone->whereAdd('milestones.milestoneType = "' . $tableName . '" AND ce_campaign_milestones.campaignId IN (' . implode(',', $userActiveCampaigns) . ')');

		if (!$campaignMilestone->find())
			return false;

		return $campaignMilestone;
	}

	/**
	 * Adds a new CampaignMilestoneProgressEntry for a given milestone, object, and user.
	 *
	 * @param Milestone $milestone The milestone associated with this progress entry.
	 * @param mixed $object The object associated with this progress entry.
	 * @param int $userId The user id associated with this progress entry.
	 * @param string $groupedWorkId The grouped work id associated with this progress entry.
	 * 
	 */
	public function addCampaignMilestoneProgressEntry( $object, $userId, $groupedWorkId)
	{
		require_once ROOT_DIR . '/sys/CommunityEngagement/UserCampaign.php';

		if (!$this->conditionalsCheck($groupedWorkId))
			return;

		# Check if this campaign milestone already has progress for this user
		$campaignMilestoneUsersProgress = new CampaignMilestoneUsersProgress();
		$campaignMilestoneUsersProgress->ce_milestone_id = $this->milestoneId;
		$campaignMilestoneUsersProgress->ce_campaign_id = $this->campaignId;
		$campaignMilestoneUsersProgress->userId = $userId;

        # There is one, bail if goal has already been met
        if ($campaignMilestoneUsersProgress->find(true)) {
            // if ($campaignMilestoneUsersProgress->progress >= $this->goal) {
            //     $campaignMilestoneUsersProgress->extraProgress++;
            //     $campaignMilestoneUsersProgress->update();
            //     return;
            // }
            
        # There isn't one, create it.
        } else {
            $campaignMilestoneUsersProgress->progress = 0;
            $campaignMilestoneUsersProgress->extraProgress = 0;
            $campaignMilestoneUsersProgress->insert();
        }

        $campaignMilestoneProgressEntry = new CampaignMilestoneProgressEntry();
        $campaignMilestoneProgressEntry->initialize(
            $this,
            [
                "object" => $object,
                "userId" => $userId,
                "campaignMilestoneUsersProgress" => $campaignMilestoneUsersProgress
            ]
        );
        $campaignMilestoneUsersProgress->progress++;    
        $campaignMilestoneUsersProgress->update();
    }

	/**
	 * Checks if a given object meets the conditionals of this milestone.
	 *
	 * If the object does not have a groupedWorkId, it is assumed to meet the conditionals.
	 * If the milestone does not have a conditional operator, field, or value, it is assumed
	 * to meet the conditionals.
	 *
	 * Otherwise, this method uses the groupedWorkDriver to get the value of the specified
	 * field from the grouped work.  It then checks if the value matches the conditional
	 * operator and value.  If it does, it returns true.  If not, it returns false.
	 *
	 * @param string $groupedWorkId The grouped work id to check against the conditionals.
	 * @return bool True if the object meets the conditionals, false otherwise.
	 */
	protected function conditionalsCheck($groupedWorkId)
	{
		$milestone = new Milestone();
		$milestone->id = $this->milestoneId;
		if (!$milestone->find(true)){
			return false;
		}else{
			$milestone->fetch();
		}
		if (!$milestone->conditionalOperator || !$milestone->conditionalValue || !$milestone->conditionalField)
			return true;

		if (!$groupedWorkId)
			return false;
		if( $milestone->conditionalField == 'user_list' && is_numeric($milestone->conditionalValue) ) {
			return $this->conditionalsListCheck($groupedWorkId);
		}

		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		$groupedWorkDriver = new GroupedWorkDriver($groupedWorkId);

		if (!$fieldValues = $groupedWorkDriver->getSolrField($milestone->conditionalField))
			return false;

		if(!is_array($fieldValues)){
			$fieldValues = [$fieldValues];
		}

		if ($milestone->conditionalOperator == 'like') {
			#Convert this foreach to array_map
			foreach ($fieldValues as $fieldValue) {
				if (str_contains(strtolower($fieldValue), strtolower($milestone->conditionalValue))) {
					return true;
				}
			}
			return false;
		} elseif ($milestone->conditionalOperator == 'equals') {
			foreach ($fieldValues as $fieldValue) {
				if (strtolower($fieldValue) == strtolower($milestone->conditionalValue)) {
					return true;
				}
			}
			return false;
		} elseif ($milestone->conditionalOperator == 'is_not') {
			foreach ($fieldValues as $fieldValue) {
				if (strtolower($fieldValue) != strtolower($milestone->conditionalValue)) {
					return true;
				}
			}
			return false;
		}

		return false;
	}

	/**
	 * Checks if a grouped work is on a certain list.
	 *
	 * @param $groupedWorkId The grouped work object to check.
	 *
	 * @return bool true if the grouped work is on the list, false otherwise.
	 */
	protected function conditionalsListCheck($groupedWorkId){
		require_once ROOT_DIR . '/sys/UserLists/UserListEntry.php';
		$milestone = new Milestone();
		$milestone->id = $this->milestoneId;
		if ($milestone->find(true)) {
			$listEntry = new UserListEntry();
			$listEntry->whereAdd("source ='GroupedWork'");
			$listEntry->whereAdd("sourceId ='" . $groupedWorkId . "'");
			$whereOp = $milestone->conditionalOperator == 'is_not' ? '!=' : '=';
			$listEntry->whereAdd('listId '.$whereOp. ' ' . $milestone->conditionalValue);
			return $listEntry->find(true);
		}
		return false;
	}
}