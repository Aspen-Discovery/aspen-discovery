<?php /** @noinspection PhpMissingFieldTypeInspection */
require_once ROOT_DIR . '/sys/LibraryLocation/LibraryEmailTemplate.php';

class EmailTemplate extends DataObject {
	public $__table = 'email_template';
	public $id;
	public $name;
	public $templateType;
	public $languageCode;
	public $subject;
	public $plainTextBody;
	public $htmlBody;

	private $_libraries;

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context]) && self::$_objectStructure[$context] !== null) {
			return self::$_objectStructure[$context];
		}
		$isCarlX = false;
		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Email Templates'));
		foreach (UserAccount::getAccountProfiles() as $accountProfileInfo) {
			/** @var AccountProfile $accountProfile */
			$accountProfile = $accountProfileInfo['accountProfile'];
			if ($accountProfile->ils == 'carlx') {
				$isCarlX = true;
			}
		}
		$availableTemplates = [
			'welcome' => 'Welcome',
			'savedSearchAlert' => 'Saved Search Alert',
		];
		global $enabledModules;
		if (in_array('Community Engagement', $enabledModules)) {
			$availableTemplates += [
				'campaignStart' => 'Campaign Start',
				'campaignEnd' => 'Campaign Ending',
				'campaignEnroll' => 'Campaign Enrollment',
				'campaignComplete' => 'Campaign Complete',
				'milestoneComplete' => 'Milestone Complete',
				'staffCampaignComplete' => 'Campaign Complete Staff Alert',
			];
		}
		if ($isCarlX){
			$availableTemplates += [
				'duplicateNameDOB' => 'Duplicate Name and Birthdate',
				'duplicateEmail' => 'Duplicate Email'
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
				'descriptions' => 'Instructions for the template including variables that can be added.',
				'doNotEscape' => true,
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
			'htmlBody' => [
				'property' => 'htmlBody',
				'type' => 'html',
				'label' => 'HTML Body',
				'description' => 'The html body of the email (will use plain text if left blank)',
				'hideInLists' => true,
				'required' => false,
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

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
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

	public function getLibraries() : ?array {
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
	public function setLibraries($val) : void {
		$this->_libraries = $val;
	}

	/** @noinspection PhpUnused */
	public function clearLibraries() : void {
		$this->clearOneToManyOptions('LibraryEmailTemplate', 'emailTemplateId');
		unset($this->_libraries);
	}

	/**
	 * Override the update functionality to save related objects
	 *
	 * @see DB/DB_DataObject::update()
	 */
	public function update(string $context = '') : int|bool {
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
	public function insert(string $context = '') : int|bool {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLibraries();
		}
		return $ret;
	}

	public function delete(bool $useWhere = false, bool $hardDelete = false) : bool|int {
		$ret = parent::delete($useWhere, $hardDelete);
		if ($ret && !empty($this->id)) {
			$this->clearLibraries();
		}
		return $ret;
	}

	public function saveLibraries() : void {
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

	public static function getActiveTemplate(string $templateType, ?User $user) : ?EmailTemplate{
		global $library;
		global $activeLanguage;
		$libraryId = $user == null ? $library->libraryId : ($user->getHomeLibrary() == null ? $library->libraryId : $user->getHomeLibrary()->libraryId);
		$activeLanguageCode = $user == null ? $activeLanguage->code : $user->interfaceLanguage;
		$templateFound = false;
		//First look for a template based on the active language
		$tmpEmailTemplate = new EmailTemplate();
		$tmpEmailTemplate->templateType = $templateType;
		$tmpEmailTemplate->languageCode = $activeLanguageCode;
		$tmpEmailTemplate->find();
		$matchingTemplates = $tmpEmailTemplate->fetchAll();
		foreach ($matchingTemplates as $emailTemplate) {
			$librariesForTemplate = $emailTemplate->getLibraries();
			if (in_array($libraryId, $librariesForTemplate)) {
				$templateFound = true;
				break;
			}
		}
		//If we didn't find a template for the active language, check english
		if (!$templateFound && $activeLanguage != 'en') {
			$emailTemplate = new EmailTemplate();
			$emailTemplate->templateType = $templateType;
			$emailTemplate->languageCode = 'en';
			$emailTemplate->find();
			while ($emailTemplate->fetch()) {
				$librariesForTemplate = $emailTemplate->getLibraries();
				if (in_array($library->libraryId, $librariesForTemplate)) {
					$templateFound = true;
					break;
				}
			}
		}

		if ($templateFound) {
			return $emailTemplate;
		}else{
			return EmailTemplate::getDefaultTemplate($templateType);
		}
	}

	public static function getDefaultTemplate(string $templateType) : ?EmailTemplate {
		global $activeLanguage;
		$emailTemplate = new EmailTemplate();
		$emailTemplate->templateType = $templateType;
		$emailTemplate->languageCode = $activeLanguage->code;
		if ($templateType === 'savedSearchAlert') {
			$emailTemplate->subject = 'New Library Materials Match Your Saved Searches';
			$emailTemplate->plainTextBody = "The library has added new materials to its collection that may be of interest based on your saved searches (%searchHistory.url%). You may view and request the material via the link(s) below.\r\n\r\n%searchHistory.updatedSearchesWithSampleTitles%";
			$emailTemplate->htmlBody = "<p>The library has added new materials to its collection that may be of interest based on your <a href='%searchHistory.url%'>saved searches</a>. You may view and request the material via the link(s) below.</p><div>%searchHistory.updatedSearchesWithSampleTitlesHtml%</div>";
			return $emailTemplate;
		}
		return null;
	}

	public function sendEmail($toEmail, $parameters) : bool {
		if (empty($toEmail)) {
			return false;
		}
		$updatedPlainTextBody = $this->applyParameters($this->plainTextBody, $parameters);
		if (empty($this->htmlBody)) {
			$updatedHtmlBody = $updatedPlainTextBody;
			$updatedHtmlBody = str_replace("\r\n", "<br/>", $updatedHtmlBody);
		}else{
			$updatedHtmlBody = $this->applyParameters($this->htmlBody, $parameters);
		}
		$updatedSubject = $this->applyParameters($this->subject, $parameters);

		require_once ROOT_DIR . '/sys/Email/Mailer.php';
		$mail = new Mailer();
		return $mail->send($toEmail, $updatedSubject, $updatedPlainTextBody, null, $updatedHtmlBody);
	}

	private function applyParameters($text, $parameters) {
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

		$text = str_replace('%library.displayName%', $library->displayName ?? '', $text);
		$text = str_replace('%library.baseUrl%', $baseUrl, $text);
		$text = str_ireplace('%library.messagingSettingsUrl%', $baseUrl . "/MyAccount/MessagingSettings", $text);
		$text = str_replace('%library.email%', $library->contactEmail ?? '', $text);
		$text = str_ireplace('%user.firstname%', $user->firstname ?? '', $text);
		$text = str_ireplace('%user.lastname%', $user->lastname ?? '', $text);
		$text = str_ireplace('%user.userPreferredName%', $user->userPreferredName ?? '', $text);
		$text = str_ireplace('%user.ils_barcode%', $user->ils_barcode ?? '', $text);

		if ($this->templateType == 'campaignStart' || $this->templateType == 'campaignEnroll' || $this->templateType == 'campaignComplete' ||  $this->templateType == 'campaignEnding') {
			$text = str_replace('%campaign.name%', $parameters['campaignName'] ?? '', $text);
			$text = str_replace('%campaign.reward%', $parameters['campaignReward'] ?? '', $text);
			$text = str_replace('%milestoneSummary%', $parameters['milestoneSummary'] ?? '', $text);
		} elseif ($this->templateType == 'staffCampaignComplete') {
			$text = str_replace('%campaign.name%', $parameters['campaignName'] ?? '', $text);
		} elseif ($this->templateType == 'milestoneComplete') {
			$text = str_replace('%campaign.name%', $parameters['campaignName'] ?? '', $text);
			$text = str_replace('%milestone.name%', $parameters['milestoneName'] ?? '', $text);
			$text = str_replace('%milestone.reward%', $parameters['milestoneReward'] ?? '', $text);
		} elseif ($this->templateType == 'savedSearchAlert') {
			$text = str_replace('%searchHistory.url%', $parameters['searchHistory']['url'] ?? '', $text);
			$text = str_replace('%searchHistory.updatedSearchesWithSampleTitlesHtml%', $parameters['searchHistory']['updatedSearchesWithSampleTitlesHtml'] ?? '', $text);
			$text = str_replace('%searchHistory.updatedSearchesWithSampleTitles%', $parameters['searchHistory']['updatedSearchesWithSampleTitles'] ?? '', $text);
			$text = str_replace('%searchHistory.updatedSearchesHtml%', $parameters['searchHistory']['updatedSearchesHtml'] ?? '', $text);
			$text = str_replace('%searchHistory.updatedSearches%', $parameters['searchHistory']['updatedSearches'] ?? '', $text);
		}
		return $text;
	}
}