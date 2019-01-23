<?php
/**
 *
 * Copyright (C) Villanova University 2010.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */
require_once ROOT_DIR . '/RecordDrivers/Interface.php';

/**
 * Index Record Driver
 *
 * This class is designed to handle records in a generic fashion, using
 * fields from the index.  It is invoked when a record-format-specific
 * driver cannot be found.
 */
class IndexRecord extends RecordInterface
{
	protected $fields;
	protected $index = false;
	protected $scopingEnabled = false;

	/**
	 * These Solr fields should NEVER be used for snippets.  (We exclude author
	 * and title because they are already covered by displayed fields; we exclude
	 * spelling because it contains lots of fields jammed together and may cause
	 * glitchy output; we exclude ID because random numbers are not helpful).
	 *
	 * @var    array
	 * @access protected
	 */
	protected $forbiddenSnippetFields = array(
		'author', 'author-letter', 'auth_author2', 'title', 'title_short', 'title_full',
		'title_auth', 'title_sub', 'title_display', 'spelling', 'id',
		'fulltext_unstemmed', 'econtentText_unstemmed',
		'spellingShingle', 'collection', 'title_proper',
		'display_description'
	);

	/**
	 * These are captions corresponding with Solr fields for use when displaying
	 * snippets.
	 *
	 * @var    array
	 * @access protected
	 */
	protected $snippetCaptions = array(
		'display_description' => 'Description'
	);

	/**
	 * Should we highlight fields in search results?
	 *
	 * @var    bool
	 * @access protected
	 */
	protected $highlight = false;

	/**
	 * Should we include snippets in search results?
	 *
	 * @var    bool
	 * @access protected
	 */
	protected $snippet = false;

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
	 * @param   array|File_MARC_Record||string   $recordData     Data to construct the driver from
	 * @param  GroupedWork $groupedWork;
	 * @access  public
	 */
	public function __construct($recordData, $groupedWork = null){
		$this->fields = $recordData;

		global $configArray;
		// Load highlighting/snippet preferences:
		$searchSettings = getExtraConfigArray('searches');
		$this->highlight = $configArray['Index']['enableHighlighting'];
		$this->snippet = $configArray['Index']['enableSnippets'];
		$this->snippetCaptions = isset($searchSettings['Snippet_Captions']) && is_array($searchSettings['Snippet_Captions']) ? $searchSettings['Snippet_Captions'] : array();

		if ($groupedWork == null){
			$this->loadGroupedWork();
		}else{
			$this->groupedWork = $groupedWork;
		}
	}

