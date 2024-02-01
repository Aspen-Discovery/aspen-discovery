<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class DonationFormFields extends DataObject {
	public $__table = 'donations_form_fields';
	public $id;
	public $textId;
	public $category;
	public $label;
	public $type;
	public $note;
	public $required;
	public $donationSettingId;

	static $fieldTypeOptions = [
		'text' => 'Text',
		'textbox' => 'Textarea',
		'checkbox' => 'Checkbox (Yes/No)',
	];

	static function getObjectStructure($context = ''): array {
		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'textId' => [
				'property' => 'textId',
				'type' => 'text',
				'label' => 'Text Id',
				'description' => 'The unique text id',
				'required' => true,
			],
			'category' => [
				'property' => 'category',
				'type' => 'text',
				'label' => 'Form Category',
				'description' => 'The name of the section this field will belong in.',
				'required' => true,
			],
			'label' => [
				'property' => 'label',
				'type' => 'text',
				'label' => 'Field Label',
				'description' => 'Label for this field that will be displayed to users.',
				'required' => true,
			],
			'type' => [
				'property' => 'type',
				'type' => 'enum',
				'label' => 'Field Type',
				'description' => 'Type of data this field will be',
				'values' => self::$fieldTypeOptions,
				'default' => 'text',
				'required' => true,
			],
			'note' => [
				'property' => 'note',
				'type' => 'text',
				'label' => 'Field Note',
				'description' => 'Note for this field that will be displayed to users.',
			],
			'required' => [
				'property' => 'required',
				'type' => 'checkbox',
				'label' => 'Required',
				'description' => 'Whether or not the field is required.',
			],
		];
		return $structure;
	}
}