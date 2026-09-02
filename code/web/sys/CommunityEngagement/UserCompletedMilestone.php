<?php /** @noinspection PhpMissingFieldTypeInspection */

class UserCompletedMilestone extends DataObject {
	public $__table = 'ce_user_completed_milestones';
	public $id;
	public $userId;
	/** @noinspection PhpUnused */
	public $milestoneId;
	public $campaignId;
	/** @noinspection PhpUnused */
	public $completedAt;

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context])) {
			return self::$_objectStructure[$context];
		}
		$structure =  [
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
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id of the milestone',
			],
			'userId' => [
				'property' => 'userId',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id of the user',
			],
			'completedAt' => [
				'property' => 'completedAt',
				'type' => 'date',
				'label' => 'Enrollment Date',
				'description' => 'The Date of Enrollment',
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	  
	}

	public static function getCompletedMilestones($userId, $campaignId) : array {
		$completedMilestone = new UserCompletedMilestone();
		$completedMilestone->userId = $userId;
		$completedMilestone->campaignId = $campaignId;

		$completedMilestones = [];
		$completedMilestone->find();
		while ($completedMilestone->fetch()) {
			$completedMilestones[] = clone($completedMilestone);
		}
		return $completedMilestones;
	}
}