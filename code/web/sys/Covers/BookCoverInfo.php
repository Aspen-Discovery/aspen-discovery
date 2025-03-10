<?php /** @noinspection PhpMissingFieldTypeInspection */

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class BookCoverInfo extends DataObject {
	public $__table = 'bookcover_info';    // table name
	protected $id;
	protected $recordType;
	protected $recordId;
	protected $firstLoaded;
	protected $lastUsed;
	protected $imageSource;
	protected $sourceWidth;
	protected $sourceHeight;
	protected $thumbnailLoaded;
	protected $mediumLoaded;
	protected $largeLoaded;
	protected $uploadedImage;
	protected $disallowThirdPartyCover;
	protected $original_url;
	protected $last_url_validation;

	public function getNumericColumnNames(): array {
		return [
			'id',
			'sourceWidth',
			'sourceHeight',
			'thumbnailLoaded',
			'mediumLoaded',
			'largeLoaded',
			'uploadedImage',
			'disallowThirdPartyCover',
			'last_url_validation',
		];
	}

	public function reloadAllDefaultCovers() {
		$this->query("UPDATE " . $this->__table . " SET thumbnailLoaded = 0, mediumLoaded = 0, largeLoaded = 0 where imageSource = 'default'");
	}

	public function reloadOMDBCovers() {
		$this->query("UPDATE " . $this->__table . " SET thumbnailLoaded = 0, mediumLoaded = 0, largeLoaded = 0 where imageSource = 'omdb_title' OR imageSource = 'omdb_title_year'");
	}

	public function getImageSource() : string {
		return $this->imageSource;
	}

	public function getDisallowThirdPartyCover() {
		return $this->disallowThirdPartyCover;
	}

	/**
	 * @return mixed
	 */
	public function getRecordId() : string {
		return $this->recordId;
	}

	/**
	 * @return mixed
	 */
	public function getRecordType() {
		return $this->recordType;
	}

	/**
	 * @param mixed $recordType
	 */
	public function setRecordType($recordType): void {
		$this->__set('recordType', $recordType);
	}

	/**
	 * @param mixed $recordId
	 */
	public function setRecordId($recordId): void {
		$this->__set('recordId', $recordId);
	}

	public function setImageSource($imageSource): void {
		$this->__set('imageSource', $imageSource);
	}

	/**
	 * Get the original URL of the cover image
	 * @return string|null
	 */
	public function getOriginalUrl(): ?string
	{
		return $this->original_url;
	}

	/**
	 * Set the original URL of the cover image
	 * @param string $url
	 */
	public function setOriginalUrl(string $url): void {
		$this->__set('original_url', $url);
	}

	/**
	 * Get the timestamp when the URL was last validated
	 * @return int|null
	 */
	public function getLastUrlValidation(): ?int
	{
		return $this->last_url_validation;
	}

	/**
	 * Set the timestamp when the URL was last validated
	 * @param int $timestamp
	 */
	public function setLastUrlValidation(int $timestamp): void {
		$this->__set('last_url_validation', $timestamp);
	}
}