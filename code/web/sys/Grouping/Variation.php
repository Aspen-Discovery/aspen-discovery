<?php

require_once ROOT_DIR . '/sys/Grouping/StatusInformation.php';

class Grouping_Variation {
	public $id;
	public $databaseId;
	public $label;
	public $language;
	public $isEContent = false;
	public $econtentSource = '';

	/** @var Grouping_Record[] */
	private $_records = [];

	/** @var Grouping_StatusInformation */
	private $_statusInformation;

	private $_hideByDefault = false;
	/**
	 * @var Grouping_Manifestation
	 */
	public $manifestation;

	/**
	 * Grouping_Variation constructor.
	 * @param Grouping_Record|array $record
	 */
	public function __construct($record) {
		if (is_array($record)) {
			$this->isEContent = $record['eContentSource'] != null;
			$this->econtentSource = $record['eContentSource'];
			$this->language = $record['language'] == null ? 'Unknown' : $record['language'];
			$this->databaseId = $record['id'];
		} else {
			$this->isEContent = $record->isEContent();
			$this->econtentSource = $record->getEContentSource();
			$this->language = $record->language;
		}
		if (!empty($this->econtentSource)) {
			$this->label = trim(translate([
					'text' => $this->econtentSource,
					'isPublicFacing' => true,
				])) . ' ';
		} else {
			$this->label = '';
		}
		if ($this->language != 'English' || !$this->isEContent) {
			$this->label .= translate([
				'text' => $this->language,
				'isPublicFacing' => true,
				'isMetadata' => true
			]);
			$this->label = trim($this->label);
		}
		$this->id = trim($this->econtentSource . ' ' . $this->language);
		$this->_statusInformation = new Grouping_StatusInformation();
		if (!is_array($record)) {
			$this->addRecord($record);
		}
	}

	/**
	 * @return Grouping_Record[]
	 */
	public function getRecords() : array {
		return $this->_records;
	}

	public function isValidForRecord(Grouping_Record $record) : bool {
		if ($record->isEContent() != $this->isEContent) {
			return false;
		}
		if ($this->isEContent && ($this->econtentSource != $record->getEContentSource())) {
			return false;
		}
		if ($record->language != $this->language) {
			return false;
		}
		return true;
	}

	public function addRecord(Grouping_Record $record) {
		$this->_records[] = $record;
		$this->_statusInformation->updateStatus($record->getStatusInformation());
	}

	public function getNumRelatedRecords(): int {
		return count($this->_records);
	}

	public function getRelatedRecords() {
		return $this->_records;
	}

	public function setSortedRelatedRecords($relatedRecords) {
		$this->_records = $relatedRecords;
	}

	/**
	 * @return Grouping_StatusInformation
	 */
	public function getStatusInformation(): Grouping_StatusInformation {
		return $this->_statusInformation;
	}

	private $_actions = null;

