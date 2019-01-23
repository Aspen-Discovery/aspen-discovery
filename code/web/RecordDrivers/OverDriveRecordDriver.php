<?php
/**
 * Record Driver to handle loading data for OverDrive Records
 *
 * @category Pika
 * @author Mark Noble <mark@marmot.org>
 * Date: 12/2/13
 * Time: 8:37 AM
 */

require_once ROOT_DIR . '/RecordDrivers/Interface.php';
class OverDriveRecordDriver extends RecordInterface {

	private $id;
	/** @var OverDriveAPIProduct  */
	private $overDriveProduct;
	/** @var  OverDriveAPIProductMetaData */
	private $overDriveMetaData;
	private $valid;
	private $isbns = null;
	private $upcs = null;
	private $asins = null;
	private $items;
	protected $scopingEnabled = false;

	/**
	 * The Grouped Work that this record is connected to
	 * @var  GroupedWork */
	protected $groupedWork;
	protected $groupedWorkDriver = null;

	/**
	 * Constructor.  We build the object using all the data retrieved
	 * from the (Solr) index.  Since we have to
	 * make a search call to find out which record driver to construct,
	 * we will already have this data available, so we might as well
	 * just pass it into the constructor.
	 *
	 * @param   string $recordId The id of the record within OverDrive.
	 * @param  GroupedWork $groupedWork;
	 * @access  public
	 */
	public function __construct($recordId, $groupedWork = null) {
		if (is_string($recordId)){
			//The record is the identifier for the overdrive title
			$this->id = $recordId;
			require_once ROOT_DIR . '/sys/OverDrive/OverDriveAPIProduct.php';
			$this->overDriveProduct = new OverDriveAPIProduct();
			$this->overDriveProduct->overdriveId = $recordId;
			if ($this->overDriveProduct->find(true)){
				$this->valid = true;
			}else{
				$this->valid = false;
			}
			if ($groupedWork == null){
				$this->loadGroupedWork();
			}else{
				$this->groupedWork = $groupedWork;
			}
		}
	}

	public function getModule(){
		return 'OverDrive';
	}

	protected $itemsFromIndex;
	public function setItemsFromIndex($itemsFromIndex, $realTimeStatusNeeded){
		$this->itemsFromIndex = $itemsFromIndex;
	}

	protected $detailedRecordInfoFromIndex;
	public function setDetailedRecordInfoFromIndex($detailedRecordInfoFromIndex, $realTimeStatusNeeded){
		$this->detailedRecordInfoFromIndex = $detailedRecordInfoFromIndex;
	}

	public function setScopingEnabled($enabled){
		$this->scopingEnabled = $enabled;
	}
	/**
	 * Load the grouped work that this record is connected to.
	 */
	public function loadGroupedWork() {
		require_once ROOT_DIR . '/sys/Grouping/GroupedWorkPrimaryIdentifier.php';
		require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
		$groupedWork = new GroupedWork();
		$query = "SELECT grouped_work.* FROM grouped_work INNER JOIN grouped_work_primary_identifiers ON grouped_work.id = grouped_work_id WHERE type='overdrive' AND identifier = '" . $this->getUniqueID() . "'";
		$groupedWork->query($query);

		if ($groupedWork->N == 1){
			$groupedWork->fetch();
			$this->groupedWork = clone $groupedWork;
		}
	}

	function getAbsoluteUrl(){
		global $configArray;
		$recordId = $this->getUniqueID();

		return $configArray['Site']['url'] . '/' . $this->getModule() . '/' . $recordId;
	}

	public function getPermanentId(){
		return $this->getGroupedWorkId();
	}
	public function getGroupedWorkId(){
		if (!isset($this->groupedWork)){
			$this->loadGroupedWork();
		}
		if ($this->groupedWork){
			return $this->groupedWork->permanent_id;
		}else{
			return null;
		}

	}

	public function isValid(){
		return $this->valid;
	}

	/**
	 * Get text that can be displayed to represent this record in
	 * breadcrumbs.
	 *
	 * @access  public
	 * @return  string              Breadcrumb text to represent this record.
	 */
	public function getBreadcrumb() {
		// TODO: Implement getBreadcrumb() method.
	}

