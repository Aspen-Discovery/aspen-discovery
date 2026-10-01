<?php /** @noinspection PhpMissingFieldTypeInspection */


class WebBuilderCategory extends DataObject {
	public $__table = 'web_builder_category';
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
			'customWebBuilderCategoryDescription' => [
				'property' => 'customWebBuilderCategoryDescription',
				'type' => 'translatableTextBlock',
				'label' => 'Description',
				'description' => 'A description for the category.',
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
			$this->saveTextBlockTranslations('customWebBuilderCategoryDescription');
		}
		return $ret;
	}
	public function update(string $context = ''): int|bool {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveTextBlockTranslations('customWebBuilderCategoryDescription');
		}
		return $ret;
	}
	public static function getCategories() : array {
		$categories = [];
		$category = new WebBuilderCategory();
		$category->orderBy('name');
		$category->find();
		while ($category->fetch()) {
			$categories[$category->id] = $category->name;
		}
		return $categories;
	}

	public function okToExport(array $selectedFilters): bool {
		return true;
	}
}
