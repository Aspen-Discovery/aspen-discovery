<?php

require_once ROOT_DIR . '/sys/Translation/TranslationTerm.php';
require_once ROOT_DIR . '/sys/Translation/Translation.php';
class Translator
{
	/** @var string path to the translation file */
	var $path;
	/** @var string the ISO code for the language  */
	var $langCode;
	var $words = array();
	var $debug = false;

	/**
	 * Constructor
	 *
	 * @param $path
	 * @param string $langCode The ISO 639-1 Language Code
	 * @access  public
	 */
	function __construct($path, $langCode)
	{
		global $timer;

		$this->path = $path;
		$this->langCode = preg_replace('/[^\w\-]/', '', $langCode);

		$timer->logTime('Initialize translator for ' . $langCode);
	}

	/**
	 * Parse a language file.
	 *
	 * @param   string $file        Filename to load
	 * @access  private
	 * @return  array
	 */
	function parseLanguageFile($file)
	{
		// Manually parse the language file:
		$words = array();
		$contents = file($file);
		if (is_array($contents)) {
			foreach($contents as $current) {
				if (strlen($current) > 0 && substr($current, 0, 1) != ';'){
					$lineContents = str_getcsv($current, '=', '"');
					if (count($lineContents) == 2){
						$key =trim($lineContents[0]);
						$words[$key] = trim($lineContents[1]);
					}
				}
			}
		}

		return $words;
	}

	//Cache any translations that have already been loaded.
	private $cachedTranslations = [];

	private $greenhouseCurlWrapper = null;