	/**
	 * Assign necessary Smarty variables and return a template name
	 * to load in order to display the requested citation format.
	 * For legal values, see getCitationFormats().  Returns null if
	 * format is not supported.
	 *
	 * @param   string  $format     Citation format to display.
	 * @access  public
	 * @return  string              Name of Smarty template file to display.
	 */
	public function getCitation($format)
	{
		require_once ROOT_DIR . '/sys/CitationBuilder.php';

		// Build author list:
		$authors = array();
		$primary = $this->getAuthor();
		if (!empty($primary)) {
			$authors[] = $primary;
		}
		$authors = array_unique(array_merge($authors, $this->getContributors()));

		// Collect all details for citation builder:
		$publishers = $this->getPublishers();
		$pubDates = $this->getPublicationDates();
		$pubPlaces = $this->getPlacesOfPublication();
		$details = array(
				'authors' => $authors,
				'title' => $this->getTitle(),
				'subtitle' => $this->getSubTitle(),
				'pubPlace' => count($pubPlaces) > 0 ? $pubPlaces[0] : null,
				'pubName' => count($publishers) > 0 ? $publishers[0] : null,
				'pubDate' => count($pubDates) > 0 ? $pubDates[0] : null,
				'edition' => $this->getEdition(),
				'format' => $this->getFormats()
		);

		// Build the citation:
		$citation = new CitationBuilder($details);
		switch($format) {
			case 'APA':
				return $citation->getAPA();
			case 'AMA':
				return $citation->getAMA();
			case 'ChicagoAuthDate':
				return $citation->getChicagoAuthDate();
			case 'ChicagoHumanities':
				return $citation->getChicagoHumanities();
			case 'MLA':
				return $citation->getMLA();
		}
		return '';
	}

	/**
	 * Get an array of strings representing citation formats supported
	 * by this record's data (empty if none).  Legal values: "APA", "MLA".
	 *
	 * @access  public
	 * @return  array               Strings representing citation formats.
	 */
	public function getCitationFormats()
	{
		return array('AMA', 'APA', 'ChicagoHumanities', 'ChicagoAuthDate', 'MLA');
	}

	/**
	 * Get the text to represent this record in the body of an email.
	 *
	 * @access  public
	 * @return  string              Text for inclusion in email.
	 */
	public function getEmail() {
		// TODO: Implement getEmail() method.
	}

	/**
	 * Get any excerpts associated with this record.  For details of
	 * the return format, see sys/Excerpts.php.
	 *
	 * @access  public
	 * @return  array               Excerpt information.
	 */
	public function getExcerpts() {
		// TODO: Implement getExcerpts() method.
	}

	/**
	 * Assign necessary Smarty variables and return a template name to
	 * load in order to export the record in the requested format.  For
	 * legal values, see getExportFormats().  Returns null if format is
	 * not supported.
	 *
	 * @param   string $format Export format to display.
	 * @access  public
	 * @return  string              Name of Smarty template file to display.
	 */
	public function getExport($format) {
		// TODO: Implement getExport() method.
	}

	/**
	 * Get an array of strings representing formats in which this record's
	 * data may be exported (empty if none).  Legal values: "RefWorks",
	 * "EndNote", "MARC", "RDF".
	 *
	 * @access  public
	 * @return  array               Strings representing export formats.
	 */
	public function getExportFormats() {
		// TODO: Implement getExportFormats() method.
	}

	/**
	 * Assign necessary Smarty variables and return a template name to
	 * load in order to display extended metadata (more details beyond
	 * what is found in getCoreMetadata() -- used as the contents of the
	 * Description tab of the record view).
	 *
	 * @access  public
	 * @return  string              Name of Smarty template file to display.
	 */
	public function getExtendedMetadata() {
		// TODO: Implement getExtendedMetadata() method.
	}

	/**
	 * Assign necessary Smarty variables and return a template name to
	 * load in order to display holdings extracted from the base record
	 * (i.e. URLs in MARC 856 fields).  This is designed to supplement,
	 * not replace, holdings information extracted through the ILS driver
	 * and displayed in the Holdings tab of the record view page.  Returns
	 * null if no data is available.
	 *
	 * @access  public
	 * @return  string              Name of Smarty template file to display.
	 */
	public function getHoldings() {
		require_once (ROOT_DIR . '/Drivers/OverDriveDriverFactory.php');
		$driver = OverDriveDriverFactory::getDriver();

		/** @var OverDriveAPIProductFormats[] $holdings */
		return $driver->getHoldings($this);
	}

	/**
	 * Assign necessary Smarty variables and return a template name to
	 * load in order to display a summary of the item suitable for use in
	 * user's favorites list.
	 *
	 * @access  public
	 * @param   object $user User object owning tag/note metadata.
	 * @param   int $listId ID of list containing desired tags/notes (or
	 *                              null to show tags/notes from all user's lists).
	 * @param   bool $allowEdit Should we display edit controls?
	 * @return  string              Name of Smarty template file to display.
	 */
	public function getListEntry($user, $listId = null, $allowEdit = true) {
		// TODO: Implement getListEntry() method.
	}

	/**
	 * Get an XML RDF representation of the data in this record.
	 *
	 * @access  public
	 * @return  mixed               XML RDF data (false if unsupported or error).
	 */
	public function getRDFXML() {
		// TODO: Implement getRDFXML() method.
	}

