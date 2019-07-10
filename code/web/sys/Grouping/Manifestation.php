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
    private $_shelfLocation = [];
    private $_callNumber = [];
    private $_isEContent = false;

    private $_hideByDefault = false;

    /** @var Grouping_Variation[]  */
    private $_variations = [];
    //TODO: This should be contained within variations
    /** @var Grouping_Record[]  */
    private $_relatedRecords = [];

    /**
     * Grouping_Manifestation constructor.
     * @param Grouping_Record $record
     */
    function __construct($record) {
        $this->format = $record->format;
        $this->formatCategory = $record->formatCategory;
        $this->_statusInformation = new Grouping_StatusInformation();
        $this->addRecord($record);
    }

    function addRecord(Grouping_Record $record){
        //Check our variations to see if we need to create a new one
        $hasExistingVariation = false;
        foreach ($this->_variations as $variation){
            if ($variation->isValidForRecord($record)){
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

        if ($record->isEContent()){
            $this->_isEContent = true;
        }

        if ($record->getShelfLocation()){
            $this->_shelfLocation[$record->getShelfLocation()] = $record->getShelfLocation();
        }
        if ($record->getCallNumber()){
            $this->_callNumber[$record->getCallNumber()] = $record->getCallNumber();
        }
        $this->_relatedRecords[] = $record;
    }

    /**
     * @return Grouping_Variation[]
     */
    function getVariations(){
        return $this->_variations;
    }

    function getNumVariations(){
        return count($this->_variations);
    }

    /**
     * @return bool
     */
    function isHideByDefault(): bool
    {
        if (!$this->_hideByDefault){
            $hideAllVariations = true;
            foreach ($this->_variations as $variation) {
                if (!$variation->isHideByDefault()){
                    $hideAllVariations = false;
                    break;
                }
            }
            return $hideAllVariations;
        }else{
            return true;
        }

    }

    function hasHiddenFormats(): bool
    {
        if (!$this->_hideByDefault){
            foreach ($this->_variations as $variation) {
                if ($variation->isHideByDefault()){
                    return true;
                }
            }
            return false;
        }else{
            return true;
        }
    }

    /**
     * @param bool $hideByDefault
     */
    function setHideByDefault(bool $hideByDefault): void
    {
        $this->_hideByDefault = $hideByDefault;
    }

    /**
     * @return Grouping_Record[]
     */
    function getRelatedRecords(): array
    {
        return $this->_relatedRecords;
    }

    function getNumRelatedRecords(){
        return count($this->_relatedRecords);
    }

    function getFirstRecord(){
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

	private $_itemSummary = null;
    /**
     * @return array
     */
    function getItemSummary()
    {
	    if ($this->_itemSummary == null){
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

    /**
     * @return Grouping_StatusInformation
     */
    function getStatusInformation(): Grouping_StatusInformation
    {
        return $this->_statusInformation;
    }

    public function getCopies(){
        return $this->_statusInformation->getCopies();
    }

}