<?php
require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';

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

		if (isset($_REQUEST['translation_changed'])) {
			foreach ($_REQUEST['translation_changed'] as $index => $value) {
				if ($value == 1) {
					$newTranslation = $_REQUEST['translation'][$index];
					$translation = new Translation();
					$translation->termId = $index;
					$translation->languageId = $activeLanguage->id;
					$translation->find(true);
					$translation->translation = $newTranslation;
					$translation->translated = 1;
					$translation->update();

					$term = new TranslationTerm();
					$term->id = $index;
					$term->find(true);
					/** @var Memcache $memCache */
					global $memCache;
					global $activeLanguage;
					$memCache->delete('translation_' . $activeLanguage->id . '_0_' . $term->term);
					$memCache->delete('translation_' . $activeLanguage->id . '_1_' . $term->term);
				}
			}
		}

		$translation = new Translation();
		if (!isset($_REQUEST['showAllTranslations'])) {
			$translation->translated = "0";
		}
		$translation->languageId = $activeLanguage->id;
		$translation->joinAdd(new TranslationTerm(), 'INNER', 'term', 'termId', 'id');
		$translation->orderBy('term.term');

		$allTerms = [];
		$translation->find();
		while ($translation->fetch()){
			$allTerms[] = clone $translation;
		}
		$interface->assign('allTerms', $allTerms);

		$this->display('translations.tpl', 'Translations');
	}

	function getAllowableRoles()
	{
		return ['opacAdmin', 'translator'];
	}
}