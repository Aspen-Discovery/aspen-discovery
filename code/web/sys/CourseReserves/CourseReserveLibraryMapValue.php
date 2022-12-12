<?php


class CourseReserveLibraryMapValue extends DataObject {
	public $__table = 'course_reserves_library_map';
	public $id;
	public $settingId;
	public $value;
	public $translation;

	public function __toString() {
		return "$this->value => $this->translation";
	}

	static function getObjectStructure($context = ''): array {
		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id within the database',
			],
			'settingId' => [
				'property' => 'settingId',
				'type' => 'foreignKey',
				'label' => 'Course Reserve Setting Id',
				'description' => 'The Course Reserves Settings this is associated with',
			],
			'value' => [
				'property' => 'value',
				'type' => 'text',
				'label' => 'Value',
				'description' => 'The value to be translated',
				'maxLength' => '50',
				'required' => true,
				'forcesReindex' => true,
			],
			'translation' => [
				'property' => 'translation',
				'type' => 'text',
				'label' => 'Translation',
				'description' => 'The translated value',
				'maxLength' => '255',
				'required' => false,
				'forcesReindex' => true,
			],
		];
	}
}