<?php

require_once ROOT_DIR . '/sys/Grouping/Variation.php';
require_once ROOT_DIR . '/sys/Grouping/StatusInformation.php';

class Grouping_Manifestation
{
	public $format;
	public $formatCategory;

	/** @var Grouping_StatusInformation */
	private $_statusInformation = null;

	//Information calculated at runtime
	private $_isEContent = false;

	private $_hideByDefault = false;

	/** @var Grouping_Variation[] */
	private $_variations = [];
	//TODO: This should be contained within variations
	/** @var Grouping_Record[] */
	private $_relatedRecords = [];

	/**
	 * Grouping_Manifestation constructor.
	 * @param Grouping_Record|array $record
	 */
	function __construct($record)
	{
		$this->_statusInformation = new Grouping_StatusInformation();
		if (is_array($record)) {
			$this->format = $record['format'];
			$this->formatCategory = $record['formatCategory'];
		}else{
			$this->format = $record->format;
			$this->formatCategory = $record->formatCategory;
			$this->addRecord($record);
		}
	}

	function addVariation(Grouping_Variation $variation){
		$variation->manifestation = $this;
		$this->_variations[] = $variation;
	}

	function removeVariation($variationKey){
		unset($this->_variations[$variationKey]);
	}
	function addRecord(Grouping_Record $record)
	{
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

	function setSortedRelatedRecords($relatedRecords){
		$this->_relatedRecords = $relatedRecords;
	}

	/**
	 * @return Grouping_Variation[]
	 */
	function getVariations()
	{
		return $this->_variations;
	}

	function getNumVariations()
	{
		return count($this->_variations);
	}

	protected $_isHideByDefault = null;
	protected $_hasHiddenFormats = null;
	/**
	 * @return bool
	 */
	function isHideByDefault(): bool
	{
		$this->loadHiddenInformation();
		return $this->_isHideByDefault;
	}

	function loadHiddenInformation(){
		if ($this->_isHideByDefault == null){
			$this->_hasHiddenFormats = false;
			if (!$this->_hideByDefault) {
				$hideAllVariations = true;
				foreach ($this->_variations as $variation) {
					if (!$variation->isHideByDefault()) {
						$hideAllVariations = false;
					}else{
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

	function hasHiddenFormats(): bool
	{
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
	public function setHideByDefault(array $selectedFormat, array $selectedFormatCategory, array $selectedAvailability, ?string $selectedDetailedAvailability, bool $addOnlineMaterialsToAvailableNow, array $selectedEcontentSources, array $selectedLanguages, string $searchSource, bool $isSuperScope): void
	{
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
			} else  if (($this->format == 'VOX Books') && (in_array('Books', $selectedFormatCategory) || in_array('Audio Books', $selectedFormatCategory))) {
				//This is another special case where the format is in 2 categories
			} else {
				$this->_hideByDefault = true;
			}
		}
		if (($this->getStatusInformation()->isAvailableOnline())) {
			$hide = !empty($selectedAvailability);
			if (in_array('available_online', $selectedAvailability) || (in_array('available', $selectedAvailability) && $addOnlineMaterialsToAvailableNow)) {
				$hide = false;
			} else if (in_array('global', $selectedAvailability) || in_array('local', $selectedAvailability)) {
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
						if (!$this->getStatusInformation()->isAvailableOnline()) {
							$this->_hideByDefault = true;
						} elseif (!$addOnlineMaterialsToAvailableNow) {
							$this->_hideByDefault = true;
						}
					} else if ($isSuperScope) {
						if (!$this->getStatusInformation()->isAvailable()) {
							$this->_hideByDefault = true;
						}
					} else if (!$this->getStatusInformation()->isAvailableLocally()) {
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
			} else if ($this->getStatusInformation()->isAvailable()) {
				foreach ($this->getItemSummary() as $itemSummary) {
					if (strlen($itemSummary['shelfLocation']) && substr_compare($itemSummary['shelfLocation'], $selectedDetailedAvailability, 0)) {
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
		if (!empty($selectedLanguages)) {
			foreach ($this->getVariations() as $variation) {
				if (!in_array($variation->language, $selectedLanguages)) {
					$variation->setHideByDefault(true);
				}
			}
		}
		if (!empty($selectedEcontentSources)) {
			foreach ($this->getVariations() as $variation) {
				if ($variation->isEContent() && !in_array($variation->econtentSource, $selectedEcontentSources)) {
					$variation->setHideByDefault(true);
				}
				if (!$variation->isEcontent() && empty($selectedFormat)) {
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
					} else if (in_array('local', $selectedAvailability) || in_array('global', $selectedAvailability)) {
						$hide = false;
					}
					$variation->setHideByDefault($hide);
				} else {
					if (in_array('available', $selectedAvailability)) {
						if ($variation->isEContent()) {
							if (!$variation->getStatusInformation()->isAvailableOnline()) {
								$variation->setHideByDefault(true);
							} elseif (!$addOnlineMaterialsToAvailableNow) {
								$variation->setHideByDefault(true);
							}
						} else if ($isSuperScope) {
							if (!$variation->getStatusInformation()->isAvailable()) {
								$variation->setHideByDefault(true);
							}
						} else if (!$variation->getStatusInformation()->isAvailableLocally()) {
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
	function getRelatedRecords(): array
	{
		return $this->_relatedRecords;
	}

	function getNumRelatedRecords()
	{
		return count($this->_relatedRecords);
	}

	function getFirstRecord()
	{
		return reset($this->_relatedRecords);
	}

	/**
	 * @return bool
	 */
	function isEContent(): bool
	{
		return $this->_isEContent;
	}

	/**
	 * @return string
	 */
	function getUrl()
	{
		$firstVariation = reset($this->_variations);
		return $firstVariation->getUrl();
	}

	/**
	 * @return array
	 */
	function getActions(): array
	{
		$firstVariation = reset($this->_variations);
		return $firstVariation->getActions();
	}

	protected $_itemSummary = null;

	/**
	 * @return array
	 */
	function getItemSummary()
	{
		if ($this->_itemSummary == null) {
			global $timer;
			require_once ROOT_DIR . '/sys/Utils/GroupingUtils.php';
			$itemSummary = [];
			foreach ($this->_variations as $variation) {
				$itemSummary = mergeItemSummary($itemSummary, $variation->getItemSummary());
			}
			ksort($itemSummary);
			$this->_itemSummary = $itemSummary;
			$timer->logTime("Got item summary for manifestation");
		}
		return $this->_itemSummary;
	}

	protected $_itemsDisplayedByDefault = null;

	/** @noinspection PhpUnused */
	function getItemsDisplayedByDefault(){
		if ($this->_itemsDisplayedByDefault == null){
			require_once ROOT_DIR . '/sys/Utils/GroupingUtils.php';
			$itemsDisplayedByDefault = [];
			foreach ($this->_variations as $variation) {
				$itemsDisplayedByDefault = mergeItemSummary($itemsDisplayedByDefault, $variation->getItemsDisplayedByDefault());
			}
			ksort($itemsDisplayedByDefault);
			$this->_itemsDisplayedByDefault = $itemsDisplayedByDefault;
		}
		return $this->_itemsDisplayedByDefault;
	}

	/**
	 * @return Grouping_StatusInformation
	 */
	function getStatusInformation(): Grouping_StatusInformation
	{
		return $this->_statusInformation;
	}

	function isAvailable(){
		return $this->_statusInformation->isAvailable();
	}

	function isAvailableOnline(){
		return $this->_statusInformation->isAvailableOnline();
	}

	public function getCopies()
	{
		return $this->_statusInformation->getCopies();
	}

	function getNumberOfCopiesMessage()
	{
		return $this->_statusInformation->getNumberOfCopiesMessage();
	}


	function getVariationInformation()
	{
		return $this->_variations;
	}

}