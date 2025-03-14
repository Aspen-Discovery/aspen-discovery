<?php
require_once ROOT_DIR . '/sys/LibraryLocation/LibraryEmailTemplate.php';

class EmailTemplate extends DataObject {
	public $__table = 'email_template';
	public $id;
	public $name;
	public $templateType;
	public $languageCode;
	public $subject;
	public $plainTextBody;
	//TODO: Add HTML Body

	private $_libraries;

	static function getObjectStructure($context = ''): array {
		$isCarlX = false;
		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Email Templates'));
		foreach (UserAccount::getAccountProfiles() as $accountProfileInfo) {
			/** @var AccountProfile $accountProfile */
			$accountProfile = $accountProfileInfo['accountProfile'];
			if ($accountProfile->ils == 'carlx') {
				$isCarlX = true;
			}
		}
		if ($isCarlX){
			$availableTemplates = [
				'welcome' => 'Welcome',
				'duplicateNameDOB' => 'Duplicate Name and Birthdate',
				'duplicateEmail' => 'Duplicate Email'
			];
		} else {
			$availableTemplates = [
				'welcome' => 'Welcome',
				'campaignStart' => 'Campaign Start',
				'campaignEnd' => 'Campaign Ending',
				'campaignEnroll' => 'Campaign Enrollment',
				'campaignComplete' => 'Campaign Complete',
				'milestoneComplete' => 'Milestone Complete',
				'staffCampaignComplete' => 'Campaign Complete Staff Alert',
			];
		}
		require_once ROOT_DIR . '/sys/Translation/Language.php';
		$validLanguage = new Language();
		$validLanguage->orderBy(["weight", "displayName"]);
		$validLanguage->find();
		$availableLanguages = [];
		while ($validLanguage->fetch()) {
			$availableLanguages[$validLanguage->code] = "$validLanguage->displayName ($validLanguage->displayNameEnglish)";
		}
		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id within the database',
			],
			'name' => [
				'property' => 'name',
				'type' => 'text',
				'label' => 'Name',
				'maxLength' => 50,
				'description' => 'A name for this indexing profile',
				'required' => true,
			],
			'templateType' => [
				'property' => 'templateType',
				'type' => 'enum',
				'values' => $availableTemplates,
				'label' => 'Template Type',
				'description' => 'The type of email being sent.',
				'hideInLists' => false,
			],
			'languageCode' => [
				'property' => 'languageCode',
				'type' => 'enum',
				'values' => $availableLanguages,
				'label' => 'Language',
				'description' => 'The language of the email.',
				'hideInLists' => false,
			],
			'subject' => [
				'property' => 'subject',
				'type' => 'text',
				'label' => 'Subject',
				'description' => 'The subject to use when sending the email.',
				'required' => true,
				'default' => '',
			],
			'instructions' => [
				'property' => 'instructions',
				'type' => 'label',
				'label' => 'Instructions',
				'hideInLists' => true,
				'descriptions' => 'Instructions for the template including variables that can be added.'
			],
			'plainTextBody' => [
				'property' => 'plainTextBody',
				'type' => 'textarea',
				'label' => 'Plain Text Body',
				'description' => 'The plain text body of the email',
				'hideInLists' => true,
				'required' => true,
				'autocomplete' => false,
			],
			'libraries' => [
				'property' => 'libraries',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Libraries',
				'description' => 'Define libraries that use this browse category group',
				'values' => $libraryList,
			],
		];

		if ($context == 'addNew') {
			unset($structure['instructions']);
		}

		return $structure;
	}

	public function __get($name) {
		if ($name == 'libraries') {
			return $this->getLibraries();
		} elseif ($name == 'instructions') {
			$optionalUpdatesPath = ROOT_DIR . '/email_template_instructions';
			require_once ROOT_DIR . '/sys/Parsedown/AspenParsedown.php';
			$parsedown = AspenParsedown::instance();
			$instructionsFilePath = $optionalUpdatesPath . '/' . $this->templateType . '.MD';
			if (!file_exists($instructionsFilePath)) {
				return '';
			}else {
				return $parsedown->parse(file_get_contents($instructionsFilePath));
			}
		} else {
			return parent::__get($name);
		}
	}

	public function __set($name, $value) {
		if ($name == "libraries") {
			$this->_libraries = $value;
		} else {
			parent::__set($name, $value);
		}
	}

	/** @return int[]
	 * @noinspection PhpUnused
	 */
	public function getLibraries() {
		if (!isset($this->_libraries) && !empty($this->id)) {
			$this->_libraries = [];
			$obj = new LibraryEmailTemplate();
			$obj->emailTemplateId = $this->id;
			$obj->find();
			while ($obj->fetch()) {
				$this->_libraries[$obj->libraryId] = $obj->libraryId;
			}
		}
		return $this->_libraries;
	}

	/** @noinspection PhpUnused */
	public function setLibraries($val) {
		$this->_libraries = $val;
	}

	/** @noinspection PhpUnused */
	public function clearLibraries() {
		$this->clearOneToManyOptions('LibraryEmailTemplate', 'emailTemplateId');
		unset($this->_libraries);
	}

	/**
	 * Override the update functionality to save related objects
	 *
	 * @see DB/DB_DataObject::update()
	 */
	public function update($context = '') {
		//Updates to properly update settings based on the ILS
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveLibraries();
		}

		return $ret;
	}

	/**
	 * Override the insert functionality to save the related objects
	 *
	 * @see DB/DB_DataObject::insert()
	 */
	public function insert($context = '') {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLibraries();
		}
		return $ret;
	}

	public function delete($useWhere = false) : int {
		$ret = parent::delete($useWhere);
		if ($ret && !empty($this->id)) {
			$this->clearLibraries();
		}
		return $ret;
	}

	public function saveLibraries() {
		if (isset ($this->_libraries) && is_array($this->_libraries)) {
			$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Email Templates'));
			foreach ($libraryList as $libraryId => $displayName) {
				$libraryEmailTemplate = new LibraryEmailTemplate();
				$libraryEmailTemplate->libraryId = $libraryId;
				$libraryEmailTemplate->emailTemplateId = $this->id;
				$alreadyLinked = $libraryEmailTemplate->find(true);
				if (in_array($libraryId, $this->_libraries)) {
					if (!$alreadyLinked){
						$libraryEmailTemplate = new LibraryEmailTemplate();
						$libraryEmailTemplate->libraryId = $libraryId;
						$libraryEmailTemplate->emailTemplateId = $this->id;
						$libraryEmailTemplate->insert();
					}
				} else {
					if ($alreadyLinked) {
						$libraryEmailTemplate->delete();
					}
				}
			}
			unset($this->_libraries);
		}
	}

	public static function getActiveTemplate(string $templateType) : ?EmailTemplate{
		global $library;
		global $activeLanguage;
		$templateFound = false;
		//First look for a template based on the active language
		$emailTemplate = new EmailTemplate();
		$emailTemplate->templateType = $templateType;
		$emailTemplate->languageCode = $activeLanguage->code;
		$emailTemplate->find();
		while ($emailTemplate->fetch()) {
			$librariesForTemplate = $emailTemplate->getLibraries();
			if (in_array($library->libraryId, $librariesForTemplate)) {
				$templateFound = true;
			}
		}
		//If we didn't find a template for the active language, check english
		if (!$templateFound) {
			$emailTemplate = new EmailTemplate();
			$emailTemplate->templateType = $templateType;
			$emailTemplate->languageCode = 'en';
			$emailTemplate->find();
			while ($emailTemplate->fetch()) {
				$librariesForTemplate = $emailTemplate->getLibraries();
				if (in_array($library->libraryId, $librariesForTemplate)) {
					$templateFound = true;
				}
			}
		}

		if ($templateFound) {
			return $emailTemplate;
		}else{
			return null;
		}
	}

	public function sendEmail($toEmail, $parameters) {
		global $logger;

		if (empty($toEmail)) {
			return false;
		}
		$plainTextBody = $this->plainTextBody;
		$updatedBody = $this->applyParameters($this->plainTextBody, $parameters);
		$logger->log("PLAIN BODY: " . $plainTextBody, Logger::LOG_ERROR);
		$logger->log("SUBJECT: " . $this->subject, Logger::LOG_ERROR);

		$updatedSubject = $this->applyParameters($this->subject, $parameters);

		require_once ROOT_DIR . '/sys/Email/Mailer.php';
		$mail = new Mailer();
		return $mail->send($toEmail, $updatedSubject, $updatedBody, null);
	}

	private function applyParameters($text, $parameters) {
		if ($this->templateType == 'welcome') {
			/* @var User $user */
			$user = $parameters['user'];
			/* @var Library $library */
			$library = $parameters['library'];
			if (empty($library->baseUrl)) {
				global $configArray;
				$baseUrl = $configArray['Site']['url'];
			} else {
				$baseUrl = $library->baseUrl;
			}

			$text = str_replace('%library.displayName%', $library->displayName, $text);
			$text = str_replace('%library.baseUrl%', $baseUrl, $text);
			$text = str_replace('%library.email%', $library->contactEmail, $text);
			$text = str_ireplace('%user.firstname%', $user->firstname, $text);
			$text = str_ireplace('%user.lastname%', $user->lastname, $text);
			$text = str_ireplace('%user.ils_barcode%', $user->ils_barcode, $text);
		} elseif ($this->templateType == 'campaignStart' || $this->templateType == 'campaignEnroll' || $this->templateType == 'campaignComplete' || $this->templateType == 'milestoneComplete' || $this->templateType == 'campaignEnding' || $this->templateType == 'staffCampaignComplete') {
			$user = $parameters['user'];
			/* @var Library $library */
			$library = $parameters['library'];
			if (empty($library->baseUrl)) {
				global $configArray;
				$baseUrl = $configArray['Site']['url'];
			} else {
				$baseUrl = $library->baseUrl;
			}

			$text = str_replace('%library.displayName%', $library->displayName, $text);
			$text = str_replace('%library.baseUrl%', $baseUrl, $text);
			$text = str_replace('%library.email%', $library->contactEmail, $text);
			$text = str_ireplace('%user.firstname%', $user->firstname, $text);
			$text = str_ireplace('%user.lastname%', $user->lastname, $text);
			$text = str_ireplace('%user.ils_barcode%', $user->ils_barcode, $text);
			$text = str_replace('%campaign.name%', $parameters['campaignName'], $text);
			$text = str_replace('%milestone.name%', $parameters['milestoneName'], $text);
		}
		return $text;
	}
}