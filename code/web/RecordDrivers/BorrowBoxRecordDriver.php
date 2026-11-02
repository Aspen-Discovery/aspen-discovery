<?php

require_once ROOT_DIR . '/RecordDrivers/RecordInterface.php';
require_once ROOT_DIR . '/RecordDrivers/GroupedWorkSubDriver.php';

class BorrowBoxRecordDriver extends GroupedWorkSubDriver {
	protected string $id;
	private ?object $borrowBoxProduct = null;
	private ?object $borrowBoxMetaData = null;
	private bool $valid;

	/** @var string[]|null */
	private ?array $isbns = null;

	//$groupedWork and $groupedWorkDriver are defined in GroupedWorkSubDriver

	/**
	 * Constructor.  We build the object using all the data retrieved
	 * from the (Solr) index.  Since we have to
	 * make a search call to find out which record driver to construct,
	 * we will already have this data available, so we might as well
	 * just pass it into the constructor.
	 *
	 * @param string|null $recordId The id of the record within BorrowBox.
	 * @param GroupedWork|null $groupedWork ;
	 * @access  public
	 */
	public function __construct($recordId = null, ?GroupedWork $groupedWork = null) {
		if (is_string($recordId)) {
			$this->id = $recordId;
			require_once ROOT_DIR . '/sys/BorrowBox/BorrowBoxAPIProduct.php';
			$borrowBoxProduct = new BorrowBoxAPIProduct();
			$borrowBoxProduct->borrowboxId = $recordId;
			if ($borrowBoxProduct->find(true)) {
				$this->borrowBoxProduct = $borrowBoxProduct;
				$this->valid = true;
			} else {
				$this->valid = false;
			}
		} else {
			$this->valid = false;
		}
		if ($this->valid) {
			parent::__construct($groupedWork);
		}
	}

	public function getIdWithSource(): string {
		return 'borrowbox:' . $this->id;
	}

	public function getModule(): string {
		return 'BorrowBox';
	}

	public function getRecordType(): string {
		return 'borrowbox';
	}

	/**
	 * Load the grouped work that this record is connected to.
	 */
	public function loadGroupedWork(): void {
		require_once ROOT_DIR . '/sys/Grouping/GroupedWorkPrimaryIdentifier.php';
		require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
		$groupedWork = new GroupedWork();
		$query = "SELECT grouped_work.* FROM grouped_work INNER JOIN grouped_work_primary_identifiers ON grouped_work.id = grouped_work_id WHERE type='borrowbox' AND identifier = '" . $this->getUniqueID() . "'";
		$groupedWork->query($query);

		if ($groupedWork->getNumResults() == 1) {
			$groupedWork->fetch();
			$this->groupedWork = clone $groupedWork;
		}
	}

	public function getPermanentId(): ?string {
		return $this->getGroupedWorkId();
	}

	public function getGroupedWorkId(): ?string {
		if ($this->groupedWork == null) {
			$this->loadGroupedWork();
		}
		if ($this->groupedWork) {
			return $this->groupedWork->permanent_id;
		} else {
			return null;
		}
	}

	public function isValid(): bool {
		return $this->valid;
	}