	/**
	 * Translate the phrase
	 *
	 * @param string $phrase                - The phrase to translate
	 * @param string $defaultText           - The default text for a phrase that is just a key for a longer phrase
	 * @param string[] $replacementValues   - Values to replace within the string
	 * @param bool $inAttribute             - Whether or not we are in an attribute. If we are, we can't show the span
	 * @param bool $isPublicFacing          - Whether or not the public will see this
	 * @param bool $isAdminFacing           - Whether or not this is in the admin interface
	 * @param bool $isMetadata              - Whether or not this is a translation of metadata in a MARC record, OverDrive, Axis360, etc
	 * @param bool $isAdminEnteredData      - Whether or not this is data an administrator entered (System message, etc)
	 * @param bool $translateParameters     - Whether or not parameters should be translated
	 * @return  string                      - The translated phrase
	 */
	function translate($phrase, $defaultText = '', $replacementValues = [], $inAttribute = false, $isPublicFacing = false, $isAdminFacing = false, $isMetadata = false, $isAdminEnteredData = false, $translateParameters=false)
	{
		if ($phrase == '' || is_numeric($phrase)){
			return $phrase;
		}

		global $activeLanguage;
		$translationMode = $this->translationModeActive() && !$inAttribute && (UserAccount::userHasPermission('Translate Aspen'));
		try{
			if (!empty($activeLanguage)) {
				$translationKey = $activeLanguage->id . '_' . ($translationMode ? 1 : 0) . '_' . $phrase;
				$existingTranslation = array_key_exists($translationKey, $this->cachedTranslations) ? $this->cachedTranslations[$translationKey] : false;
				if ($existingTranslation == false || isset($_REQUEST['reload'])) {
					//Search for the term
					$translationTerm = new TranslationTerm();
					$translationTerm->term = $phrase;
					$defaultTextChanged = false;
					if (!$translationTerm->find(true)) {
						$translationTerm->defaultText = $defaultText;
						//Insert the translation term
						$translationTerm->samplePageUrl = $_SERVER['REQUEST_URI'];
						$translationTerm->isPublicFacing = $isPublicFacing;
						$translationTerm->isAdminFacing = $isAdminFacing;
						$translationTerm->isMetadata = $isMetadata;
						$translationTerm->isAdminEnteredData = $isAdminEnteredData;
						$translationTerm->lastUpdate = time();
						try {
							$translationTerm->insert();
							//Send this to the Greenhouse as well

							require_once ROOT_DIR . '/sys/SystemVariables.php';
							$systemVariables = SystemVariables::getSystemVariables();
							if ($systemVariables && !empty($systemVariables->greenhouseUrl)) {
								if ($this->greenhouseCurlWrapper == null) {
									require_once ROOT_DIR . '/sys/CurlWrapper.php';
									$this->greenhouseCurlWrapper = new CurlWrapper();
								}
								$body = [
									'term' => $phrase,
									'isPublicFacing' => $isPublicFacing,
									'isAdminFacing' => $isAdminFacing,
									'isMetadata' => $isMetadata,
									'isAdminEnteredData' => $isAdminEnteredData,
								];
								$this->greenhouseCurlWrapper->curlPostPage($systemVariables->greenhouseUrl . '/API/GreenhouseAPI?method=addTranslationTerm', $body);
							}
						} catch (Exception $e) {
							if (UserAccount::isLoggedIn() && UserAccount::userHasPermission('Translate Aspen')) {
								//Just show the phrase for now, maybe show the error in debug mode?
								if (IPAddress::showDebuggingInformation()) {
									return "TERM TOO LONG for translation \"$phrase\"";
								} else {
									return $phrase;
								}
							} else {
								return $phrase;
							}
						}
					} else {
						$termChanged = false;
						if ($defaultText != $translationTerm->defaultText) {
							$translationTerm->defaultText = $defaultText;
							$defaultTextChanged = true;
							$termChanged = true;
						}
						if ($isPublicFacing && !$translationTerm->isPublicFacing) {
							$translationTerm->isPublicFacing = $isPublicFacing;
							$termChanged = true;
						}
						if ($isAdminFacing && !$translationTerm->isAdminFacing) {
							$translationTerm->isAdminFacing = $isAdminFacing;
							$termChanged = true;
						}
						if ($isMetadata && !$translationTerm->isMetadata) {
							$translationTerm->isMetadata = $isMetadata;
							$termChanged = true;
						}
						if ($isAdminEnteredData && !$translationTerm->isAdminEnteredData) {
							$translationTerm->isAdminEnteredData = $isAdminEnteredData;
							$termChanged = true;
						}
						if ($termChanged) {
							$translationTerm->lastUpdate = time();
							$translationTerm->update();
						}
					}

					if ($activeLanguage->code == 'pig') {
						$fullTranslation = $this->getPigLatinTranslation($phrase);
					}elseif ($activeLanguage->code == 'ubb') {
						$fullTranslation = $this->getUbbiDubbiTranslation($phrase);
					}else {
						//Search for the translation
						$translation = new Translation();
						$translation->termId = $translationTerm->id;
						$translation->languageId = $activeLanguage->id;
						if (!$translation->find(true)) {
							if (!empty($defaultText)) {
								$defaultTranslation = $defaultText;
								$translation->translated = ($activeLanguage->id == 1) ? 1 : 0;
							} else {
								//Check the greenhouse to see if there is a translation there
								$translatedInGreenhouse = false;
								if ($activeLanguage->code != 'en') {
									require_once ROOT_DIR . '/sys/SystemVariables.php';
									$systemVariables = SystemVariables::getSystemVariables();
									if ($systemVariables && !empty($systemVariables->greenhouseUrl)) {
										if ($this->greenhouseCurlWrapper == null) {
											require_once ROOT_DIR . '/sys/CurlWrapper.php';
											$this->greenhouseCurlWrapper = new CurlWrapper();
										}
										$body = [
											'term' => $phrase,
											'languageCode' => $activeLanguage->code,
										];
										$response = $this->greenhouseCurlWrapper->curlPostPage($systemVariables->greenhouseUrl . '/API/GreenhouseAPI?method=getDefaultTranslation', $body);
										if ($response !== false) {
											$jsonResponse = json_decode($response);
											if ($jsonResponse->success) {
												$translation->translated = 1;
												$defaultTranslation = $jsonResponse->result->translation;
												$translatedInGreenhouse = true;
											}
										}
									}
								}
								if (!$translatedInGreenhouse) {
									//We don't have a translation in the database, load a default from the ini file if possible
									$this->loadTranslationsFromIniFile();
									if (isset($this->words[$phrase])) {
										$defaultTranslation = $this->words[$phrase];
										$translation->translated = 1;
									} else {
										$translation->translated = ($activeLanguage->id == 1) ? 1 : 0;
										//Nothing in the ini, just return default
										if ($this->debug) {
											$defaultTranslation = "translate_index_not_found($phrase)";
										} else {
											$defaultTranslation = $phrase;
										}
									}
								}
							}

							$translation->translation = $defaultTranslation;
							$ret = $translation->update();
							if (!$ret) {
								global $logger;
								$logger->log("Could not update translation", Logger::LOG_ERROR);
							}
						} else if ($defaultTextChanged) {
							$translation->needsReview = 1;
							$translation->update();
						}

						if ($translationMode) {
							if ($translation->translated) {
								$translationStatus = 'translated';
							} else {
								$translationStatus = 'not_translated';
							}
							$translationIdentifier = "<span class='translation_id translation_id_{$translation->id} {$translationStatus}' onclick=\"event.stopPropagation();return AspenDiscovery.showTranslateForm('{$translationTerm->id}');\">{$translationTerm->id}</span> ";
							$fullTranslation = "<span class='term_{$translationTerm->id}'>$translation->translation</span> $translationIdentifier";
						} else {
							$fullTranslation = $translation->translation;
						}
					}

					$this->cachedTranslations[$translationKey] = $fullTranslation;
					$returnString = $fullTranslation;
				} else {
					$returnString = $existingTranslation;
				}
			}else{
				//Translation not setup (happens from book covers)
				if (!empty($defaultText)){
					$returnString = $defaultText;
				}else{
					$returnString = $phrase;
				}
			}
		}catch (PDOException $e){
			//tables likely don't exist, ignore
			$returnString = $phrase;
			if (!empty($defaultText)){
				$returnString = $defaultText;
			}else{
				$returnString = $phrase;
			}
		}
		if (count($replacementValues) > 0){
			foreach ($replacementValues as $index => $replacementValue){
				if ($translateParameters){
					$replacementValue = $this->translate($replacementValue, '', [], true, $isPublicFacing, $isAdminFacing, $isMetadata, $isAdminEnteredData, $translateParameters);
				}
				$returnString = str_replace('%' . $index . '%', $replacementValue, $returnString);
			}
		}
		if (IPAddress::showDebuggingInformation() && !$isPublicFacing && !$isAdminFacing && !$isMetadata && !$isAdminEnteredData) {
			$returnString .= ' Translation metadata not set properly';
		}
		return $returnString;
	}