	/**
	 * Get any reviews associated with this record.  For details of
	 * the return format, see sys/Reviews.php.
	 *
	 * @access  public
	 * @return  array               Review information.
	 */
	public function getReviews() {
		// TODO: Implement getReviews() method.
	}

	/**
	 * Assign necessary Smarty variables and return a template name to
	 * load in order to display a summary of the item suitable for use in
	 * search results.
	 *
	 * @access  public
	 * @return  string              Name of Smarty template file to display.
	 */
	public function getSearchResult() {
		// TODO: Implement getSearchResult() method.
	}

	public function getSeries(){
		$seriesData = $this->getGroupedWorkDriver()->getSeries();
		if ($seriesData == null){
			$seriesName = isset($this->getOverDriveMetaData()->getDecodedRawData()->series) ? $this->getOverDriveMetaData()->getDecodedRawData()->series : null;
			if ($seriesName != null){
				$seriesData = array(
					'seriesTitle' => $seriesName,
					'fromNovelist' => false,
				);
			}
		}
		return $seriesData;
	}

	/**
	 * Assign necessary Smarty variables and return a template name to
	 * load in order to display the full record information on the Staff
	 * View tab of the record view page.
	 *
	 * @access  public
	 * @return  string              Name of Smarty template file to display.
	 */
	public function getStaffView() {
		global $interface;

		$overDriveAPIProduct = new OverDriveAPIProduct();
		$overDriveAPIProduct->overdriveId = strtolower($this->id);
		$overDriveAPIProductMetaData = new OverDriveAPIProductMetaData();
		$overDriveAPIProduct->joinAdd($overDriveAPIProductMetaData, 'INNER');
		$overDriveAPIProduct->selectAdd("overdrive_api_products.rawData as productRaw");
		$overDriveAPIProduct->selectAdd("overdrive_api_product_metadata.rawData as metaDataRaw");
		if ($overDriveAPIProduct->find(true)){
			$interface->assign('overDriveProduct', $overDriveAPIProduct);
			$productRaw = json_decode($overDriveAPIProduct->productRaw);
			//Remove links to overdrive that could be used to get semi-sensitive data
			unset($productRaw->links);
			unset($productRaw->contentDetails->account);
			$interface->assign('overDriveProductRaw', $productRaw);
			$overDriveMetadata = $overDriveAPIProduct->metaDataRaw;
			//Replace http links to content reserve with https so we don't get mixed content warnings
			$overDriveMetadata = str_replace('http://images.contentreserve.com', 'https://images.contentreserve.com', $overDriveMetadata);
			$overDriveMetadata = json_decode($overDriveMetadata);
			$interface->assign('overDriveMetaDataRaw', $overDriveMetadata);
		}

		$lastGroupedWorkModificationTime = $this->groupedWork->date_updated;
		$interface->assign('lastGroupedWorkModificationTime', $lastGroupedWorkModificationTime);

		return 'RecordDrivers/OverDrive/staff.tpl';
	}

	/**
	 * Assign necessary Smarty variables and return a template name to
	 * load in order to display the Table of Contents extracted from the
	 * record.  Returns null if no Table of Contents is available.
	 *
	 * @access  public
	 * @return  string              Name of Smarty template file to display.
	 */
	public function getTOC() {
		return array();
	}

	/**
	 * Return the unique identifier of this record within the Solr index;
	 * useful for retrieving additional information (like tags and user
	 * comments) from the external MySQL database.
	 *
	 * @access  public
	 * @return  string              Unique identifier.
	 */
	public function getUniqueID() {
		return $this->id;
	}

	/**
	 * Does this record have audio content available?
	 *
	 * @access  public
	 * @return  bool
	 */
	public function hasAudio() {
		// TODO: Implement hasAudio() method.
	}

	/**
	 * Does this record have an excerpt available?
	 *
	 * @access  public
	 * @return  bool
	 */
	public function hasExcerpt() {
		// TODO: Implement hasExcerpt() method.
	}

	/**
	 * Does this record have searchable full text in the index?
	 *
	 * Note: As of this writing, searchable full text is not a VuFind feature,
	 *       but this method will be useful if/when it is eventually added.
	 *
	 * @access  public
	 * @return  bool
	 */
	public function hasFullText() {
		// TODO: Implement hasFullText() method.
	}

	/**
	 * Does this record have image content available?
	 *
	 * @access  public
	 * @return  bool
	 */
	public function hasImages() {
		// TODO: Implement hasImages() method.
	}

	/**
	 * Does this record support an RDF representation?
	 *
	 * @access  public
	 * @return  bool
	 */
	public function hasRDF() {
		// TODO: Implement hasRDF() method.
	}

