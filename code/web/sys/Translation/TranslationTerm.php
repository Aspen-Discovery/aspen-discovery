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

	/**
	 * @return mixed
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @param mixed $id
	 */
	public function setId($id): void {
		$this->id = $id;
	}

	/**
	 * @return mixed
	 */
	public function getTerm() {
		return $this->term;
	}

	/**
	 * @param mixed $term
	 */
	public function setTerm($term): void {
		$this->term = $term;
	}

	/**
	 * @param mixed $defaultText
	 */
	public function setDefaultText($defaultText): void {
		$this->defaultText = $defaultText;
	}

	/**
	 * @return mixed
	 */
	public function getParameterNotes() {
		return $this->parameterNotes;
	}

	/**
	 * @param mixed $parameterNotes
	 */
	public function setParameterNotes($parameterNotes): void {
		$this->parameterNotes = $parameterNotes;
	}

	/**
	 * @return mixed
	 */
	public function getSamplePageUrl() {
		return $this->samplePageUrl;
	}

	/**
	 * @param mixed $samplePageUrl
	 */
	public function setSamplePageUrl($samplePageUrl): void {
		$this->samplePageUrl = $samplePageUrl;
	}

	/**
	 * @return mixed
	 */
	public function getIsPublicFacing() {
		return $this->isPublicFacing;
	}

	/**
	 * @param mixed $isPublicFacing
	 */
	public function setIsPublicFacing($isPublicFacing): void {
		$this->isPublicFacing = $isPublicFacing;
	}

	/**
	 * @return mixed
	 */
	public function getIsAdminFacing() {
		return $this->isAdminFacing;
	}

	/**
	 * @param mixed $isAdminFacing
	 */
	public function setIsAdminFacing($isAdminFacing): void {
		$this->isAdminFacing = $isAdminFacing;
	}

	/**
	 * @return mixed
	 */
	public function getIsMetadata() {
		return $this->isMetadata;
	}

	/**
	 * @param mixed $isMetadata
	 */
	public function setIsMetadata($isMetadata): void {
		$this->isMetadata = $isMetadata;
	}

	/**
	 * @return mixed
	 */
	public function getIsAdminEnteredData() {
		return $this->isAdminEnteredData;
	}

	/**
	 * @param mixed $isAdminEnteredData
	 */
	public function setIsAdminEnteredData($isAdminEnteredData): void {
		$this->isAdminEnteredData = $isAdminEnteredData;
	}

	/**
	 * @return mixed
	 */
	public function getLastUpdate() {
		return $this->lastUpdate;
	}

	/**
	 * @param mixed $lastUpdate
	 */
	public function setLastUpdate($lastUpdate): void {
		$this->lastUpdate = $lastUpdate;
	}
}