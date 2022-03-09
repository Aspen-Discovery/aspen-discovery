<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';
class DonationFormFields extends DataObject
{
	public $__table = 'donations_form_fields';
	public $id;
	public $textId;
	public $category;
	public $label;
	public $type;
	public $note;
	public $required;
	public $donationSettingId;

	static $fieldTypeOptions = array(
		'text'     => 'Text',
		'textbox'  => 'Textarea',
		'checkbox' => 'Checkbox (Yes/No)',
	);

	static function getObjectStructure() : array {
		$structure = array(
			'id'       => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id'),
			'textId'   => array('property' => 'textId', 'type' => 'text', 'label' => 'Text Id', 'description' => 'The unique text id', 'required' => true),
			'category' => array('property' => 'category', 'type' => 'text', 'label' => 'Form Category', 'description' => 'The name of the section this field will belong in.', 'required' => true),
			'label'    => array('property' => 'label', 'type' => 'text', 'label' => 'Field Label', 'description' => 'Label for this field that will be displayed to users.', 'required' => true),
			'type'     => array('property' => 'type', 'type' => 'enum', 'label' => 'Field Type', 'description' => 'Type of data this field will be', 'values' => self::$fieldTypeOptions, 'default' => 'text', 'required' => true),
			'note'     => array('property' => 'note', 'type' => 'text', 'label' => 'Field Note', 'description' => 'Note for this field that will be displayed to users.'),
			'required' => array('property' => 'required', 'type' => 'checkbox', 'label' => 'Required', 'description' => 'Whether or not the field is required.'),
		);
		return $structure;
	}


	static function getDefaults($donationSettingId) {
		$defaultFieldsToDisplay = array();

		// Donation Information
		$defaultField = new DonationFormFields();
		$defaultField->donationSettingId = $donationSettingId;
		$defaultField->category = 'Choose an amount to donate';
		$defaultField->label = 'Donation Amount';
		$defaultField->textId = 'valueList';
		$defaultField->type = 'select';
		$defaultField->required = 0;
		$defaultField->insert();
		$defaultFieldsToDisplay[] = $defaultField;

		$defaultField = new DonationFormFields();
		$defaultField->donationSettingId = $donationSettingId;
		$defaultField->category = 'Choose an amount to donate';
		$defaultField->label = 'What would you like your donation to support?';
		$defaultField->textId = 'earmarkList';
		$defaultField->type = 'select';
		$defaultField->required = 0;
		$defaultField->insert();
		$defaultFieldsToDisplay[] = $defaultField;

		$defaultField = new DonationFormFields();
		$defaultField->donationSettingId = $donationSettingId;
		$defaultField->category = 'Choose an amount to donate';
		$defaultField->label = 'If your donation is for a specific branch, please select the branch';
		$defaultField->textId = 'locationList';
		$defaultField->type = 'select';
		$defaultField->required = 0;
		$defaultField->insert();
		$defaultFieldsToDisplay[] = $defaultField;

		$defaultField = new DonationFormFields();
		$defaultField->donationSettingId = $donationSettingId;
		$defaultField->category = 'Choose an amount to donate';
		$defaultField->label = 'Dedicate my donation in honor or in memory of someone';
		$defaultField->textId = 'shouldBeDedicated';
		$defaultField->type = 'checkbox';
		$defaultField->required = 1;
		$defaultField->insert();
		$defaultFieldsToDisplay[] = $defaultField;

		$defaultField = new DonationFormFields();
		$defaultField->donationSettingId = $donationSettingId;
		$defaultField->category = 'Choose an amount to donate';
		$defaultField->label = 'Choose an amount to donate';
		$defaultField->textId = 'dedicationType';
		$defaultField->type = 'radio';
		$defaultField->required = 0;
		$defaultField->insert();
		$defaultFieldsToDisplay[] = $defaultField;

		$defaultField = new DonationFormFields();
		$defaultField->donationSettingId = $donationSettingId;
		$defaultField->category = 'Choose an amount to donate';
		$defaultField->label = 'Honoree\'s First Name';
		$defaultField->textId = 'honoreeFirstName';
		$defaultField->type = 'text';
		$defaultField->required = 1;
		$defaultField->insert();
		$defaultFieldsToDisplay[] = $defaultField;

		$defaultField = new DonationFormFields();
		$defaultField->donationSettingId = $donationSettingId;
		$defaultField->category = 'Choose an amount to donate';
		$defaultField->label = 'Honoree\'s Last Name';
		$defaultField->textId = 'honoreeLastName';
		$defaultField->type = 'text';
		$defaultField->required = 0;
		$defaultField->insert();
		$defaultFieldsToDisplay[] = $defaultField;

		// User Information
		$defaultField = new DonationFormFields();
		$defaultField->donationSettingId = $donationSettingId;
		$defaultField->category = 'Enter your information';
		$defaultField->label = 'First Name';
		$defaultField->textId = 'firstName';
		$defaultField->type = 'text';
		$defaultField->required = 1;
		$defaultField->insert();
		$defaultFieldsToDisplay[] = $defaultField;

		$defaultField = new DonationFormFields();
		$defaultField->donationSettingId = $donationSettingId;
		$defaultField->category = 'Enter your information';
		$defaultField->label = 'Last Name';
		$defaultField->textId = 'lastName';
		$defaultField->type = 'text';
		$defaultField->required = 1;
		$defaultField->insert();
		$defaultFieldsToDisplay[] = $defaultField;

		$defaultField = new DonationFormFields();
		$defaultField->donationSettingId = $donationSettingId;
		$defaultField->category = 'Enter your information';
		$defaultField->label = 'Don\'t show my name publicly';
		$defaultField->textId = 'makeAnonymous';
		$defaultField->type = 'checkbox';
		$defaultField->required = 0;
		$defaultField->insert();
		$defaultFieldsToDisplay[] = $defaultField;

		$defaultField = new DonationFormFields();
		$defaultField->donationSettingId = $donationSettingId;
		$defaultField->category = 'Enter your information';
		$defaultField->label = 'Email Address';
		$defaultField->textId = 'emailAddress';
		$defaultField->type = 'text';
		$defaultField->note = 'Your receipt will be emailed here';
		$defaultField->required = 1;
		$defaultField->insert();
		$defaultFieldsToDisplay[] = $defaultField;

		return $defaultFieldsToDisplay;

	}

}