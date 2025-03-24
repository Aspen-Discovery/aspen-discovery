<?php

class UserCompletedMilestone extends DataObject {
	public $__table = 'ce_user_completed_milestones';
	public $id;
	public $userId;
	public $milestoneId;
	public $campaignId;
	public $completedAt;

	public static function getObjectStructure($context = ''): array {
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
	  
	}

	public static function getCompletedMilestones($userId, $campaignId) {
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