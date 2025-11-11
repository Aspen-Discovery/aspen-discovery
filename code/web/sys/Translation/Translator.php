<?php

require_once ROOT_DIR . '/sys/Translation/TranslationTerm.php';
require_once ROOT_DIR . '/sys/Translation/Translation.php';

class Translator {
	/** @var string path to the translation file */
	private string $path;
	/** @var string the ISO code for the language */
	private string $langCode;
	private array $words = [];
	private bool $debug = false;

	/**
	 * Constructor
	 *
	 * @param string $path the path to load translations from
	 * @param string $langCode The ISO 639-1 Language Code
	 * @access  public
	 */
	function __construct(string $path, string $langCode) {
		global $timer;

		$this->path = $path;
		$this->langCode = preg_replace('/[^\w\-]/', '', $langCode);

		$timer->logTime('Initialize translator for ' . $langCode);
	}

	/**
	 * Parse a language file.
	 *
	 * @param string $file Filename to load
	 * @return  array
	 */
	private function parseLanguageFile(string $file) : array {
		// Manually parse the language file:
		$words = [];
		$contents = file($file);
		if (is_array($contents)) {
			foreach ($contents as $current) {
				if (strlen($current) > 0 && !str_starts_with($current, ';')) {
					$lineContents = str_getcsv($current, '=');
					if (count($lineContents) == 2) {
						$key = trim($lineContents[0]);
						$words[$key] = trim($lineContents[1]);
					}
				}
			}
		}

		return $words;
	}

	//Cache any translations that have already been loaded.
	private array $cachedTranslations = [];
	private ?PDOStatement $dbPhraseStmt = null;

	private ?CurlWrapper $communityContentCurlWrapper = null;

