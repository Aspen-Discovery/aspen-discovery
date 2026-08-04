<?php

require_once ROOT_DIR . '/sys/Grouping/Variation.php';
require_once ROOT_DIR . '/sys/Grouping/StatusInformation.php';

class Grouping_Manifestation {
	public string $format;
	public string $formatCategory;

	/** @var ?Grouping_StatusInformation */
	private ?Grouping_StatusInformation $_statusInformation;

	//Information calculated at runtime
	private bool $_isEContent = false;

	private bool $_hideByDefault = false;

	/** @var Grouping_Variation[] */
	private array $_variations = [];
	//TODO: This should be contained within variations
	/** @var Grouping_Record[] */
	private array $_relatedRecords = [];

	private static ?GroupedWorkEContentSortingGroup $_econtentSorting = null;

	/**
	 * Grouping_Manifestation constructor.
	 * @param Grouping_Record|array $record
	 */
	function __construct(Grouping_Record|array $record) {
		$this->_statusInformation = new Grouping_StatusInformation();
		if (is_array($record)) {
			$this->format = $record['format'] ?? '';
			$this->formatCategory = $record['formatCategory'] ?? '';
		} else {
			$this->format = $record->format ?? '';
			$this->formatCategory = $record->formatCategory ?? '';
			$this->addRecord($record);
		}
	}

	function addVariation(Grouping_Variation $variation) : void {
		$variation->manifestation = $this;
		$this->_variations[] = $variation;
	}

	function removeVariation($variationKey) : void {
		unset($this->_variations[$variationKey]);
	}

	function sortVariations() : void {
		if (count($this->_variations) <= 1 ) {
			return;
		}
		//Only do special sorting for eContent manifestations
		if (!$this->isEContent()){
			return;
		}

		if (self::$_econtentSorting == null) {
			global $library;
			$groupedWorkDisplaySettings = $library->getGroupedWorkDisplaySettings();
			self::$_econtentSorting = $groupedWorkDisplaySettings->getEContentSortingGroup();
		}

		if (self::$_econtentSorting == null) {
			//Default to alphabetic sorting
			$sortMethod = 1;
			$sortAvailableFirst = false;
			$eContentSourceWeights = [];
		}else{
			$sortMethod = self::$_econtentSorting->sortMethod;
			$sortAvailableFirst = self::$_econtentSorting->sortAvailableSourcesFirst;
			$eContentSourceWeights = self::$_econtentSorting->getEContentSourceWeights();
		}

		usort($this->_variations, function(Grouping_Variation $a, Grouping_Variation $b) use ($sortMethod, $sortAvailableFirst, $eContentSourceWeights) {
			//The Available Online flag does not seem to be being set properly within status information.
			//Therefore, compare the Grouped Status which for eContent can only be Available Online, Checked Out, or On Order
			$availabilityComparison = $sortAvailableFirst ? $a->getStatusInformation()->getGroupedStatus() <=> $b->getStatusInformation()->getGroupedStatus() : 0;
			if ($availabilityComparison == 0) {
				if ($sortMethod == 1) {
					return strnatcasecmp($a->econtentSource, $b->econtentSource);
				}else{
					//Get the index of each source
					$weightA = array_key_exists($a->econtentSource, $eContentSourceWeights) ?  $eContentSourceWeights[$a->econtentSource] : 999;
					$weightB = array_key_exists($b->econtentSource, $eContentSourceWeights) ?  $eContentSourceWeights[$b->econtentSource] : 999;
					$weightComparison = $weightA <=> $weightB;
					if ($weightComparison == 0) {
						return strnatcasecmp($a->econtentSource, $b->econtentSource);
					}else{
						return $weightComparison;
					}
				}
			}else{
				if ($a->getStatusInformation()->getGroupedStatus() === 'Available Online') {
					return -1;
				}else{
					return 1;
				}
			}
		});
	}

	function addRecord(Grouping_Record $record) : void {
		//Check our variations to see if we need to create a new one
		$hasExistingVariation = false;
		foreach ($this->_variations as $variation) {
			if ($variation->isValidForRecord($record)) {
				$variation->addRecord($record);
				$hasExistingVariation = true;
				break;
			}
		}
		if (!$hasExistingVariation) {
			$variation = new Grouping_Variation($record);
			$this->_variations[] = $variation;
		}

		$this->_statusInformation->updateStatus($record->getStatusInformation());

		if ($record->isEContent()) {
			$this->_isEContent = true;
		}

		$this->_relatedRecords[] = $record;
	}

	function setSortedRelatedRecords($relatedRecords) : void {
		$this->_relatedRecords = $relatedRecords;
	}

