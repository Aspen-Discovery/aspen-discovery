<?php /** @noinspection PhpMissingFieldTypeInspection */

require_once ROOT_DIR . '/sys/WebBuilder/CustomFormField.php';
require_once ROOT_DIR . '/sys/WebBuilder/LibraryCustomForm.php';

class CustomForm extends DB_LibraryLinkedObject {
	public $__table = 'web_builder_custom_form';
	public $id;
	public $title;
	public $urlAlias;
	public $emailResultsTo;
	public $includeIntroductoryTextInEmail;
	public $requireLogin;
	public $introText;
	public $submissionResultText;
	public $deleted;
	public $dateDeleted;
	public $deletedBy;

	private $_libraries;
	/** @var CustomFormField[] */
	private $_formFields;

	public function getUniquenessFields(): array {
		return [
			'id',
		];
	}

	public function getNumericColumnNames(): array {
		return ['requireLogin'];
	}

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context]) && self::$_objectStructure[$context] !== null) {
			return self::$_objectStructure[$context];
		}

		$formFieldStructure = CustomFormField::getObjectStructure($context);
		unset ($formFieldStructure['weight']);
		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Custom Forms'));
		// get the structure for the library system's emails
		$emailStructure = LibraryCustomForm::getObjectStructure($context);
		
		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id within the database',
			],
			'title' => [
				'property' => 'title',
				'type' => 'text',
				'label' => 'Title',
				'description' => 'The title of the page',
				'size' => '40',
				'maxLength' => 100,
				'required' => true,
			],
			'urlAlias' => [
				'property' => 'urlAlias',
				'type' => 'text',
				'label' => 'URL Alias (no domain, should start with /)',
				'description' => 'The url of the page (no domain name)',
				'size' => '40',
				'maxLength' => 100,
			],
			'requireLogin' => [
				'property' => 'requireLogin',
				'type' => 'checkbox',
				'label' => 'Require Login',
				'description' => 'Whether or not the user must be logged in to view the form',
				'default' => 0,
			],
			'introText' => [
				'property' => 'introText',
				'type' => 'markdown',
				'label' => 'Introductory Text',
				'description' => 'Introductory Text displayed above the fields',
				'hideInLists' => true,
			],
			'formFields' => [
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
				'canAddNew' => true,
				'canDelete' => true,
			],
			'emailResultsTo' => [
				'property' => 'emailResultsTo',
				'type' => 'text',
				'label' => 'Email Results To (separate multiple addresses with semi-colons)',
				'description' => 'An email address to send submission results to',
				'size' => '40',
				'maxLength' => 150,
			],
			'includeIntroductoryTextInEmail' => [
				'property' => 'includeIntroductoryTextInEmail',
				'type' => 'checkbox',
				'label' => 'Include Introductory Text in Email',
				'description' => 'Whether or not the introductory text is included in the emailed results',
				'default' => 0,
			],
			'submissionResultText' => [
				'property' => 'submissionResultText',
				'type' => 'markdown',
				'label' => 'Submission Result Text',
				'description' => 'Text to be displayed to the user when submission is complete',
				'hideInLists' => true,
			],
			'libraries' => [
				'property' => 'libraries',
				'type' => 'oneToMany',
				'label' => 'Libraries',
				'description' => 'Define libraries that use these settings and email to send results to',
				'keyThis' => 'id',
				'keyOther' => 'formId',
				'subObjectType' => 'LibraryCustomForm',
				'structure' => $emailStructure,
				'canAddNew' => true,
				'canDelete' => true,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	public function insert(string $context = '') : int|bool {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveFormFields();
		}
		return $ret;
	}

	public function update(string $context = '') : int|bool {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveFormFields();
		}
		return $ret;
	}

	public function __get($name) {
		if ($name == "libraries") {
			return $this->getLibraries();
		} elseif ($name == "formFields") {
			return $this->getFormFields();
		} else {
			return parent::__get($name);
		}
	}

	public function __set($name, $value) {
		if ($name == "libraries") {
			$this->_libraries = $value;
		} elseif ($name == "formFields") {
			$this->_formFields = $value;
		} else {
			parent::__set($name, $value);
		}
	}

	public function delete(bool $useWhere = false, bool $hardDelete = false) : bool|int {
		$ret = parent::delete($useWhere, $hardDelete);
		if ($ret && $hardDelete && !empty($this->id)) {
			$this->clearLibraries();
			$this->clearFormFields();
		}
		return $ret;
	}

	public function getLibraries() : ?array {
		if (!isset($this->_libraries) && $this->id) {
			$this->_libraries = [];
			$libraryLink = new LibraryCustomForm();
			$libraryLink->formId = $this->id;
			$libraryLink->find();
			while ($libraryLink->fetch()) {
				$this->_libraries[$libraryLink->id] = clone $libraryLink;
			}
		}
		return $this->_libraries;
	}

	public function getFormFields() : ?array {
		if (!isset($this->_formFields) && $this->id) {
			$this->_formFields = [];
			$formField = new CustomFormField();
			$formField->formId = $this->id;
			$formField->orderBy('weight');
			$formField->find();
			while ($formField->fetch()) {
				$this->_formFields[$formField->id] = clone $formField;
			}
		}
		return $this->_formFields;
	}

	public function saveLibraries() : void {
		if (isset($this->_libraries) && is_array($this->_libraries)) {
			$this->saveOneToManyOptions($this->_libraries, 'formId');
			unset($this->_libraries);
		}
	}

	public function saveFormFields() : void {
		if (isset($this->_formFields) && is_array($this->_formFields)) {
			$this->saveOneToManyOptions($this->_formFields, 'formId');
			unset($this->_formFields);
		}
	}

	private function clearLibraries() : void {
		//Delete links to the libraries
		$libraryLink = new LibraryCustomForm();
		$libraryLink->formId = $this->id;
		$libraryLink->delete(true);
	}

	private function clearFormFields() : void {
		//Delete links to the libraries
		$field = new CustomFormField();
		$field->formId = $this->id;
		$field->delete(true);
	}

	public function getFormStructure() : array {
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
				'default' => $field->defaultValue,
			];
			if ($fieldType == 'enum') {
				$enumValues = explode(',', $field->enumValues);
				$fieldStructure['values'] = $enumValues;
			}
			$structure[$field->id] = $fieldStructure;
		}
		return $structure;
	}

	public function getFormattedFields() : string {
		$structure = $this->getFormStructure();
		global $interface;
		$interface->assign('submitUrl', '/WebBuilder/SubmitForm?id=' . $this->id);
		$interface->assign('structure', $structure);
		$interface->assign('saveButtonText', 'Submit');
		if (isset($_GET['objectAction'])) {
			$interface->assign('objectAction', $_GET['objectAction']);
		} else {
			$interface->assign('objectAction', '');
		}

		if (!UserAccount::isLoggedIn()) {
			if (!$this->requireLogin) {
				$interface->assign('patronIdCheck', 0);
				require_once ROOT_DIR . '/sys/Enrichment/RecaptchaSetting.php';
				require_once ROOT_DIR . '/recaptcha/recaptchalib.php';
				$recaptcha = new RecaptchaSetting();
				if ($recaptcha->find(true) && !empty($recaptcha->publicKey)) {
					$captchaCode = recaptcha_get_html($recaptcha->publicKey, $this->id);
					$interface->assign('captcha', $captchaCode);
					$interface->assign('captchaKey', $recaptcha->publicKey);
				}
			} else {
				return "<div class='alert alert-warning'>" . translate([
						'text' => "You must be logged to submit this form",
						'isPublicFacing' => true,
					]) . "</div>";
			}
		} else {
			$userId = UserAccount::getActiveUserId();
			$interface->assign('patronIdCheck', $userId);
		}
		return $interface->fetch('DataObjectUtil/objectEditForm.tpl');
	}

	public function getAdditionalListActions(): array {
		$objectActions = [];
		require_once ROOT_DIR . '/sys/WebBuilder/CustomForm.php';
		$customForm = new CustomForm();
		if ($customForm->find(true)) {
			$objectActions[] = [
				'text' => 'View Submissions',
				'url' => '/WebBuilder/CustomFormSubmissions?formId=' . $this->id,
			];
		}
		return $objectActions;
	}


	public function getLinksForJSON(): array {
		$links = parent::getLinksForJSON();

		//Form Fields
		$formFields = $this->getFormFields();
		$links['formFields'] = [];
		foreach ($formFields as $formField) {
			$fieldArray = $formField->toArray(false, true);
			$links['formFields'][] = $fieldArray;
		}

		return $links;
	}

	public function loadRelatedLinksFromJSON($jsonData, $mappings, string $overrideExisting = 'keepExisting'): bool {
		$result = parent::loadRelatedLinksFromJSON($jsonData, $mappings, $overrideExisting);

		if (array_key_exists('formFields', $jsonData)) {
			$formFields = [];
			foreach ($jsonData['formFields'] as $formField) {
				$formFieldObj = new CustomFormField();
				$formFieldObj->formId = $this->id;
				unset($formField['formId']);
				$formFieldObj->loadFromJSON($formField, $mappings, $overrideExisting);
				$formFields[$formFieldObj->id] = $formFieldObj;
			}
			$this->_formFields = $formFields;
			$result = true;
		}

		return $result;
	}

	public function supportsSoftDelete(): bool {
		return true;
	}
}