	/**
	 * Translate the phrase.
	 *
	 * @param ?string $phrase The phrase to translate.
	 * @param string $defaultText The default text for a phrase that is just a key for a longer phrase.
	 * @param string[] $replacementValues Values to replace within the string.
	 * @param bool $inAttribute Whether we are in an attribute. If we are, we can't show the span.
	 * @param bool $isPublicFacing Whether the public will see this.
	 * @param bool $isAdminFacing Whether this is in the admin interface.
	 * @param bool $isMetadata Whether this is a translation of metadata in a MARC record, OverDrive, Axis360, etc.
	 * @param bool $isAdminEnteredData Whether this is data an administrator entered (System message, etc.).
	 * @param bool $translateParameters Whether parameters should be translated.
	 * @param bool $escape Whether the translation should be escaped before rendering.
	 * @param bool $fromLiDA Whether the translation is being requested from a verified LiDA API call.
	 * @return string The translated phrase.
	 */
	function translate(
		?string $phrase, string $defaultText = '', array $replacementValues = [],
		bool $inAttribute = false, bool $isPublicFacing = false, bool $isAdminFacing = false,
		bool $isMetadata = false, bool $isAdminEnteredData = false,
		bool $translateParameters = false, bool $escape = false, bool $fromLiDA = false
	): string {

		if ($phrase === '' || is_numeric($phrase)) {
			return $phrase;
		}
		if ($phrase == null) {
			return '';
		}

		global $activeLanguage;
		//Determine whether the translation box (ID with popup should show). We don't show in attributes since the injected HTML breaks the overall page.
		$translationMode = $this->translationModeActive() && !$inAttribute && (UserAccount::userHasPermission('Translate Aspen'));
		//Determine if we should create terms in the database. We want to limit this to only translation mode or calls from LiDA for performance.
		$allowTermCreation = ($this->translationModeActive() && (UserAccount::userHasPermission('Translate Aspen'))) || $fromLiDA;
		//We will allow adding terms to the database even if we aren't in translation mode if the user is a translator, and we have the ability to do google translations.
		$googleSettings = $this->getGoogleTranslationSettings();
		if (!is_null($googleSettings) && !empty($activeLanguage) && $activeLanguage->code != 'en') {
			//Allow automatic translation the first time if we have Google Translate active.
			$allowTermCreation = true;
		}

		try {
			if (!empty($activeLanguage)) {
				$translationKey = $activeLanguage->id . '_' . ($translationMode ? 1 : 0) . '_' . $phrase;
				$existingTranslation = array_key_exists($translationKey, $this->cachedTranslations) ? $this->cachedTranslations[$translationKey] : false;
				if (!$existingTranslation || isset($_REQUEST['reload']) || !empty($replacementValues)) {
					//Search for the term
					$translationTerm = new TranslationTerm();
					$translationTerm->setTerm($phrase);
					$defaultTextChanged = false;
					// Write term records to DB in translation mode or when called from a verified LiDA instance.
					if ($allowTermCreation) {
						if (!$translationTerm->find(true)) {
							$translationTerm->setDefaultText ($defaultText);
							$translationTerm->setSamplePageUrl(mb_strimwidth($_SERVER['REQUEST_URI'], 0, 255));
							$translationTerm->setIsPublicFacing($isPublicFacing);
							$translationTerm->setIsAdminFacing($isAdminFacing);
							$translationTerm->setIsMetadata($isMetadata);
							$translationTerm->setIsAdminEnteredData($isAdminEnteredData);
							$translationTerm->setLastUpdate(time());
							try {
								if ($translationTerm->insert() !== false) {
									$termTooLong = false;
									// Send this to the Community Content Server as well.
									require_once ROOT_DIR . '/sys/SystemVariables.php';
									$systemVariables = SystemVariables::getSystemVariables();
									if ($systemVariables && !empty($systemVariables->communityContentUrl)) {
										if ($this->communityContentCurlWrapper == null) {
											require_once ROOT_DIR . '/sys/CurlWrapper.php';
											$this->communityContentCurlWrapper = new CurlWrapper();
										}
										$body = [
											'term' => $phrase,
											'isPublicFacing' => $isPublicFacing,
											'isAdminFacing' => $isAdminFacing,
											'isMetadata' => $isMetadata,
											'isAdminEnteredData' => $isAdminEnteredData,
										];
										$this->communityContentCurlWrapper->curlPostPage($systemVariables->communityContentUrl . '/API/CommunityAPI?method=addTranslationTerm', $body);
									}
								} else {
									$termTooLong = true;
								}
							} catch (Exception) {
								$termTooLong = true;
							}
							if ($termTooLong) {
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
							if ($defaultText == null) {
								$defaultText = '';
							}
							if ($defaultText != $translationTerm->getDefaultText()) {
								if (empty($translationTerm->getDefaultText()) && !empty($defaultText)) {
									$translationTerm->setDefaultText($defaultText);
									$defaultTextChanged = true;
									$termChanged = true;
								}
							}
							if ($isPublicFacing && !$translationTerm->getIsPublicFacing()) {
								$translationTerm->setIsPublicFacing($isPublicFacing);
								$termChanged = true;
							}
							if ($isAdminFacing && !$translationTerm->getIsAdminFacing()) {
								$translationTerm->setIsAdminFacing($isAdminFacing);
								$termChanged = true;
							}
							if ($isMetadata && !$translationTerm->getIsMetadata()) {
								$translationTerm->setIsMetadata($isMetadata);
								$termChanged = true;
							}
							if ($isAdminEnteredData && !$translationTerm->getIsAdminEnteredData()) {
								$translationTerm->setIsAdminEnteredData($isAdminEnteredData);
								$termChanged = true;
							}
							if ($termChanged) {
								$translationTerm->setLastUpdate(time());
								$translationTerm->update();
							}
						}
					} else {
						// Non-translation mode: try to load the term if it exists.
						if (!$translationTerm->find(true)) {
							// Term doesn't exist - check DB override first, then .ini defaults.
							if (empty($this->words)) {
								$this->loadTranslationsFromIniFile();
							}
							$dbTranslation = $this->loadDbOverride($phrase);
							if ($dbTranslation !== null) {
								$returnString = $dbTranslation;
							} elseif (isset($this->words[$phrase])) {
								$returnString = $this->words[$phrase];
							} elseif (!empty($defaultText)) {
								$returnString = $defaultText;
							} else {
								$returnString = $phrase;
							}
							if (count($replacementValues) > 0) {
								foreach ($replacementValues as $index => $replacementValue) {
									if ($translateParameters) {
										$replacementValue = $this->translate($replacementValue, '', [], true, $isPublicFacing, $isAdminFacing, $isMetadata, $isAdminEnteredData, $translateParameters, false, $fromLiDA);
									}
									$returnString = str_replace('%' . $index . '%', $replacementValue, $returnString);
								}
							}
							if ($escape) {
								$returnString = htmlentities($returnString);
							}
							$this->cachedTranslations[$translationKey] = $returnString;
							return $returnString;
						}
					}

					if ($activeLanguage->code == 'pig') {
						$fullTranslation = $this->getPigLatinTranslation($phrase);
					} elseif ($activeLanguage->code == 'ubb') {
						$fullTranslation = $this->getUbbiDubbiTranslation($phrase);
					} else {
						//Search for the translation
						$translation = new Translation();
						$translation->termId = $translationTerm->getId();
						$translation->languageId = $activeLanguage->id;
						if (!$translation->find(true) || empty($translation->translation)) {
							if (!empty($defaultText)) {
								$defaultTranslation = $defaultText;
								$translation->translated = ($activeLanguage->id == 1) ? 1 : 0;
							} else {
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
										//We don't have a translation yet. If we are not in english, see if we can get a translation from Google
										$defaultTranslation = $phrase;
										if ($allowTermCreation && !$isMetadata) {
											$googleTranslation = $this->getGoogleTranslation($phrase, $activeLanguage->code);
											if ($googleTranslation !== null) {
												$defaultTranslation = $googleTranslation;
												$translation->googleTranslated = 1;
											}
										}
									}
								}
							}

							$translation->translation = $defaultTranslation;
							if ($allowTermCreation) {
								if ($translation->id) {
									$ret = $translation->update();
								} else {
									$ret = $translation->insert();
								}
								if (!$ret) {
									global $logger;
									$logger->log("Could not save translation for term ID " . $translationTerm->getId(), Logger::LOG_ERROR);
								}
							}
						} elseif ($defaultTextChanged) {
							$translation->needsReview = 1;
							if ($translationMode) {
								$translation->update();
							}
						}

						if ($escape) {
							$translation->translated = htmlentities( $translation->translation);
						}
						if ($translationMode) {
							if ($translation->translated) {
								$translationStatus = 'translated';
							} else if ($translation->googleTranslated) {
								$translationStatus = 'google_translated';
							} else {
								$translationStatus = 'not_translated';
							}
							$translationIdentifier = "<span class='translation_id translation_id_$translation->id $translationStatus' onclick=\"return AspenDiscovery.showTranslateForm('{$translationTerm->getId()}');\">{$translationTerm->getId()}</span> ";
							$fullTranslation = "<span class='term_{$translationTerm->getId()}'>$translation->translation</span> $translationIdentifier";
						} else {
							$fullTranslation = $translation->translation;
						}
					}

					$this->cachedTranslations[$translationKey] = $fullTranslation;
					$returnString = $fullTranslation;
				} else {
					$returnString = $existingTranslation;
					if ($escape) {
						$returnString = htmlentities( $returnString);
					}
				}
			} else {
				//Translation not setup (happens from book covers)
				if (!empty($defaultText)) {
					$returnString = $defaultText;
				} else {
					$returnString = $phrase;
				}
			}
		} catch (PDOException) {
			//tables likely don't exist, ignore
			if (!empty($defaultText)) {
				$returnString = $defaultText;
			} else {
				$returnString = $phrase;
			}
		}
		if (count($replacementValues) > 0) {
			foreach ($replacementValues as $index => $replacementValue) {
				if ($translateParameters) {
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

	/**
	 * @param string $phrase
	 * @param Language $activeLanguage
	 * @return array
	 * @noinspection PhpUnused
	 */
	public function getCommunityTranslation(string $phrase, Language $activeLanguage): array {
		require_once ROOT_DIR . '/sys/SystemVariables.php';
		$systemVariables = SystemVariables::getSystemVariables();
		$translatedInCommunity = false;
		$defaultTranslation = null;
		if ($systemVariables && !empty($systemVariables->communityContentUrl)) {
			if ($this->communityContentCurlWrapper == null) {
				require_once ROOT_DIR . '/sys/CurlWrapper.php';
				$this->communityContentCurlWrapper = new CurlWrapper();
			}
			$body = [
				'term' => $phrase,
				'languageCode' => $activeLanguage->code,
			];
			$response = $this->communityContentCurlWrapper->curlPostPage($systemVariables->communityContentUrl . '/API/CommunityAPI?method=getDefaultTranslation', $body);
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

	private function loadTranslationsFromIniFile() : void {
		if (empty($this->words)) {
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

	private ?bool $translationModeActive = null;

	public function translationModeActive() : bool {
		if ($this->translationModeActive === null) {
			if (isset($_REQUEST['startTranslationMode'])) {
				@session_start();
				$_SESSION['translationMode'] = 'on';
				$translationModeActive = true;
			} elseif (isset($_REQUEST['stopTranslationMode'])) {
				@session_start();
				$_SESSION['translationMode'] = 'off';
				$translationModeActive = false;
			} elseif (isset($_SESSION['translationMode'])) {
				$translationModeActive = ($_SESSION['translationMode'] == 'on');
			} else {
				$translationModeActive = false;
			}
			$this->translationModeActive = $translationModeActive;
		}
		return $this->translationModeActive;
	}

	private static array $vowels = [
		'a',
		'e',
		'i',
		'o',
		'u',
		'y',
		'A',
		'E',
		'I',
		'O',
		'U',
	];

	private function getPigLatinTranslation(string $phrase) : string {
		$translation = '';
		$words = explode(' ', $phrase);
		foreach ($words as $word) {
			if (strlen($word) == 0) {
				$translation .= ' ';
			}else if (preg_match('/%\d+%/', $word)) {
				$translation .= $word . ' ';
			} elseif (in_array($word[0], Translator::$vowels)) {
				$translation .= $word . 'way ';
			} elseif (strlen($word) >= 2 && !in_array($word[0], Translator::$vowels) && !in_array($word[1], Translator::$vowels)) {
				$translation .= substr($word, 2) . $word[0] . $word[1] . 'ay ';
			} else {
				$translation .= substr($word, 1) . $word[0] . 'ay ';
			}
		}
		$translation = strtolower($translation);
		if (preg_match('/[A-Z]/', $phrase[0])) {
			$translation = ucfirst($translation);
		}
		return trim($translation);
	}

	private function getUbbiDubbiTranslation(string $phrase) : string {
		$translation = '';
		$words = explode(' ', $phrase);
		foreach ($words as $word) {
			if (preg_match('/%\d+%/', $word)) {
				$translation .= $word . ' ';
			} else {
				$translatedWord = '';
				$lastCharWasVowel = false;
				for ($i = 0; $i < strlen($word); $i++) {
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
		if (preg_match('/[A-Z]/', $phrase[0])) {
			$translation = ucfirst($translation);
		}
		return trim($translation);
	}

	private function loadDbOverride(string $phrase): ?string {
		global $aspen_db, $activeLanguage;
		if ($this->dbPhraseStmt === null) {
			$this->dbPhraseStmt = $aspen_db->prepare(
				"SELECT tr.translation
				 FROM translations AS tr
				 JOIN translation_terms AS tt ON tr.termId = tt.id
				 WHERE tt.term = ? AND tr.languageId = ?"
			);
		}
		$this->dbPhraseStmt->execute([$phrase, $activeLanguage->id]);
		$row = $this->dbPhraseStmt->fetch(PDO::FETCH_ASSOC);
		return $row !== false ? $row['translation'] : null;
	}

	private GoogleApiSetting|bool|null $googleSettings = false;
	private ?CurlWrapper $googleTranslateWrapper = null;

	private function getGoogleTranslationSettings() : ?GoogleApiSetting {
		if ($this->googleSettings === false) {
			$this->googleSettings = null;
			require_once ROOT_DIR . '/sys/Enrichment/GoogleApiSetting.php';
			$googleSettings = new GoogleApiSetting();
			if ($googleSettings->find(true)) {
				if (!empty($googleSettings->googleMapsKey)) {
					if (!empty($googleSettings->googleTranslateKey)) {
						$this->googleSettings = $googleSettings;
					}
				}
			}
		}
		return $this->googleSettings;
	}

	public function getGoogleTranslation(string $phrase, string $targetLanguage) : ?string {
		$googleSettings = $this->getGoogleTranslationSettings();
		if (!is_null($googleSettings)) {
			if ($this->googleTranslateWrapper === null) {
				global $configArray;
				$serverUrl = $configArray['Site']['url'];
				$this->googleTranslateWrapper = new CurlWrapper();
				$headers = [
					"X-goog-api-key: $googleSettings->googleTranslateKey",
					"Content-Type: application/json; charset=utf-8",
					"Referer: $serverUrl"
				];
				$this->googleTranslateWrapper->addCustomHeaders($headers, false);
			}
			$url = "https://translation.googleapis.com/language/translate/v2";
			$postBody = [
				"source" => "en",
				"target" => $targetLanguage,
				"q" => $phrase,
				"format" => "text"
			];

			$response = $this->googleTranslateWrapper->curlPostBodyData($url, $postBody);
			global $logger;
			if (!empty($response)) {
				$jsonResponse = json_decode($response);
				if (isset($jsonResponse->data->translations)) {
					$translations =  $jsonResponse->data->translations;
					if (count($translations) > 0) {
						$translation = $translations[0];
						return $translation->translatedText;
					}
				}else{
					$logger->log("Did not get a good result from google translator", Logger::LOG_WARNING);
					$logger->log($response, Logger::LOG_WARNING);
				}
			}else{
				$logger->log("Got an empty result from google translator", Logger::LOG_WARNING);
			}
		}
		return null;
	}
}