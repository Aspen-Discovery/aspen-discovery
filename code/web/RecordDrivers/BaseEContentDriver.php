<?php
/**
 * Description goes here
 *
 * @category VuFind-Plus 
 * @author Mark Noble <mark@marmot.org>
 * Date: 2/9/14
 * Time: 9:50 PM
 */

require_once ROOT_DIR . '/RecordDrivers/MarcRecord.php';

abstract class BaseEContentDriver  extends MarcRecord {
	abstract function getModuleName();
	abstract function getValidProtectionTypes();

	/**
	 * Constructor.  We build the object using all the data retrieved
	 * from the (Solr) index.  Since we have to
	 * make a search call to find out which record driver to construct,
	 * we will already have this data available, so we might as well
	 * just pass it into the constructor.
	 *
	 * @param   array|File_MARC_Record||string   $recordData     Data to construct the driver from
	 * @access  public
	 */
	public function __construct($recordData){
		parent::__construct($recordData);
	}

	function getHelpText($fileOrUrl){
		return "";
	}

	protected function isValidProtectionType($protectionType) {
		return in_array(strtolower($protectionType), $this->getValidProtectionTypes());
	}

	abstract function isEContentHoldable($locationCode, $eContentFieldData);
	abstract function isLocalItem($locationCode, $eContentFieldData);
	abstract function isLibraryItem($locationCode, $eContentFieldData);
	abstract function isItemAvailable($itemId, $totalCopies);
	function getUsageRestrictions($sharing, $libraryLabel, $locationLabel){
		if ($sharing == 'shared'){
			return "Available to Everyone";
		}else if ($sharing == 'library'){
			return 'Available to patrons of ' . $libraryLabel;
		}else if ($sharing == 'location'){
			return 'Available to patrons of ' .  $locationLabel;
		}else{
			return 'Unable to determine usage restrictions';
		}
	}
	abstract function isValidForUser($locationCode, $eContentFieldData);

	public function getLinkUrl($useUnscopedHoldingsSummary = false) {
		global $interface;
		$baseUrl = $this->getRecordUrl();
		$linkUrl = $baseUrl . '?searchId=' . $interface->get_template_vars('searchId') . '&amp;recordIndex=' . $interface->get_template_vars('recordIndex') . '&amp;page='  . $interface->get_template_vars('page');
		if ($useUnscopedHoldingsSummary){
			$linkUrl .= '&amp;searchSource=marmot';
		}else{
			$linkUrl .= '&amp;searchSource=' . $interface->get_template_vars('searchSource');
		}
		return $linkUrl;
	}

	function getQRCodeUrl(){
		global $configArray;
		return $configArray['Site']['url'] . '/qrcode.php?type=' . $this->getModuleName() . '&id=' . $this->getPermanentId();
	}

	abstract function getSharing($locationCode, $eContentFieldData);

	abstract function getActionsForItem($itemId, $fileName, $acsId);

	abstract function getEContentFormat($fileOrUrl, $iType);

	function getFormatNotes($fileOrUrl) {
		return '';
	}

	function getFileSize($fileOrUrl) {
		return 0;
	}

	protected function isHoldable(){
		return false;
	}
}
