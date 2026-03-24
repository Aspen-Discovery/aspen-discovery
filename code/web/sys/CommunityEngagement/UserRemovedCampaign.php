<?php /** @noinspection PhpMissingFieldTypeInspection */

class UserRemovedCampaign extends DataObject {
	public $__table = 'user_removed_campaigns';
	public $id;
	public $userId;
	public $campaignId;

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context]) && self::$_objectStructure[$context] !== null) {
			return self::$_objectStructure[$context];
		}
		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'userId' => [
				'property' => 'userId',
				'type' => 'label',
				'label' => 'User ID',
				'description' => 'The id of the user',
			],
			'campaignId' => [
				'property' => 'campaignId',
				'type' => 'label',
				'label' => 'Campaign ID',
				'description' => 'The ID odf the campaign to be hidden',
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	public static function getRemovedCampaignIds($userId): array {
		$removed = new UserRemovedCampaign();
		$removed->userId = $userId;
		$campaignIds = [];
		if ($removed->find()) {
			while ($removed->fetch()) {
				$campaignIds[] = $removed->campaignId;
			}
		}
		return $campaignIds;
	}
}