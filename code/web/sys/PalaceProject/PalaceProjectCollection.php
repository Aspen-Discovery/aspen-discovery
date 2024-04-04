<?php
require_once ROOT_DIR . '/sys/PalaceProject/PalaceProjectSetting.php';

class PalaceProjectCollection extends DataObject {
	public $__table = 'palace_project_collections';    // table name
	public $id;
	public $settingId;
	public $palaceProjectName;
	public $displayName;
	public $hasCirculation;
	public $lastIndexed;

	public function getUniquenessFields(): array {
		return [
			'id',
		];
	}

	public static function getObjectStructure($context = ''): array {
		$palaceProjectSettings = [];
		$palaceProjectSetting = new PalaceProjectSetting();
		$palaceProjectSetting->find();
		while ($palaceProjectSetting->fetch()) {
			$palaceProjectSettings[$palaceProjectSetting->id] = (string)$palaceProjectSetting;
		}

		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id within the database',
			],
			'settingId' => [
				'property' => 'settingId',
				'type' => 'enum',
				'values' => $palaceProjectSettings,
				'label' => 'Setting Id',
				'readOnly' => true,
			],
			'palaceProjectName' => [
				'property' => 'palaceProjectName',
				'type' => 'text',
				'label' => 'Palace Project Name',
				'description' => 'The name of the collection within Palace Project',
				'readOnly' => true,
			],
			'displayName' => [
				'property' => 'displayName',
				'type' => 'text',
				'label' => 'Aspen Display Name',
				'description' => 'The name of the collection for display within Asepn',
			],
			'hasCirculation' => [
				'property' => 'hasCirculation',
				'type' => 'checkbox',
				'label' => 'Has Circulation',
				'description' => 'If the collection has circulation. Collections with circulation will be indexed continuously.',
			],
			'includeInAspen' => [
				'property' => 'includeInAspen',
				'type' => 'checkbox',
				'label' => 'Include In Aspen',
				'description' => 'Whether the collection is included within Aspen.',
			],
			'lastIndexed' => [
				'property' => 'lastIndexed',
				'type' => 'timestamp',
				'label' => 'Last Indexed',
				'description' => 'When the collection was indexed last.  Collections without circulation will index every 24 hours',
			],
		];
		return $structure;
	}

	public function getEditLink($context): string {
		return '/PalaceProject/Collections?objectAction=edit&id=' . $this->id;
	}
}