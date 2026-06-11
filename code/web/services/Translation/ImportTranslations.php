<?php
require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';

class Translation_ImportTranslations extends Admin_Admin {
	function launch() {
		global $interface;

		//Figure out the maximum upload size
		require_once ROOT_DIR . '/sys/Utils/SystemUtils.php';
		$interface->assign('max_file_size', SystemUtils::file_upload_max_size_mb());

		if (isset($_REQUEST['submit'])) {
			//Make sure we don't time out while loading translations
			set_time_limit(-1);
			ini_set('memory_limit', '1G');

			//Check mbstring extension is enabled
			if (!extension_loaded('mbstring')) {
				ini_set('mbstring.encoding_translation', 'On');
				ini_set('mbstring.internal_encoding', 'UTF-8');
				ini_set('mbstring.func_overload', 6);
			}

			$overrideExistingTranslations = isset($_REQUEST['overwriteExisting']);

			$languagesToImport = [];
			$validLanguage = new Language();
			$validLanguage->orderBy(["weight", "displayName"]);
			$validLanguage->find();
			$codeToLanguageId = [];
			while ($validLanguage->fetch()) {
				if (isset($_REQUEST['import_' . $validLanguage->code])) {
					$languagesToImport[$validLanguage->code] = $validLanguage->code;
				}
				$codeToLanguageId[$validLanguage->code] = $validLanguage->id;
			}

			if (empty($languagesToImport)) {
				$interface->assign('error', 'Please select at least one language to import');
			} else {
				//Import the translations and redirect back to the main translations page
				if (isset ($_FILES['importFile'])) {
					if (isset($_FILES['importFile']["error"]) && $_FILES['importFile']["error"] != 0) {
						$interface->assign('error', SystemUtils::getUploadErrorMessage($_FILES['importFile']["error"]));
					} else {
						$fileToLoad = $_FILES['importFile']['tmp_name'];
						$fHnd = fopen($fileToLoad, 'r');
						$headerRow = fgetcsv($fHnd);
						//Map columns form export to what we want to import
						for ($i = 1; $i < count($headerRow); $i++) {
							foreach ($languagesToImport as $code => $index) {
								if ($code == $headerRow[$i]) {
									$languagesToImport[$code] = $i;
								}
							}
						}
						/** @var Memcache $memCache */ global $memCache;
						while ($curRow = fgetcsv($fHnd)) {
							$term = $curRow[0];
							//Make sure there is at least one translation for the term before importing it.
							$hasTranslations = false;
							foreach ($languagesToImport as $code => $columnIndex) {
								//Checks if each term is already utf8 encoded to avoid double encoding.
								$isUTF8Encoded = mb_check_encoding($curRow[$columnIndex],'UTF-8');
								if ($isUTF8Encoded){
									$newValue = $curRow[$columnIndex];
								} else{
									$newValue = mb_convert_encoding($curRow[$columnIndex],'UTF-8');
								}
								if (!empty($newValue)) {
									$hasTranslations = true;
								}
							}
							if ($hasTranslations) {
								$translationTerm = new TranslationTerm();
								$translationTerm->term = $term;
								if (!$translationTerm->find(true)) {
									$translationTerm->insert();
								}
								foreach ($languagesToImport as $code => $columnIndex) {
									$isUTF8Encoded = mb_check_encoding($curRow[$columnIndex],'UTF-8');
									if ($isUTF8Encoded){
										$newValue = $curRow[$columnIndex];
									} else{
										$newValue = mb_convert_encoding($curRow[$columnIndex],'UTF-8');
									}
									if (!empty($newValue) && strcasecmp($newValue, 'null') != 0) {
										$translation = new Translation();
										$translation->languageId = $codeToLanguageId[$code];
										$translation->termId = $translationTerm->id;
										if ($translation->find(true)) {
											if (!$translation->translated || $overrideExistingTranslations) {
												if ($newValue != $translation->translation) {
													$translation->setTranslation($newValue, $translationTerm);
												}
											}
										} else {
											$translation->setTranslation($newValue, $translationTerm);
										}
									}
								}
								$translationTerm = null;
							}
						}
						fclose($fHnd);
						header('Location: /Translation/Translations');
						die();
					}
				} else {
					$interface->assign('error', translate([
						'text' => 'Please select a file to import',
						'isAdminFacing' => true,
					]));
				}
			}

		}
		$this->display('importTranslationsForm.tpl', 'Import Translations');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#translations', 'Languages and Translations');
		$breadcrumbs[] = new Breadcrumb('/Translation/Translations', 'Translations');
		$breadcrumbs[] = new Breadcrumb('', 'Import Translations');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'translations';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Translate Aspen');
	}
}