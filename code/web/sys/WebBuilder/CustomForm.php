<?php

require_once ROOT_DIR . '/sys/WebBuilder/CustomFormField.php';
require_once ROOT_DIR . '/sys/WebBuilder/LibraryCustomForm.php';
class CustomForm extends DataObject
{
	public $__table = 'web_builder_custom_form';
	public $id;
	public $title;
	public $urlAlias;
	public $emailResultsTo;
	public $requireLogin;
	public $introText;
	public $submissionResultText;

	private $_libraries;
	/** @var CustomFormField[] */
	private $_formFields;

	public function getNumericColumnNames()
	{
		return ['requireLogin'];
	}

	static function getObjectStructure()
	{
		$formFieldStructure = CustomFormField::getObjectStructure();
		unset ($formFieldStructure['weight']);
		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Custom Forms'));
		return [
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id within the database'),
			'title' => array('property' => 'title', 'type' => 'text', 'label' => 'Title', 'description' => 'The title of the page', 'size' => '40', 'maxLength' => 100, 'required' => true),
			'urlAlias' => array('property' => 'urlAlias', 'type' => 'text', 'label' => 'URL Alias (no domain, should start with /)', 'description' => 'The url of the page (no domain name)', 'size' => '40', 'maxLength' => 100),
			'requireLogin' => array('property' => 'requireLogin', 'type' => 'checkbox', 'label' => 'Require Login', 'description' => 'Whether or not the user must be logged in to view the form', 'default' => 0),
			'introText' => array('property' => 'introText', 'type' => 'markdown', 'label' => 'Introductory Text', 'description' => 'Introductory Text displayed above the fields', 'hideInLists' => true),
			'formFields' => array(
				'property' => 'formFields',
				'type' => 'oneToMany',
				'label' => 'Fields',
				'description' => 'Fields within the form',
				'keyThis' => 'id',
				'keyOther' => 'formId',
				'subObjectType' => 'CustomFormField',
				'structure' => $formFieldStructure,
				'sortable' => true,
				'storeDb' => true,
				'allowEdit' => false,
				'canEdit' => false,
			),
			'emailResultsTo' => array('property' => 'emailResultsTo', 'type' => 'text', 'label' => 'Email Results To (separate multiple addresses with semi-colons)', 'description' => 'An email address to send submission results to', 'size' => '40', 'maxLength' => 100),
			'submissionResultText' => array('property' => 'submissionResultText', 'type' => 'markdown', 'label' => 'Submission Result Text', 'description' => 'Text to be displayed to the user when submission is complete', 'hideInLists' => true),
			'libraries' => array(
				'property' => 'libraries',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Libraries',
				'description' => 'Define libraries that use these settings',
				'values' => $libraryList,
				'hideInLists' => true
			),
		];
	}

	public function insert(){
		$ret = parent::insert();
		if ($ret !== FALSE ){
			$this->saveLibraries();
			$this->saveFormFields();
		}
		return $ret;
	}

	public function update(){
		$ret = parent::update();
		if ($ret !== FALSE ){
			$this->saveLibraries();
			$this->saveFormFields();
		}
		return $ret;
	}

	public function __get($name){
		if ($name == "libraries") {
			return $this->getLibraries();
		}elseif ($name == "formFields") {
			return $this->getFormFields();
		}else{
			return $this->_data[$name];
		}
	}

	public function __set($name, $value){
		if ($name == "libraries") {
			$this->_libraries = $value;
		}elseif ($name == "formFields") {
			$this->_formFields = $value;
		}else{
			$this->_data[$name] = $value;
		}
	}

	public function delete($useWhere = false)
	{
		$ret = parent::delete($useWhere);
		if ($ret && !empty($this->id)) {
			$this->clearLibraries();
			$this->clearFormFields();
		}
		return $ret;
	}

	public function getLibraries() {
		if (!isset($this->_libraries) && $this->id){
			$this->_libraries = array();
			$libraryLink = new LibraryCustomForm();
			$libraryLink->formId = $this->id;
			$libraryLink->find();
			while($libraryLink->fetch()){
				$this->_libraries[$libraryLink->libraryId] = $libraryLink->libraryId;
			}
		}
		return $this->_libraries;
	}

	public function getFormFields() {
		if (!isset($this->_formFields) && $this->id){
			$this->_formFields = array();
			$formField = new CustomFormField();
			$formField->formId = $this->id;
			$formField->orderBy('weight');
			$formField->find();
			while($formField->fetch()){
				$this->_formFields[$formField->id] = clone $formField;
			}
		}
		return $this->_formFields;
	}

	public function saveLibraries(){
		if (isset($this->_libraries) && is_array($this->_libraries)){
			$this->clearLibraries();

			foreach ($this->_libraries as $libraryId) {
				$libraryLink = new LibraryCustomForm();

				$libraryLink->formId = $this->id;
				$libraryLink->libraryId = $libraryId;
				$libraryLink->insert();
			}
			unset($this->_libraries);
		}
	}

	public function saveFormFields(){
		if (isset($this->_formFields) && is_array($this->_formFields)){
			$this->saveOneToManyOptions($this->_formFields, 'formId');
			unset($this->_formFields);
		}
	}

	private function clearLibraries()
	{
		//Delete links to the libraries
		$libraryLink = new LibraryCustomForm();
		$libraryLink->formId = $this->id;
		return $libraryLink->delete(true);
	}

	private function clearFormFields()
	{
		//Delete links to the libraries
		$field = new CustomFormField();
		$field->formId = $this->id;
		return $field->delete(true);
	}

	public function getFormStructure(){
		$fields = $this->getFormFields();
		$structure = [];
		foreach ($fields as $field) {
			$fieldType = CustomFormField::$fieldTypes[$field->fieldType];
			$fieldStructure = [
				'property' => $field->id,
				'type' => $fieldType,
				'label' => $field->label,
				'description' => $field->description,
				'required' => $field->required,
				'default' => $field->defaultValue
			];
			if ($fieldType == 'enum') {
				$enumValues = explode(',', $field->enumValues);
				$fieldStructure['values'] = $enumValues;
			}
			$structure[$field->id] = $fieldStructure;
		}
		return $structure;
	}
	public function getFormattedFields()
	{
		$structure = $this->getFormStructure();
		global $interface;
		$interface->assign('submitUrl', '/WebBuilder/SubmitForm?id=' . $this->id);
		$interface->assign('structure', $structure);
		$interface->assign('saveButtonText', 'Submit');
		return $interface->fetch('DataObjectUtil/objectEditForm.tpl');
	}
}