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

    public function __construct(Grouping_Record $record){
        $this->isEcontent = $record->isEContent();
        $this->econtentSource = $record->getEContentSource();
        $this->language = $record->language;
        $this->label = $this->econtentSource;
        if ($this->language != 'English' || !$this->isEcontent){
            $this->label = trim($this->econtentSource . ' ' . translate($this->language));
        }
        $this->id = trim($this->econtentSource . ' ' . $this->language);
        $this->_statusInformation = new Grouping_StatusInformation();
        $this->addRecord($record);
    }

    /**
     * @return Grouping_Record[]
     */
    public function getRecords(){
        return $this->_records;
    }

    public function isValidForRecord(Grouping_Record $record)
    {
        if ($record->isEContent() != $this->isEcontent) {
            return false;
        }
        if ($this->isEcontent && ($this->econtentSource != $record->getEContentSource())){
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

    public function getNumRelatedRecords() :int
    {
        return count($this->_records);
    }

    public function getRelatedRecords(){
        return $this->_records;
    }

    /**
     * @return Grouping_StatusInformation
     */
    public function getStatusInformation(): Grouping_StatusInformation
    {
        return $this->_statusInformation;
    }

    /**
     * @return array
     */
    public function getActions(): array
    {
        if ($this->getNumRelatedRecords() == 1) {
            $firstRecord = $this->getFirstRecord();
            return $firstRecord->getActions();
        } else {
            //Figure out what the preferred record is to place a hold on.  Since sorting has been done properly, this should always be the first
            $bestRecord = $this->getFirstRecord();

            if ($this->getNumRelatedRecords() > 1 && array_key_exists($bestRecord->getStatusInformation()->getGroupedStatus(), GroupedWorkDriver::$statusRankings) && GroupedWorkDriver::$statusRankings[ $bestRecord->getStatusInformation()->getGroupedStatus() ] <= 5) {
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
                        $action['onclick'] = str_replace('Record.showPlaceHold', 'Record.showPlaceHoldEditions', $action['onclick']);
                        $alteredActions[] = $action;
                    }
                    return $alteredActions;
                } else {
                    return $bestRecord->getActions();
                }
            } else {
                return $bestRecord->getActions();
            }
        }
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

    public function getFirstRecord() : Grouping_Record{
        return reset($this->_records);
    }

    /**
     * @return array
     */
    function getItemSummary()
    {
        require_once ROOT_DIR . '/sys/Utils/GroupingUtils.php';
        $itemSummary = [];
        foreach ($this->_records as $record){
            $itemSummary = mergeItemSummary($itemSummary, $record->getItemSummary());
        }
        return $itemSummary;
    }

    public function getCopies(){
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
}