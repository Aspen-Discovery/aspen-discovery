<?php
require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';

class Translation_ImportBulkTranslations extends Admin_Admin {
	function launch() {
		global $interface;

		//Figure out the maximum upload size
		require_once ROOT_DIR . '/sys/Utils/SystemUtils.php';
		$interface->assign('max_file_size', SystemUtils::file_upload_max_size() / (1024 * 1024));

		if (isset($_REQUEST['submit'])) {
			global $activeLanguage;
			//Import the translations and redirect back to the main translations page
			if (isset ($_FILES['importFile'])) {
				if (isset($_FILES['importFile']["error"]) && $_FILES['importFile']["error"] != 0) {
					switch ($_FILES['importFile']["error"]) {
						case UPLOAD_ERR_INI_SIZE:
							$message = "The uploaded file exceeds the upload_max_filesize directive in php.ini";
							break;
						case UPLOAD_ERR_FORM_SIZE:
							$message = "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form";
							break;
						case UPLOAD_ERR_PARTIAL:
							$message = "The uploaded file was only partially uploaded";
							break;
						case UPLOAD_ERR_NO_FILE:
							$message = "No file was uploaded";
							break;
						case UPLOAD_ERR_NO_TMP_DIR:
							$message = "Missing a temporary folder";
							break;
						case UPLOAD_ERR_CANT_WRITE:
							$message = "Failed to write file to disk";
							break;
						case UPLOAD_ERR_EXTENSION:
							$message = "File upload stopped by extension";
							break;

						default:
							$message = "Unknown upload error";
							break;
					}
					$interface->assign('error', $message);
				} else {
					$fileToLoad = $_FILES['importFile']['tmp_name'];
					$fHnd = fopen($fileToLoad, 'r');

					global $memCache;
					while ($translationLine = fgets($fHnd)) {
						//Google sometimes strips the pipe symbol we add
						if (preg_match('/(\d+)\s?\|?\s?(.*)/i', $translationLine, $matches)) {
							$termId = trim($matches[1]);
							$newText = trim($matches[2]);
							$newText = str_replace([
								'% 1 %',
								'% 2 %',
								'% 3 %',
								'% 4 %',
								'% 5 %',
								'% 6 %',
							], [
								'%1%',
								'%2%',
								'%3%',
								'%4%',
								'%5%',
								'%6%',
							], $newText);

							$translationTerm = new TranslationTerm();
							$translationTerm->id = $termId;
							if ($translationTerm->find(true)) {
								//Figure out if the bulk translator did anything
								$defaultText = $translationTerm->getDefaultText();

								if ($defaultText != $newText) {
									$translation = new Translation();
									$translation->languageId = $activeLanguage->id;
									$translation->termId = $translationTerm->id;
									if ($translation->find(true)) {
										$translation->translation = $newText;
										$translation->translated = true;
										$translation->update();
									} else {
										$translation->translation = $newText;
										$translation->translated = true;
										$translation->insert();
									}
									$memCache->delete('translation_' . $activeLanguage->id . '_0_' . $translationTerm->term);
									$memCache->delete('translation_' . $activeLanguage->id . '_1_' . $translationTerm->term);
									$translation->__destruct();
									$translation = null;
								}
							}
							$translationTerm->__destruct();
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
					'isPublicFacing' => true,
				]));
			}

		}
		$this->display('importBulkTranslationsForm.tpl', 'Import Bulk Translations');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#translations', 'Languages and Translations');
		$breadcrumbs[] = new Breadcrumb('/Translation/Translations', 'Translations');
		$breadcrumbs[] = new Breadcrumb('', 'Import Bulk Translations');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'translations';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Translate Aspen');
	}
}