	function getStatusSummary(): array {
		$availabilityInfo = $this->getAvailabilityInformation();

		// BorrowBox uses status-based availability rather than copy counts.
		// Determine availability from the availabilityStatus field.
		$hasAvailable = false;
		foreach ($availabilityInfo as $availability) {
			if (strtoupper($availability->availabilityStatus ?? '') === 'AVAILABLE') {
				$hasAvailable = true;
			}
		}

		//Load status summary
		$statusSummary = [];
		$statusSummary['recordId'] = $this->id;
		$statusSummary['totalCopies'] = count($availabilityInfo);
		$statusSummary['accessType'] = 'borrowbox';
		$statusSummary['alwaysAvailable'] = false;
		$statusSummary['isBorrowBox'] = true;
		$statusSummary['availableCopies'] = $hasAvailable ? 1 : 0;

		// Map BorrowBox availability statuses
		if ($this->borrowBoxProduct !== null && isset($this->borrowBoxProduct->rawStatus)) {
			$rawStatus = $this->borrowBoxProduct->rawStatus;
			switch ($rawStatus) {
				case 'AVAILABLE':
					$statusSummary['status'] = 'Available from BorrowBox';
					$statusSummary['available'] = true;
					$statusSummary['class'] = 'available';
					break;
				case 'ON_LOAN':
					$statusSummary['status'] = 'Checked Out';
					$statusSummary['available'] = false;
					$statusSummary['class'] = 'checkedOut';
					break;
				case 'NEW':
					$statusSummary['status'] = 'Coming Soon';
					$statusSummary['available'] = false;
					$statusSummary['class'] = 'comingSoon';
					break;
				case 'UNAVAILABLE':
					$statusSummary['status'] = 'Unavailable';
					$statusSummary['available'] = false;
					$statusSummary['class'] = 'unavailable';
					break;
				default:
					if ($hasAvailable) {
						$statusSummary['status'] = 'Available from BorrowBox';
						$statusSummary['available'] = true;
						$statusSummary['class'] = 'available';
					} else {
						$statusSummary['status'] = 'Checked Out';
						$statusSummary['available'] = false;
						$statusSummary['class'] = 'checkedOut';
					}
					break;
			}
		} else {
			if ($hasAvailable) {
				$statusSummary['status'] = 'Available from BorrowBox';
				$statusSummary['available'] = true;
				$statusSummary['class'] = 'available';
			} else {
				$statusSummary['status'] = 'Checked Out';
				$statusSummary['available'] = false;
				$statusSummary['class'] = 'checkedOut';
			}
		}

		//Determine which buttons to show
		$statusSummary['holdQueueLength'] = 0;
		$statusSummary['numHolds'] = 0;
		$statusSummary['showPlaceHold'] = !($statusSummary['available'] ?? false);
		$statusSummary['showCheckout'] = $statusSummary['available'] ?? false;
		$statusSummary['showAddToWishlist'] = false;
		$statusSummary['showAccessOnline'] = false;

		return $statusSummary;
	}

	/** @var BorrowBoxAPIProductAvailability[]|null */
	private ?array $availability = null;

	/**
	 * @return BorrowBoxAPIProductAvailability[]
	 */
	function getAvailabilityInformation(): array {
		if ($this->availability == null) {
			require_once ROOT_DIR . '/sys/BorrowBox/BorrowBoxAPIProductAvailability.php';
			$this->availability = [];
			if ($this->borrowBoxProduct !== null) {
				$availabilityObj = new BorrowBoxAPIProductAvailability();
				$availabilityObj->productId = $this->borrowBoxProduct->id;
				$availabilityObj->find();
				while ($availabilityObj->fetch()) {
					$this->availability[] = clone $availabilityObj;
				}
			}
		}
		return $this->availability;
	}

	/**
	 * Get the items (formats) for this BorrowBox product.
	 * BorrowBox products typically have a single format per product,
	 * so this is simpler than OverDrive.
	 *
	 * @return array
	 */
	public function getItems(): array {
		// BorrowBox products don't have multiple formats per product like OverDrive.
		// The format is determined at the product level.
		$items = [];
		$hasProduct = $this->valid && $this->borrowBoxProduct !== null;
		if ($hasProduct) {
			$items[] = $this->borrowBoxProduct;
		}
		return $items;
	}

	public function getSeries(): array {
		$seriesData = $this->getGroupedWorkDriver()->getSeries();
		if ($seriesData == null) {
			$metaData = $this->getBorrowBoxMetaData();
			if ($metaData !== null) {
				$rawData = json_decode($metaData->rawData ?? '{}');
				$seriesName = isset($rawData->series) ? $rawData->series : null;
				if ($seriesName != null) {
					$seriesData = [
						'seriesTitle' => $seriesName,
						'fromNovelist' => false,
						'fromSeriesIndex' => false,
					];
				}
			}
		}
		return $seriesData ?? [];
	}

	/**
	 * The Table of Contents extracted from the record.
	 * Returns null if no Table of Contents is available.
	 *
	 * @access  public
	 * @return  null|array              Array of elements in the table of contents
	 */
	public function getTableOfContents(): ?array {
		return null;
	}

	/**
	 * Return the unique identifier of this record within the Solr index;
	 * useful for retrieving additional information (like tags and user
	 * comments) from the external MySQL database.
	 *
	 * @access  public
	 * @return  string              Unique identifier.
	 */
	public function getUniqueID(): string {
		return $this->id;
	}

	/**
	 * @return string[]
	 */
	function getLanguage(): array {
		$metaData = $this->getBorrowBoxMetaData();
		$languages = [];
		if ($metaData !== null) {
			$rawData = json_decode($metaData->rawData ?? '{}');
			if (isset($rawData->languages)) {
				foreach ($rawData->languages as $language) {
					$languages[] = is_object($language) ? $language->name : $language;
				}
			}
		}
		return $languages;
	}

	public function getDescriptionFast() {
		$metaData = $this->getBorrowBoxMetaData();
		if ($metaData !== null) {
			return $metaData->fullDescription;
		}
		return '';
	}

