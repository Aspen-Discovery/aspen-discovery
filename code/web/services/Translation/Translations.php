<?php
require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';

class Translation_Translations extends Admin_Admin {

	function launch() {
		global $interface;
		global $translator;
		global $activeLanguage;
		$translationModeActive = $translator->translationModeActive();
		$interface->assign('translationModeActive', $translationModeActive);

		if (isset($_REQUEST['exportAllTranslations'])) {
			$this->exportAllTranslations();
		} elseif (isset($_REQUEST['exportForBulkTranslation'])) {
			$this->exportForBulkTranslation();
		}

		if (isset($_REQUEST['translation_changed'])) {
			foreach ($_REQUEST['translation_changed'] as $index => $value) {
				if ($value == 1) {
					$newTranslation = $_REQUEST['translation'][$index];
					$translation = new Translation();
					$translation->termId = $index;
					$translation->languageId = $activeLanguage->id;
					$translation->find(true);
					$translation->setTranslation($newTranslation);
				}
			}
		}

		$translation = new Translation();
		if (isset($_REQUEST['hideTranslated'])) {
			$translation->whereAdd('(translated = 0 OR needsReview = 1)');
			$interface->assign('hideTranslated', true);
		} else {
			$interface->assign('hideTranslated', false);
		}
		$interfaceArea = $_REQUEST['interfaceArea'] ?? 'both';
		$interface->assign('interfaceArea', $interfaceArea);
		if ($interfaceArea == 'public') {
			$translation->whereAdd('term.isPublicFacing = 1');
		} elseif ($interfaceArea == 'admin') {
			$translation->whereAdd('term.isAdminFacing = 1');
		}

		$todayFormatted = date('Y-m-d', strtotime('now'));
		$interface->assign('today', $todayFormatted);
		if (!empty($_REQUEST['updatedSince'])) {
			$translation->whereAdd('term.lastUpdate > ' . strtotime($_REQUEST['updatedSince']));
			$interface->assign('updatedSince', $_REQUEST['updatedSince']);
		}else{
			$interface->assign('updatedSince', '');
		}

		if (!empty($_REQUEST['showMetadata'])) {
			$interface->assign('showMetadata', true);
		} else {
			$translation->whereAdd('term.isMetadata = 0');
			$interface->assign('showMetadata', false);
		}

		if (!empty($_REQUEST['showAdminEnteredData'])) {
			$interface->assign('showAdminEnteredData', true);
		} else {
			$translation->whereAdd('term.isAdminEnteredData = 0');
			$interface->assign('showAdminEnteredData', false);
		}

		if (!empty($_REQUEST['filterTerm'])) {
			$filterTerm = $_REQUEST['filterTerm'];
			$translation->whereAdd("LOWER(term.term) LIKE LOWER(" . $translation->escape('%' . $filterTerm . '%') . ')');
			$interface->assign('filterTerm', $filterTerm);
		} else {
			$interface->assign('filterTerm', '');
		}

		if (!empty($_REQUEST['filterTranslation'])) {
			$filterTranslation = $_REQUEST['filterTranslation'];
			$translation->whereAdd("LOWER(translation) LIKE LOWER(" . $translation->escape('%' . $filterTranslation . '%') . ')');
			$interface->assign('filterTranslation', $filterTranslation);
		} else {
			$interface->assign('filterTranslation', '');
		}

		$translation->languageId = $activeLanguage->id;
		$translation->joinAdd(new TranslationTerm(), 'INNER', 'term', 'termId', 'id');
		$translation->orderBy('term.term');

		$total = $translation->count();

		$page = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
		$pageSize = isset($_REQUEST['pageSize']) ? $_REQUEST['pageSize'] : 50; // to adjust number of items listed on a page
		$interface->assign('recordsPerPage', $pageSize);
		$interface->assign('page', $page);
		$translation->limit(($page - 1) * $pageSize, $pageSize);
		$translation->selectAdd('term.*');

		$allTerms = [];
		$translation->find();
		while ($translation->fetch()) {
			$allTerms[] = clone $translation;
		}
		$interface->assign('allTerms', $allTerms);

		$options = [
			'totalItems' => $total,
			'perPage' => $pageSize,
		];
		$pager = new Pager($options);
		$interface->assign('pageLinks', $pager->getLinks());

		$this->display('translations.tpl', 'Translations');
	}

