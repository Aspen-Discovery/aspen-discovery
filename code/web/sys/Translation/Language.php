<?php /** @noinspection PhpMissingFieldTypeInspection */

class Language extends DataObject {
	public $__table = 'languages';
	public $id;
	public $weight;
	public $code;
	public $displayName;
	public $displayNameEnglish;
	public $locale;
	public $facetValue;
	public $displayToTranslatorsOnly;
	public $isDefault;

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context]) && self::$_objectStructure[$context] !== null) {
			return self::$_objectStructure[$context];
		}
		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'hidden',
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
			'code' => [
				'property' => 'code',
				'type' => 'text',
				'label' => 'Code',
				'description' => 'The code for the language see https://www.w3schools.com/tags/ref_language_codes.asp',
				'size' => '3',
				'required' => true,
			],
			'displayName' => [
				'property' => 'displayName',
				'type' => 'text',
				'label' => 'Display name - native',
				'description' => 'Display Name for the language in the language itself',
				'size' => '50',
				'required' => true,
			],
			'displayNameEnglish' => [
				'property' => 'displayNameEnglish',
				'type' => 'text',
				'label' => 'Display name - English',
				'description' => 'The display name of the language in English',
				'size' => '50',
				'required' => true,
			],
			'locale' => [
				'property' => 'locale',
				'type' => 'text',
				'label' => 'Locale (i.e. en-US, en-CA, es-US, fr-CA)',
				'description' => 'The locale to use when formatting numbers',
				'default' => 'en-US',
				'required' => true,
			],
			'facetValue' => [
				'property' => 'facetValue',
				'type' => 'text',
				'label' => 'Facet Value',
				'description' => 'The facet value for filtering results and applying preferences',
				'size' => '100',
				'required' => true,
			],
			'displayToTranslatorsOnly' => [
				'property' => 'displayToTranslatorsOnly',
				'type' => 'checkbox',
				'label' => 'Display To Translators Only',
				'description' => 'Whether or not only translators should see the translation (good practice before the translation is completed)',
				'default' => 0,
			],
			'isDefault' => [
				'property' => 'isDefault',
				'type' => 'checkbox',
				'label' => 'Default Language',
				'description' => 'Whether this is the default language for unauthenticated users. Only one language can be set as default.',
				'default' => 0,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	/**
	 * @return string[]
	 */
	public static function getLanguageList() : array {
		$language = new Language();
		$language->selectAdd();
		$language->selectAdd('id');
		$language->selectAdd('displayName');
		$language->orderBy('displayName');
		$language->find();
		$languageList = [];
		while ($language->fetch()) {
			$languageList[$language->id] = $language->displayName;
		}
		return $languageList;
	}

	public static function getLanguageIdsByCode(): array {
		$language = new Language();
		$language->selectAdd('code');
		$language->selectAdd('id');
		$language->orderBy('displayName');
		$language->find();
		$languageList = [];
		while ($language->fetch()) {
			$languageList[$language->code] = $language->id;
		}
		return $languageList;
	}

	private static $_validLanguages = null;

	/**
	 * @return Language[]
	 */
	public static function getValidLanguages() : array {
		if (self::$_validLanguages == null) {
			$validLanguages = [];
			try {
				require_once ROOT_DIR . '/sys/Translation/Language.php';
				$validLanguage = new Language();
				$validLanguage->orderBy(["weight", "displayName"]);
				$validLanguage->find();
				$userIsTranslator = UserAccount::userHasPermission('Translate Aspen');
				while ($validLanguage->fetch()) {
					if (!$validLanguage->displayToTranslatorsOnly || $userIsTranslator) {
						$validLanguages[$validLanguage->code] = clone $validLanguage;
					}
				}
			} catch (Exception) {
				$defaultLanguage = new Language();
				$defaultLanguage->code = 'en';
				$defaultLanguage->displayName = 'English';
				$defaultLanguage->displayNameEnglish = 'English';
				$defaultLanguage->facetValue = 'English';
				$validLanguages['en'] = $defaultLanguage;
			}
			self::$_validLanguages = $validLanguages;
		}
		return  self::$_validLanguages;
	}

	public function update($context = ''): bool|int {
		if ($this->isDefault) {
			$other = new Language();
			$other->whereAdd('id != ' . (int)$this->id);
			$other->find();
			while ($other->fetch()) {
				if ($other->isDefault) {
					$other->isDefault = 0;
					$other->update();
				}
			}
		}
		self::$_validLanguages = null;
		return parent::update($context);
	}

	public static function getDefaultLanguageCode(): string {
		$validLanguages = self::getValidLanguages();
		$language = new Language();
		$language->isDefault = 1;
		if ($language->find(true) && isset($validLanguages[$language->code])) {
			return $language->code;
		}
		return array_key_first($validLanguages) ?? 'en';
	}

	public static function getLanguageFromBrowser(): string {
		if (empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
			return '';
		}
		$validLanguages = self::getValidLanguages();
		$accepted = [];
		foreach (explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']) as $part) {
			$part = trim($part);
			if (preg_match('/^([a-zA-Z]{1,8}(?:-[a-zA-Z0-9]{1,8})*)(?:;q=([0-9.]+))?$/', $part, $m)) {
				$accepted[strtolower($m[1])] = isset($m[2]) ? (float)$m[2] : 1.0;
			}
		}
		arsort($accepted);
		// Exact match first (e.g. "es" matches language code "es")
		foreach ($accepted as $tag => $q) {
			if (isset($validLanguages[$tag])) {
				return $tag;
			}
		}
		// Primary subtag match (e.g. "es-419" or "es-US" matches code "es")
		foreach ($accepted as $tag => $q) {
			$primary = explode('-', $tag)[0];
			foreach (array_keys($validLanguages) as $code) {
				if (strtolower(explode('-', $code)[0]) === $primary) {
					return $code;
				}
			}
		}
		return '';
	}

	public function getNumericColumnNames(): array {
		return [
			'id',
			'weight',
			'isDefault',
		];
	}

	static $rtl_languages = [
		'ar',
		'he',
        'ku',
	];

	public function isRTL() : bool {
		return in_array($this->code, Language::$rtl_languages);
	}
}