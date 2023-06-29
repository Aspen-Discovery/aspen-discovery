<?php

/**
 * Class TranslationTerm
 *
 * A term or phrase that is being translated.  The term can have parameters to it indicated as %1%, %2%, etc
 * The terms are automatically generated if not found in the table during the translation process.
 */
class TranslationTerm extends DataObject {
	public $__table = 'translation_terms';
	protected $id;
	protected $term;
	protected $defaultText;
	protected $parameterNotes;
	protected $samplePageUrl;
	protected $isPublicFacing;
	protected $isAdminFacing;
	protected $isMetadata;
	protected $isAdminEnteredData;
	protected $lastUpdate;

	public function getNumericColumnNames(): array {
		return [
			'isPublicFacing',
			'isAdminFacing',
			'isMetadata',
			'isAdminEnteredData',
			'lastUpdate',
		];
	}

	public function getDefaultText() {
		$defaultText = '';
		$translation = new Translation();
		$translation->termId = $this->id;
		$translation->languageId = 1;
		if ($translation->find(true)) {
			if ($translation->translated) {
				$defaultText = $translation->translation;
			}
		}
		$translation->__destruct();
		$translation = null;
		if (empty($defaultText)) {
			if (!empty($this->defaultText)) {
				$defaultText = $this->defaultText;
			} else {
				$defaultText = $this->term;
			}
		}
		return $defaultText;
	}
}