	/**
	 * Does this record have reviews available?
	 *
	 * @access  public
	 * @return  bool
	 */
	public function hasReviews() {
		// TODO: Implement hasReviews() method.
	}

	/**
	 * Does this record have a Table of Contents available?
	 *
	 * @access  public
	 * @return  bool
	 */
	public function hasTOC() {
		// TODO: Implement hasTOC() method.
	}

	/**
	 * Does this record have video content available?
	 *
	 * @access  public
	 * @return  bool
	 */
	public function hasVideo() {
		// TODO: Implement hasVideo() method.
	}

	function getLanguage(){
		$metaData = $this->getOverDriveMetaData()->getDecodedRawData();
		$languages = array();
		if (isset($metaData->languages)){
			foreach ($metaData->languages as $language){
				$languages[] = $language->name;
			}
		}
		return $languages;
	}

	function getLanguages(){
		return $this->getLanguage();
	}

	private $availability = null;

	/**
	 * @return OverDriveAPIProductAvailability[]
	 */
	function getAvailability(){
		if ($this->availability == null){
			$this->availability = array();
			require_once ROOT_DIR . '/sys/OverDrive/OverDriveAPIProductAvailability.php';
			$availability = new OverDriveAPIProductAvailability();
			$availability->productId = $this->overDriveProduct->id;
			//Only include shared collection if include digital collection is on
			$searchLibrary = Library::getSearchLibrary();
			$searchLocation = Location::getSearchLocation();
			$includeSharedTitles = true;
			if($searchLocation != null){
				$includeSharedTitles = $searchLocation->enableOverdriveCollection != 0;
			}elseif ($searchLibrary != null){
				$includeSharedTitles = $searchLibrary->enableOverdriveCollection != 0;
			}
			$libraryScopingId = $this->getLibraryScopingId();
			if ($includeSharedTitles){
				$availability->whereAdd('libraryId = -1 OR libraryId = ' . $libraryScopingId);
			}else{
				if ($libraryScopingId == -1){
					return $this->availability;
				}else{
					$availability->whereAdd('libraryId = ' . $libraryScopingId);
				}
			}
			$availability->find();
			while ($availability->fetch()){
				$this->availability[] = clone $availability;
			}
		}
		return $this->availability;
	}

	public function getLibraryScopingId(){
		//For econtent, we need to be more specific when restricting copies
		//since patrons can't use copies that are only available to other libraries.
		$searchLibrary = Library::getSearchLibrary();
		$searchLocation = Location::getSearchLocation();
		$activeLibrary = Library::getActiveLibrary();
		$activeLocation = Location::getActiveLocation();
		$homeLibrary = Library::getPatronHomeLibrary();

		//Load the holding label for the branch where the user is physically.
		if (!is_null($homeLibrary)){
			return $homeLibrary->includeOutOfSystemExternalLinks ? -1 : $homeLibrary->libraryId;
		}else if (!is_null($activeLocation)){
			$activeLibrary = Library::getLibraryForLocation($activeLocation->locationId);
			return $activeLibrary->includeOutOfSystemExternalLinks ? -1 : $activeLibrary->libraryId;
		}else if (isset($activeLibrary)) {
			return $activeLibrary->includeOutOfSystemExternalLinks ? -1 : $activeLibrary->libraryId;
		}else if (!is_null($searchLocation)){
			$searchLibrary = Library::getLibraryForLocation($searchLibrary->locationId);
			return $searchLibrary->includeOutOfSystemExternalLinks ? -1 : $searchLocation->libraryId;
		}else if (isset($searchLibrary)) {
			return $searchLibrary->includeOutOfSystemExternalLinks ? -1 : $searchLibrary->libraryId;
		}else{
			return -1;
		}
	}

	public function getDescriptionFast(){
		$metaData =  $this->getOverDriveMetaData();
		return $metaData->fullDescription;
	}

	public function getDescription(){
		$metaData =  $this->getOverDriveMetaData();
		return $metaData->fullDescription;
	}