	private function loadTranslationsFromIniFile()
	{
		if (empty($this->words)){
			global $configArray;

			// Load file in specified path
			if ($dh = opendir($this->path)) {
				$file = $this->path . '/' . $this->langCode . '.ini';
				if ($this->langCode != '' && is_file($file)) {
					$this->words = $this->parseLanguageFile($file);
				}
				closedir($dh);
			} else {
				AspenError::raiseError("Cannot open $this->path for reading");
			}

			//Check for a more specific language file for the site
			global $serverName;
			$serverLangPath = $configArray['Site']['local'] . '/../../sites/' . $serverName . '/lang';
			if (is_dir($serverLangPath)) {
				if ($dh = @opendir($serverLangPath)) {
					$serverFile = $serverLangPath . '/' . $this->langCode . '.ini';
					if (file_exists($serverFile)) {
						$siteWords = $this->parseLanguageFile($serverFile);
						$this->words = array_merge($this->words, $siteWords);
					}
					closedir($dh);
				}
			}
		}
	}

	private $translationModeActive = null;
	public function translationModeActive(){
		if ($this->translationModeActive === null){
			if (isset($_REQUEST['startTranslationMode'])){
				@session_start();
				$_SESSION['translationMode'] = 'on';
				$translationModeActive = true;
			}elseif (isset($_REQUEST['stopTranslationMode'])){
				@session_start();
				$_SESSION['translationMode'] = 'off';
				$translationModeActive = false;
			}elseif (isset($_SESSION['translationMode'])){
				$translationModeActive = ($_SESSION['translationMode'] == 'on');
			}else{
				$translationModeActive = false;
			}
			$this->translationModeActive = $translationModeActive;
		}
		return $this->translationModeActive;
	}

	private static $vowels = ['a', 'e', 'i', 'o', 'u','y', 'A', 'E', 'I', 'O', 'U'];
	private function getPigLatinTranslation(string $phrase)
	{
		$translation = '';
		$words = explode(' ', $phrase);
		foreach ($words as $word){
			if (preg_match('/%\d+%/', $word)){
				$translation .= $word . ' ';
			}elseif (in_array($word[0], Translator::$vowels)){
				$translation .= $word . 'way ';
			}elseif (strlen($word) >= 2 && !in_array($word[0], Translator::$vowels) && !in_array($word[1], Translator::$vowels)){
				$translation .= substr($word, 2) . $word[0] . $word[1] . 'ay ';
			}else{
				$translation .= substr($word, 1) . $word[0] . 'ay ';
			}
		}
		$translation = strtolower($translation);
		if (preg_match('/[A-Z]/', $phrase[0])){
			$translation = ucfirst($translation);
		}
		return trim($translation);
	}

	private function getUbbiDubbiTranslation(string $phrase) {
		$translation = '';
		$words = explode(' ', $phrase);
		foreach ($words as $word){
			if (preg_match('/%\d+%/', $word)){
				$translation .= $word . ' ';
			}else {
				$translatedWord = '';
				$lastCharWasVowel = false;
				for ($i = 0; $i < strlen($word); $i++){
					$char = $word[$i];
					if (in_array($char, Translator::$vowels)) {
						if (!$lastCharWasVowel) {
							$translatedWord .= 'ub';
						}
						$lastCharWasVowel = true;
					} else {
						$lastCharWasVowel = false;
					}
					$translatedWord .= $char;
				}
				$translation .= $translatedWord . ' ';
			}
		}
		$translation = strtolower($translation);
		if (preg_match('/[A-Z]/', $phrase[0])){
			$translation = ucfirst($translation);
		}
		return trim($translation);
	}
}