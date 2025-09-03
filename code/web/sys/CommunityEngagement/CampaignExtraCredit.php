<?php /** @noinspection PhpMissingFieldTypeInspection */
require_once ROOT_DIR . '/sys/CommunityEngagement/Reward.php';
require_once ROOT_DIR . '/sys/CommunityEngagement/ExtraCredit.php';
require_once ROOT_DIR . '/sys/CommunityEngagement/CampaignExtraCreditActivityUsersProgress.php';

class CampaignExtraCredit extends DataObject {
	public $__table = 'ce_campaign_extra_credit';
	public $id;
	public $campaignId;
	public $extraCreditId;
	public $goal;
	public $reward;
	public $weight;

	public function getNumericColumnNames(): array {
		return [
			'campaignId',
			'extraCreditId',
		];
	}

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context]) && self::$_objectStructure[$context] !== null) {
			return self::$_objectStructure[$context];
		}
		require_once ROOT_DIR . '/sys/CommunityEngagement/ExtraCredit.php';
		$extraCredit = new ExtraCredit();
		$availableExtraCreditActivities = [];
		$extraCredit->orderBy('name');
		$extraCredit->find();
		while ($extraCredit->fetch()) {
			$availableExtraCreditActivities[$extraCredit->id] = $extraCredit->name;
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
				'weight' => 'Defines how items are sorted. Lower weights are displayed higher.',
				'required' => true,
			],
			'campaignId' => [
				'property' => 'campaignId',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id of the campaign',
			],
			'extraCreditId' => [
				'property' => 'extraCreditId',
				'type' => 'enum',
				'label' => 'Extra Credit Criteria',
				'values' => $availableExtraCreditActivities,
				'description' => 'The extra credit activity to be added to the campaign',
			],
			'goal' => [
				'property' => 'goal',
				'type' => 'enum',
				'label' => 'Goal',
				'description' => 'The numerical goal for this activity',
				'values' => array_combine($goalRange, $goalRange),
				'required' => true,
			],
			'reward' => [
				'property' => 'reward',
				'type' => 'enum',
				'label' => 'Reward',
				'description' => 'The reward given for completing the activity',
				'values' => $rewardList,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	public static function getExtraCreditByCampaign($campaignId, $userId = null) {
		$extraCreditActivities = [];
		$campaignExtraCredit = new CampaignExtraCredit();
		$campaignExtraCredit->whereAdd('campaignId = ' . $campaignId);
		$campaignExtraCredit->find();

		$extraCreditIds = [];
		$rewardMapping = [];
		$goalMappaing = [];

		while ($campaignExtraCredit->fetch()) {
			$extraCreditIds[] = $campaignExtraCredit->extraCreditId;
			$rewardMapping[$campaignExtraCredit->extraCreditId] = $campaignExtraCredit->reward;
			$goalMapping[$campaignExtraCredit->extraCreditId] = $campaignExtraCredit->goal;
		}

		if (!empty($extraCreditIds)) {
			$extraCredit = new ExtraCredit();
			$extraCredit->whereAddIn('id', $extraCreditIds, true);
			$extraCredit->find();

			while ($extraCredit->fetch()) {
				$extraCreditObj = clone $extraCredit;

				if ($userId) {
					$userProgress = CampaignExtraCreditActivityUsersProgress::getProgressByExtraCreditId($extraCredit->id, $campaignId, $userId);
					$rewardGiven = CampaignExtraCreditActivityUsersProgress::getRewardGivenForExtraCreditActivity($extraCredit->id, $userId, $campaignId);
					$extraCreditObj->completedGoals = $userProgress;
					$extraCreditObj->totalGoals = $goalMapping[$extraCredit->id] ?? 1;
					$extraCreditObj->rewardGiven = $rewardGiven;
				}

				$rewardId = $rewardMapping[$extraCredit->id] ?? null;
				if ($rewardId) {
					$reward = new Reward();
					$reward->id = $rewardId;
					if ($reward->find(true)) {
						$extraCreditObj->rewardName = $reward->name;
						$extraCreditObj->displayName = $reward->displayName;
						$extraCreditObj->rewardType = $reward->rewardType;
						$extraCreditObj->rewardId = $reward->id;
						$extraCreditObj->awardAutomatically = $reward->awardAutomatically;
						$extraCreditObj->rewardImage = $reward->getDisplayUrl();
						if (!empty($reward->badgeImage)) {
							$extraCreditObj->rewardExists = true;
						} else {
							$extraCreditObj->rewardExists = false;
						}
					}
				}
				$extraCreditActivities[] = $extraCreditObj;
			}
		}
		return $extraCreditActivities;
	}

	public static function getExtraCreditGoalCountByCampaign($campaignId, $extraCreditId) {
		$campaignExtraCredit = new CampaignExtraCredit();
		$campaignExtraCredit->whereAdd('campaignId = ' . $campaignId);
		$campaignExtraCredit->whereAdd('extraCreditId = ' . $extraCreditId);
		$campaignExtraCredit->find(true);

		return $campaignExtraCredit->goal;
	}

	public static function getExtraCreditActivityProgress($campaignId, $userId, $extraCreditId) {
		$campaignExtraCreditActivityUsersProgress = new CampaignExtraCreditActivityUsersProgress();
		$campaignExtraCredit = new CampaignExtraCredit();

		$goal = $campaignExtraCredit->getExtraCreditGoalCountByCampaign($campaignId, $extraCreditId);

		$userCompletedGoalCount = $campaignExtraCreditActivityUsersProgress->getProgressByExtraCreditId($extraCreditId, $campaignId, $userId);

		if ($goal > 0) {
			$progress = ($userCompletedGoalCount / $goal) * 100;
		} else {
			$progress = 0;
		}
		return [
			'progress' => round($progress, 2),
			'completed' => $userCompletedGoalCount
		];
	}
}