<?php


class CustomFormField extends DataObject {
	public $__table = 'web_builder_custom_form_field';
	public $id;
	public $formId;
	public $weight;
	public $label;
	public $description;
	public $fieldType;
	public $enumValues;
	public $defaultValue;
	public $required;

	public static $fieldTypeNames = [
		0 => 'Text Field',
		1 => 'Text Area',
		2 => 'Checkbox',
		'3' => 'Select List',
		'4' => 'Date',
		'5' => 'Email address',
		'6' => 'URL',
	];
	public static $fieldTypes = [
		0 => 'text',
		1 => 'textarea',
		2 => 'checkbox',
		'3' => 'enum',
		'4' => 'date',
		'5' => 'email',
		'6' => 'url',
	];

	static function getObjectStructure(): array {
		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id within the database',
			],
			'formId' => [
				'property' => 'formId',
				'type' => 'label',
				'label' => 'Form',
				'description' => 'The parent form',
			],
			'label' => [
				'property' => 'label',
				'type' => 'text',
				'label' => 'Label',
				'description' => 'A label for the field',
				'size' => '40',
				'maxLength' => 100,
				'required' => true,
			],
			'fieldType' => [
				'property' => 'fieldType',
				'type' => 'enum',
				'values' => CustomFormField::$fieldTypeNames,
				'label' => 'Field Type',
				'description' => 'The type of field to',
				'default' => 0,
				'required' => true,
			],
			'description' => [
				'property' => 'description',
				'type' => 'text',
				'label' => 'Description',
				'description' => 'A description for the field',
				'size' => '40',
				'maxLength' => 255,
				'required' => false,
			],
			'enumValues' => [
				'property' => 'enumValues',
				'type' => 'text',
				'label' => 'Select List Values (separate values with commas)',
				'description' => 'A list of valid values for the select list',
				'size' => '40',
				'maxLength' => 255,
				'required' => false,
			],
			'defaultValue' => [
				'property' => 'defaultValue',
				'type' => 'text',
				'label' => 'Default Value',
				'description' => 'The default value for the field',
				'required' => false,
			],
			'required' => [
				'property' => 'required',
				'type' => 'checkbox',
				'label' => 'Required',
				'description' => 'Whether or not the user must enter a value for the field',
				'default' => 0,
			],
			'weight' => [
				'property' => 'weight',
				'type' => 'integer',
				'label' => 'Weight',
				'description' => 'The sort order',
				'default' => 0,
			],
		];
	}
}