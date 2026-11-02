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
		$this->valid = false;
		if (!is_string($recordId)) {
			return;
		}

		$this->id = $recordId;
		require_once ROOT_DIR . '/sys/BorrowBox/BorrowBoxAPIProduct.php';
		$borrowBoxProduct = new BorrowBoxAPIProduct();
		$borrowBoxProduct->borrowboxId = $recordId;
		if (!$borrowBoxProduct->find(true)) {
			return;
		}

		$this->borrowBoxProduct = $borrowBoxProduct;
		$this->valid = true;
		parent::__construct($groupedWork);
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

		if ($groupedWork->getNumResults() != 1) {
			return;
		}

		$groupedWork->fetch();
		$this->groupedWork = clone $groupedWork;
	}

	public function getPermanentId(): ?string {
		return $this->getGroupedWorkId();
	}

	public function getGroupedWorkId(): ?string {
		if ($this->groupedWork == null) {
			$this->loadGroupedWork();
		}
		if ($this->groupedWork == null) {
			return null;
		}
		return $this->groupedWork->permanent_id;
	}

	public function isValid(): bool {
		return $this->valid;
	}

	function getStatusSummary(): array {
		$availabilityInfo = $this->getAvailabilityInformation();

		$hasAvailable = false;
		foreach ($availabilityInfo as $availability) {
			if (strtoupper($availability->availabilityStatus ?? '') === 'AVAILABLE') {
				$hasAvailable = true;
			}
		}

		$statusSummary = [];
		$statusSummary['recordId'] = $this->id;
		$statusSummary['totalCopies'] = count($availabilityInfo);
		$statusSummary['accessType'] = 'borrowbox';
		$statusSummary['alwaysAvailable'] = false;
		$statusSummary['isBorrowBox'] = true;
		$statusSummary['availableCopies'] = $hasAvailable ? 1 : 0;

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
		if ($this->availability != null) {
			return $this->availability;
		}

		require_once ROOT_DIR . '/sys/BorrowBox/BorrowBoxAPIProductAvailability.php';
		$this->availability = [];
		if ($this->borrowBoxProduct === null) {
			return $this->availability;
		}

		$availabilityObj = new BorrowBoxAPIProductAvailability();
		$availabilityObj->productId = $this->borrowBoxProduct->id;
		$availabilityObj->find();
		while ($availabilityObj->fetch()) {
			$this->availability[] = clone $availabilityObj;
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
		$items = [];
		$hasProduct = $this->valid && $this->borrowBoxProduct !== null;
		if ($hasProduct) {
			$items[] = $this->borrowBoxProduct;
		}
		return $items;
	}

	public function getSeries(): array {
		$seriesData = $this->getGroupedWorkDriver()->getSeries();
		if ($seriesData != null) {
			return $seriesData;
		}

		$metaData = $this->getBorrowBoxMetaData();
		if ($metaData === null) {
			return $seriesData ?? [];
		}

		$rawData = json_decode($metaData->rawData ?? '{}');
		$seriesName = $rawData->seriesName ?? $this->borrowBoxProduct->series ?? null;
		if ($seriesName == null) {
			return $seriesData ?? [];
		}

		return [
			'seriesTitle' => $seriesName,
			'fromNovelist' => false,
			'fromSeriesIndex' => false,
		];
	}

	/**
	 * Returns the template for the staff view.
	 * @return string
	 */
	public function getStaffView(): string {
		global $interface;

		$interface->assign('bookcoverInfo', $this->getBookcoverInfo());

		$groupedWorkDriver = $this->getGroupedWorkDriver();
		$hasValidGroupedWork = false;
		if ($groupedWorkDriver != null) {
			$groupedWorkDriver->assignGroupedWorkStaffView();
			$hasValidGroupedWork = $groupedWorkDriver->isValid();
		}
		$interface->assign('hasValidGroupedWork', $hasValidGroupedWork);

		if ($hasValidGroupedWork) {
			$this->getGroupedWorkDriver()->assignGroupedWorkStaffView();

			require_once ROOT_DIR . '/sys/Grouping/NonGroupedRecord.php';
			$nonGroupedRecord = new NonGroupedRecord();
			$nonGroupedRecord->source = $this->getRecordType();
			$nonGroupedRecord->recordId = $this->id;
			if ($nonGroupedRecord->find(true)) {
				$interface->assign('isUngrouped', true);
				$interface->assign('ungroupingId', $nonGroupedRecord->id);
			} else {
				$interface->assign('isUngrouped', false);
			}
		}

		if ($this->borrowBoxProduct !== null) {
			$interface->assign('borrowBoxProduct', $this->borrowBoxProduct);
		}

		return 'RecordDrivers/BorrowBox/staff.tpl';
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
		if ($metaData === null) {
			return [];
		}

		$rawData = json_decode($metaData->rawData ?? '{}');
		if (!isset($rawData->languageDetails)) {
			return [];
		}

		$languages = [];
		foreach ($rawData->languageDetails as $language) {
			$languages[] = is_object($language) ? $language->name : $language;
		}
		return $languages;
	}

	public function getDescriptionFast() {
		$metaData = $this->getBorrowBoxMetaData();
		if ($metaData === null) {
			return '';
		}
		return $metaData->summary;
	}

	public function getDescription() {
		$metaData = $this->getBorrowBoxMetaData();
		if ($metaData === null) {
			return '';
		}
		return $metaData->summary;
	}

	/**
	 * Return the first valid ISBN found in the record (favoring ISBN-10 over
	 * ISBN-13 when possible).
	 *
	 * @return  string
	 */
	public function getCleanISBN(): string {
		require_once ROOT_DIR . '/sys/ISBN.php';

		$isbns = $this->getISBNs();
		$isbn13 = false;

		foreach ($isbns as $isbn) {
			if ($pos = strpos($isbn, ' ')) {
				$isbn = substr($isbn, 0, $pos);
			}

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
		if ($this->isbns != null) {
			return $this->isbns;
		}

		$this->isbns = [];
		if ($this->borrowBoxProduct === null) {
			return $this->isbns;
		}

		if (!empty($this->borrowBoxProduct->isbn13)) {
			$this->isbns[] = $this->borrowBoxProduct->isbn13;
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
		if ($this->borrowBoxProduct === null) {
			return '';
		}
		return $this->borrowBoxProduct->title ?? '';
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
		if ($this->borrowBoxProduct === null) {
			return '';
		}
		return $this->borrowBoxProduct->subtitle ?? '';
	}

	/**
	 * Get an array of all the formats associated with the record.
	 *
	 * @access  protected
	 * @return  string[]
	 */
	public function getFormats(): array {
		$relatedRecord = $this->getRelatedRecord();
		if ($relatedRecord == null) {
			return [];
		}

		$formats = [];
		$formats[$relatedRecord->getFormat()] = $relatedRecord->getFormat();
		return $formats;
	}

	/**
	 * Get an array of all the format categories associated with the record.
	 */
	public function getFormatCategory(): string|array|null {
		return [$this->getGroupedWorkDriver()->getFormatCategory()];
	}

	public function getAuthor(): string {
		if ($this->borrowBoxProduct === null) {
			return '';
		}
		return $this->borrowBoxProduct->primaryCreatorName ?? '';
	}

	public function getPrimaryAuthor(): string {
		return $this->getAuthor();
	}

	/**
	 * @return string[]
	 */
	public function getContributors(): array {
		$metaData = $this->getBorrowBoxMetaData();
		if ($metaData === null) {
			return [];
		}

		$rawData = json_decode($metaData->rawData ?? '{}');
		if (!isset($rawData->authors)) {
			return [];
		}

		$contributors = [];
		foreach ($rawData->authors as $author) {
			$name = $author->fullName ?? '';
			if ($name !== '') {
				$contributors[$name] = $name;
			}
		}
		return $contributors;
	}

	public function getBookcoverUrl($size = 'small', $absolutePath = false): string {
		global $configArray;
		$bookCoverUrl = $absolutePath ? $configArray['Site']['url'] : '';
		$bookCoverUrl .= '/bookcover.php?size=' . $size;
		$bookCoverUrl .= '&id=' . $this->id;
		$bookCoverUrl .= '&type=borrowbox';
		return $bookCoverUrl;
	}

	public function getBorrowBoxBookcoverUrl(): ?string {
		if (!empty($this->borrowBoxProduct->cover)) {
			return $this->borrowBoxProduct->cover;
		}
		$metaData = $this->getBorrowBoxMetaData();
		$metaDataHasCover = $metaData !== null && !empty($metaData->cover);
		if ($metaDataHasCover) {
			return $metaData->cover;
		}
		return null;
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

	public function getRatingData(): ?array {
		require_once ROOT_DIR . '/services/API/WorkAPI.php';
		$workAPI = new WorkAPI();
		$groupedWorkId = $this->getGroupedWorkId();
		if ($groupedWorkId == null) {
			return null;
		}
		return $workAPI->getRatingData($this->getGroupedWorkId());
	}

	public function getMoreDetailsOptions(): array {
		global $interface;
		global $library;

		$isbn = $this->getCleanISBN();

		$availabilityInfo = $this->getAvailabilityInformation();
		$interface->assign('availability', $availabilityInfo);
		$interface->assign('showAvailability', !empty($availabilityInfo));

		$moreDetailsOptions = $this->getBaseMoreDetailsOptions($isbn);

		$relatedRecords = $this->getGroupedWorkDriver()->getRelatedRecords();
		if (count($relatedRecords) > 1) {
			$interface->assign('relatedManifestations', $this->getGroupedWorkDriver()->getRelatedManifestations());
			$interface->assign('workId', $this->getGroupedWorkDriver()->getPermanentId());
			$moreDetailsOptions['otherEditions'] = [
				'label' => 'Other Editions and Formats',
				'body' => $interface->fetch('GroupedWork/relatedManifestations.tpl'),
				'hideByDefault' => false,
			];
		}

		$moreDetailsOptions['moreDetails'] = [
			'label' => 'More Details',
			'body' => $interface->fetch('BorrowBox/view-more-details.tpl'),
		];
		$moreDetailsOptions['citations'] = [
			'label' => 'Citations',
			'body' => $interface->fetch('Record/cite.tpl'),
		];
		$moreDetailsOptions['copyDetails'] = [
			'label' => 'Copy Details',
			'body' => $interface->fetch('BorrowBox/view-copies.tpl'),
		];
		if ($interface->getVariable('showStaffView')) {
			$moreDetailsOptions['staff'] = [
				'label' => 'Staff View',
				'onShow' => "AspenDiscovery.BorrowBox.getStaffView('$this->id');",
				'body' => '<div id="staffViewPlaceHolder">' . translate([
						'text' => 'Loading Staff View.',
						'isPublicFacing' => true,
					]) . '</div>',
			];
		}

		return $this->filterAndSortMoreDetailsOptions($moreDetailsOptions);
	}

	public function getRecordUrl(): string {
		$id = $this->getUniqueID();
		return '/BorrowBox/' . $id . '/Home';
	}

	/**
	 * @return string[]
	 */
	function getPublishers(): array {
		$metaData = $this->getBorrowBoxMetaData();
		$publisherMissing = $metaData === null || !isset($metaData->publisher);
		if ($publisherMissing) {
			return [];
		}

		$publishers = [];
		$publishers[] = $metaData->publisher;
		return $publishers;
	}

	/**
	 * @return string[]
	 */
	function getPublicationDates(): array {
		$metaData = $this->getBorrowBoxMetaData();
		if ($metaData === null) {
			return [];
		}

		$rawData = json_decode($metaData->rawData ?? '{}');
		if (!isset($rawData->releaseDate)) {
			return [];
		}

		$releaseDate = $rawData->releaseDate;
		if (is_numeric($releaseDate)) {
			return [date('Y', intdiv((int)$releaseDate, 1000))];
		}
		return [substr($releaseDate, 0, 4)];
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
		}

		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		if ($this->groupedWorkDriver == null) {
			$this->groupedWorkDriver = new GroupedWorkDriver($this->getPermanentId());
		}
		return $this->groupedWorkDriver;
	}

	protected ?array $_actions = null;

	public function getRecordActions($relatedRecord, $variationId, $isAvailable, $isHoldable, $volumeData = null): array {
		if ($this->_actions !== null) {
			return $this->_actions;
		}

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

		if (!$loadDefaultActions) {
			return $this->_actions;
		}

		global $offlineMode;
		global $loginAllowedWhileOffline;
		$loginBlockedWhileOffline = $offlineMode && !$loginAllowedWhileOffline;
		if ($loginBlockedWhileOffline) {
			return $this->_actions;
		}

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
		return $this->_actions;
	}

	function getNumHolds(): int {
		return 0;
	}

	public function getSemanticData(): ?array {
		require_once ROOT_DIR . '/RecordDrivers/LDRecordOffer.php';
		$relatedRecord = $this->getRelatedRecord();
		if ($relatedRecord == null) {
			return null;
		}

		$linkedDataRecord = new LDRecordOffer($relatedRecord);
		$semanticData[] = [
			'@context' => 'https://schema.org',
			'@type' => $linkedDataRecord->getWorkType(),
			'name' => $this->getTitle(),
			'creator' => $this->getAuthor(),
			'bookEdition' => $this->getEditions(),
			'isAccessibleForFree' => true,
			'image' => $this->getBookcoverUrl('medium', true),
			'offers' => $linkedDataRecord->getOffers(),
		];

		global $interface;
		$interface->assign('og_title', $this->getTitle());
		$interface->assign('og_description', $this->getDescriptionFast());
		$interface->assign('og_type', $this->getGroupedWorkDriver()->getOGType());
		$interface->assign('og_image', $this->getBookcoverUrl('medium', true));
		$interface->assign('og_url', $this->getAbsoluteUrl());
		return $semanticData;
	}

	function getRelatedRecord(): ?Grouping_Record {
		$id = strtolower('borrowbox:' . $this->id);
		$groupedWorkDriver = $this->getGroupedWorkDriver();
		if ($groupedWorkDriver == null) {
			return null;
		}
		return $groupedWorkDriver->getRelatedRecord($id);
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
