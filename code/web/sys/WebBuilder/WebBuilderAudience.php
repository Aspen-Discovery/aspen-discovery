<?php /** @noinspection PhpMissingFieldTypeInspection */


class WebBuilderAudience extends DataObject {
	public $__table = 'web_builder_audience';
	public $__displayNameColumn = 'name';
	public $id;
	public $name;
	public $description;

	public function getUniquenessFields(): array {
		return ['name'];
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
				'type' => 'text',
				'label' => 'Name',
				'description' => 'A name for the settings',
				'required' => true,
				'maxLength' => 100,
			],
			'customWebBuilderAudienceDescription' => [
				'property' => 'customWebBuilderAudienceDescription',
				'type' => 'translatableTextBlock',
				'label' => 'Description',
				'description' => 'A description for the audience.',
				'defaultTextFile' => '',
				'hideInLists' => true,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	public function insert(string $context = ''): int|bool {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveTextBlockTranslations('customWebBuilderAudienceDescription');
		}
		return $ret;
	}
	public function update(string $context = ''): int|bool {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveTextBlockTranslations('customWebBuilderAudienceDescription');
		}
		return $ret;
	}
	public static function getAudiences() : array {
		$audiences = [];
		$audience = new WebBuilderAudience();
		$audience->orderBy('name');
		$audience->find();
		while ($audience->fetch()) {
			$audiences[$audience->id] = $audience->name;
		}
		return $audiences;
	}

	public function okToExport(array $selectedFilters): bool {
		return true;
	}
}