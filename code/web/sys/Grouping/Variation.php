<?php

require_once ROOT_DIR . '/sys/Grouping/StatusInformation.php';

class Grouping_Variation
{
	public $id;
	public $label;
	public $language;
	public $isEcontent = false;
	public $econtentSource = '';

	/** @var Grouping_Record[] */
	private $_records;

	/** @var Grouping_StatusInformation */
	private $_statusInformation;

	private $_hideByDefault = false;

	public function __construct(Grouping_Record $record)
	{
		$this->isEcontent = $record->isEContent();
		$this->econtentSource = $record->getEContentSource();
		$this->language = $record->language;
		$this->label = $this->econtentSource;
		if ($this->language != 'English' || !$this->isEcontent) {
			$this->label = trim($this->econtentSource . ' ' . translate($this->language));
		}
		$this->id = trim($this->econtentSource . ' ' . $this->language);
		$this->_statusInformation = new Grouping_StatusInformation();
		$this->addRecord($record);
	}

	/**
	 * @return Grouping_Record[]
	 */
	public function getRecords()
	{
		return $this->_records;
	}

	public function isValidForRecord(Grouping_Record $record)
	{
		if ($record->isEContent() != $this->isEcontent) {
			return false;
		}
		if ($this->isEcontent && ($this->econtentSource != $record->getEContentSource())) {
			return false;
		}
		if ($record->language != $this->language) {
			return false;
		}
		return true;
	}

	public function addRecord(Grouping_Record $record)
	{
		$this->_records[] = $record;
		$this->_statusInformation->updateStatus($record->getStatusInformation());
	}

	public function getNumRelatedRecords(): int
	{
		return count($this->_records);
	}

	public function getRelatedRecords()
	{
		return $this->_records;
	}

	/**
	 * @return Grouping_StatusInformation
	 */
	public function getStatusInformation(): Grouping_StatusInformation
	{
		return $this->_statusInformation;
	}

	private $_actions = null;

	/**
	 * @return array
	 */
	public function getActions(): array
	{
		if ($this->_actions == null) {
			if ($this->getNumRelatedRecords() == 1) {
				$firstRecord = $this->getFirstRecord();
				$this->_actions = $firstRecord->getActions();
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
						$alteredActions = array();
						foreach ($bestRecord->getActions() as $action) {
							$action['onclick'] = str_replace('Record.showPlaceHold(', 'Record.showPlaceHoldEditions(', $action['onclick']);
							$alteredActions[] = $action;
						}
						$this->_actions = $alteredActions;
					} else {
						$this->_actions = $bestRecord->getActions();
					}
				} else {
					$this->_actions = $bestRecord->getActions();
				}

				if ($this->getNumRelatedRecords() > 1){
					//Check to see if there are any downloadable files for the related records and if so make sure we have an action to download them.
					$numDownloadablePDFs = 0;
					$downloadPdfAction = '';
					$numDownloadableSupplementalFiles = 0;
					$downloadSupplementalFileAction = '';
					foreach ($this->_records as $relatedRecord) {
						$actions = $relatedRecord->getActions();
						foreach ($actions as $action) {
							if ($action['type'] == 'download_pdf'){
								$numDownloadablePDFs += 1;
								if ($numDownloadablePDFs == 1) {
									$downloadPdfAction = $action;
								}
							}elseif ($action['type'] == 'download_supplemental_file'){
								$numDownloadableSupplementalFiles += 1;
								if ($numDownloadableSupplementalFiles == 1) {
									$downloadSupplementalFileAction = $action;
								}
							}
						}
					}
					//Remove the action for downloading pdf & supplemental files if they exist
					foreach ($this->_actions as $key => $action) {
						if ($action['type'] == 'download_pdf' || $action['type'] == 'view_pdf' || $action['type'] == 'download_supplemental_file'){
							unset($this->_actions[$key]);
						}
					}
					if ($numDownloadablePDFs == 1) {
						//Add the existing action
						$this->_actions[] = $downloadPdfAction;
					}elseif ($numDownloadablePDFs > 1) {
						//Create a new action to allow the patron to select the correct pdf
						$driver = $bestRecord->getDriver();
						if ($driver == null) {
							$driver = RecordDriverFactory::initRecordDriverById($bestRecord->id);
						}
						$this->_actions[] = array(
							'title' => 'View PDF',
							'url' => '',
							'onclick' => "return AspenDiscovery.GroupedWork.selectFileToView('{$driver->getPermanentId()}', 'RecordPDF');",
							'requireLogin' => false,
							'type' => 'view_pdfs'
						);
						$this->_actions[] = array(
							'title' => 'Download PDF',
							'url' => '',
							'onclick' => "return AspenDiscovery.GroupedWork.selectFileDownload('{$driver->getPermanentId()}', 'RecordPDF');",
							'requireLogin' => false,
							'type' => 'download_pdfs'
						);
					}
					if ($numDownloadableSupplementalFiles == 1) {
						//Add the existing action
						$this->_actions[] = $downloadSupplementalFileAction;
					}elseif ($numDownloadableSupplementalFiles > 1) {
						//Create a new action to allow the patron to select the correct supplemental file
						$driver = $bestRecord->getDriver();
						if ($driver == null) {
							$driver = RecordDriverFactory::initRecordDriverById($bestRecord->id);
						}
						$this->_actions[] = array(
							'title' => 'Download Supplemental File',
							'url' => '',
							'onclick' => "return AspenDiscovery.GroupedWork.selectFileDownload('{$driver->getPermanentId()}', 'RecordSupplementalFile');",
							'requireLogin' => false,
							'type' => 'download_supplemental_file'
						);
					}
				}
			}
		}
		return $this->_actions;

	}

	/**
	 * @return string
	 */
	public function getUrl(): string
	{
		if ($this->getNumRelatedRecords() == 1) {
			$firstRecord = $this->getFirstRecord();
			return $firstRecord->getUrl();
		} else {
			return '';
		}
	}

	public function getFirstRecord(): Grouping_Record
	{
		return reset($this->_records);
	}

	private $_itemSummary = null;

	/**
	 * @return array
	 */
	function getItemSummary()
	{
		if ($this->_itemSummary == null) {
			global $timer;
			require_once ROOT_DIR . '/sys/Utils/GroupingUtils.php';
			$itemSummary = [];
			foreach ($this->_records as $record) {
				$itemSummary = mergeItemSummary($itemSummary, $record->getItemSummary());
			}
			ksort($itemSummary);
			$this->_itemSummary = $itemSummary;
			$timer->logTime("Got item summary for variation");
		}
		return $this->_itemSummary;
	}

	public function getCopies()
	{
		return $this->_statusInformation->getCopies();
	}

	/**
	 * @return bool
	 */
	function isHideByDefault(): bool
	{
		return $this->_hideByDefault;
	}

	/**
	 * @param bool $hideByDefault
	 */
	function setHideByDefault(bool $hideByDefault): void
	{
		$this->_hideByDefault = $hideByDefault;
	}

	function isEContent()
	{
		return $this->isEcontent;
	}
}