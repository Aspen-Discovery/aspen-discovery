<?php
require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';

class Translation_ImportBulkTranslations extends Admin_Admin {
	function launch() {
		global $interface;

		//Figure out the maximum upload size
		require_once ROOT_DIR . '/sys/Utils/SystemUtils.php';
		$interface->assign('max_file_size', SystemUtils::file_upload_max_size_mb());

		if (isset($_REQUEST['submit'])) {
			global $activeLanguage;
			//Import the translations and redirect back to the main translations page
			if (isset ($_FILES['importFile'])) {
				if (isset($_FILES['importFile']["error"]) && $_FILES['importFile']["error"] != 0) {
					$interface->assign('error', SystemUtils::getUploadErrorMessage($_FILES['importFile']["error"]));
				} else {
					$fileToLoad = $_FILES['importFile']['tmp_name'];
					$fHnd = fopen($fileToLoad, 'r');
					set_time_limit(-1);

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
										if ($newText != $translation->translation) {
											$translation->setTranslation($newText, $translationTerm);
										}
									} else {
										$translation->setTranslation($newText, $translationTerm);
									}
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