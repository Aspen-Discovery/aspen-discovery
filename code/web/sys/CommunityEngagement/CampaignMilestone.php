<?php /** @noinspection PhpMissingFieldTypeInspection */
require_once ROOT_DIR . '/sys/CommunityEngagement/Reward.php';
require_once ROOT_DIR . '/sys/CommunityEngagement/Milestone.php';
require_once ROOT_DIR . '/sys/CommunityEngagement/CampaignMilestoneUsersProgress.php';
require_once ROOT_DIR . '/sys/CommunityEngagement/CampaignMilestoneProgressEntry.php';

class CampaignMilestone extends DataObject {
	public $__table = 'ce_campaign_milestones';
	public $id;
	public $campaignId;
	public $milestoneId;
	public $goal;
	public $reward;
	public $weight;

	public function getNumericColumnNames(): array {
		return [
			'campaignId',
			'milestoneId',
			'weight'
		];
	}

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context]) && self::$_objectStructure[$context] !== null) {
			return self::$_objectStructure[$context];
		}
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

		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'weight' => [
				'property' => 'weight',
				'type' => 'numeric',
				'label' => 'Weight',
				'weight' => 'Defines how items are sorted.  Lower weights are displayed higher.',
				'required' => true,
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

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}


	/**
     * Fetch all campaign milestones for a given campaign,
     * augmented with milestone and reward data.
     * @param int $campaignId
     * @return array Array of CampaignMilestone objects
     */
    public static function getCampaignMilestoneByCampaign($campaignId) {
        global $activeLanguageCode;

        $campaignMilestones = [];
        $campaignMilestone = new CampaignMilestone();
        $campaignMilestone->campaignId = $campaignId;
        $campaignMilestone->find();

        while ($campaignMilestone->fetch()) {
            // This is the specific record from ce_campaign_milestones
            $campaignMilestoneObj = clone $campaignMilestone;

            // 1. Augment with the actual Milestone data
            $milestone = new Milestone();
            $milestone->id = $campaignMilestoneObj->milestoneId;

            if ($milestone->find(true)) {
                // Inject properties into the campaignMilestoneObj
                $campaignMilestoneObj->name = $milestone->name;
                $campaignMilestoneObj->description = $milestone->description;
                $campaignMilestoneObj->milestoneType = $milestone->milestoneType;
            }

            // 2. Augment with the related Reward data
            if (!empty($campaignMilestoneObj->reward)) {
                require_once ROOT_DIR . '/sys/CommunityEngagement/Reward.php';
                $reward = new Reward();
                $reward->id = $campaignMilestoneObj->reward;

                if ($reward->find(true)) {
                    $campaignMilestoneObj->rewardName = $reward->name;
                    $campaignMilestoneObj->rewardId = $reward->id;
                    $campaignMilestoneObj->rewardImage = $reward->getDisplayUrl();
                    $campaignMilestoneObj->rewardExists = !empty($reward->badgeImage);
                    $campaignMilestoneObj->rewardDescription = $reward->getTextBlockTranslation('description', $activeLanguageCode);

                    // Maintain standard attributes for the UI
                    $campaignMilestoneObj->displayName = $reward->displayName;
                    $campaignMilestoneObj->rewardType = $reward->rewardType;
                    $campaignMilestoneObj->awardAutomatically = $reward->awardAutomatically;
                }
            } else {
                $campaignMilestoneObj->rewardName = '';
                $campaignMilestoneObj->rewardExists = false;
            }

            $campaignMilestones[] = $campaignMilestoneObj;
        }

        return $campaignMilestones;
    }

	public static function getCampaignMilestoneGoalCountByCampaign($campaignMilestoneId) {

		$campaignMilestone = new CampaignMilestone();
		$campaignMilestone->id = $campaignMilestoneId;
		$campaignMilestone->find(true);

		return $campaignMilestone->goal;
	}

    public static function getCampaignMilestoneProgress($campaignMilestoneId, $userId) {
		$campaignMilestone = new CampaignMilestone();

		//Get goal total
		$goal = $campaignMilestone->getCampaignMilestoneGoalCountByCampaign($campaignMilestoneId);

		//Number of completed goals for this milestone
		$userCompletedGoalCount = CampaignMilestoneUsersProgress::getProgressByCampaignMilestoneId($campaignMilestoneId, $userId);

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
	private static function getCampaignMilestonesToUpdate($object, $tableName, $userId)
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
		$campaignMilestoneUsersProgress->ce_campaign_milestone_id = $this->id;
		$campaignMilestoneUsersProgress->userId = $userId;

		# Create campaign milestone if doesn't exist yet
		if (!$campaignMilestoneUsersProgress->find(true)) {
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
		if( $milestone->conditionalField == 'user_list') {
			$listIds = explode('|', $milestone->conditionalValue);
			$valid = array_filter($listIds, 'is_numeric');
			if (count($valid) > 0) {
				return $this->conditionalsListCheck($groupedWorkId);
			}
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
	 * @param string $groupedWorkId The grouped work object to check.
	 *
	 * @return bool true if the grouped work is on the list, false otherwise.
	 */
	protected function conditionalsListCheck($groupedWorkId){
		require_once ROOT_DIR . '/sys/UserLists/UserListEntry.php';
		$milestone = new Milestone();
		$milestone->id = $this->milestoneId;
	
		if (!$milestone->find(true)) {
			return false;
		}
	
		$listIds = explode('|', $milestone->conditionalValue);
		$validListIds = array_filter(array_map('trim', $listIds), 'is_numeric');
	
		if (empty($validListIds)) {
			return false;
		}
	
		$listEntry = new UserListEntry();
		$listEntry->whereAdd("source ='GroupedWork'");
		$listEntry->whereAdd("sourceId ='" . $groupedWorkId . "'");
		$listEntry->find();
	
		$matchedListIds = [];
		while ($listEntry->fetch()) {
			$matchedListIds[] = $listEntry->listId;
		}
	
		if ($milestone->conditionalOperator == 'equals') {
			foreach ($validListIds as $listId) {
				if (in_array($listId, $matchedListIds)) {
					return true;
				}
			}
			return false;
		}
	
		if ($milestone->conditionalOperator == 'is_not') {
			foreach ($validListIds as $listId) {
				if (in_array($listId, $matchedListIds)) {
					return false;
				}
			}
			return true;
		}
		return false;
	}

	/**
	 * Process campaign milestone for a given object and date
	 *
	 * @param mixed $value The object being processed
	 * @param string $objectType The type of object ('user_checkout', 'user_hold', 'user_work_review')
	 * @param int $userId The user ID
	 * @param int $date The date to check (unix timestamp)
	 * @param mixed $groupedId The grouped work/record ID (optional)
	 */
	public static function processCampaignMilestoneProgress($value, $objectType, $userId, $date, $groupedId = null) {
		$campaignMilestone = self::getCampaignMilestonesToUpdate($value, $objectType, $userId);

		if (!$campaignMilestone) {
			return;
		}

		while ($campaignMilestone->fetch()) {
			$campaign = new Campaign();
			$campaign->id = $campaignMilestone->campaignId;
			if (!$campaign->find(true)) {
				continue;
			}

			if (!self::_isDateWithinCampaignPeriod($date, $campaign)) {
				continue;
			}

			if (self::_campaignMilestoneProgressEntryObjectAlreadyExists($value, $campaignMilestone)) {
				continue;
			}

			$milestone = new Milestone();
			$milestone->id = $campaignMilestone->milestoneId;
			$milestone->find(true);

			if (!$milestone->progressBeyondOneHundredPercent) {
				$currentProgress = new CampaignMilestoneUsersProgress();
				$currentProgress->userId = $userId;
				$currentProgress->ce_campaign_milestone_id = $campaignMilestone->id;

				if ($currentProgress->find(true)) {
					if ($currentProgress->progress >= $campaignMilestone->goal) {
						continue;
					}
				}
			}

			$campaignMilestone->addCampaignMilestoneProgressEntry($value, $userId, $groupedId);

			$userCampaign = new UserCampaign();
			$userCampaign->userId = $userId;
			$userCampaign->campaignId = $campaignMilestone->campaignId;
			$userCampaign->checkAndHandleCampaignCompletion($userId, $campaignMilestone->campaignId);
		}
	}

	/**
	 * Checks if an object entry already exists in the ce_milestone_progress_entries table, for a specific milestone.
	 * This check is required because a some objects being added to the database may not actually be a instance.
	 * For example, for checkouts and holds, these may be purged from the database and re-fetched from the ILS.
	 *
	 * @param object $value The object containing the sourceId, recordId, and userId.
	 * @param CampaignMilestone $campaignMilestone The milestone object.
	 * @return bool Returns true if an entry exists, false otherwise.
	 */
	private static function _campaignMilestoneProgressEntryObjectAlreadyExists($value, $campaignMilestone) {
		$campaignMilestoneProgressEntryCheck = new CampaignMilestoneProgressEntry();
		$campaignMilestoneProgressEntryCheck->initialize($campaignMilestone);
		if ($campaignMilestoneProgressEntryCheck->find()) {
			while ($campaignMilestoneProgressEntryCheck->fetch()) {
				$decoded_object = json_decode($campaignMilestoneProgressEntryCheck->object);
				if ($value instanceof UserWorkReview) {
					if ($decoded_object->groupedRecordPermanentId == $value->groupedRecordPermanentId && $decoded_object->userId == $value->userId) {
						return true;
					}
				}else{
					if ($decoded_object->sourceId == $value->sourceId && $decoded_object->recordId == $value->recordId && $decoded_object->userId == $value->userId) {
						return true;
					}
				}
			}
		}
		return false;
	}

	/**
	 * Check if a date falls within the campaign period
	 *
	 * @param int $date The date to check (unix timestamp)
	 * @param Campaign $campaign The campaign object
	 * @return bool True if date is within campaign period, false otherwise
	 */
	private static function _isDateWithinCampaignPeriod($date, $campaign) {
		$campaignStartDate = strtotime($campaign->startDate);
		$campaignEndDate = strtotime($campaign->endDate);

		return $date >= $campaignStartDate && $date <= $campaignEndDate;
	}

}