<?php


class WebBuilderAudience extends DataObject {
	public $__table = 'web_builder_audience';
	public $__displayNameColumn = 'name';
	public $id;
	public $name;

	public function getUniquenessFields(): array {
		return ['name'];
	}

	public static function getObjectStructure(): array {
		return [
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
		];
	}

	public static function getAudiences() {
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