	/**
	 * @return Grouping_Variation[]
	 */
	function getVariations() : array {
		return $this->_variations;
	}

	/** @noinspection PhpUnused */
	function getNumVariations() : int {
		return count($this->_variations);
	}

	protected ?bool $_isHideByDefault = null;
	protected ?bool $_hasHiddenFormats = null;

	/**
	 * @return bool
	 */
	function isHideByDefault(): bool {
		$this->loadHiddenInformation();
		return $this->_isHideByDefault;
	}

	/** @noinspection PhpUnused */
	function showActionButton(): bool {
		$firstRecord = reset($this->_relatedRecords);
		if ($firstRecord->isHoldable() || $firstRecord->isEContent()){
			return true;
		}
		return false;
	}

	function loadHiddenInformation() : void {
		if ($this->_isHideByDefault == null) {
			$this->_hasHiddenFormats = false;
			if (!$this->_hideByDefault) {
				$hideAllVariations = true;
				foreach ($this->_variations as $variation) {
					if (!$variation->isHideByDefault()) {
						$hideAllVariations = false;
					} else {
						$this->_hasHiddenFormats = true;
					}
				}
				$this->_isHideByDefault = $hideAllVariations;
			} else {
				$this->_isHideByDefault = true;
				$this->_hasHiddenFormats = true;
			}
		}
	}

	/** @noinspection PhpUnused */
	function hasHiddenFormats(): bool {
		$this->loadHiddenInformation();
		return $this->_hasHiddenFormats;
	}

	/**
	 * @param array $selectedFormat
	 * @param array $selectedFormatCategory
	 * @param array $selectedAvailability
	 * @param string|null $selectedDetailedAvailability
	 * @param bool $addOnlineMaterialsToAvailableNow
	 * @param array $selectedEcontentSources
	 * @param array $selectedLanguages
	 * @param string $searchSource
	 * @param bool $isSuperScope
	 */
	public function setHideByDefault(array $selectedFormat, array $selectedFormatCategory, array $selectedAvailability, ?string $selectedDetailedAvailability, bool $addOnlineMaterialsToAvailableNow, array $selectedEcontentSources, array $selectedLanguages, string $searchSource, bool $isSuperScope): void {
		if (!empty($selectedFormat) && !in_array($this->format, $selectedFormat)) {
			$allHidden = true;
			foreach ($selectedFormat as $tmpFormat) {
				//Do a secondary check to see if we have a more detailed format in the facet
				$detailedFormat = mapValue('format_by_detailed_format', $tmpFormat);
				//Also check the reverse
				$detailedFormat2 = mapValue('format_by_detailed_format', $this->format);
				if (!($this->format != $detailedFormat && !in_array($detailedFormat2, $selectedFormat))) {
					$allHidden = false;
				}
			}
			if ($allHidden) {
				$this->_hideByDefault = true;
			}
		}
		if (!empty($selectedFormatCategory) && !in_array($this->formatCategory, $selectedFormatCategory)) {
			if (($this->format == 'eAudiobook') && (in_array('eBook', $selectedFormatCategory) || in_array('Audio Books', $selectedFormatCategory))) {
				//This is a special case where the format is in 2 categories
			} elseif (($this->format == 'VOX Books') && (in_array('Books', $selectedFormatCategory) || in_array('Audio Books', $selectedFormatCategory))) {
				//This is another special case where the format is in 2 categories
			} else {
				$this->_hideByDefault = true;
			}
		}
		if ($this->getStatusInformation()->isAvailableOnline()) {
			$hide = !empty($selectedAvailability);
			if (in_array('available_online', $selectedAvailability) || (in_array('available', $selectedAvailability) && $addOnlineMaterialsToAvailableNow)) {
				$hide = false;
			} elseif (in_array('global', $selectedAvailability) || in_array('local', $selectedAvailability)) {
				$hide = false;
			}
			if ($hide) {
				$this->_hideByDefault = true;
			}
		} else {
			if (!$this->isEContent() && in_array('available_online', $selectedAvailability)) {
				$this->_hideByDefault = true;
			} else {
				if (in_array('available', $selectedAvailability)) {
					if ($this->isEContent()) {
						$this->_hideByDefault = true;
					} elseif ($isSuperScope) {
						if (!$this->getStatusInformation()->isAvailable()) {
							$this->_hideByDefault = true;
						}
					} elseif (!$this->getStatusInformation()->isAvailableLocally()) {
						$this->_hideByDefault = true;
					}
				} elseif (in_array('local', $selectedAvailability) && !$isSuperScope && (!$this->getStatusInformation()->hasLocalItem() && !$this->isEContent())) {
					$this->_hideByDefault = true;
				}
			}
		}

		if ($selectedDetailedAvailability) {
			$manifestationIsAvailable = false;
			if ($this->getStatusInformation()->isAvailableOnline()) {
				$manifestationIsAvailable = true;
			} elseif ($this->getStatusInformation()->isAvailable()) {
				foreach ($this->getItemSummary() as $itemSummary) {
					if (strlen($itemSummary['shelfLocation']) && substr_compare($itemSummary['shelfLocation'], $selectedDetailedAvailability, 0, strlen($selectedDetailedAvailability)) == 0) {
						if ($itemSummary['available']) {
							$manifestationIsAvailable = true;
							break;
						}
					}
				}
			}
			if (!$manifestationIsAvailable) {
				$this->_hideByDefault = true;
			}
		}

		if ($searchSource == 'econtent') {
			if (!$this->isEContent()) {
				$this->_hideByDefault = true;
			}
		}

		//Hide variations as needed
		if (!empty($selectedEcontentSources)) {
			foreach ($this->getVariations() as $variation) {
				if ($variation->isEContent() && !in_array($variation->econtentSource, $selectedEcontentSources)) {
					$variation->setHideByDefault(true);
				}
				if (!$variation->isEContent() && empty($selectedFormat)) {
					$variation->setHideByDefault(true);
				}
			}
		}
		if (!empty($selectedAvailability)) {
			foreach ($this->getVariations() as $variation) {
				if (($variation->getStatusInformation()->isAvailableOnline())) {
					$hide = true;
					if (in_array('available_online', $selectedAvailability) || (in_array('available', $selectedAvailability) && $addOnlineMaterialsToAvailableNow)) {
						$hide = false;
					} elseif (in_array('local', $selectedAvailability) || in_array('global', $selectedAvailability)) {
						$hide = false;
					}
					$variation->setHideByDefault($hide);
				} else {
					if (in_array('available', $selectedAvailability)) {
						if ($variation->isEContent()) {
							$variation->setHideByDefault(true);
						} elseif ($isSuperScope) {
							if (!$variation->getStatusInformation()->isAvailable()) {
								$variation->setHideByDefault(true);
							}
						} elseif (!$variation->getStatusInformation()->isAvailableLocally()) {
							$variation->setHideByDefault(true);
						}
					} elseif (in_array('local', $selectedAvailability) && !$isSuperScope && (!$variation->getStatusInformation()->hasLocalItem() && !$variation->isEContent())) {
						$variation->setHideByDefault(true);
					}
				}
			}
		}
	}

