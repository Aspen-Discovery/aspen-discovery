<?php /** @noinspection PhpMissingFieldTypeInspection */

class OptionalUpdate extends DataObject {
	public $__table = 'optional_updates';
	public $id;
	public $name;
	public $descriptionFile;
	/** @noinspection PhpUnused */
	public $versionIntroduced;
	public $status;

	public function getNumericColumnNames(): array {
		return ['status'];
	}

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context])) {
			return self::$_objectStructure[$context];
		}
		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'name' => [
				'property' => 'name',
				'type' => 'label',
				'label' => 'Name',
				'description' => 'The name of the update',
				'canBatchUpdate' => false,
				'readOnly' => true,
			],
			'descriptionFile' => [
				'property' => 'descriptionFile',
				'type' => 'label',
				'label' => 'Description File',
				'description' => 'The file where the description for the update to be applied',
				'readOnly' => true
			],
			'versionIntroduced' => [
				'property' => 'versionIntroduced',
				'type' => 'label',
				'label' => 'Version Introduced',
				'description' => 'The version the update was introduced',
			],
			'status' => [
				'property' => 'status',
				'type' => 'enum',
				'values' => [
					1 => 'Un-applied',
					2 => 'Applied',
					3 => 'Skipped'
				],
				'label' => 'Status',
				'description' => 'The status of the update',
				'readOnly' => true,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	public function getDescription() : string {
		$optionalUpdatesPath = ROOT_DIR . '/optionalUpdates';
		require_once ROOT_DIR . '/sys/Parsedown/AspenParsedown.php';
		$parsedown = AspenParsedown::instance();
		$descriptionFilePath = $optionalUpdatesPath . '/' . $this->descriptionFile;
		if (!file_exists($descriptionFilePath)) {
			return 'Unable to find description for ' . $this->name;
		}else {
			return $parsedown->parse(file_get_contents($descriptionFilePath));
		}
	}

	public function applyUpdate() : void {
		global $aspen_db;
		if ($this->name == 'moveSearchToolsToTop') {
			/** @noinspection SqlWithoutWhere */
			$aspen_db->exec('UPDATE grouped_work_display_settings set showSearchToolsAtTop=1');
		}elseif ($this->name == 'useFloatingCoverStyle') {
			/** @noinspection SqlWithoutWhere */
			$aspen_db->exec("UPDATE themes set coverStyle='floating'");
		}elseif ($this->name == 'displayCoversForEditions') {
			/** @noinspection SqlWithoutWhere */
			$aspen_db->exec("UPDATE grouped_work_display_settings set showEditionCovers=1");
		}elseif ($this->name == 'enableNewBadge') {
			/** @noinspection SqlWithoutWhere */
			$aspen_db->exec("UPDATE grouped_work_display_settings set alwaysFlagNewTitles=1");
		}
		$this->status = 2;
		$this->update();
	}
}