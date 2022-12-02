<?php


class Translation extends DataObject {
	public $__table = 'translations';
	public $id;
	public $termId;
	public $languageId;
	public $translation;
	public $translated;
	public $needsReview;

	public function getNumericColumnNames(): array {
		return [
			'termId',
			'languageId',
			'translated',
			'needsReview',
		];
	}

	public function setTranslation($translation) {
		$this->translation = $translation;
		$this->translated = 1;
		$this->needsReview = 0;
		$this->update();

		$term = new TranslationTerm();
		$term->id = $this->termId;
		$term->find(true);
		global $memCache;
		global $activeLanguage;
		$memCache->delete('translation_' . $activeLanguage->id . '_0_' . $term->term);
		$memCache->delete('translation_' . $activeLanguage->id . '_1_' . $term->term);

		//Send the translation to the greenhouse
		require_once ROOT_DIR . '/sys/SystemVariables.php';
		$systemVariables = SystemVariables::getSystemVariables();
		if ($systemVariables && !empty($systemVariables->greenhouseUrl)) {
			require_once ROOT_DIR . '/sys/CurlWrapper.php';
			$curl = new CurlWrapper();
			$body = [
				'term' => $term->term,
				'translation' => $translation,
				'languageCode' => $activeLanguage->code,
			];
			$curl->curlPostPage($systemVariables->greenhouseUrl . '/API/GreenhouseAPI?method=setTranslation', $body);
		}
	}
}