	/**
	 * Return the first valid ISBN found in the record (favoring ISBN-10 over
	 * ISBN-13 when possible).
	 *
	 * @return  mixed
	 */
	public function getCleanISBN()
	{
		require_once ROOT_DIR . '/sys/ISBN.php';

		// Get all the ISBNs and initialize the return value:
		$isbns = $this->getISBNs();
		$isbn13 = false;

		// Loop through the ISBNs:
		foreach($isbns as $isbn) {
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
	 * Get an array of all ISBNs associated with the record (may be empty).
	 *
	 * @access  protected
	 * @return  array
	 */
	public function getISBNs()
	{
		//Load ISBNs for the product
		if ($this->isbns == null){
			require_once ROOT_DIR . '/sys/OverDrive/OverDriveAPIProductIdentifiers.php';
			$overDriveIdentifiers = new OverDriveAPIProductIdentifiers();
			$overDriveIdentifiers->type = 'ISBN';
			$overDriveIdentifiers->productId = $this->overDriveProduct->id;
			$this->isbns = array();
			$overDriveIdentifiers->find();
			while ($overDriveIdentifiers->fetch()){
				$this->isbns[] = $overDriveIdentifiers->value;
			}
		}
		return $this->isbns;
	}

	/**
	 * Get an array of all UPCs associated with the record (may be empty).
	 *
	 * @access  protected
	 * @return  array
	 */
	public function getUPCs()
	{
		//Load UPCs for the product
		if ($this->upcs == null){
			require_once ROOT_DIR . '/sys/OverDrive/OverDriveAPIProductIdentifiers.php';
			$overDriveIdentifiers = new OverDriveAPIProductIdentifiers();
			$overDriveIdentifiers->type = 'UPC';
			$overDriveIdentifiers->productId = $this->overDriveProduct->id;
			$this->upcs = array();
			$overDriveIdentifiers->find();
			while ($overDriveIdentifiers->fetch()){
				$this->upcs[] = $overDriveIdentifiers->value;
			}
		}
		return $this->upcs;
	}

	public function getAcceleratedReaderData(){
		return $this->getGroupedWorkDriver()->getAcceleratedReaderData();
	}
	public function getAcceleratedReaderDisplayString() {
		return $this->getGroupedWorkDriver()->getAcceleratedReaderDisplayString();
	}
	public function getLexileCode(){
		return $this->getGroupedWorkDriver()->getLexileCode();
	}
	public function getLexileScore(){
		return $this->getGroupedWorkDriver()->getLexileScore();
	}
	public function getLexileDisplayString() {
		return $this->getGroupedWorkDriver()->getLexileDisplayString();
	}
	public function getFountasPinnellLevel(){
		return $this->getGroupedWorkDriver()->getFountasPinnellLevel();
	}

	public function getSubjects(){
		return $this->getOverDriveMetaData()->getDecodedRawData()->subjects;
	}

	/**
	 * Get an array of all ASINs associated with the record (may be empty).
	 *
	 * @access  protected
	 * @return  array
	 */
	public function getASINs()
	{
		//Load UPCs for the product
		if ($this->asins == null){
			require_once ROOT_DIR . '/sys/OverDrive/OverDriveAPIProductIdentifiers.php';
			$overDriveIdentifiers = new OverDriveAPIProductIdentifiers();
			$overDriveIdentifiers->type = 'ASIN';
			$overDriveIdentifiers->productId = $this->overDriveProduct->id;
			$this->asins = array();
			$overDriveIdentifiers->find();
			while ($overDriveIdentifiers->fetch()){
				$this->asins[] = $overDriveIdentifiers->value;
			}
		}
		return $this->asins;
	}

	/**
	 * Get the full title of the record.
	 *
	 * @return  string
	 */
	public function getTitle()
	{
		return $this->overDriveProduct->title;
	}

	/**
	 * Get the full title of the record.
	 *
	 * @return  string
	 */
	public function getSubTitle()
	{
		return $this->overDriveProduct->subtitle;
	}

	/**
	 * Get an array of all the formats associated with the record.
	 *
	 * @access  protected
	 * @return  array
	 */
	public function getFormats()
	{
		$formats = array();
		foreach ($this->getItems() as $item){
			$formats[] = $item->name;
		}
		return $formats;
	}

	public function getItems(){
		if ($this->items == null){
			require_once ROOT_DIR . '/sys/OverDrive/OverDriveAPIProductFormats.php';
			$overDriveFormats = new OverDriveAPIProductFormats();
			$this->items = array();
			if ($this->valid){
				$overDriveFormats->productId = $this->overDriveProduct->id;
				$overDriveFormats->find();
				while ($overDriveFormats->fetch()){
					$this->items[] = clone $overDriveFormats;
				}
			}

			global $timer;
			$timer->logTime("Finished getItems for OverDrive record {$this->overDriveProduct->id}");
		}
		return $this->items;
	}

	public function getAuthor(){
		return $this->overDriveProduct->primaryCreatorName;
	}

	public function getPrimaryAuthor(){
		return $this->overDriveProduct->primaryCreatorName;
	}

	public function getContributors(){
		return array();
	}

	public function getBookcoverUrl($size = 'small', $absolutePath = false){
		global $configArray;
		if ($absolutePath){
			$bookCoverUrl = $configArray['Site']['url'];
		}else{
			$bookCoverUrl = $configArray['Site']['path'];
		}
		$bookCoverUrl .= '/bookcover.php?size=' . $size;
		$bookCoverUrl .= '&id=' . $this->id;
		$bookCoverUrl .= '&type=overdrive';
		return $bookCoverUrl;
	}

	public function getCoverUrl($size = 'small'){
		return $this->getBookcoverUrl($size);
	}

	private function getOverDriveMetaData() {
		if ($this->overDriveMetaData == null){
			require_once ROOT_DIR . '/sys/OverDrive/OverDriveAPIProductMetaData.php';
			$this->overDriveMetaData = new OverDriveAPIProductMetaData();
			$this->overDriveMetaData->productId = $this->overDriveProduct->id;
			$this->overDriveMetaData->find(true);
		}
		return $this->overDriveMetaData;
	}

	public function getRatingData() {
		require_once ROOT_DIR . '/services/API/WorkAPI.php';
		$workAPI = new WorkAPI();
		$groupedWorkId = $this->getGroupedWorkId();
		if ($groupedWorkId == null){
			return null;
		}else{
			return $workAPI->getRatingData($this->getGroupedWorkId());
		}
	}

	public function getMoreDetailsOptions(){
		global $interface;

		$isbn = $this->getCleanISBN();

		//Load holdings information from the driver
		require_once (ROOT_DIR . '/Drivers/OverDriveDriverFactory.php');
		$driver = OverDriveDriverFactory::getDriver();

		/** @var OverDriveAPIProductFormats[] $holdings */
		$holdings = $driver->getHoldings($this);
		$scopedAvailability = $driver->getScopedAvailability($this);
		$interface->assign('availability', $scopedAvailability['mine']);
		$interface->assign('availabilityOther', $scopedAvailability['other']);
		$numberOfHolds = 0;
		foreach ($scopedAvailability['mine'] as $availability){
			if ($availability->numberOfHolds > 0){
				$numberOfHolds = $availability->numberOfHolds;
				break;
			}
		}
		$interface->assign('numberOfHolds', $numberOfHolds);
		$showAvailability = true;
		$showAvailabilityOther = true;
		$interface->assign('showAvailability', $showAvailability);
		$interface->assign('showAvailabilityOther', $showAvailabilityOther);
		$showOverDriveConsole = false;
		$showAdobeDigitalEditions = false;
		foreach ($holdings as $item){
			if (in_array($item->textId, array('ebook-epub-adobe', 'ebook-pdf-adobe'))){
				$showAdobeDigitalEditions = true;
			}else if (in_array($item->textId, array('video-wmv', 'music-wma', 'music-wma', 'audiobook-wma', 'audiobook-mp3'))){
				$showOverDriveConsole = true;
			}
		}
		$interface->assign('showOverDriveConsole', $showOverDriveConsole);
		$interface->assign('showAdobeDigitalEditions', $showAdobeDigitalEditions);

		$interface->assign('holdings', $holdings);

		//Load more details options
		$moreDetailsOptions = $this->getBaseMoreDetailsOptions($isbn);
		$moreDetailsOptions['formats'] = array(
			'label' => 'Formats',
			'body' => $interface->fetch('OverDrive/view-formats.tpl'),
			'openByDefault' => true
		);
		//Other editions if applicable (only if we aren't the only record!)
		$relatedRecords = $this->getGroupedWorkDriver()->getRelatedRecords();
		if (count($relatedRecords) > 1){
			$interface->assign('relatedManifestations', $this->getGroupedWorkDriver()->getRelatedManifestations());
			$moreDetailsOptions['otherEditions'] = array(
					'label' => 'Other Editions and Formats',
					'body' => $interface->fetch('GroupedWork/relatedManifestations.tpl'),
					'hideByDefault' => false
			);
		}

		$moreDetailsOptions['moreDetails'] = array(
			'label' => 'More Details',
			'body' => $interface->fetch('OverDrive/view-more-details.tpl'),
		);
		$moreDetailsOptions['citations'] = array(
			'label' => 'Citations',
			'body' => $interface->fetch('Record/cite.tpl'),
		);
		$moreDetailsOptions['copyDetails'] = array(
			'label' => 'Copy Details',
			'body' => $interface->fetch('OverDrive/view-copies.tpl'),
		);
		if ($interface->getVariable('showStaffView')){
			$moreDetailsOptions['staff'] = array(
				'label' => 'Staff View',
				'body' => $interface->fetch($this->getStaffView()),
			);
		}

		return $this->filterAndSortMoreDetailsOptions($moreDetailsOptions);
	}

	public function getRecordUrl() {
		$id = $this->getUniqueID();
		$linkUrl = '/OverDrive/' . $id . '/Home';
		return $linkUrl;
	}
	public function getLinkUrl($useUnscopedHoldingsSummary = false) {
		global $interface;
		$id = $this->getUniqueID();
		$linkUrl = '/OverDrive/' . $id . '/Home?searchId=' . $interface->get_template_vars('searchId') . '&amp;recordIndex=' . $interface->get_template_vars('recordIndex') . '&amp;page='  . $interface->get_template_vars('page');
		if ($useUnscopedHoldingsSummary){
			$linkUrl .= '&amp;searchSource=marmot';
		}else{
			$linkUrl .= '&amp;searchSource=' . $interface->get_template_vars('searchSource');
		}
		return $linkUrl;
	}

	function getQRCodeUrl(){
		global $configArray;
		return $configArray['Site']['url'] . '/qrcode.php?type=OverDrive&id=' . $this->getPermanentId();
	}

	private function getPublishers() {
		$publishers = array();
		if (isset($this->overDriveMetaData->publisher)){
			$publishers[] = $this->overDriveMetaData->publisher;
		}
		return $publishers;
	}

	private function getPublicationDates() {
		$publicationDates = array();
		if (isset($this->getOverDriveMetaData()->getDecodedRawData()->publishDateText)){
			$publishDate = $this->getOverDriveMetaData()->getDecodedRawData()->publishDateText;
			$publishYear = substr($publishDate, -4);
			$publicationDates[] = $publishYear;
		}
		return $publicationDates;
	}

	private function getPlacesOfPublication() {
		return array();
	}

	/**
	 * Get an array of publication detail lines combining information from
	 * getPublicationDates(), getPublishers() and getPlacesOfPublication().
	 *
	 * @access  public
	 * @return  array
	 */
	function getPublicationDetails()
	{
		$places = $this->getPlacesOfPublication();
		$names = $this->getPublishers();
		$dates = $this->getPublicationDates();

		$i = 0;
		$returnVal = array();
		while (isset($places[$i]) || isset($names[$i]) || isset($dates[$i])) {
			// Put all the pieces together, and do a little processing to clean up
			// unwanted whitespace.
			$publicationInfo = (isset($places[$i]) ? $places[$i] . ' ' : '') .
					(isset($names[$i]) ? $names[$i] . ' ' : '') .
					(isset($dates[$i]) ? $dates[$i] : '');
			$returnVal[] = trim(str_replace('  ', ' ', $publicationInfo));
			$i++;
		}

		return $returnVal;
	}

	public function getEdition($returnFirst = false) {
		$edition = isset($this->overDriveMetaData->getDecodedRawData()->edition) ? $this->overDriveMetaData->getDecodedRawData()->edition : null;
		if ($returnFirst || is_null($edition)){
			return $edition;
		}else{
			return array($edition);
		}
	}

	public function getStreetDate(){
		return isset($this->overDriveMetaData->getDecodedRawData()->publishDateText) ? $this->overDriveMetaData->getDecodedRawData()->publishDateText : null;
	}

	public function getGroupedWorkDriver() {
		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		if ($this->groupedWorkDriver == null){
			$this->groupedWorkDriver = new GroupedWorkDriver($this->getPermanentId());
		}
		return $this->groupedWorkDriver;
	}
	public function getTags(){
		return $this->getGroupedWorkDriver()->getTags();
	}
	/**
	 * Get the OpenURL parameters to represent this record (useful for the
	 * title attribute of a COinS span tag).
	 *
	 * @access  public
	 * @return  string              OpenURL parameters.
	 */
	public function getOpenURL()
	{
		// Get the COinS ID -- it should be in the OpenURL section of config.ini,
		// but we'll also check the COinS section for compatibility with legacy
		// configurations (this moved between the RC2 and 1.0 releases).
		$coinsID = 'pika';

		// Start an array of OpenURL parameters:
		$params = array(
			'ctx_ver' => 'Z39.88-2004',
			'ctx_enc' => 'info:ofi/enc:UTF-8',
			'rfr_id' => "info:sid/{$coinsID}:generator",
			'rft.title' => $this->getTitle(),
		);

		// Get a representative publication date:
		$pubDate = $this->getPublicationDates();
		if (count($pubDate) == 1){
			$params['rft.date'] = $pubDate[0];
		}elseif (count($pubDate > 1)){
			$params['rft.date'] = $pubDate;
		}

		// Add additional parameters based on the format of the record:
		$formats = $this->getFormats();

		// If we have multiple formats, Book and Journal are most important...
		if (in_array('Book', $formats)) {
			$format = 'Book';
		} else if (in_array('Journal', $formats)) {
			$format = 'Journal';
		} else {
			$format = $formats[0];
		}
		switch($format) {
			case 'Book':
				$params['rft_val_fmt'] = 'info:ofi/fmt:kev:mtx:book';
				$params['rft.genre'] = 'book';
				$params['rft.btitle'] = $params['rft.title'];

				$series = $this->getSeries(false);
				if ($series != null) {
					// Handle both possible return formats of getSeries:
					$params['rft.series'] = $series['seriesTitle'];
				}

				$params['rft.au'] = $this->getPrimaryAuthor();
				$publishers = $this->getPublishers();
				if (count($publishers) == 1) {
					$params['rft.pub'] = $publishers[0];
				}elseif (count($publishers) > 1) {
					$params['rft.pub'] = $publishers;
				}
				$params['rft.edition'] = $this->getEdition();
				$params['rft.isbn'] = $this->getCleanISBN();
				break;
			case 'Journal':
				/* This is probably the most technically correct way to represent
				 * a journal run as an OpenURL; however, it doesn't work well with
				 * Zotero, so it is currently commented out -- instead, we just add
				 * some extra fields and then drop through to the default case.
				 $params['rft_val_fmt'] = 'info:ofi/fmt:kev:mtx:journal';
				 $params['rft.genre'] = 'journal';
				 $params['rft.jtitle'] = $params['rft.title'];
				 $params['rft.issn'] = $this->getCleanISSN();
				 $params['rft.au'] = $this->getPrimaryAuthor();
				 break;
				 */
				$issns = $this->getISSNs();
				if (count($issns) > 0){
					$params['rft.issn'] = $issns[0];
				}

				// Including a date in a title-level Journal OpenURL may be too
				// limiting -- in some link resolvers, it may cause the exclusion
				// of databases if they do not cover the exact date provided!
				unset($params['rft.date']);
			default:
				$params['rft_val_fmt'] = 'info:ofi/fmt:kev:mtx:dc';
				$params['rft.creator'] = $this->getPrimaryAuthor();
				$publishers = $this->getPublishers();
				if (count($publishers) > 0) {
					$params['rft.pub'] = $publishers[0];
				}
				$params['rft.format'] = $format;
				$langs = $this->getLanguages();
				if (count($langs) > 0) {
					$params['rft.language'] = $langs[0];
				}
				break;
		}

		// Assemble the URL:
		$parts = array();
		foreach($params as $key => $value) {
			if (is_array($value)){
				foreach($value as $arrVal){
					$parts[] = $key . '[]=' . urlencode($arrVal);
				}
			}else{
				$parts[] = $key . '=' . urlencode($value);
			}
		}
		return implode('&', $parts);
	}

	public function getItemActions($itemInfo){
		return array();
	}

	public function getRecordActions($isAvailable, $isHoldable, $isBookable, $relatedUrls = null, $volumeData = null) {
		$actions = array();
		if ($isAvailable){
			$actions[] = array(
				'title' => 'Check Out OverDrive',
				'onclick' => "return VuFind.OverDrive.checkOutOverDriveTitle('{$this->id}');",
				'requireLogin' => false,
			);
		}else{
			$actions[] = array(
				'title' => 'Place Hold OverDrive',
				'onclick' => "return VuFind.OverDrive.placeOverDriveHold('{$this->id}');",
				'requireLogin' => false,
			);
		}
		return $actions;
	}

	function getNumHolds(){
		$totalHolds = 0;
		/** @var OverDriveAPIProductAvailability $availabilityInfo */
		foreach ($this->getAvailability() as $availabilityInfo){
			//Holds is set once for everyone so don't add them up.
			if ($availabilityInfo->numberOfHolds > $totalHolds){
				$totalHolds = $availabilityInfo->numberOfHolds;
			}
		}
		return $totalHolds;
	}

	function getVolumeHolds($volumeData){
		return 0;
	}


	public function getSemanticData() {
		// Schema.org
		// Get information about the record
		require_once ROOT_DIR . '/RecordDrivers/LDRecordOffer.php';
		$linkedDataRecord = new LDRecordOffer($this->getRelatedRecord());
		$semanticData [] = array(
				'@context' => 'http://schema.org',
				'@type' => $linkedDataRecord->getWorkType(),
				'name' => $this->getTitle(),                             //getTitleSection(),
				'creator' => $this->getAuthor(),
				'bookEdition' => $this->getEdition(),
				'isAccessibleForFree' => true,
				'image' => $this->getBookcoverUrl('medium', true),
				"offers" => $linkedDataRecord->getOffers()
		);

		global $interface;
		$interface->assign('og_title', $this->getTitle());
		$interface->assign('og_type', $this->getGroupedWorkDriver()->getOGType());
		$interface->assign('og_image', $this->getBookcoverUrl('medium', true));
		$interface->assign('og_url', $this->getAbsoluteUrl());
		return $semanticData;
	}

	function getRelatedRecord() {
		$id = 'overdrive:' . $this->id;
		return $this->getGroupedWorkDriver()->getRelatedRecord($id);
	}

}