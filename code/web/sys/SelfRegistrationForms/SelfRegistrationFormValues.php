<?php
require_once ROOT_DIR . '/sys/DB/DataObject.php';

class SelfRegistrationFormValues extends DataObject {
	public $__table = 'self_reg_form_values';
	public $id;
	public $selfRegistrationFormId;
	public $weight;
	public $symphonyName;
	public $displayName;
	public $fieldType;
	public $patronUpdate;
	public $required;
	public $note;

	public function getNumericColumnNames(): array {
		return [
			'weight',
			'selfRegistrationFormId',
			'required',
		];
	}

	public static function getFieldValues() {
		$fieldValues = [
			"firstName" => "firstName",
			"middleName" => "middleName",
			"lastName" => "lastName",
			"suffix" => "suffix",
			"dob" => "birthDate",
			"care_of" => "CARE/OF",
			"po_box" => "PO_BOX",
			"street" => "STREET",
			"apt_suite" => "APT/SUITE",
			"city" => "CITY",
			"state" => "STATE",
			"zip" => "ZIP",
			"email" => "EMAIL",
			"phone" => "PHONE",
			"dayphone" => "DAYPHONE",
			"cellphone" => "CELLPHONE",
			"workphone" => "WORKPHONE",
			"homephone" => "HOMEPHONE",
			"ext" => "EXT",
			"fax" => "FAX",
			"employer" => "EMPLOYER",
			"parentname" => "PARENTNAME",
			"location" => "LOCATION",
			"not_type" => "NOT TYPE",
			"userfor" => "USERFOR",
		];

		return $fieldValues;
	}

	static function getObjectStructure($fieldValues = null) {
		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'weight' => [
				'property' => 'weight',
				'type' => 'integer',
				'label' => 'Weight',
				'description' => 'The sort order',
				'default' => 0,
			],
			'symphonyName' => [
				'property' => 'symphonyName',
				'type' => 'enum',
				'label' => 'Symphony Name',
				'values' => self::getFieldValues(),
				'description' => 'The name of the field in Symphony',
			],
			'displayName' => [
				'property' => 'displayName',
				'type' => 'text',
				'label' => 'Display Name',
				'description' => 'The name of the field in Aspen',
			],
			'fieldType' => [
				'property' => 'fieldType',
				'type' => 'enum',
				'label' => 'Field Type',
				'values' => [
					'text' => 'Text',
					'date' => 'Date',
				],
				'description' => 'The field type for the field',
				'default' => '0',
			],
			'required' => [
				'property' => 'required',
				'type' => 'checkbox',
				'label' => 'Required?',
				'description' => 'Whether or not the field is required',
				'default' => '0',
			],
			'patronUpdate' => [
				'property' => 'patronUpdate',
				'type' => 'enum',
				'label' => 'Patron Update Actions',
				'values' => [
					'read_only' => 'Read Only',
					'hidden' => 'Hidden',
					'editable' => 'Editable',
					'editable_required' => 'Editable & Required',
				],
				'description' => 'How the field appears in patron update form',
				'default' => '0',
			],
			'note' => [
				'property' => 'note',
				'type' => 'text',
				'label' => 'Note',
				'description' => 'Note for the patron to see under field',
			],
		];
	}
}