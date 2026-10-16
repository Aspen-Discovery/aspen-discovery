<?php /** @noinspection PhpMissingFieldTypeInspection */

class NonGroupedRecord extends DataObject {
	public $__table = 'nongrouped_records';
	public $id;
	public $source;
	public $recordId;
	public $notes;

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context])) {
			return self::$_objectStructure[$context];
		}

		global $indexingProfiles;
		global $sideLoadSettings;
		$availableSources = [];
		foreach ($indexingProfiles as $profile) {
			$availableSources[$profile->name] = $profile->name;
		}
		foreach ($sideLoadSettings as $profile) {
			$availableSources[$profile->name] = $profile->name;
		}
		$availableSources['axis360'] = 'Boundless';
		$availableSources['cloud_library'] = 'cloudLibrary';
		$availableSources['hoopla'] = 'Hoopla';
		$availableSources['overdrive'] = 'Overdrive';
		$availableSources['palace_project'] = 'Palace Project';

		$structure =  [
			[
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id of the merged grouped work in the database',
				'storeDb' => true,
			],
			[
				'property' => 'source',
				'type' => 'enum',
				'values' => $availableSources,
				'label' => 'Source of the Record Id',
				'description' => 'The source of the record to avoid merging.',
				'default' => 'ils',
				'storeDb' => true,
				'required' => true,
			],
			[
				'property' => 'recordId',
				'type' => 'text',
				'size' => 36,
				'maxLength' => 36,
				'label' => 'Record Id',
				'description' => 'The id of the record that should not be merged.',
				'storeDb' => true,
				'required' => true,
			],
			[
				'property' => 'notes',
				'type' => 'text',
				'size' => 255,
				'maxLength' => 255,
				'label' => 'Notes',
				'description' => 'Notes related to the record.',
				'storeDb' => true,
				'required' => true,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	public function insert(string $context = ''): int|bool {
		if ($this->isRecordInManuallyGroupedWork()) {
			$this->setLastError("Cannot mark record '$this->recordId' from source '$this->source' as non-grouped because it is part of a manually grouped work. Edit the manually grouped work directly to remove records.");
			return false;
		}
		return parent::insert();
	}

	public function update(string $context = ''): int|bool {
		if ($this->isRecordInManuallyGroupedWork()) {
			$this->setLastError("Cannot mark record '$this->recordId' from source '$this->source' as non-grouped because it is part of a manually grouped work. Edit the manually grouped work directly to remove records.");
			return false;
		}
		return parent::update();
	}

	/**
	 * Check if this record is part of a manually grouped work.
	 *
	 * @return bool
	 */
	private function isRecordInManuallyGroupedWork(): bool {
		if (empty($this->source) || empty($this->recordId)) {
			return false;
		}

		require_once ROOT_DIR . '/sys/Grouping/ManuallyGroupedWorkRecord.php';
		$manualRecord = new ManuallyGroupedWorkRecord();
		$manualRecord->type = $this->source;
		$manualRecord->identifier = $this->recordId;
		return $manualRecord->find(true) !== false;
	}

}