	/**
	 * Get text that can be displayed to represent this record in
	 * breadcrumbs.
	 *
	 * @access  public
	 * @return  string              Breadcrumb text to represent this record.
	 */
	public function getBreadcrumb()
	{
		return $this->getShortTitle();
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
		$primary = $this->getPrimaryAuthor();
		if (!empty($primary)) {
			$authors[] = $primary;
		}
		$authors = array_unique(array_merge($authors, $this->getSecondaryAuthors()));

		// Collect all details for citation builder:
		$publishers = $this->getPublishers();
		$pubDates = $this->getPublicationDates();
		$pubPlaces = $this->getPlacesOfPublication();
		$details = array(
            'authors' => $authors,
            'title' => $this->getShortTitle(),
            'subtitle' => $this->getSubtitle(),
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
	 * Get an array of search results for other editions of the title
	 * represented by this record (empty if unavailable).  In most cases,
	 * this will use the XISSN/XISBN logic to find matches.
	 *
	 * @access  public
	 * @return  mixed               Editions in index engine result format.
	 *                              (or null if no hits, or PEAR_Error object).
	 */
	public function getEditions()
	{
		require_once ROOT_DIR . '/sys/WorldCatUtils.php';
		$wc = new WorldCatUtils();

		// Try to build an array of ISBN or ISSN-based sub-queries:
		$parts = array();
		$isbn = $this->getCleanISBN();
		if (!empty($isbn)) {
			$isbnList = $wc->getXISBN($isbn);
			foreach($isbnList as $current) {
				$parts[] = 'isbn:' . $current;
			}
		} else {
			$issn = $this->getCleanISSN();
			if (!empty($issn)) {
				$issnList = $wc->getXISSN($issn);
				foreach($issnList as $current) {
					$parts[] = 'issn:' . $current;
				}
			}
		}

		// If we have query parts, we should try to find related records:
		if (!empty($parts)) {
			// Assemble the query parts and filter out current record:
			$query = '(' . implode(' OR ', $parts) . ') NOT id:' .
			$this->getUniqueID();

			// Perform the search and return either results or an error:
			$index = $this->getIndexEngine();
			$result = $index->search($query, null, null, 0, 5);
			if (PEAR_Singleton::isError($result)) {
				return $result;
			}
			if (isset($result['response']['docs']) &&
			!empty($result['response']['docs'])) {
				return $result['response']['docs'];
			}
		}

		// If we got this far, we were unable to find any results:
		return null;
	}

	/**
	 * Get the text to represent this record in the body of an email.
	 *
	 * @access  public
	 * @return  string              Text for inclusion in email.
	 */
	public function getEmail()
	{
		return "  " . $this->getTitle() . "\n";
	}

	/**
	 * Get any excerpts associated with this record.  For details of
	 * the return format, see sys/Excerpts.php.
	 *
	 * @access  public
	 * @return  array               Excerpt information.
	 */
	public function getExcerpts()
	{
		require_once ROOT_DIR . '/sys/Excerpts.php';

		$ed = new ExternalExcerpts($this->getCleanISBN());
		return $ed->fetch();
	}

	/**
	 * Assign necessary Smarty variables and return a template name to
	 * load in order to export the record in the requested format.  For
	 * legal values, see getExportFormats().  Returns null if format is
	 * not supported.
	 *
	 * @param   string  $format     Export format to display.
	 * @access  public
	 * @return  string              Name of Smarty template file to display.
	 */
	public function getExport($format)
	{
		// Not currently supported for index-based records:
		return null;
	}

	/**
	 * Get an array of strings representing formats in which this record's
	 * data may be exported (empty if none).  Legal values: "RefWorks",
	 * "EndNote", "MARC", "RDF".
	 *
	 * @access  public
	 * @return  array               Strings representing export formats.
	 */
	public function getExportFormats()
	{
		// No export formats currently supported for index-based records:
		return array();
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
	public function getExtendedMetadata()
	{
		global $interface;

		// Assign various values for display by the template; we'll prefix
		// everything with "extended" to avoid clashes with values assigned
		// elsewhere.
		$interface->assign('extendedSummary', $this->getSummary());
		$interface->assign('extendedAccess', $this->getAccessRestrictions());
		$interface->assign('extendedRelated', $this->getRelationshipNotes());
		$interface->assign('extendedNotes', $this->getGeneralNotes());
		$interface->assign('extendedDateSpan', $this->getDateSpan());
		$interface->assign('extendedISBNs', $this->getISBNs());
		$interface->assign('extendedISSNs', $this->getISSNs());
		$interface->assign('extendedPhysical', $this->getPhysicalDescriptions());
		$interface->assign('extendedFrequency', $this->getPublicationFrequency());
		$interface->assign('extendedPlayTime', $this->getPlayingTimes());
		$interface->assign('extendedSystem', $this->getSystemDetails());
		$interface->assign('extendedAudience', $this->getTargetAudienceNotes());
		$interface->assign('extendedAwards', $this->getAwards());
		$interface->assign('extendedCredits', $this->getProductionCredits());
		$interface->assign('extendedBibliography', $this->getBibliographyNotes());
		$interface->assign('extendedFindingAids', $this->getFindingAids());

		return 'RecordDrivers/Index/extended.tpl';
	}

	/**
	 * Assign necessary Smarty variables and return a template name to
	 * load in order to display a summary of the item suitable for use in
	 * user's favorites list.
	 *
	 * @access  public
	 * @param   object  $user       User object owning tag/note metadata.
	 * @param   int     $listId     ID of list containing desired tags/notes (or
	 *                              null to show tags/notes from all user's lists).
	 * @param   bool    $allowEdit  Should we display edit controls?
	 * @return  string              Name of Smarty template file to display.
	 */
	public function getListEntry($user, $listId = null, $allowEdit = true)
	{
		global $interface;

		// Extract bibliographic metadata from the record:
		$id = $this->getUniqueID();
		$interface->assign('listId', $id);
		$shortId = trim($id, '.');
		$interface->assign('listShortId', $shortId);
		$interface->assign('listFormats', $this->getFormats());
		$interface->assign('listTitle', $this->getTitle());
		$interface->assign('listAuthor', $this->getPrimaryAuthor());
		$interface->assign('listISBN', $this->getCleanISBN());
		$interface->assign('listISSN', $this->getCleanISSN());
		$interface->assign('listUPC', $this->getUPC());
		$interface->assign('listFormatCategory', $this->getFormatCategory());
		$interface->assign('listFormats', $this->getFormats());
		$interface->assign('listDate', $this->getPublicationDates());

		// Extract user metadata from the database:
		if ($user != false){
			$data = $user->getSavedData($id, $listId);
			$notes = array();
			foreach($data as $current) {
				if (!empty($current->notes)) {
					$notes[] = $current->notes;
				}
			}
			$interface->assign('listNotes', $notes);
		}

		// Pass some parameters along to the template to influence edit controls:
		$interface->assign('listSelected', $listId);
		$interface->assign('listEditAllowed', $allowEdit);

		//Get Rating
		$interface->assign('ratingData', $this->getRatingData());

		return 'RecordDrivers/Index/listentry.tpl';
	}

	/**
	 * Get an XML RDF representation of the data in this record.
	 *
	 * @access  public
	 * @return  mixed               XML RDF data (false if unsupported or error).
	 */
	public function getRDFXML()
	{
		// Not supported.
		return false;
	}

	/**
	 * Get any reviews associated with this record.  For details of
	 * the return format, see sys/Reviews.php.
	 *
	 * @access  public
	 * @return  array               Review information.
	 */
	public function getReviews()
	{
		require_once ROOT_DIR . '/sys/Reviews.php';

		$rev = new ExternalReviews($this->getCleanISBN());
		return $rev->fetch();
	}

	/**
	 * Assign necessary Smarty variables and return a template name to
	 * load in order to display a summary of the item suitable for use in
	 * search results.
	 *
	 * @access  public

	 * @param string $view The current view.
	 * @param boolean $useUnscopedHoldingsSummary Whether or not the result should show an unscoped holdings summary.
	 *
	 * @return  string              Name of Smarty template file to display.
	 */
	public function getSearchResult($view = 'list', $useUnscopedHoldingsSummary = false) {
		global $configArray;
		global $interface;

		$interface->assign('useUnscopedHoldingsSummary', $useUnscopedHoldingsSummary);

		$id = $this->getUniqueID();
		$interface->assign('summId', $id);
		if (substr($id, 0, 1) == '.'){
			$interface->assign('summShortId', substr($id, 1));
		}else{
			$interface->assign('summShortId', $id);
		}
		$interface->assign('module', $this->getModule());

		$interface->assign('summUrl', $this->getLinkUrl($useUnscopedHoldingsSummary));
		$formats = $this->getFormats();
		$interface->assign('summFormats', $formats);
		$formatCategories = $this->getFormatCategory();
		$interface->assign('summFormatCategory', $formatCategories);
		$interface->assign('summTitle', $this->getTitle());
		$interface->assign('summSubTitle', $this->getSubtitle());
		$interface->assign('summTitleStatement', $this->getTitleSection());
		$interface->assign('summAuthor', $this->getPrimaryAuthor());
		$publishers = $this->getPublishers();
		$pubDates = $this->getPublicationDates();
		$pubPlaces = $this->getPlacesOfPublication();
		$interface->assign('summPublicationDates', $pubDates);
		$interface->assign('summPublishers', $publishers);
		$interface->assign('summPublicationPlaces',$pubPlaces);
		$interface->assign('summDate', $this->getPublicationDates());
		$interface->assign('summISBN', $this->getCleanISBN());
		$issn = $this->getCleanISSN();
		$interface->assign('summISSN', $issn);
		$upc = $this->getCleanUPC();
		$interface->assign('summUPC', $upc);
		if ($configArray['System']['debugSolr'] == 1){
			$interface->assign('summScore', $this->getScore());
			$interface->assign('summExplain', $this->getExplain());
		}
		$interface->assign('summPhysical', $this->getPhysicalDescriptions());
		$interface->assign('summEditions', $this->getEdition());

		// Obtain and assign snippet information:
		$snippet = $this->getHighlightedSnippet();
		$interface->assign('summSnippetCaption', $snippet ? $snippet['caption'] : false);
		$interface->assign('summSnippet', $snippet ? $snippet['snippet'] : false);

		$interface->assign('summURLs', $this->getURLs());

		//Get Rating
		$interface->assign('summRating', $this->getRatingData());

		//Description
		$interface->assign('summDescription', $this->getDescription());

		//Determine the cover to use
		$interface->assign('bookCoverUrl', $this->getBookcoverUrl('small'));
		$interface->assign('bookCoverUrlMedium', $this->getBookcoverUrl('medium'));

		// By default, do not display AJAX status; we won't assume that all
		// records exist in the ILS.  Child classes can override this setting
		// to turn on AJAX as needed:
		$interface->assign('summAjaxStatus', false);

		return 'RecordDrivers/Index/result.tpl';
	}

	function getDescription(){
		/** @var Memcache $memCache */
		global $memCache;
		global $configArray;
		global $interface;
		global $timer;
		$id = $this->getUniqueID();
		//Bypass loading solr, etc if we already have loaded the descriptive info before
		$descriptionArray = $memCache->get("record_description_{$id}");
		if (!$descriptionArray){
			require_once ROOT_DIR . '/services/Record/Description.php';
			$searchObject = SearchObjectFactory::initSearchObject();
			$searchObject->init();

			$description = new Record_Description(true, $id);
			$descriptionArray = $description->loadData();
			$memCache->set("record_description_{$id}", $descriptionArray, 0, $configArray['Caching']['record_description']);
			$timer->logTime("Retrieved description for index record");
		}
		$interface->assign('description', $descriptionArray['description']);
		$interface->assign('length', isset($descriptionArray['length']) ? $descriptionArray['length'] : '');
		$interface->assign('publisher', isset($descriptionArray['publisher']) ? $descriptionArray['publisher'] : '');

		return $interface->fetch('Record/ajax-description-popup.tpl');
	}

	public function getSupplementalSearchResult(){
		global $configArray;
		global $interface;

		$id = $this->getUniqueID();
		$interface->assign('summId', $id);
		if (substr($id, 0, 1) == '.'){
			$interface->assign('summShortId', substr($id, 1));
		}else{
			$interface->assign('summShortId', $id);
		}
		$interface->assign('module', $this->getModule());

		$formats = $this->getFormats();
		$interface->assign('summFormats', $formats);
		$formatCategories = $this->getFormatCategory();
		$interface->assign('summFormatCategory', $formatCategories);
		$interface->assign('summTitle', $this->getTitle());
		$interface->assign('summSubTitle', $this->getSubtitle());
		$interface->assign('summTitleStatement', $this->getTitleSection());
		$interface->assign('summAuthor', $this->getPrimaryAuthor());
		$publishers = $this->getPublishers();
		$pubDates = $this->getPublicationDates();
		$pubPlaces = $this->getPlacesOfPublication();
		$interface->assign('summPublicationDates', $pubDates);
		$interface->assign('summPublishers', $publishers);
		$interface->assign('summPublicationPlaces',$pubPlaces);
		$interface->assign('summDate', $this->getPublicationDates());
		$interface->assign('summISBN', $this->getCleanISBN());
		$issn = $this->getCleanISSN();
		$interface->assign('summISSN', $issn);
		$upc = $this->getCleanUPC();
		$interface->assign('summUPC', $upc);
		if ($configArray['System']['debugSolr'] == 1){
			$interface->assign('summScore', $this->getScore());
			$interface->assign('summExplain', $this->getExplain());
		}
		$interface->assign('summPhysical', $this->getPhysicalDescriptions());
		$interface->assign('summEditions', $this->getEdition());

		// Obtain and assign snippet information:
		$snippet = $this->getHighlightedSnippet();
		$interface->assign('summSnippetCaption', $snippet ? $snippet['caption'] : false);
		$interface->assign('summSnippet', $snippet ? $snippet['snippet'] : false);

		//Get Rating
		$interface->assign('summRating', $this->getRatingData());

		//Determine the cover to use
		$interface->assign('bookCoverUrl', $this->getBookcoverUrl('small'));
		$interface->assign('bookCoverUrlMedium', $this->getBookcoverUrl('medium'));

		$interface->assign('summUrl', $this->getRecordUrl());

		// By default, do not display AJAX status; we won't assume that all
		// records exist in the ILS.  Child classes can override this setting
		// to turn on AJAX as needed:
		$interface->assign('summAjaxStatus', false);

		return 'RecordDrivers/Index/supplementalResult.tpl';
	}

	function getBookcoverUrl($size = 'small'){
		$id = $this->getIdWithSource();
		$formatCategory = $this->getFormatCategory();
		if (is_array($formatCategory)){
			$formatCategory = reset($formatCategory);
		}
		$formats = $this->getFormat();
		$format = reset($formats);
		global $configArray;
		$bookCoverUrl = $configArray['Site']['coverUrl'] . "/bookcover.php?id={$id}&amp;size={$size}&amp;category=" . urlencode($formatCategory) . "&amp;format=" . urlencode($format);
		$isbn = $this->getCleanISBN();
		if ($isbn){
			$bookCoverUrl .= "&amp;isn={$isbn}";
		}
		$upc = $this->getCleanUPC();
		if ($upc){
			$bookCoverUrl .= "&amp;upc={$upc}";
		}
		$issn = $this->getCleanISSN();
		if ($issn){
			$bookCoverUrl .= "&amp;issn={$issn}";
		}
		return $bookCoverUrl;
	}

	/**
	 * Assign necessary Smarty variables and return a template name to
	 * load in order to display the full record information on the Staff
	 * View tab of the record view page.
	 *
	 * @access  public
	 * @return  string              Name of Smarty template file to display.
	 */
	public function getStaffView()
	{
		global $interface;
		$interface->assign('details', $this->fields);

		$lastGroupedWorkModificationTime = $this->groupedWork->date_updated;
		$interface->assign('lastGroupedWorkModificationTime', $lastGroupedWorkModificationTime);

		return 'RecordDrivers/Index/staff.tpl';
	}

	/**
	 * Assign necessary Smarty variables and return a template name to
	 * load in order to display the Table of Contents extracted from the
	 * record.  Returns null if no Table of Contents is available.
	 *
	 * @access  public
	 * @return  string              Name of Smarty template file to display.
	 */
	public function getTOC()
	{
		global $interface;

		if (!$this->hasTOC()) {
			return null;
		}

		$interface->assign('toc', $this->fields['contents']);
		return 'RecordDrivers/Index/toc.tpl';
	}

	/**
	 * Return the unique identifier of this record within the Solr index;
	 * useful for retrieving additional information (like tags and user
	 * comments) from the external MySQL database.
	 *
	 * @access  public
	 * @return  string              Unique identifier.
	 */
	public function getUniqueID()
	{
		return $this->fields['id'];
	}

	/**
	 * Does this record have audio content available?
	 *
	 * @access  public
	 * @return  bool
	 */
	public function hasAudio()
	{
		/* Audio is not supported yet.
		 */
		return false;
	}

	/**
	 * Does this record have an excerpt available?
	 *
	 * @access  public
	 * @return  bool
	 */
	public function hasExcerpt()
	{
		// If we have ISBN(s), we might have reviews:
		$isbns = $this->getISBNs();
		return !empty($isbns);
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
	public function hasFullText()
	{
		/* Full text is not supported yet.
		 */
		return false;
	}

	/**
	 * Does this record have image content available?
	 *
	 * @access  public
	 * @return  bool
	 */
	public function hasImages()
	{
		// Images are not supported yet.
		return false;
	}

	/**
	 * Does this record support an RDF representation?
	 *
	 * @access  public
	 * @return  bool
	 */
	public function hasRDF()
	{
		// No RDF for Solr-based entries yet.
		return false;
	}

	/**
	 * Does this record have reviews available?
	 *
	 * @access  public
	 * @return  bool
	 */
	public function hasReviews()
	{
		// If we have ISBN(s), we might have reviews:
		$isbns = $this->getISBNs();
		return !empty($isbns);
	}

	/**
	 * Does this record have a Table of Contents available?
	 *
	 * @access  public
	 * @return  bool
	 */
	public function hasTOC()
	{
		// Do we have a table of contents stored in the index?
		return (isset($this->fields['contents']) &&
		count($this->fields['contents']) > 0);
	}

	/**
	 * Does this record have video content available?
	 *
	 * @access  public
	 * @return  bool
	 */
	public function hasVideo()
	{
		/* Video is not supported yet.
		 */
		return false;
	}

	/**
	 * Get access restriction notes for the record.
	 *
	 * @access  protected
	 * @return  array
	 */
	protected function getAccessRestrictions()
	{
		// Not currently stored in the Solr index
		return array();
	}

	/**
	 * Get all subject headings associated with this record.  Each heading is
	 * returned as an array of chunks, increasing from least specific to most
	 * specific.
	 *
	 * @access  protected
	 * @return array
	 */
	public function getAllSubjectHeadings()
	{
		$topic = isset($this->fields['topic']) ? $this->fields['topic'] : array();
		$geo = isset($this->fields['geographic']) ?
		$this->fields['geographic'] : array();
		$genre = isset($this->fields['genre']) ? $this->fields['genre'] : array();

		// The Solr index doesn't currently store subject headings in a broken-down
		// format, so we'll just send each value as a single chunk.  Other record
		// drivers (i.e. MARC) can offer this data in a more granular format.
		$retval = array();
		foreach($topic as $t) {
			$retval[] = array($t);
		}
		foreach($geo as $g) {
			$retval[] = array($g);
		}
		foreach($genre as $g) {
			$retval[] = array($g);
		}

		return $retval;
	}

	/**
	 * Get award notes for the record.
	 *
	 * @access  protected
	 * @return  array
	 */
	protected function getAwards()
	{
		// Not currently stored in the Solr index
		return array();
	}

	/**
	 * Get notes on bibliography content.
	 *
	 * @access  protected
	 * @return  array
	 */
	protected function getBibliographyNotes()
	{
		// Not currently stored in the Solr index
		return array();
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

	public function getCleanISBNs(){
		require_once ROOT_DIR . '/sys/ISBN.php';

		$cleanIsbns = array();
		// Get all the ISBNs and initialize the return value:
		$isbns = $this->getISBNs();

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
				if (!array_key_exists($isbn10, $cleanIsbns)){
					$cleanIsbns[$isbn10] = $isbn10;
				}
			}
			if ($isbn13 = $isbnObj->get13()) {
				if (!array_key_exists($isbn13, $cleanIsbns)) {
					$cleanIsbns[$isbn13] = $isbn13;
				}
			}
		}
		return $cleanIsbns;
	}

	/**
	 * Get just the base portion of the first listed ISSN (or false if no ISSNs).
	 *
	 * @access  protected
	 * @return  mixed
	 */
	protected function getCleanISSN()
	{
		$issns = $this->getISSNs();
		if (empty($issns)) {
			return false;
		}
		$issn = $issns[0];
		if ($pos = strpos($issn, ' ')) {
			$issn = substr($issn, 0, $pos);
		}
		return $issn;
	}

	public function getCleanUPC(){
		$upcs = $this->getUPCs();
		if (empty($upcs)) {
			return false;
		}
		$upc = $upcs[0];
		if ($pos = strpos($upc, ' ')) {
			$upc = substr($upc, 0, $pos);
		}
		return $upc;
	}

	public function getCleanUPCs(){
		$cleanUPCs = array();
		$upcs = $this->getUPCs();
		if (empty($upcs)) {
			return $cleanUPCs;
		}
		foreach ($upcs as $upc){
			if ($pos = strpos($upc, ' ')) {
				$upc = substr($upc, 0, $pos);
			}
			if (!array_key_exists($upc, $cleanUPCs)){
				$cleanUPCs[$upc] = $upc;
			}
		}

		return $cleanUPCs;
	}

	/**
	 * Get the main corporate author (if any) for the record.
	 *
	 * @access  protected
	 * @return  string
	 */
	protected function getCorporateAuthor()
	{
		// Not currently stored in the Solr index
		return null;
	}

	/**
	 * Get the date coverage for a record which spans a period of time (i.e. a
	 * journal).  Use getPublicationDates for publication dates of particular
	 * monographic items.
	 *
	 * @access  protected
	 * @return  array
	 */
	protected function getDateSpan()
	{
		return isset($this->fields['dateSpan']) ?
		$this->fields['dateSpan'] : array();
	}

	/**
	 * Get the edition of the current record.
	 *
	 * @access  protected
	 * @return  string
	 */
	protected function getEdition()
	{
		return isset($this->fields['edition']) ?
		$this->fields['edition'] : '';
	}

	/**
	 * Get notes on finding aids related to the record.
	 *
	 * @access  protected
	 * @return  array
	 */
	protected function getFindingAids()
	{
		// Not currently stored in the Solr index
		return array();
	}

	/**
	 * Get an array of all the formats associated with the record.
	 *
	 * @access  public
	 * @return  array
	 */
	public function getFormats()
	{
		return isset($this->fields['format']) ? $this->fields['format'] : array();
	}

	public function getPrimaryFormat(){
		$formats = $this->getFormats();
		return reset($formats);
	}

	/**
	 * Get an array of all the format categories associated with the record.
	 *
	 * @return  array
	 */
	public function getFormatCategory()
	{
		return isset($this->fields['format_category']) ? $this->fields['format_category'] : '';
	}
	/**
	 * Get general notes on the record.
	 *
	 * @access  protected
	 * @return  array
	 */
	protected function getGeneralNotes()
	{
		// Not currently stored in the Solr index
		return array();
	}

	static $groupedWorks = array();
	/**
	 * Load the grouped work that this record is connected to.
	 */
	public function loadGroupedWork() {
		if ($this->groupedWork == null){
			global $timer;
			require_once ROOT_DIR . '/sys/Grouping/GroupedWorkPrimaryIdentifier.php';
			require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
			$groupedWorkPrimaryIdentifier = new GroupedWorkPrimaryIdentifier();
			$groupedWorkPrimaryIdentifier->type = $this->getRecordType();
			$groupedWorkPrimaryIdentifier->identifier = $this->getUniqueID();
			if ($groupedWorkPrimaryIdentifier->find(true)){
				$groupedWork = new GroupedWork();
				$groupedWork->id = $groupedWorkPrimaryIdentifier->grouped_work_id;
				if ($groupedWork->find(true)){
					$this->groupedWork = clone $groupedWork;
				}
			}

			$timer->logTime("Loaded Grouped Work for record");
		}
	}

	public function getPermanentId(){
		return $this->getGroupedWorkId();
	}
	public function getGroupedWorkId(){
		if ($this->groupedWork == null){
			return null;
		}else{
			return $this->groupedWork->permanent_id;
		}
	}

	public function getGroupedWorkDriver(){
		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		if ($this->groupedWorkDriver == null){
			$this->groupedWorkDriver = new GroupedWorkDriver($this->getPermanentId());
		}
		return $this->groupedWorkDriver;
	}


	/**
	 * Get a highlighted author string, if available.
	 *
	 * @return string
	 * @access protected
	 */
	protected function getHighlightedAuthor()
	{
		// Don't check for highlighted values if highlighting is disabled:
		if (!$this->highlight) {
			return '';
		}
		return (isset($this->fields['_highlighting']['author'][0]))
		? $this->fields['_highlighting']['author'][0] : '';
	}

	/**
	 * Given a Solr field name, return an appropriate caption.
	 *
	 * @param string $field Solr field name
	 *
	 * @return mixed        Caption if found, false if none available.
	 * @access protected
	 */
	protected function getSnippetCaption($field)
	{
		if (isset($this->snippetCaptions[$field])){
			return $this->snippetCaptions[$field];
		}else{
			if (preg_match('/callnumber/', $field)){
				return 'Call Number';
			}else{
				return ucwords(str_replace('_', ' ', $field));
			}

		}
	}

	/**
	 * Pick one line from the highlighted text (if any) to use as a snippet.
	 *
	 * @return mixed False if no snippet found, otherwise associative array
	 * with 'snippet' and 'caption' keys.
	 * @access protected
	 */
	protected function getHighlightedSnippets()
	{
		$snippets = array();
		// Only process snippets if the setting is enabled:
		if ($this->snippet && isset($this->fields['_highlighting'])) {
			if (is_array($this->fields['_highlighting'])) {
				foreach ($this->fields['_highlighting'] as $key => $value) {
					if (!in_array($key, $this->forbiddenSnippetFields)) {
						$snippets[] = array(
							'snippet' => $value[0],
							'caption' => $this->getSnippetCaption($key)
						);
					}
				}
			}
			return $snippets;
		}

		// If we got this far, no snippet was found:
		return false;
	}

	/**
	 * Get a highlighted title string, if available.
	 *
	 * @return string
	 * @access protected
	 */
	protected function getHighlightedTitle()
	{
		// Don't check for highlighted values if highlighting is disabled:
		if (!$this->highlight) {
			return '';
		}
		return (isset($this->fields['_highlighting']['title'][0]))
		? $this->fields['_highlighting']['title'][0] : '';
	}

	/**
	 * Get the index engine to do a follow-up query.
	 *
	 * @access  protected
	 * @return  object
	 */
	protected function getIndexEngine()
	{
		global $configArray;

		// Build the index engine if we don't already have one:
		if (!$this->index) {
			$searchObject = SearchObjectFactory::initSearchObject();
			$this->index = new $searchObject;
		}

		return $this->index;
	}

	/**
	 * Get an array of all ISBNs associated with the record (may be empty).
	 *
	 * @access  protected
	 * @return  array
	 */
	public function getISBNs()
	{
		// If ISBN is in the index, it should automatically be an array... but if
		// it's not set at all, we should normalize the value to an empty array.
		if (isset($this->fields['isbn'])){
			if (is_array($this->fields['isbn'])){
				return $this->fields['isbn'];
			}else{
				return array($this->fields['isbn']);
			}
		}else{
			return array();
		}
	}

	/**
	 * Get the UPC associated with the record (may be empty).
	 *
	 * @return  array
	 */
	public function getUPCs()
	{
		// If UPCs is in the index, it should automatically be an array... but if
		// it's not set at all, we should normalize the value to an empty array.
		if (isset($this->fields['upc'])){
			if (is_array($this->fields['upc'])){
				return $this->fields['upc'];
			}else{
				return array($this->fields['upc']);
			}
		}else{
			return array();
		}
	}

	public function getUPC()
	{
		// If UPCs is in the index, it should automatically be an array... but if
		// it's not set at all, we should normalize the value to an empty array.
		return isset($this->fields['upc']) && is_array($this->fields['upc']) ? $this->fields['upc'][0] : '';
	}

	/**
	 * Get an array of all ISSNs associated with the record (may be empty).
	 *
	 * @access  public
	 * @return  array
	 */
	public function getISSNs()
	{
		// If ISSN is in the index, it should automatically be an array... but if
		// it's not set at all, we should normalize the value to an empty array.
		return isset($this->fields['issn']) && is_array($this->fields['issn']) ?
		$this->fields['issn'] : array();
	}

	/**
	 * Get an array of all the languages associated with the record.
	 *
	 * @access  protected
	 * @return  array
	 */
	public function getLanguages()
	{
		return isset($this->fields['language']) ?
		$this->fields['language'] : array();
	}

	/**
	 * Get an array of newer titles for the record.
	 *
	 * @access  protected
	 * @return  array
	 */
	protected function getNewerTitles()
	{
		return isset($this->fields['title_new']) ?
		$this->fields['title_new'] : array();
	}

	/**
	 * Get an array of physical descriptions of the item.
	 *
	 * @access  protected
	 * @return  array
	 */
	protected function getPhysicalDescriptions()
	{
		return isset($this->fields['physical']) ?
		$this->fields['physical'] : array();
	}

	/**
	 * Get the item's place of publication.
	 *
	 * @access  protected
	 * @return  array
	 */
	protected function getPlacesOfPublication()
	{
		// Not currently stored in the Solr index
		return array();
	}

	/**
	 * Get an array of playing times for the record (if applicable).
	 *
	 * @access  protected
	 * @return  array
	 */
	protected function getPlayingTimes()
	{
		// Not currently stored in the Solr index
		return array();
	}

	/**
	 * Get an array of previous titles for the record.
	 *
	 * @access  protected
	 * @return  array
	 */
	protected function getPreviousTitles()
	{
		return isset($this->fields['title_old']) ?
		$this->fields['title_old'] : array();
	}

	/**
	 * Get the main author of the record.
	 *
	 * @access  protected
	 * @return  string
	 */
	protected function getPrimaryAuthor()
	{
		return isset($this->fields['author']) ? $this->fields['author'] : '';
	}

	/**
	 * Get credits of people involved in production of the item.
	 *
	 * @access  protected
	 * @return  array
	 */
	protected function getProductionCredits()
	{
		// Not currently stored in the Solr index
		return array();
	}

	/**
	 * Get the publication dates of the record.  See also getDateSpan().
	 *
	 * @access  public
	 * @return  array
	 */
	public function getPublicationDates()
	{
		return isset($this->fields['publishDate']) ? $this->fields['publishDate'] : array();
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
					(isset($dates[$i]) ? (', ' . $dates[$i] . '.') : '');
			$publicationInfo = trim(str_replace('  ', ' ', $publicationInfo));
			$publicationInfo = str_replace(' ,', ',', $publicationInfo);
			$publicationInfo = htmlentities($publicationInfo);
			$returnVal[] = $publicationInfo;
			$i++;
		}

		return $returnVal;
	}

	/**
	 * Get an array of publication frequency information.
	 *
	 * @access  protected
	 * @return  array
	 */
	protected function getPublicationFrequency()
	{
		// Not currently stored in the Solr index
		return array();
	}

	/**
 * Get the publishers of the record.
 *
 * @access  protected
 * @return  array
 */
	protected function getPublishers()
	{
		return isset($this->fields['publisher']) ?
			$this->fields['publisher'] : array();
	}

	/**
	 * Get an array of strings describing relationships to other items.
	 *
	 * @access  protected
	 * @return  array
	 */
	protected function getRelationshipNotes()
	{
		// Not currently stored in the Solr index
		return array();
	}

	/**
	 * Get an array of all secondary authors (complementing getPrimaryAuthor()).
	 *
	 * @access  protected
	 * @return  array
	 */
	protected function getSecondaryAuthors()
	{
		return isset($this->fields['author2']) ?
		$this->fields['author2'] : array();
	}

	/**
	 * Get an array of all series names containing the record.  Array entries may
	 * be either the name string, or an associative array with 'name' and 'number'
	 * keys.
	 *
	 * @access  protected
	 * @return  array
	 */
	protected function getSeries()
	{
		// Only use the contents of the series2 field if the series field is empty
		if (isset($this->fields['series']) && !empty($this->fields['series'])) {
			return $this->fields['series'];
		}
		return isset($this->fields['series2']) ?
		$this->fields['series2'] : array();
	}

	/**
	 * Get the short (pre-subtitle) title of the record.
	 *
	 * @access  protected
	 * @return  string
	 */
	protected function getShortTitle()
	{
		return isset($this->fields['title_short']) ?
		$this->fields['title_short'] : '';
	}

	/**
	 * Get the subtitle of the record.
	 *
	 * @access  protected
	 * @return  string
	 */
	protected function getSubtitle()
	{
		return isset($this->fields['title_sub']) ?
		$this->fields['title_sub'] : '';
	}

	/**
	 * Get an array of technical details on the item represented by the record.
	 *
	 * @access  protected
	 * @return  array
	 */
	protected function getSystemDetails()
	{
		// Not currently stored in the Solr index
		return array();
	}

	/**
	 * Get an array of summary strings for the record.
	 *
	 * @access  protected
	 * @return  array
	 */
	protected function getSummary()
	{
		// Not currently stored in the Solr index
		return array();
	}

	/**
	 * Get an array of note about the record's target audience.
	 *
	 * @access  protected
	 * @return  array
	 */
	protected function getTargetAudienceNotes()
	{
		// Not currently stored in the Solr index
		return array();
	}

	/**
	 * Get the full title of the record.
	 *
	 * @return  string
	 */
	public function getTitle()
	{
		return isset($this->fields['title']) ? $this->fields['title'] : (isset($this->fields['title_display']) ? $this->fields['title_display'] : '');
	}

	/**
	 * Get the text of the part/section portion of the title.
	 *
	 * @access  protected
	 * @return  string
	 */
	protected function getTitleSection()
	{
		// Not currently stored in the Solr index
		return null;
	}

	/**
	 * Get the statement of responsibility that goes with the title (i.e. "by John Smith").
	 *
	 * @access  protected
	 * @return  string
	 */
	public function getTitleStatement()
	{
		// Not currently stored in the Solr index
		return null;
	}

	/**
	 * Return an associative array of URLs associated with this record (key = URL,
	 * value = description).
	 *
	 * @access  protected
	 * @return  array
	 */
	protected function getURLs()
	{
		$urls = array();
		if (isset($this->fields['url']) && is_array($this->fields['url'])) {
			foreach($this->fields['url'] as $url) {
				// The index doesn't contain descriptions for URLs, so we'll just
				// use the URL itself as the description.
				$urls[$url] = $url;
			}
		}
		return $urls;
	}

	public function getScore(){
		if (isset($this->fields['score'])){
			return $this->fields['score'];
		}
		return null;
	}

	public function getExplain(){
		if (isset($this->fields['explain'])){
			return nl2br(str_replace(' ', '&nbsp;', $this->fields['explain']));
		}
		return null;
	}

	public function getId(){
		if (isset($this->fields['id'])){
			return $this->fields['id'];
		}
		return null;
	}

	/**
	 * @return string[]
	 */
	public function getFormat(){
		if (isset($this->fields['format'])){
			if (is_array($this->fields['format'])){
				return $this->fields['format'];
			}else{
				return array($this->fields['format']);
			}
		}else{
			return array("Unknown");
		}
	}

	public function getLanguage(){
		if (isset($this->fields['language'])){
			return $this->fields['language'];
		}else{
			return "Implement this when not backed by Solr data";
		}
	}

	public function getRatingData() {
		require_once ROOT_DIR . '/services/API/WorkAPI.php';
		$workAPI = new WorkAPI();
		return $workAPI->getRatingData($this->getGroupedWorkId());
	}

	protected function getRecordType(){
		return 'unknown';
	}

	public function setScopingEnabled($enabled){
		$this->scopingEnabled = $enabled;
	}

	function getRecordUrl(){
		global $configArray;
		$recordId = $this->getUniqueID();

		//TODO: This should have the correct module set
		return $configArray['Site']['path'] . '/' . $this->getModule() . '/' . $recordId;
	}

	function getAbsoluteUrl(){
		global $configArray;
		$recordId = $this->getUniqueID();

		return $configArray['Site']['url'] . '/' . $this->getModule() . '/' . $recordId;
	}

	public function getLinkUrl($useUnscopedHoldingsSummary = false) {
		global $interface;
		$linkUrl = $this->getRecordUrl();
		$extraParams = array();
		if ($interface != null && strlen($interface->get_template_vars('searchId')) > 0){
			$extraParams[] = 'searchId=' . $interface->get_template_vars('searchId');
			$extraParams[] = 'recordIndex=' . $interface->get_template_vars('recordIndex');
			$extraParams[] = 'page='  . $interface->get_template_vars('page');

		}

		if ($useUnscopedHoldingsSummary){
			$extraParams[] = 'searchSource=marmot';
		}else{
			$extraParams[] = 'searchSource=' . $interface->get_template_vars('searchSource');
		}
		if (count($extraParams) > 0){
			$linkUrl .= '?' . implode('&', $extraParams);
		}
		return $linkUrl;
	}

	function getQRCodeUrl(){
		global $configArray;
		return $configArray['Site']['url'] . '/qrcode.php?type=Record&id=' . $this->getPermanentId();
	}

	public function getTags(){
		return $this->getGroupedWorkDriver()->getTags();
	}

	public function getExploreMoreInfo(){
		global $interface;
		global $configArray;
		$exploreMoreOptions = array();
		if ($configArray['Catalog']['showExploreMoreForFullRecords']) {
			$interface->assign('showMoreLikeThisInExplore', true);

			if ($this->getCleanISBN()){
				if ($interface->getVariable('showSimilarTitles')) {
					$exploreMoreOptions['similarTitles'] = array(
							'label' => 'Similar Titles From NoveList',
							'body' => '<div id="novelisttitlesPlaceholder"></div>',
							'hideByDefault' => true
					);
				}
				if ($interface->getVariable('showSimilarAuthors')) {
					$exploreMoreOptions['similarAuthors'] = array(
							'label' => 'Similar Authors From NoveList',
							'body' => '<div id="novelistauthorsPlaceholder"></div>',
							'hideByDefault' => true
					);
				}
				if ($interface->getVariable('showSimilarTitles')) {
					$exploreMoreOptions['similarSeries'] = array(
							'label' => 'Similar Series From NoveList',
							'body' => '<div id="novelistseriesPlaceholder"></div>',
							'hideByDefault' => true
					);
				}
			}

			require_once ROOT_DIR . '/sys/ExploreMore.php';
			$exploreMore = new ExploreMore();
			$exploreMore->loadExploreMoreSidebar('catalog', $this);
		}
		return $exploreMoreOptions;
	}

	public function getMoreDetailsOptions(){
		return $this->getBaseMoreDetailsOptions(false);
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

	/**
	 * Load Record actions when we don't have detailed information about the record yet
	 */
	public function getRecordActionsFromIndex()
	{
		$groupedWork = $this->getGroupedWorkDriver();
		if ($groupedWork != null) {
			$relatedRecords = $groupedWork->getRelatedRecords();
			foreach ($relatedRecords as $relatedRecord) {
				if ($relatedRecord['id'] == $this->getIdWithSource()) {
					return $relatedRecord['actions'];
				}
			}
		}
		return array();
	}

	public function getItemActions($itemInfo){
		return array();
	}

	public function getRecordActions($isAvailable, $isHoldable, $isBookable, $relatedUrls = null){
		return array();
	}

	public function getModule() {
		return 'Record';
	}
}
