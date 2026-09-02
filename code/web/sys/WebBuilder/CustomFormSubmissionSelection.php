<?php /** @noinspection PhpMissingFieldTypeInspection */

class CustomFormSubmissionSelection extends DataObject {
	public $__table = 'web_builder_custom_form_field_submission';
	public $id;
	public $formSubmissionId;
	public $submissionFieldId;
	public $formFieldContent;

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
				'description' => 'The unique id within the database',
			],
			'formFieldContent' => [
				'property' => 'formFieldContent',
				'type' => 'text',
				'label' => 'Custom Form',
				'description' => 'The parent custom form',
				'readOnly' => true,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}
}