	public function getDescription() {
		$metaData = $this->getBorrowBoxMetaData();
		if ($metaData !== null) {
			return $metaData->fullDescription;
		}
		return '';
	}

	/**
	 * Return the first valid ISBN found in the record (favoring ISBN-10 over
	 * ISBN-13 when possible).
	 *
	 * @return  string
	 */
	public function getCleanISBN(): string {
		require_once ROOT_DIR . '/sys/ISBN.php';

		// Get all the ISBNs and initialize the return value:
		$isbns = $this->getISBNs();
		$isbn13 = false;

		// Loop through the ISBNs:
		foreach ($isbns as $isbn) {
			// Strip off any unwanted notes:
			if ($pos = strpos($isbn, ' ')) {
				$isbn = substr($isbn, 0, $pos);
			}

			// If we find an ISBN-10, return it immediately; otherwise, if we find
			// an ISBN-13, save it if it is the first one encountered.
			$isbnObj = new ISBN($isbn);
			if ($isbn10 = $isbnObj->get10()) {
				return $isbn10;
			}
			if (!$isbn13) {
				$isbn13 = $isbnObj->get13();
			}
		}
		return $isbn13;
	}

	/**
	 * Get an array of all ISBNs associated with the record (might be empty).
	 *
	 * @access  protected
	 * @return  string[]
	 */
	public function getISBNs(): array {
		if ($this->isbns == null) {
			$this->isbns = [];
			if ($this->borrowBoxProduct !== null) {
				$metaData = $this->getBorrowBoxMetaData();
				if ($metaData !== null) {
					$rawData = json_decode($metaData->rawData ?? '{}');
					if (isset($rawData->isbn)) {
						$this->isbns[] = $rawData->isbn;
					}
				}
			}
		}
		return $this->isbns;
	}

	public function getOCLCNumber(): string {
		return '';
	}

	/**
	 * Get an array of all UPCs associated with the record (might be empty).
	 *
	 * @access  protected
	 * @return  string[]
	 */
	public function getUPCs(): array {
		return [];
	}

	/**
	 * Get the full title of the record.
	 *
	 * @return  string
	 */
	public function getTitle(): string {
		if ($this->borrowBoxProduct !== null) {
			return $this->borrowBoxProduct->title ?? '';
		}
		return '';
	}

	/**
	 * Get the full title of the record.
	 *
	 * @return  string
	 */
	public function getSortableTitle(): string {
		return $this->getTitle();
	}

	public function getShortTitle(): string {
		return $this->getTitle();
	}

	public function getSubtitle(): string {
		if ($this->borrowBoxProduct !== null) {
			return $this->borrowBoxProduct->subtitle ?? '';
		}
		return '';
	}

	/**
	 * Get an array of all the formats associated with the record.
	 *
	 * @access  protected
	 * @return  string[]
	 */
	public function getFormats(): array {
		$relatedRecord = $this->getRelatedRecord();
		$formats = [];
		if ($relatedRecord != null) {
			$formats[$relatedRecord->getFormat()] = $relatedRecord->getFormat();
		}
		return $formats;
	}

	/**
	 * Get an array of all the format categories associated with the record.
	 */
	public function getFormatCategory(): string|array|null {
		return [$this->getGroupedWorkDriver()->getFormatCategory()];
	}

	public function getAuthor(): string {
		if ($this->borrowBoxProduct !== null) {
			return $this->borrowBoxProduct->primaryCreatorName ?? '';
		}
		return '';
	}

	public function getPrimaryAuthor(): string {
		return $this->getAuthor();
	}

	/**
	 * @return string[]
	 */
	public function getContributors(): array {
		$contributors = [];
		$metaData = $this->getBorrowBoxMetaData();
		if ($metaData !== null) {
			$rawData = json_decode($metaData->rawData ?? '{}');
			if (isset($rawData->creators)) {
				foreach ($rawData->creators as $creator) {
					$contributors[$creator->fileAs ?? $creator->name ?? ''] = $creator->fileAs ?? $creator->name ?? '';
				}
			}
		}
		return $contributors;
	}

	private function getBorrowBoxMetaData(): ?object {
		$metaDataNeedsLoading = $this->borrowBoxMetaData == null && $this->borrowBoxProduct !== null;
		if ($metaDataNeedsLoading) {
			require_once ROOT_DIR . '/sys/BorrowBox/BorrowBoxAPIProductMetaData.php';
			$this->borrowBoxMetaData = new BorrowBoxAPIProductMetaData();
			$this->borrowBoxMetaData->productId = $this->borrowBoxProduct->id;
			if (!$this->borrowBoxMetaData->find(true)) {
				$this->borrowBoxMetaData = null;
			}
		}
		return $this->borrowBoxMetaData;
	}

