<?php

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

	static function getObjectStructure($context = ''): array {
		return [
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
		];
	}

	public static function getLanguageList() {
		$language = new Language();
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
		$language->orderBy('displayName');
		$language->find();
		$languageList = [];
		while ($language->fetch()) {
			$languageList[$language->code] = $language->id;
		}
		return $languageList;
	}

	public function getNumericColumnNames(): array {
		return [
			'id',
			'weight',
		];
	}

	static $rtl_languages = [
		'ar',
		'he',
        'ku',
	];

	public function isRTL() {
		return in_array($this->code, Language::$rtl_languages);
	}

	public function update($context = '') {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveTranslations();
		}
		return true;
	}

	public function insert($context = '') {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveTranslations();
		}
		return $ret;
	}

	public function saveTranslations() {
		if($this->code !== 'pig' && $this->code !== 'ubb') {
			require_once ROOT_DIR . '/sys/Translation/TranslationTerm.php';
			require_once ROOT_DIR . '/sys/Translation/Translation.php';
			$allTranslationTerms = new TranslationTerm();
			$translationTerms = array_filter($allTranslationTerms->fetchAll('id'));
			foreach($translationTerms as $translationTermId) {
				$term = new TranslationTerm();
				$term->id = $translationTermId;
				if($term->find(true))  {
					if($term->isMetadata == 0 && $term->isAdminEnteredData == 0) {
						$translation = new Translation();
						$translation->termId = $translationTermId;
						$translation->languageId = $this->id;
						if (!$translation->find(true)) {
							try {
								$now = time();
								$translationResponse = $this->getCommunityTranslations($term->term, $this);
								if ($translationResponse['isTranslatedInCommunity']) {
									$translation->translated = 1;
									$translation->translation = trim($translationResponse['translation']);
								} else {
									$translation->lastCheckInCommunity = $now;
								}
								$translation->update();
							} catch (Exception $e) {
								// This will happen before last check in community is set.
							}
						}

						$translation->__destruct();
						$translation = null;
					}
				}
				$term->__destruct();
				$term = null;
			}
		}
	}

	/**
	 * @param string $phrase
	 * @param Language $activeLanguage
	 * @return array
	 */
	function getCommunityTranslations(string $phrase, $activeLanguage): array {
		require_once ROOT_DIR . '/sys/SystemVariables.php';
		$systemVariables = SystemVariables::getSystemVariables();
		$translatedInCommunity = false;
		$defaultTranslation = null;
		if ($systemVariables && !empty($systemVariables->communityContentUrl)) {
			require_once ROOT_DIR . '/sys/CurlWrapper.php';
			$communityContentCurlWrapper = new CurlWrapper();
			$body = [
				'term' => $phrase,
				'languageCode' => $activeLanguage,
			];
			$response = $communityContentCurlWrapper->curlPostPage($systemVariables->communityContentUrl . '/API/CommunityAPI?method=getDefaultTranslation', $body);
			if ($response !== false) {
				$jsonResponse = json_decode($response);
				if (!empty($jsonResponse) && $jsonResponse->success) {
					$defaultTranslation = $jsonResponse->translation;
					$translatedInCommunity = true;
				}
			}
		}
		return [
			'isTranslatedInCommunity' => $translatedInCommunity,
			'translation' => $defaultTranslation
		];
	}
}