	/**
	 * @return Grouping_Record[]
	 */
	function getRelatedRecords(): array {
		return $this->_relatedRecords;
	}

	function getNumRelatedRecords() : int {
		return count($this->_relatedRecords);
	}

	function getFirstRecord() : Grouping_Record {
		return reset($this->_relatedRecords);
	}

	/**
	 * @return bool
	 */
	function isEContent(): bool {
		return $this->_isEContent;
	}

	/** @noinspection PhpUnused */
	public function showCopySummary() : bool {
		if (!$this->_isEContent) {
			return true;
		}else{
			//For eContent, we will only show if there is more than one item
			foreach ($this->_relatedRecords as $record) {
				if (count($record->getItems()) > 1) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * @return string
	 */
	function getUrl() : string {
		$firstVariation = reset($this->_variations);
		return $firstVariation->getUrl();
	}

	/**
	 * @return array
	 */
	function getActions(): array {
		$firstVariation = reset($this->_variations);
		return $firstVariation->getActions();
	}

	protected ?array $_itemSummary = null;

	/**
	 * @return array
	 */
	function getItemSummary() : array {
		if ($this->_itemSummary == null) {
			global $timer;
			require_once ROOT_DIR . '/sys/Utils/GroupingUtils.php';
			$itemSummary = [];
			foreach ($this->_variations as $variation) {
				$itemSummary = mergeItemSummary($itemSummary, $variation->getItemSummary());
			}
			require_once ROOT_DIR . '/sys/Utils/GroupingUtils.php';
			if ($this->isPeriodical()) {
				$itemSummary = sortPeriodicalItemsByShelfLocationAndCallNumber($itemSummary);
			} else {
				$itemSummary = sortItemsByShelfLocationAndCallNumber($itemSummary);
			}
			$this->_itemSummary = $itemSummary;
			$timer->logTime("Got item summary for manifestation");
		}
		return $this->_itemSummary;
	}

	protected ?array $_itemDetails = null;

	/**
	 * @return array
	 */
	function getItemDetails() : array {
		if ($this->_itemDetails == null) {
			global $timer;
			require_once ROOT_DIR . '/sys/Utils/GroupingUtils.php';
			$itemDetails = [];
			foreach ($this->_variations as $variation) {
				$itemDetails += $variation->getItemDetails();
			}
			require_once ROOT_DIR . '/sys/Utils/GroupingUtils.php';
			if ($this->isPeriodical()) {
				$itemDetails = sortPeriodicalItemsByShelfLocationAndCallNumber($itemDetails);
			} else {
				$itemDetails = sortItemsByShelfLocationAndCallNumber($itemDetails);
			}
			$this->_itemDetails = $itemDetails;
			$timer->logTime("Got item details for manifestation");
		}
		return $this->_itemDetails;
	}

	function hasVolumes() : bool {
		foreach ($this->_variations as $variation) {
				$records = $variation->getRecords();
				foreach ($records as $record) {
					if (!empty($record->getUnsuppressedVolumeData())) {
						return true;
					}
				}
			}
		return false;
	}

	protected ?array $_itemsDisplayedByDefault = null;

	/** @noinspection PhpUnused */
	function getItemsDisplayedByDefault() : array {
		if ($this->_itemsDisplayedByDefault == null) {
			require_once ROOT_DIR . '/sys/Utils/GroupingUtils.php';
			$itemsDisplayedByDefault = [];
			if ($this->_variations != null) {
				foreach ($this->_variations as $variation) {
					$itemsDisplayedByDefault = mergeItemSummary($itemsDisplayedByDefault, $variation->getItemsDisplayedByDefault());
				}
			}
			//sort things alphabetically and newest first for periodicals/serials
			require_once ROOT_DIR . '/sys/Utils/GroupingUtils.php';
			if ($this->isPeriodical()) {
				$itemsDisplayedByDefault = sortPeriodicalItemsByShelfLocationAndCallNumber($itemsDisplayedByDefault);
			}else{
				$itemsDisplayedByDefault = sortItemsByShelfLocationAndCallNumber($itemsDisplayedByDefault);
			}
			$this->_itemsDisplayedByDefault = $itemsDisplayedByDefault;
		}
		return $this->_itemsDisplayedByDefault;
	}

	private ?bool $_isPeriodical = null;
	function isPeriodical(): bool {
		if ($this->_isPeriodical === null) {
			global $library;
			$ils = 'Unknown';
			if ($library->getAccountProfile() != null) {
				$ils = $library->getAccountProfile()->ils;

			}
			//If this is a periodical we may have additional information
			$isPeriodical = false;
			$format = $this->format;
			require_once ROOT_DIR . '/sys/Indexing/FormatMapValue.php';
			if ($ils == 'sierra' || $ils == 'millennium') {
				$formatValue = new FormatMapValue();
				$formatValue->format = $format;
				$formatValue->displaySierraCheckoutGrid = 1;
				if ($formatValue->find(true)) {
					$isPeriodical = true;
				}
			} else {
				if ($format == 'Journal' || $format == 'Newspaper' || $format == 'Print Periodical' || $format == 'Magazine') {
					$isPeriodical = true;
				}
			}
			$this->_isPeriodical = $isPeriodical;
		}
		return $this->_isPeriodical;
	}

	/**
	 * @return Grouping_StatusInformation
	 */
	function getStatusInformation(): Grouping_StatusInformation {
		return $this->_statusInformation;
	}

	function isAvailable() : bool {
		return $this->_statusInformation->isAvailable();
	}

	function isAvailableOnline() : bool {
		return $this->_statusInformation->isAvailableOnline();
	}

	public function getCopies() : int {
		return $this->_statusInformation->getCopies();
	}

	/** @noinspection PhpUnused */
	public function getNumAvailableCopies() : bool {
		return $this->_statusInformation->getAvailableCopies();
	}


	function getNumberOfCopiesMessage() : string {
		return $this->_statusInformation->getNumberOfCopiesMessage();
	}


	function getVariationInformation() : array {
		return $this->_variations;
	}

	/** @noinspection PhpUnused */
	function getFirstVariation() : Grouping_Variation {
		return reset($this->_variations);
	}

	/**
	 * Returns information for use when displaying grouped work manifestations using the horizontal display
	 *
	 * @return string
	 * @noinspection PhpUnused
	 */
	function getHorizontalFormatDisplayInfo() : string {
		$variationsData = array_map(function ($variation) {
			return $variation->getHorizontalFormatDisplayInfo();
		}, $this->_variations);

		$data = [
			'numVariations' => $this->getNumVariations(),
			'variations' => $variationsData
		];
		return json_encode($data);
	}

	/**
	 * Check if this manifestation has invalid/missing format information.
	 *
	 * @return bool
	 * @noinspection PhpUnused
	 */
	function hasInvalidFormat(): bool {
		return empty($this->format) || empty($this->formatCategory);
	}

}