<?php

class TimeToReshelve extends DataObject {
	public $__table = 'time_to_reshelve';    // table name

	public $id;
	public $weight;
	public $indexingProfileId;
	public $locations;
	public /** @noinspection PhpUnused */
		$numHoursToOverride;
	public $status;
	public /** @noinspection PhpUnused */
		$groupedStatus;

	static function getObjectStructure(): array {
		$indexingProfiles = [];
		require_once ROOT_DIR . '/sys/Indexing/IndexingProfile.php';
		$indexingProfile = new IndexingProfile();
		$indexingProfile->orderBy('name');
		$indexingProfile->find();
		while ($indexingProfile->fetch()) {
			$indexingProfiles[$indexingProfile->id] = $indexingProfile->name;
		}
		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id within the database',
			],
			'indexingProfileId' => [
				'property' => 'indexingProfileId',
				'type' => 'enum',
				'values' => $indexingProfiles,
				'label' => 'Indexing Profile Id',
				'description' => 'The Indexing Profile this map is associated with',
			],
			'locations' => [
				'property' => 'locations',
				'type' => 'text',
				'label' => 'Locations',
				'description' => 'The locations to apply this rule to',
				'maxLength' => '100',
				'required' => true,
				'forcesReindex' => true,
			],
			'numHoursToOverride' => [
				'property' => 'numHoursToOverride',
				'type' => 'integer',
				'label' => 'Num. Hours to Override',
				'description' => 'The number of hours that this override should be applied',
				'required' => true,
				'forcesReindex' => true,
			],
			'status' => [
				'property' => 'status',
				'type' => 'text',
				'label' => 'Status',
				'description' => 'The Status to display to the user in full record/copies',
				'hideInLists' => false,
				'default' => false,
				'forcesReindex' => true,
			],
			'groupedStatus' => [
				'property' => 'groupedStatus',
				'type' => 'enum',
				'values' => [
					'Currently Unavailable' => 'Currently Unavailable',
					'On Order' => 'On Order',
					'Coming Soon' => 'Coming Soon',
					'In Processing' => 'In Processing',
					'Checked Out' => 'Checked Out',
					'Library Use Only' => 'Library Use Only',
					'Available Online' => 'Available Online',
					'In Transit' => 'In Transit',
					'On Shelf' => 'On Shelf',
				],
				'label' => 'Grouped Status',
				'description' => 'The Status to display to the when grouping multiple copies',
				'hideInLists' => false,
				'default' => false,
				'forcesReindex' => true,
			],
		];
	}
}