	private function exportAllTranslations() {
		set_time_limit(0);
		ini_set('memory_limit', '1G');
		header('Content-type: application/csv');
		header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
		$now = date('Y-m-d-H-i');
		header("Content-Disposition: attachment; filename=aspen_translations_$now.csv");

		$validLanguage = new Language();
		$validLanguage->orderBy(["weight", "displayName"]);
		$validLanguage->find();
		$validLanguages = [];
		echo('"Term"');
		while ($validLanguage->fetch()) {
			$validLanguages[$validLanguage->code] = $validLanguage->id;
			if ($validLanguage->code != 'ubb' && $validLanguage->code != 'pig') {
				echo(",\"{$validLanguage->code}\"");
			}
		}
		echo("\n");

		$term = new TranslationTerm();
		$term->orderBy('term');

		//Apply filters
		$interfaceArea = $_REQUEST['interfaceArea'] ?? 'both';
		if ($interfaceArea == 'public') {
			$term->isPublicFacing = 1;
		} elseif ($interfaceArea == 'admin') {
			$term->isAdminFacing = 1;
		}

		if (!empty($_REQUEST['updatedSince'])) {
			$term->whereAdd('lastUpdate > ' . strtotime($_REQUEST['updatedSince']));
		}

		if (empty($_REQUEST['showMetadata'])) {
			$term->isMetadata = 0;;
		}

		if (empty($_REQUEST['showAdminEnteredData'])) {
			$term->isAdminEnteredData = 0;
		}

		$term->find();
		while ($term->fetch()) {
			echo('"' . str_replace('"', '\"', $term->term) . '"');
			foreach ($validLanguages as $languageId) {
				echo ",";
				$translation = new Translation();
				$translation->termId = $term->id;
				$translation->languageId = $languageId;
				if ($translation->find(true)) {
					if ($translation->translated || $languageId == 1) {
						echo('"' . str_replace('"', '\"', $translation->translation) . '"');
					}
				}
				$translation->__destruct();
				$translation = null;
			}
			echo("\n");
		}

		flush();
		exit();
	}

	private function exportForBulkTranslation() {
		set_time_limit(0);
		header('Content-type: application/txt');
		header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
		$now = date('Y-m-d-H-i');
		header("Content-Disposition: attachment; filename=aspen_translations_$now.txt");

		//Get English and the current language
		$englishLanguage = new Language();
		$englishLanguage->id = 1;
		$englishLanguage->find(true);

		global $activeLanguage;

		$term = new TranslationTerm();
		$term->orderBy('id');

		$interfaceArea = $_REQUEST['interfaceArea'] ?? 'both';
		if ($interfaceArea == 'public') {
			$term->setIsPublicFacing(1);
		} elseif ($interfaceArea == 'admin') {
			$term->setIsAdminFacing(1);
		}

		if (!empty($_REQUEST['updatedSince'])) {
			$term->whereAdd('lastUpdate > ' . strtotime($_REQUEST['updatedSince']));
		}

		if (empty($_REQUEST['showMetadata'])) {
			$term->setIsMetadata(0);;
		}

		if (empty($_REQUEST['showAdminEnteredData'])) {
			$term->setIsAdminEnteredData(0);
		}

		$term->find();
		while ($term->fetch()) {
			//Look to see if we have translated it into the active language
			$translation = new Translation();
			$translation->termId = $term->id;
			$translation->languageId = $activeLanguage->id;
			$writeTerm = false;
			if ($translation->find(true)) {
				if (!$translation->translated) {
					$writeTerm = true;
				}
			} else {
				$writeTerm = true;
			}
			$translation->__destruct();
			$translation = null;
			if ($writeTerm) {
				$termToWrite = $term->getDefaultText();

				if (!empty($termToWrite) && !is_numeric($termToWrite)) {
					echo("{$term->id}| {$termToWrite}\r\n");
				}
			}
		}

		flush();
		exit();
	}

    function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#translations', 'Languages and Translations');
		$breadcrumbs[] = new Breadcrumb('/Translation/Translations', 'Translations');
		$breadcrumbs[] = new Breadcrumb('', 'Translations');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'translations';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Translate Aspen');
	}
}