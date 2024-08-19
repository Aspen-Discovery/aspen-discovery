<?php
require_once ROOT_DIR . '/sys/DB/DataObject.php';

class SelfRegistrationFormValues extends DataObject {
	public $__table = 'self_reg_form_values';
	public $id;
	public $selfRegistrationFormId;
	public $weight;
	public $ilsName;
	public $displayName;
	public $fieldType;
	public $section;
	//public $patronUpdate;
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
		//Determine ILS profile to return correct values
		$ils = '';
		$fieldValues = [];
		$accountProfiles = new AccountProfile();
		$accountProfiles->find();
		while ($accountProfiles->fetch()) {
			if ($accountProfiles->ils != 'na') {
				$ils = $accountProfiles->ils;
			}
		}
		if ($ils == 'symphony') {
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
				"birthdate" => "BIRTHDATE",
				"care_of" => "CARE/OF",
				"careof" => "CARE_OF",
				"guardian" => "GUARDIAN",
				"po_box" => "PO_BOX",
				"street" => "STREET",
				"mailingaddr" => "MAILINGADDR",
				"apt_suite" => "APT/SUITE",
				"city" => "CITY",
				"state" => "STATE",
//			"city_state" => "CITY/STATE",
				"zip" => "ZIP",
				"email" => "EMAIL",
				"phone" => "PHONE",
				"dayphone" => "DAYPHONE",
				"cellPhone" => "CELLPHONE",
				"workphone" => "WORKPHONE",
				"homephone" => "HOMEPHONE",
				"ext" => "EXT",
				"fax" => "FAX",
				"employer" => "EMPLOYER",
				"parentname" => "PARENTNAME",
				"location" => "LOCATION",
				"type" => "TYPE",
				"not_type" => "NOT TYPE",
				"usefor" => "USEFOR",
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
		} else if ($ils == 'sierra') {
			$fieldValues = [
				"firstName" => "names (First)",
				"middleName" => "names (Middle)",
				"lastName" => "names (Last)",
				"library" => "homeLibraryCode",
				"birthDate" => "birthDate",
				"street" => "addresses (Street)",
				"city" => "addresses (City)",
				"state" => "addresses (State)",
				"zip" => "addresses (ZIP)",
				"email" => "emails",
				"phone" => "phones",
				"pin" => "pin",
				"barcode" => "barcodes",
			];
		}

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
			'ilsName' => [
				'property' => 'ilsName',
				'type' => 'enum',
				'label' => 'ILS Name',
				'values' => self::getFieldValues(),
				'description' => 'The name of the field in the ILS',
			],
			'displayName' => [
				'property' => 'displayName',
				'type' => 'text',
				'label' => 'Display Name',
				'description' => 'The name of the field in Aspen',
				'required' => true,
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
			'section' => [
				'property' => 'section',
				'type' => 'enum',
				'label' => 'Section',
				'values' => [
					'librarySection' => 'Library',
					'identitySection' => 'Identity',
					'mainAddressSection' => 'Address',
					'contactInformationSection' => 'Contact Information',
				],
				'description' => 'The field type for the field',
				'default' => 'identitySection',
			],
			'required' => [
				'property' => 'required',
				'type' => 'checkbox',
				'label' => 'Required?',
				'description' => 'Whether or not the field is required',
				'default' => '0',
			],
//			'patronUpdate' => [
//				'property' => 'patronUpdate',
//				'type' => 'enum',
//				'label' => 'Patron Update Actions',
//				'values' => [
//					'read_only' => 'Read Only',
//					'hidden' => 'Hidden',
//					'editable' => 'Editable',
//					'editable_required' => 'Editable & Required',
//				],
//				'description' => 'How the field appears in patron update form',
//				'default' => '0',
//			],
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
			$fieldNames[] = $fields->ilsName;
		}

		return $fieldNames;
	}
}