	/**
	 * @return array
	 */
	public function getActions(): array {
		global $timer;
		if ($this->_actions == null) {
			if ($this->getNumRelatedRecords() == 0) {
				$this->_actions = [];
			} elseif ($this->getNumRelatedRecords() == 1) {
				$firstRecord = $this->getFirstRecord();
				$this->_actions = $firstRecord->getActions($this->databaseId);
			} else {
				//Figure out what the preferred record is to place a hold on.  Since sorting has been done properly, this should always be the first
				$bestRecord = $this->getFirstRecord();

				if ($this->getNumRelatedRecords() > 1 && array_key_exists($bestRecord->getStatusInformation()->getGroupedStatus(), GroupedWorkDriver::$statusRankings) && GroupedWorkDriver::$statusRankings[$bestRecord->getStatusInformation()->getGroupedStatus()] <= 5) {
					// Check to set prompt for Alternate Edition for any grouped status equal to or less than that of "Checked Out"
					$promptForAlternateEdition = false;
					foreach ($this->_records as $relatedRecord) {
						if ($relatedRecord->getStatusInformation()->isAvailable() && $relatedRecord->isHoldable()) {
							$promptForAlternateEdition = true;
							unset($relatedRecord);
							break;
						}
					}
					if ($promptForAlternateEdition) {
						$alteredActions = [];
						foreach ($bestRecord->getActions($this->databaseId) as $action) {
							$action['onclick'] = str_replace('Record.showPlaceHold(', 'Record.showPlaceHoldEditions(', $action['onclick']);
							$alteredActions[] = $action;
						}
						$this->_actions = $alteredActions;
					} else {
						$this->_actions = $bestRecord->getActions($this->databaseId);
					}
					$timer->logTime("Done checking for whether or not we should be prompting for an alternate edition");
				} else {
					$this->_actions = $bestRecord->getActions($this->databaseId);
				}

				//Check to see if there are any downloadable files for the related records and if so make sure we have an action to download them.
				$numDownloadablePDFs = 0;
				$downloadPdfAction = '';
				$numDownloadableSupplementalFiles = 0;
				$downloadSupplementalFileAction = '';
				foreach ($this->_records as $relatedRecord) {
					$actions = $relatedRecord->getActions($this->databaseId);
					foreach ($actions as $action) {
						if(isset($action['type'])) {
							if ($action['type'] == 'download_pdf') {
								$numDownloadablePDFs += 1;
								if ($numDownloadablePDFs == 1) {
									$downloadPdfAction = $action;
								}
							} elseif ($action['type'] == 'download_supplemental_file') {
								$numDownloadableSupplementalFiles += 1;
								if ($numDownloadableSupplementalFiles == 1) {
									$downloadSupplementalFileAction = $action;
								}
							}
						}
					}
				}
				if (($downloadPdfAction > 0) || ($numDownloadableSupplementalFiles > 0)) {
					//Remove the action for downloading pdf & supplemental files if they exist
					foreach ($this->_actions as $key => $action) {
						if ($action['type'] == 'download_pdf' || $action['type'] == 'view_pdf' || $action['type'] == 'download_supplemental_file') {
							unset($this->_actions[$key]);
						}
					}
					if ($numDownloadablePDFs == 1) {
						//Add the existing action
						$this->_actions[] = $downloadPdfAction;
					} elseif ($numDownloadablePDFs > 1) {
						//Create a new action to allow the patron to select the correct pdf
						$driver = $bestRecord->getDriver();
						if ($driver == null) {
							$driver = RecordDriverFactory::initRecordDriverById($bestRecord->id);
						}
						$this->_actions[] = [
							'title' => translate([
								'text' => 'View PDF',
								'isPublicFacing' => true,
							]),
							'url' => '',
							'onclick' => "return AspenDiscovery.GroupedWork.selectFileToView('{$driver->getPermanentId()}', 'RecordPDF');",
							'requireLogin' => false,
							'type' => 'view_pdfs',
						];
						$this->_actions[] = [
							'title' => translate([
								'text' => 'Download PDF',
								'isPublicFacing' => true,
							]),
							'url' => '',
							'onclick' => "return AspenDiscovery.GroupedWork.selectFileDownload('{$driver->getPermanentId()}', 'RecordPDF');",
							'requireLogin' => false,
							'type' => 'download_pdfs',
						];
					}
					if ($numDownloadableSupplementalFiles == 1) {
						//Add the existing action
						$this->_actions[] = $downloadSupplementalFileAction;
					} elseif ($numDownloadableSupplementalFiles > 1) {
						//Create a new action to allow the patron to select the correct supplemental file
						$driver = $bestRecord->getDriver();
						if ($driver == null) {
							$driver = RecordDriverFactory::initRecordDriverById($bestRecord->id);
						}
						$this->_actions[] = [
							'title' => translate([
								'text' => 'Download Supplemental File',
								'isPublicFacing' => true,
							]),
							'url' => '',
							'onclick' => "return AspenDiscovery.GroupedWork.selectFileDownload('{$driver->getPermanentId()}', 'RecordSupplementalFile');",
							'requireLogin' => false,
							'type' => 'download_supplemental_file',
						];
					}
				}
			}
			global $timer;
			$timer->logTime("Loaded actions for variation");
		}
		return $this->_actions;

	}

	/**
	 * @return string
	 */
	public function getUrl(): string {
		if ($this->getNumRelatedRecords() == 1) {
			$firstRecord = $this->getFirstRecord();
			return $firstRecord->getUrl();
		} else {
			return '';
		}
	}

	public function getFirstRecord(): Grouping_Record {
		return reset($this->_records);
	}

	protected $_itemSummary = null;

	/**
	 * @return array
	 */
	function getItemSummary() : array {
		if ($this->_itemSummary == null) {
			global $timer;
			require_once ROOT_DIR . '/sys/Utils/GroupingUtils.php';
			$itemSummary = [];
			foreach ($this->_records as $record) {
				$recordSummary = $record->getItemSummary($this->databaseId);
				$itemSummary = mergeItemSummary($itemSummary, $recordSummary);
			}
			$this->_itemSummary = $itemSummary;
			$timer->logTime("Got item summary for variation");
		}
		ksort($itemSummary);
		return $this->_itemSummary;
	}

	protected $_itemsDisplayedByDefault = null;

	function getItemsDisplayedByDefault() {
		if ($this->_itemsDisplayedByDefault == null) {
			require_once ROOT_DIR . '/sys/Utils/GroupingUtils.php';
			$itemsDisplayedByDefault = [];
			if ($this->_records != null) {
				foreach ($this->_records as $record) {
					$itemsDisplayedByDefault = mergeItemSummary($itemsDisplayedByDefault, $record->getItemsDisplayedByDefault());
				}
			}
			ksort($itemsDisplayedByDefault);
			$this->_itemsDisplayedByDefault = $itemsDisplayedByDefault;
		}
		return $this->_itemsDisplayedByDefault;
	}

	public function getCopies() {
		return $this->_statusInformation->getCopies();
	}

	/**
	 * @return bool
	 */
	function isHideByDefault(): bool {
		return $this->_hideByDefault;
	}

	/**
	 * @param bool $hideByDefault
	 */
	function setHideByDefault(bool $hideByDefault): void {
		$this->_hideByDefault = $hideByDefault;
	}

	function isEContent() {
		return $this->isEContent;
	}

	public function showCopySummary() : bool {
		if (!$this->isEContent) {
			return true;
		}else{
			//For eContent, we will only show if there is more than one item
			foreach ($this->_records as $record) {
				if (count($record->getItems()) > 1) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Returns information for use when displaying grouped work manifestations using the horizontal display
	 *
	 * @return array
	 */
	function getHorizontalFormatDisplayInfo() : array {
		return [
			'databaseId' => $this->databaseId,
			'label' => $this->label,
			'groupedStatus' => translate(['text' => $this->getStatusInformation()->getGroupedStatus(), 'isPublicFacing' => true]),
			'numEditions' => $this->getNumRelatedRecords()
		];
	}
}