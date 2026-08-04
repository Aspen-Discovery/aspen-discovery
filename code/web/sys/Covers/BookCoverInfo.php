<?php /** @noinspection PhpMissingFieldTypeInspection */


class BookCoverInfo extends DataObject {
	public $__table = 'bookcover_info';    // table name
	protected $id;
	protected $recordType;
	protected $recordId;
	protected $firstLoaded;
	/** @noinspection PhpUnused */
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
	protected $original_url_small;
	protected $original_url_medium;
	protected $original_url_large;
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

	private static $_allCoversReloadedThisSession = false;

	/**
	 * Reloads all default covers, will only reload them once per session to improve performance when updating all themes etc.
	 * @return void
	 */
	public function reloadAllDefaultCovers() : void {
		if (!self::$_allCoversReloadedThisSession) {
			$this->query("UPDATE " . $this->__table . " SET thumbnailLoaded = 0, mediumLoaded = 0, largeLoaded = 0 where imageSource = 'default'");
			self::$_allCoversReloadedThisSession = true;
		}
	}

	public function reloadOMDBCovers() : void {
		$this->query("UPDATE " . $this->__table . " SET thumbnailLoaded = 0, mediumLoaded = 0, largeLoaded = 0 where imageSource = 'omdb_title' OR imageSource = 'omdb_title_year'");
	}

	public function getImageSource() : ?string {
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
	 * @return string
	 */
	public function getRecordType() : string {
		return $this->recordType;
	}

	/**
	 * @param string $recordType
	 */
	public function setRecordType(string $recordType): void {
		$this->__set('recordType', $recordType);
	}

	public function setRecordId(string $recordId): void {
		$this->__set('recordId', $recordId);
	}

	public function setImageSource(string $imageSource): void {
		$this->__set('imageSource', $imageSource);
	}

	/**
	 * Get the original URL of the cover image
	 * @return string|null
	 */
	//TODO: Keep original_url as a legacy fallback for one release, then remove the fallback and drop the column
	public function getOriginalUrl(string $size): ?string
	{
		$sizeProperty = $this->getOriginalUrlPropertyForSize($size);
		if ($sizeProperty !== null && !empty($this->$sizeProperty)) {
			return $this->$sizeProperty;
		}
		return $this->original_url;
	}

	/**
	 * Set the original URL of the cover image
	 * @param ?string $url
	 */
	public function setOriginalUrl(?string $url, string $size): void {
		$sizeProperty = $this->getOriginalUrlPropertyForSize($size);
		if ($sizeProperty !== null) {
			$this->__set($sizeProperty, $url);
			return;
		}
		$this->__set('original_url', $url);
	}

	public function clearOriginalUrls(): void {
		$this->__set('original_url', null);
		$this->__set('original_url_small', null);
		$this->__set('original_url_medium', null);
		$this->__set('original_url_large', null);
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
	 * @param ?int $timestamp
	 */
	public function setLastUrlValidation(?int $timestamp): void {
		$this->__set('last_url_validation', $timestamp);
	}

	public function setThumbnailLoaded(int $loaded) : void {
		$this->__set('thumbnailLoaded', $loaded);
	}

	public function setMediumLoaded(int $loaded) : void {
		$this->__set('mediumLoaded', $loaded);
	}

	public function setLargeLoaded(int $loaded) : void {
		$this->__set('largeLoaded', $loaded);
	}

	public function isThumbnailLoaded() : bool {
		return $this->thumbnailLoaded;
	}

	public function isMediumLoaded() : bool {
		return $this->mediumLoaded;
	}

	public function isLargeLoaded() : bool {
		return $this->largeLoaded;
	}

	public function setLastUsed(int $lastUsed): void {
		$this->__set('lastUsed', $lastUsed);
	}

	public function getSourceWidth() : ?int {
		return $this->sourceWidth;
	}

	public function setSourceWidth(int $sourceWidth): void {
		$this->sourceWidth = $sourceWidth;
	}

	public function getSourceHeight() : ?int {
		return $this->sourceHeight;
	}

	public function setSourceHeight(int $sourceHeight): void {
		$this->sourceHeight = $sourceHeight;
	}

	public function getUploadedImage() : ?bool {
		return $this->uploadedImage;
	}

	public function setUploadedImage(bool $uploadedImage): void {
		$this->uploadedImage = $uploadedImage;
	}

	/** @noinspection PhpUnused */
	public function getFirstLoaded() : ?int {
		return $this->firstLoaded;
	}

	public function setFirstLoaded(int $firstLoaded): void {
		$this->firstLoaded = $firstLoaded;
	}

	private function getOriginalUrlPropertyForSize(string $size): ?string {
		return match ($size) {
			'small' => 'original_url_small',
			'medium' => 'original_url_medium',
			'large' => 'original_url_large',
			default => null,
		};
	}
}
