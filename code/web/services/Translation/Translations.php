<?php
require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';

/** @noinspection PhpUnused */
class Translation_Translations extends Admin_Admin
{

	function launch()
	{
		global $interface;
		/** @var Translator $translator */
		global $translator;
		global $activeLanguage;
		$translationModeActive = $translator->translationModeActive();
		$interface->assign('translationModeActive', $translationModeActive);

		if (isset($_REQUEST['exportAllTranslations'])){
			$this->exportAllTranslations();
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
		if (!isset($_REQUEST['showAllTranslations'])) {
			$translation->translated = "0";
			$interface->assign('showAllTranslations', false);
		}else{
			$interface->assign('showAllTranslations', true);
		}

		if (!empty($_REQUEST['filterTerm'])){
			$filterTerm = $_REQUEST['filterTerm'];
			$translation->whereAdd("term.term LIKE " . $translation->escape('%' . $filterTerm . '%'));
			$interface->assign('filterTerm', $filterTerm);
		}else{
			$interface->assign('filterTerm', '');
		}

		if (!empty($_REQUEST['filterTranslation'])){
			$filterTranslation = $_REQUEST['filterTranslation'];
			$translation->whereAdd("translation LIKE " . $translation->escape('%' . $filterTranslation . '%'));
			$interface->assign('filterTranslation', $filterTranslation);
		}else{
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

		$allTerms = [];
		$translation->find();
		while ($translation->fetch()){
			$allTerms[] = clone $translation;
		}
		$interface->assign('allTerms', $allTerms);

		$options = array('totalItems' => $total,
			'fileName'   => "/Translation/Translations?page=%d". (empty($_REQUEST['pageSize']) ? '' : '&pageSize=' . $_REQUEST['pageSize']),
			'perPage'    => $pageSize,
		);
		$pager = new Pager($options);
		$interface->assign('pageLinks', $pager->getLinks());

		$this->display('translations.tpl', 'Translations');
	}

	function getAllowableRoles()
	{
		return ['opacAdmin', 'translator'];
	}

	private function exportAllTranslations()
	{
		header('Content-type: application/csv');
		header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
		$now = date('Y-m-d-H-i');
		header("Content-Disposition: attachment; filename=aspen_translations_$now.csv");

		$validLanguage = new Language();
		$validLanguage->orderBy("weight");
		$validLanguage->find();
		$validLanguages = [];
		echo('"Term"');
		while ($validLanguage->fetch()){
			$validLanguages[$validLanguage->code] = $validLanguage->id;
			echo(",\"{$validLanguage->code}\"");
		}
		echo("\n");

		$term = new TranslationTerm();
		$term->orderBy('term');
		$term->find();
		while ($term->fetch()){
			echo('"' . $term->term . '"');
			foreach ($validLanguages as $languageId){
				echo ",";
				$translation = new Translation();
				$translation->termId = $term->id;
				$translation->languageId = $languageId;
				if ($translation->find(true)){
					if ($translation->translated || $languageId == 1){
						echo('"' . $translation->translation . '"');
					}
				}
			}
			echo("\n");
		}

		die();
	}
}