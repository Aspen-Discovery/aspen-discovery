<?php
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
		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Email Templates'));
		$availableTemplates = [
			'self_registration' => 'Self Registration'
		];
		require_once ROOT_DIR . '/sys/Translation/Language.php';
		$validLanguage = new Language();
		$validLanguage->orderBy("weight");
		$validLanguage->find(true);
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
			'language' => [
				'property' => 'language',
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

		return $structure;
	}


	/** @return Library[]
	 * @noinspection PhpUnused
	 */
	public function getLibraries() {
		return $this->_libraries;
	}

	/** @noinspection PhpUnused */
	public function setLibraries($val) {
		$this->_libraries = $val;
	}

	/** @noinspection PhpUnused */
	public function clearLibraries() {
		$this->clearOneToManyOptions('Library', 'groupedWorkDisplaySettingId');
		unset($this->_libraries);
	}
}