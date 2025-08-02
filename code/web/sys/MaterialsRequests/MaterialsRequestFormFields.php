<?php /** @noinspection PhpMissingFieldTypeInspection */

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class MaterialsRequestFormFields extends DataObject {
	public $__table = 'materials_request_form_fields';
	public $id;
	public $libraryId;
	public $weight;
	public $formCategory;
	public $fieldLabel; // unique
	public $fieldType;

	static $fieldTypeOptions = [
		'about' => 'About',
		'ageLevel' => 'Age Level',
		'author' => 'Author',
		'assignedTo' => 'Assigned To (staff view only)',
		'bookmobileStop' => 'Bookmobile Stop',
		'bookType' => 'Book Type',
		'comments' => 'Comments',
		'createdBy' => 'Created By (staff view only)',
		'dateCreated' => 'Date Created',
		'dateUpdated' => 'Date Updated',
		'email' => 'Email',
		'emailSent' => 'Email Sent',
		'format' => 'Format',
		'holdPickupLocation' => 'Hold Pick-up Location',
		'holdsCreated' => 'Holds Created',
		'illItem' => 'Inter-library Loan Item',
		'isbn' => 'ISBN',
		'issn' => 'ISSN',
		'libraryCardNumber' => 'Library Card Number (staff view only)',
		'oclcNumber' => 'OCLC Number',
		'placeHoldWhenAvailable' => 'Place Hold when Available',
		'phone' => 'Phone',
		'publisher' => 'Publisher',
		'publicationYear' => 'Publication Year',
		'id' => 'Request ID Number (staff view only)',
		'source' => 'Request Source',
		'staffComments' => 'Staff Comments (staff view only)',
		'status' => 'Status (staff view only)',
		'title' => 'Title',
		'upc' => 'UPC',
	];


	/** @noinspection PhpUnusedParameterInspection */
	static function getObjectStructure($context = ''): array {
		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'formCategory' => [
				'property' => 'formCategory',
				'type' => 'text',
				'label' => 'Form Category',
				'description' => 'The name of the section this field will belong in.',
			],
			'fieldLabel' => [
				'property' => 'fieldLabel',
				'type' => 'text',
				'label' => 'Field Label',
				'description' => 'Label for this field that will be displayed to users.',
			],
			'fieldType' => [
				'property' => 'fieldType',
				'type' => 'enum',
				'label' => 'Field Type',
				'description' => 'Type of data this field will be',
				'values' => self::$fieldTypeOptions,
				'default' => 'text',
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

	/**
	 * @param int|string $libraryId
	 * @return MaterialsRequestFormFields[]
	 */
	static function getDefaultFormFields(int|string $libraryId = -1) : array {
		$defaultFieldsToDisplay = [];

		//This Replicates MyRequest Form structure.

		// Title Information
		$defaultField = new MaterialsRequestFormFields();
		$defaultField->libraryId = $libraryId;
		$defaultField->formCategory = 'Material Information';
		$defaultField->fieldLabel = 'Format';
		$defaultField->fieldType = 'format';
		$defaultField->weight = 1;
		$defaultFieldsToDisplay[] = $defaultField;

		$defaultField = new MaterialsRequestFormFields();
		$defaultField->libraryId = $libraryId;
		$defaultField->formCategory = 'Title Information';
		$defaultField->fieldLabel = 'Title';
		$defaultField->fieldType = 'title';
		$defaultField->weight = count($defaultFieldsToDisplay) + 1;
		$defaultFieldsToDisplay[] = $defaultField;

		$defaultField = new MaterialsRequestFormFields();
		$defaultField->libraryId = $libraryId;
		$defaultField->formCategory = 'Title Information';
		$defaultField->fieldLabel = 'Author';
		$defaultField->fieldType = 'author';
		$defaultField->weight = count($defaultFieldsToDisplay) + 1;
		$defaultFieldsToDisplay[] = $defaultField;

		// Hold Options
		$defaultField = new MaterialsRequestFormFields();
		$defaultField->libraryId = $libraryId;
		$defaultField->formCategory = 'Hold Options';
		$defaultField->fieldLabel = 'Place a hold for me when the item is available';
		$defaultField->fieldType = 'placeHoldWhenAvailable';
		$defaultField->weight = count($defaultFieldsToDisplay) + 1;
		$defaultFieldsToDisplay[] = $defaultField;

		$defaultField = new MaterialsRequestFormFields();
		$defaultField->libraryId = $libraryId;
		$defaultField->formCategory = 'Hold Options';
		$defaultField->fieldLabel = 'Pick-up Location';
		$defaultField->fieldType = 'holdPickupLocation';
		$defaultField->weight = count($defaultFieldsToDisplay) + 1;
		$defaultFieldsToDisplay[] = $defaultField;

		$defaultField = new MaterialsRequestFormFields();
		$defaultField->libraryId = $libraryId;
		$defaultField->formCategory = 'Hold Options';
		$defaultField->fieldLabel = 'Do you want us to borrow from another library if not purchased?';
		$defaultField->fieldType = 'illItem';
		$defaultField->weight = count($defaultFieldsToDisplay) + 1;
		$defaultFieldsToDisplay[] = $defaultField;

		// Supplemental Details (optional)
		$defaultField = new MaterialsRequestFormFields();
		$defaultField->libraryId = $libraryId;
		$defaultField->formCategory = 'Supplemental Details (optional)';
		$defaultField->fieldLabel = 'Age Level';
		$defaultField->fieldType = 'ageLevel';
		$defaultField->weight = count($defaultFieldsToDisplay) + 1;
		$defaultFieldsToDisplay[] = $defaultField;

		$defaultField = new MaterialsRequestFormFields();
		$defaultField->libraryId = $libraryId;
		$defaultField->formCategory = 'Supplemental Details (optional)';
		$defaultField->fieldLabel = 'Type';
		$defaultField->fieldType = 'bookType';
		$defaultField->weight = count($defaultFieldsToDisplay) + 1;
		$defaultFieldsToDisplay[] = $defaultField;

		$defaultField = new MaterialsRequestFormFields();
		$defaultField->libraryId = $libraryId;
		$defaultField->formCategory = 'Supplemental Details (optional)';
		$defaultField->fieldLabel = 'Publisher';
		$defaultField->fieldType = 'publisher';
		$defaultField->weight = count($defaultFieldsToDisplay) + 1;
		$defaultFieldsToDisplay[] = $defaultField;

		$defaultField = new MaterialsRequestFormFields();
		$defaultField->libraryId = $libraryId;
		$defaultField->formCategory = 'Supplemental Details (optional)';
		$defaultField->fieldLabel = 'Publication Year';
		$defaultField->fieldType = 'publicationYear';
		$defaultField->weight = count($defaultFieldsToDisplay) + 1;
		$defaultFieldsToDisplay[] = $defaultField;

		$defaultField = new MaterialsRequestFormFields();
		$defaultField->libraryId = $libraryId;
		$defaultField->formCategory = 'Supplemental Details (optional)';
		$defaultField->fieldLabel = 'How and/or where did you hear about this title';
		$defaultField->fieldType = 'about';
		$defaultField->weight = count($defaultFieldsToDisplay) + 1;
		$defaultFieldsToDisplay[] = $defaultField;

		$defaultField = new MaterialsRequestFormFields();
		$defaultField->libraryId = $libraryId;
		$defaultField->formCategory = 'Supplemental Details (optional)';
		$defaultField->fieldLabel = 'Comments';
		$defaultField->fieldType = 'comments';
		$defaultField->weight = count($defaultFieldsToDisplay) + 1;
		$defaultFieldsToDisplay[] = $defaultField;


		// Contact Information
		$defaultField = new MaterialsRequestFormFields();
		$defaultField->libraryId = $libraryId;
		$defaultField->formCategory = 'Contact Information';
		$defaultField->fieldLabel = 'Email';
		$defaultField->fieldType = 'email';
		$defaultField->weight = count($defaultFieldsToDisplay) + 1;
		$defaultFieldsToDisplay[] = $defaultField;

		$defaultField = new MaterialsRequestFormFields();
		$defaultField->libraryId = $libraryId;
		$defaultField->formCategory = 'Contact Information';
		$defaultField->fieldLabel = 'Phone';
		$defaultField->fieldType = 'phone';
		$defaultField->weight = count($defaultFieldsToDisplay) + 1;
		$defaultFieldsToDisplay[] = $defaultField;

		$defaultField = new MaterialsRequestFormFields();
		$defaultField->libraryId = $libraryId;
		$defaultField->formCategory = 'Staff Information';
		$defaultField->fieldLabel = 'Request Id';
		$defaultField->fieldType = 'id';
		$defaultField->weight = count($defaultFieldsToDisplay) + 1;
		$defaultFieldsToDisplay[] = $defaultField;

		$defaultField = new MaterialsRequestFormFields();
		$defaultField->libraryId = $libraryId;
		$defaultField->formCategory = 'Staff Information';
		$defaultField->fieldLabel = 'Request Source';
		$defaultField->fieldType = 'source';
		$defaultField->weight = count($defaultFieldsToDisplay) + 1;
		$defaultFieldsToDisplay[] = $defaultField;

		$defaultField = new MaterialsRequestFormFields();
		$defaultField->libraryId = $libraryId;
		$defaultField->formCategory = 'Staff Information';
		$defaultField->fieldLabel = 'Status';
		$defaultField->fieldType = 'status';
		$defaultField->weight = count($defaultFieldsToDisplay) + 1;
		$defaultFieldsToDisplay[] = $defaultField;

		$defaultField = new MaterialsRequestFormFields();
		$defaultField->libraryId = $libraryId;
		$defaultField->formCategory = 'Staff Information';
		$defaultField->fieldLabel = 'Assigned To';
		$defaultField->fieldType = 'assignedTo';
		$defaultField->weight = count($defaultFieldsToDisplay) + 1;
		$defaultFieldsToDisplay[] = $defaultField;

		$defaultField = new MaterialsRequestFormFields();
		$defaultField->libraryId = $libraryId;
		$defaultField->formCategory = 'Staff Information';
		$defaultField->fieldLabel = 'Created By';
		$defaultField->fieldType = 'createdBy';
		$defaultField->weight = count($defaultFieldsToDisplay) + 1;
		$defaultFieldsToDisplay[] = $defaultField;

		$defaultField = new MaterialsRequestFormFields();
		$defaultField->libraryId = $libraryId;
		$defaultField->formCategory = 'Staff Information';
		$defaultField->fieldLabel = 'Library Card';
		$defaultField->fieldType = 'libraryCardNumber';
		$defaultField->weight = count($defaultFieldsToDisplay) + 1;
		$defaultFieldsToDisplay[] = $defaultField;

//		$defaultField = new MaterialsRequestFormFields();
//		$defaultField->libraryId = $libraryId;
//		$defaultField->formCategory = '';
//		$defaultField->fieldLabel = '';
//		$defaultField->fieldType = '';
//		$defaultField->weight = count($defaultFieldsToDisplay)+1;
//		$defaultFieldsToDisplay[] = $defaultField;

		return $defaultFieldsToDisplay;

	}

}