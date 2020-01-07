<?php


class Translation extends DataObject
{
	public $__table = 'translations';
	public $id;
	public $termId;
	public $languageId;
	public $translation;
	public $translated;
	public $needsReview;

	public function getNumericColumnNames()
	{
		return ['termId', 'languageId'];
	}

	public function setTranslation($translation)
	{
		$this->translation = $translation;
		$this->translated = 1;
		$this->needsReview = 0;
		$this->update();

		$term = new TranslationTerm();
		$term->id = $this->termId;
		$term->find(true);
		/** @var Memcache $memCache */
		global $memCache;
		global $activeLanguage;
		$memCache->delete('translation_' . $activeLanguage->id . '_0_' . $term->term);
		$memCache->delete('translation_' . $activeLanguage->id . '_1_' . $term->term);
	}
}