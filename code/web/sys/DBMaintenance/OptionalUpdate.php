<?php

class OptionalUpdate extends DataObject {
	public $__table = 'optional_updates';
	public $id;
	public $name;
	public $descriptionFile;
	public $versionIntroduced;
	public $status;

	static function getObjectStructure($context = ''): array {
		return [
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
				'description' => 'The versioni the update was introduced',
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
	}

	public function getDescription() {
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

	public function applyUpdate() {
		global $aspen_db;
		if ($this->name == 'moveSearchToolsToTop') {
			$aspen_db->exec('UPDATE grouped_work_display_settings set showSearchToolsAtTop=1');
		}elseif ($this->name == 'useFloatingCoverStyle') {
			$aspen_db->exec("UPDATE themes set coverStyle='floating'");
		}elseif ($this->name == 'displayCoversForEditions') {
			$aspen_db->exec("UPDATE grouped_work_display_settings set showEditionCovers=1");
		}elseif ($this->name == 'enableNewBadge') {
			$aspen_db->exec("UPDATE grouped_work_display_settings set alwaysFlagNewTitles=1");
		}
		$this->status = 2;
		$this->update();
	}
}