	/**
	 * @return string[]
	 */
	function getPublishers(): array {
		$publishers = [];
		$metaData = $this->getBorrowBoxMetaData();
		if ($metaData !== null && isset($metaData->publisher)) {
			$publishers[] = $metaData->publisher;
		}
		return $publishers;
	}

	/**
	 * @return string[]
	 */
	function getPublicationDates(): array {
		$publicationDates = [];
		$metaData = $this->getBorrowBoxMetaData();
		if ($metaData !== null) {
			$rawData = json_decode($metaData->rawData ?? '{}');
			if (isset($rawData->publishDate)) {
				$publishYear = substr($rawData->publishDate, 0, 4);
				$publicationDates[] = $publishYear;
			}
		}
		return $publicationDates;
	}

	/**
	 * @return array
	 */
	public function getSubjects(): array {
		$metaData = $this->getBorrowBoxMetaData();
		if ($metaData === null) {
			return [];
		}
		$rawData = json_decode($metaData->rawData ?? '{}');
		$hasGenres = !empty($rawData->genres) && is_array($rawData->genres);
		if (!$hasGenres) {
			return [];
		}
		$subjects = [];
		foreach ($rawData->genres as $genre) {
			if (!empty($genre->name)) {
				$subjects[] = $genre->name;
			}
		}
		return array_values(array_unique($subjects));
	}

	/**
	 * @return string[]
	 */
	function getPlacesOfPublication(): array {
		return [];
	}

	/**
	 * @return string[]
	 */
	public function getEditions(): array {
		return [];
	}

	public function getGroupedWorkDriver(): ?GroupedWorkDriver {
		$permanentId = $this->getPermanentId();
		if ($permanentId == null) {
			return null;
		} else {
			require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
			if ($this->groupedWorkDriver == null) {
				$this->groupedWorkDriver = new GroupedWorkDriver($this->getPermanentId());
			}
			return $this->groupedWorkDriver;
		}
	}

	protected ?array $_actions = null;

	public function getRecordActions($relatedRecord, $variationId, $isAvailable, $isHoldable, $volumeData = null): array {
		if ($this->_actions === null) {
			if ($relatedRecord == null) {
				$relatedRecord = $this->getRelatedRecord();
			}
			$this->_actions = [];

			if (UserAccount::isLoggedIn()) {
				$activeUser = UserAccount::getActiveUserObj();
				if ($activeUser->isValidForEContentSource('borrowbox')) {
					$this->_actions = array_merge($this->_actions, $activeUser->getCirculatedRecordActionsWithLazyLoading('borrowbox', $this->id));
				}
				$loadDefaultActions = count($this->_actions) == 0;
			} else {
				$activeUser = null;
				$loadDefaultActions = true;
			}

			if ($loadDefaultActions) {
				global $offlineMode;
				global $loginAllowedWhileOffline;
				if (!$offlineMode || $loginAllowedWhileOffline) {
					if ($isAvailable) {
						$this->_actions[] = [
							'title' => translate([
								'text' => 'Borrow with BorrowBox',
								'isPublicFacing' => true,
							]),
							'onclick' => "return AspenDiscovery.BorrowBox.checkOutTitle('$this->id', this);",
							'requireLogin' => false,
							'type' => 'borrowbox_checkout',
						];
					} else {
						$this->_actions[] = [
							'title' => translate([
								'text' => 'Place Hold with BorrowBox',
								'isPublicFacing' => true,
							]),
							'onclick' => "return AspenDiscovery.BorrowBox.placeHold('$this->id', this);",
							'requireLogin' => false,
							'type' => 'borrowbox_hold',
						];
					}
				}
			}
		}
		return $this->_actions;
	}

	function getNumHolds(): int {
		// BorrowBox API does not expose hold queue counts
		return 0;
	}

	function getRelatedRecord(): ?Grouping_Record {
		$id = strtolower('borrowbox:' . $this->id);
		$groupedWorkDriver = $this->getGroupedWorkDriver();
		if ($groupedWorkDriver == null) {
			return null;
		} else {
			return $groupedWorkDriver->getRelatedRecord($id);
		}
	}

	/**
	 * Get an array of all ISSNs associated with the record (might be empty).
	 *
	 * @access  public
	 * @return  array
	 */
	public function getISSNs(): array {
		return [];
	}
}
