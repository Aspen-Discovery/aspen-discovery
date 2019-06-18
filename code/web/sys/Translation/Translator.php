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

	/**
	 * Translate the phrase
	 *
	 * @param string $phrase                - The phrase to translate
	 * @param string $defaultText           - The default text for a phrase that is just a key for a longer phrase
	 * @param string[] $replacementValues   - Values to replace within the string
	 * @param bool $inAttribute             - Whether or not we are in an attribute. If we are, we can't show the span
	 * @return  string                      - The translated phrase
	 */
	function translate($phrase, $defaultText = '', $replacementValues = [], $inAttribute = false)
	{
		//TODO: Determine if there is a performance improvement to preloading all of this, or if caching within Memcache is good enough
		/** @var Language */
		global $activeLanguage;
		$translationMode = $this->translationModeActive() && !$inAttribute && (UserAccount::userHasRole('opacAdmin') || UserAccount::userHasRole('translator'));
		try{
			/** @var Memcache $memCache */
			global $memCache;

			$existingTranslation = $memCache->get('translation_' . $activeLanguage->id . '_' . $translationMode . '_' . $phrase);
			if ($existingTranslation == false || isset($_REQUEST['reload'])){
				//Search for the term
				$translationTerm = new TranslationTerm();
				$translationTerm->term = $phrase;
				if (!$translationTerm->find(true)){
					//Insert the translation term
					$translationTerm->samplePageUrl = $_SERVER['REQUEST_URI'];
					try{
						$translationTerm->insert();
					}catch(Exception $e){
						return "TERM TOO LONG for translation \"$phrase\"";
					}

				}

				//Search for the translation
				$translation = new Translation();
				$translation->termId = $translationTerm->id;
				$translation->languageId = $activeLanguage->id;
				if (!$translation->find(true)){
					if (!empty($defaultText)){
						$defaultTranslation = $defaultText;
						$translation->translated = ($activeLanguage->id == 1) ? 1 : 0;
					}else{
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

					$translation->translation = $defaultTranslation;
					$ret = $translation->update();
					if (!$ret){
						global $logger;
						$logger->log("Could not update translation", Logger::LOG_ERROR);
					}
				}

				if ($translationMode){
					if ($translation->translated){
						$translationStatus = 'translated';
					}else{
						$translationStatus = 'not_translated';
					}
					$translationIdentifier = "<span class='translation_id translation_id_{$translation->id} {$translationStatus}' onclick=\"event.stopPropagation();return AspenDiscovery.showTranslateForm('{$translationTerm->id}');\">{$translationTerm->id}</span> ";
					$fullTranslation = "<span class='term_{$translationTerm->id}'>$translation->translation</span> $translationIdentifier";
				}else{
					$fullTranslation = $translation->translation;
				}

				global $configArray;
				$memCache->set('translation_' . $activeLanguage->id . '_' . $translationMode . '_' . $phrase, $fullTranslation, $configArray['Caching']['translation']);
				$returnString = $fullTranslation;
			}else{
				$returnString = $existingTranslation;
			}
		}catch (PDOException $e){
			//tables likely don't exist, ignore
			$returnString = $phrase;
		}
		if (count($replacementValues) > 0){
			foreach ($replacementValues as $index => $replacementValue){
				$returnString = str_replace('%' . ($index + 1) . '%', $replacementValue, $returnString);
			}
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
				} else {
					AspenError::raiseError("Unknown language file");
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
				session_start();
				$_SESSION['translationMode'] = 'on';
				session_write_close();
				$translationModeActive = true;
			}elseif (isset($_REQUEST['stopTranslationMode'])){
				session_start();
				$_SESSION['translationMode'] = 'off';
				session_write_close();
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
}