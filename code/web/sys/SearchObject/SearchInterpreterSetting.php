<?php /** @noinspection PhpMissingFieldTypeInspection */
require_once ROOT_DIR . '/sys/SearchObject/SearchInterpreterTermsToSkip.php';
require_once ROOT_DIR . '/sys/SearchObject/SearchInterpreterSpecialTerms.php';

class SearchInterpreterSetting extends DataObject {
	public $__table = 'search_interpreter_settings';
	public $id;
	public $processFormatCategories;
	public $formatCategoriesToSkip;
	public $processFormats;
	public $formatsToSkip;
	public $processPluralFormats;
	public $pluralFormatsToSkip;
	public $processAudiences;
	public $audiencesToSkip;
	public $processPluralAudiences;
	public $pluralAudiencesToSkip;
	public $processFictionNonFiction;
	public $processNew;
	public $processAvailable;
	public $triggerSeriesSearch;

	public $_termsToSkip;
	public $_specialTerms;


	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context]) && self::$_objectStructure[$context] !== null) {
			return self::$_objectStructure[$context];
		}

		require_once ROOT_DIR . '/sys/Indexing/IndexedFormat.php';
		$indexedFormatObj = new IndexedFormat();
		$indexedFormatObj->orderBy('format');
		$indexedFormats = $indexedFormatObj->fetchAll('format');

		$termsToSkipStructure = SearchInterpreterTermsToSkip::getObjectStructure($context);
		$specialTermsStructure = SearchInterpreterSpecialTerms::getObjectStructure($context);
		unset($specialTermsStructure['weight']);

		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id within the database',
				'uniqueProperty' => true,
			],
			'termsToSkip' => [
				'property' => 'termsToSkip',
				'type' => 'oneToMany',
				'label' => 'Terms To Skip Interpreting',
				'description' => 'A list of terms that will cause the search to not be interpreted.',
				'noteBullets' => [
					'These are applied at the beginning of processing and if found, will cause the search interpreter to stop processing.',
					'Terms are case insensitive.',
				],
				'keyThis' => 'id',
				'keyOther' => 'settingId',
				'subObjectType' => 'SearchInterpreterTermsToSkip',
				'structure' => $termsToSkipStructure,
				'sortable' => false,
				'storeDb' => true,
				'allowEdit' => false,
				'canEdit' => false,
				'canAddNew' => true,
				'canDelete' => true,
			],
			'formatSection' => [
				'property' => 'formatSection',
				'type' => 'section',
				'label' => 'Formats (applies the Format facet)',
				'hideInLists' => true,
				'expandByDefault' => true,
				'properties' => [
					'processFormats' => [
						'property' => 'processFormats',
						'type' => 'checkbox',
						'label' => 'Process Formats',
						'description' => 'Whether formats should be processed in the interpreter.',
						'hideInLists' => true,
						'default' => 1,
					],
					'formatsToSkip' => [
						'property' => 'formatsToSkip',
						'type' => 'text',
						'label' => 'Formats to Skip',
						'description' => 'A pipe delimited list of values to skip',
						'noteBullets' => [
							'Separate values with pipes or commas',
							'Valid values are ' . implode(', ', $indexedFormats),
						],
						'default' => '',
						'maxLength' => 255
					],
					'processPluralFormats' => [
						'property' => 'processPluralFormats',
						'type' => 'checkbox',
						'label' => 'Process Plural Formats',
						'description' => 'Whether formats should be processed with an added s in the interpreter.',
						'hideInLists' => true,
						'default' => 1,
					],
					'pluralFormatsToSkip' => [
						'property' => 'pluralFormatsToSkip',
						'type' => 'text',
						'label' => 'Plural Formats to Skip',
						'description' => 'A pipe delimited list of values to skip',
						'default' => '',
						'maxLength' => 255
					],
				],
			],
			'formatCategoriesSection' => [
				'property' => 'formatCategoriesSection',
				'type' => 'section',
				'label' => 'Format Categories (applies the Format Category facet)',
				'hideInLists' => true,
				'expandByDefault' => true,
				'properties' => [
					'processFormatCategories' => [
						'property' => 'processFormatCategories',
						'type' => 'checkbox',
						'label' => 'Process Format Categories',
						'description' => 'Whether format categories should be processed in the interpreter.',
						'hideInLists' => true,
						'default' => 1,
					],
					'formatCategoriesToSkip' => [
						'property' => 'formatCategoriesToSkip',
						'type' => 'text',
						'label' => 'Format Categories to Skip',
						'description' => 'A pipe delimited list of values to skip',
						'note' => 'Valid values are Books,eBooks,Audio Books,Music,Movies',
						'default' => '',
						'maxLength' => 255
					],
				],
			],
			'audienceSection' => [
				'property' => 'audienceSection',
				'type' => 'section',
				'label' => 'Audiences (applies the Audience facet)',
				'hideInLists' => true,
				'expandByDefault' => true,
				'properties' => [
					'processAudiences' => [
						'property' => 'processAudiences',
						'type' => 'checkbox',
						'label' => 'Process Audiences',
						'description' => 'Whether audiences should be processed in the interpreter.',
						'hideInLists' => true,
						'default' => 1,
					],
					'audiencesToSkip' => [
						'property' => 'audiencesToSkip',
						'type' => 'text',
						'label' => 'Audience to Skip',
						'description' => 'A pipe delimited list of values to skip',
						'note' => 'Valid values are Kid,Teen,Adult',
						'default' => '',
						'maxLength' => 255
					],
					'processPluralAudiences' => [
						'property' => 'processPluralAudiences',
						'type' => 'checkbox',
						'label' => 'Process Plural Audiences',
						'description' => 'Whether audiences should be processed with an added s in the interpreter.',
						'note' => 'Valid values are Kids,Teens,Adults',
						'hideInLists' => true,
						'default' => 1,
					],
					'pluralAudiencesToSkip' => [
						'property' => 'pluralAudiencesToSkip',
						'type' => 'text',
						'label' => 'Plural Audiences to Skip',
						'description' => 'A pipe delimited list of values to skip',
						'default' => '',
					],
				],
			],
			'fictionSection' => [
				'property' => 'fictionSection',
				'type' => 'section',
				'label' => 'Fiction/Non-Fiction (applies the Form facet)',
				'hideInLists' => true,
				'expandByDefault' => true,
				'properties' => [
					'processFictionNonFiction' => [
						'property' => 'processFictionNonFiction',
						'type' => 'checkbox',
						'label' => 'Process Fiction/Non-Fiction',
						'description' => 'Whether fiction/non-fiction should be processed in the interpreter.',
						'hideInLists' => true,
						'default' => 1,
					],
				],
			],
			'modifiersSection' => [
				'property' => 'modifiersSection',
				'type' => 'section',
				'label' => 'Modifiers',
				'hideInLists' => true,
				'expandByDefault' => true,
				'properties' => [
					'processNew' => [
						'property' => 'processNew',
						'type' => 'checkbox',
						'label' => 'Process New Modifier (applies the Earliest Publication Year facet)',
						'description' => 'Whether the search should be checked for new searches.',
						'hideInLists' => true,
						'default' => 1,
					],
					'processAvailable' => [
						'property' => 'processAvailable',
						'type' => 'checkbox',
						'label' => 'Process Available Modifier (applies the Search Within facet)',
						'description' => 'Whether the search should be checked for available searches.',
						'hideInLists' => true,
						'default' => 1,
					],
				],
			],
			'additionalSection' => [
				'property' => 'additionalSection',
				'type' => 'section',
				'label' => 'Additional Settings',
				'hideInLists' => true,
				'expandByDefault' => true,
				'properties' => [
					'triggerSeriesSearch' => [
						'property' => 'triggerSeriesSearch',
						'type' => 'checkbox',
						'label' => 'Trigger Series Search',
						'description' => 'Whether the search interpreter should default to a series search when the keyword &ldquo;series&rdquo; is used.',
						'hideInLists' => true,
						'default' => 0,
					],
				],
			],
			'specialTerms' => [
				'property' => 'specialTerms',
				'type' => 'oneToMany',
				'label' => 'Special Terms',
				'description' => 'A list of terms that will be processed according to library defined rules.',
				'noteBullets' => [
					'Special Terms are applied after the standard fields above.',
					'Terms are case insensitive.',
					'Terms can be regular expressions.',
					'If you are applying special terms that contain a format, audience, etc you should skip that value above. I.e. if you create a special term for PlayStation, skip the PlayStation format above.',
					"Each Facet should be on it's own line.",
					"Format each facet to apply as <em>facet_name</em>:<em>facet_value</em>."
				],
				'keyThis' => 'id',
				'keyOther' => 'settingId',
				'subObjectType' => 'SearchInterpreterSpecialTerms',
				'structure' => $specialTermsStructure,
				'sortable' => true,
				'storeDb' => true,
				'allowEdit' => false,
				'canEdit' => false,
				'canAddNew' => true,
				'canDelete' => true,
			]
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	public function __get($name) {
		if ($name == "termsToSkip") {
			return $this->getTermsToSkip();
		} else if ($name == "specialTerms") {
			return $this->getSpecialTerms();
		} else {
			return parent::__get($name);
		}
	}

	/**
	 * @return SearchInterpreterTermsToSkip[]
	 */
	public function getTermsToSkip(): array {
		if (!isset($this->_termsToSkip)) {
			$this->_termsToSkip = [];
			if ($this->id) {
				$obj = new SearchInterpreterTermsToSkip();
				$obj->settingId = $this->id;
				$obj->find();
				while ($obj->fetch()) {
					$this->_termsToSkip[$obj->id] = clone $obj;
				}
			}
		}
		return $this->_termsToSkip;
	}

	private ?array $_termsToSkipString = null;

	/**
	 * @return string[]
	 */
	public function getTermsToSkipAsStrings() : array {
		if ($this->_termsToSkipString == null) {
			$obj = new SearchInterpreterTermsToSkip();
			$obj->settingId = $this->id;
			$this->_termsToSkipString = $obj->fetchAll('term');
		}
		return $this->_termsToSkipString;
	}

	/**
	 * @return SearchInterpreterSpecialTerms[]
	 */
	public function getSpecialTerms(): array {
		if (!isset($this->_specialTerms)) {
			$this->_specialTerms = [];
			if ($this->id) {
				$obj = new SearchInterpreterSpecialTerms();
				$obj->settingId = $this->id;
				$obj->orderBy('weight');
				$obj->find();
				while ($obj->fetch()) {
					$this->_specialTerms[$obj->id] = clone $obj;
				}
			}
		}
		return $this->_specialTerms;
	}

	public function __set($name, $value) {
		if ($name == "termsToSkip") {
			$this->_termsToSkip = $value;
		} else if ($name == "specialTerms") {
			$this->_specialTerms = $value;
		} else {
			parent::__set($name, $value);
		}
	}

	public function update(string $context = '') : int|bool {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveTermsToSkip();
			$this->saveSpecialTerms();
		}
		return $ret;
	}

	public function insert(string $context = '') : int|bool {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveTermsToSkip();
			$this->saveSpecialTerms();
		}
		return $ret;
	}

	public function delete(bool $useWhere = false, bool $hardDelete = false) : bool|int {
		$ret = parent::delete($useWhere, $hardDelete);
		if ($ret && !empty($this->id)) {
			$this->clearOneToManyOptions('SearchInterpreterTermsToSkip', 'settingId');
			$this->clearOneToManyOptions('SearchInterpreterSpecialTerms', 'settingId');
		}
		return $ret;
	}

	public function saveTermsToSkip() : void {
		if (isset ($this->_termsToSkip) && is_array($this->_termsToSkip)) {
			$this->saveOneToManyOptions($this->_termsToSkip, 'settingId');
			unset($this->_termsToSkip);
		}
	}

	public function saveSpecialTerms() : void {
		if (isset ($this->_specialTerms) && is_array($this->_specialTerms)) {
			$this->saveOneToManyOptions($this->_specialTerms, 'settingId');
			unset($this->_specialTerms);
		}
	}
}