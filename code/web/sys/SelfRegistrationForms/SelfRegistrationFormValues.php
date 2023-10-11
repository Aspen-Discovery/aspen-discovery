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
			"preferredName" => 'preferredName',
			"usePreferredName" => 'usePreferredName',
			"library" => "Home Library",
			"suffix" => "suffix",
			"title" => "title",
			"dob" => "birthDate",
			"care_of" => "CARE/OF",
			"po_box" => "PO_BOX",
			"street" => "STREET",
			"apt_suite" => "APT/SUITE",
			"city" => "CITY",
			"state" => "STATE",
			"city_state" => "CITY/STATE",
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
//			"category01" => "category01",
//			"category02" => "category02",
//			"category03" => "category03",
//			"category04" => "category04",
//			"category05" => "category05",
//			"category06" => "category06",
//			"category07" => "category07",
//			"category08" => "category08",
//			"category09" => "category09",
//			"category10" => "category10",
//			"category11" => "category11",
//			"category12" => "category12",
			"customInformation" => "customInformation",
			"primaryAddress" => "primaryAddress",
			"primaryPhone" => "primaryPhone",
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

	public function getFormFieldsInOrder($selfRegFormId) {
		$fields = new SelfRegistrationFormValues();
		$fields->selfRegistrationFormId = $selfRegFormId;
		$fields->orderBy('weight');
		$fields->find();
		$fieldNames = [];
		while ($fields->fetch()) {
			$fieldNames[] = $fields->symphonyName;
		}

		return $fieldNames;
	}
}