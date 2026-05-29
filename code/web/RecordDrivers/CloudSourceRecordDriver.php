<?php

require_once ROOT_DIR . '/RecordDrivers/RecordInterface.php';

class CloudSourceRecordDriver extends RecordInterface {
	private $record;

	/**
	 * Constructor.  We build the object using all the data retrieved
	 * @param array|File_MARC_Record||string   $recordData     Data to construct the driver from
	 * @access  public
	 */
	public function __construct($record)
	{
		if (is_string($record)) {
			/** @var SearchObject_CloudSourceSearcher $cloudSourceSearcher */
			$cloudSourceSearcher = SearchObjectFactory::initSearchObject("CloudSource");
			[
				$id,
				$index,
			] = explode('_', $record, 2);
			$this->record = $cloudSourceSearcher->retrieveRecord($id, $index);
		} else {
			$this->record = $record;
		}
	}

	public function isValid()
	{
		return true;
	}

	public function getBookcoverUrl($size = 'small', $absolutePath = false) {
		global $configArray;

		if ($absolutePath) {
			$bookCoverUrl = $configArray['Site']['url'];
		} else {
			$bookCoverUrl = '';
		}
		$bookCoverUrl .= "/bookcover.php?id={$this->getUniqueID()}&size={$size}&type=cloudsource";

		if (!empty($this->record->isbn[0])){
			$bookCoverUrl .= "&isbn={$this->record->isbn[0]}";
		}
		if (!empty($this->record->publication->issnL)) {
			$bookCoverUrl .= "&issn={$this->record->publication->issnL}";
		}

		return $bookCoverUrl;
	}

	/**
	 * @param bool $unscoped
	 * @return string
	 */
	public function getLinkUrl($unscoped = false, $redirectUrl = '') {
		if ($this->bypassAspenCloudSourcePageSetting()){
			return $this->getRecordUrl($redirectUrl);
		}
		return '/CloudSource/Record?id=' . $this->getId();
	}

	public function bypassAspenCloudSourcePageSetting(): bool {
		global $library;
		require_once ROOT_DIR . '/sys/CloudSource/CloudSourceSetting.php';
		$libraryCloudSourceSetting = new CloudSourceSetting();
		$libraryCloudSourceSetting->id = $library->getCloudSourceSettingId();
		if ($libraryCloudSourceSetting->find(true)) {
			if ($libraryCloudSourceSetting->bypassAspenCloudSourcePage) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @return string
	 */
	public function getAbsoluteUrl()
	{
		return $this->getRecordUrl();
	}

	public function getRecordUrl($redirectUrl = ''): null|string {
		if ($redirectUrl != '') {
			$doi = $this->record->doi;
			return preg_replace('/(?<=qu=)[^&]+/', $doi, $redirectUrl);
		}
		elseif (isset($this->record->webUrl)) {
			return $this->record->webUrl;
		} else {
			return null;
		}
	}

	public function getUniqueID()
	{
		if (isset($this->record->id) && isset($this->record->index)) {
			return (string)$this->record->id . '_' . (string)$this->record->index;
		} else {
			return null;
		}
	}

	public function getModule(): string
	{
		return 'CloudSource';
	}

	public function getDoi() {
		return $this->record->doi;
	}

	private static ?string $teValue = null;
	public function getTeValue() : string {
		if (CloudSourceRecordDriver::$teValue === null) {
			CloudSourceRecordDriver::$teValue = '';

			global $library;
			require_once ROOT_DIR . '/sys/CloudSource/LibraryCloudSourceSetting.php';
			$libraryCloudSourceSetting = new LibraryCloudSourceSetting();
			$libraryCloudSourceSetting->libraryId = $library->libraryId;
			if ($libraryCloudSourceSetting->find(true)){
				require_once ROOT_DIR . '/sys/CloudSource/CloudSourceSetting.php';
				$cloudSourceSetting = new CloudSourceSetting();
				$cloudSourceSetting->id = $libraryCloudSourceSetting->cloudsourceSettingId;
				if ($cloudSourceSetting->find(true)){
					$patronUrl = $cloudSourceSetting->patronUrl . "/search/results";

					$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL, $patronUrl);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
					$html = curl_exec($ch);
					curl_close($ch);

					// First grab the full input tag with id="targetValue"
					if ($html && preg_match('/<input[^>]*id="targetValue"[^>]*>/', $html, $tag)) {
						// Then extract the number from its value attribute
						if (preg_match('/value="(-\d+):/', $tag[0], $matches)) {
							CloudSourceRecordDriver::$teValue = $matches[1]; // e.g. "-261243320"
						}
					}
				}
			}
		}
		return CloudSourceRecordDriver::$teValue;
	}

	private $patronUrl = null;
	public function getPatronUrl($recordView = false): string {
		if ($this->patronUrl == null) {
			require_once ROOT_DIR . '/sys/CloudSource/CloudSourceSetting.php';

			global $library;
			$patronUrl = '';
			require_once ROOT_DIR . '/sys/CloudSource/LibraryCloudSourceSetting.php';
			$libraryCloudSourceSetting = new LibraryCloudSourceSetting();
			$libraryCloudSourceSetting->libraryId = $library->libraryId;
			if ($libraryCloudSourceSetting->find(true)){
				require_once ROOT_DIR . '/sys/CloudSource/CloudSourceSetting.php';
				$cloudSourceSetting = new CloudSourceSetting();
				$cloudSourceSetting->id = $libraryCloudSourceSetting->cloudsourceSettingId;
				if ($cloudSourceSetting->find(true)){
					if (!empty($this->getDoi())) {
						$patronUrl = $cloudSourceSetting->patronUrl . "/search/results?qu=doi:" . $this->getDoi();
					} else {
						$patronUrl = $cloudSourceSetting->patronUrl . "/search/results?qu=" . $_REQUEST["lookfor"];
					}
				}
			}

			$this->patronUrl = $patronUrl . "&te=" . $this->getTeValue();
		}
		return $this->patronUrl;
	}
	public function getSearchResult($view = 'list', $showListsAppearingOn = true) : string {
		if ($view == 'covers') {
			return $this->getBrowseResult();
		}

		global $interface;

		$redirectUrl = $this->getPatronUrl();

		$id = $this->getUniqueID();
		$formats = $this->getFormats();
		$interface->assign('summId', $id);
		$interface->assign('summShortId', $id);
		$interface->assign('module', $this->getModule());
		$interface->assign('summFormats', $formats);
		$interface->assign('summUrl', $this->getLinkUrl(false, $redirectUrl));
		$interface->assign('externalUrl', $redirectUrl);
		$interface->assign('summTitle', $this->getTitle());
		$interface->assign('summAuthor', $this->getAuthor());
		$interface->assign('summPublicationDates', $this->getPublicationDate());

		//Check to see if there are lists the record is on
		if ($showListsAppearingOn) {
			require_once ROOT_DIR . '/sys/UserLists/UserList.php';
			$appearsOnLists = UserList::getUserListsForRecord('cloudsource', $this->getId());
			$interface->assign('appearsOnLists', $appearsOnLists);
		}

		$interface->assign('summDescription', $this->getDescription());
		$interface->assign('bookCoverUrl', $this->getBookcoverUrl('small'));
		$interface->assign('bookCoverUrlMedium', $this->getBookcoverUrl('medium'));
		$interface->assign('bypassAspenPage', $this->bypassAspenCloudSourcePageSetting());

		return 'RecordDrivers/CloudSource/result.tpl';
	}

	public function getBrowseResult() {
		global $interface;

		$redirectUrl = $this->getPatronUrl();

		$interface->assign('summId', $this->getUniqueID());
		$interface->assign('summUrl', $this->getLinkUrl(false, $redirectUrl));
		$interface->assign('summTitle', $this->getTitle());

		//Get cover image size
		$appliedTheme = $interface->getAppliedTheme();
		$interface->assign('bookCoverUrl', $this->getBookcoverUrl('medium'));

		$accessibleBrowseCategories = 0;
		if ($appliedTheme) {
			if ($appliedTheme->browseCategoryImageSize == 1) {
				$interface->assign('bookCoverUrlMedium', $this->getBookcoverUrl('large'));
			} else {
				$interface->assign('bookCoverUrlMedium', $this->getBookcoverUrl('medium'));
			}
			$accessibleBrowseCategories = $appliedTheme->accessibleBrowseCategories;
		} else {
			$interface->assign('bookCoverUrlMedium', $this->getBookcoverUrl('medium'));
		}
		$interface->assign('accessibleBrowseCategories', $accessibleBrowseCategories);

		return 'RecordDrivers/CloudSource/browse_result.tpl';
	}

	public function getRecordViewData()
	{
		return $this->record;
	}

	/**
	 * Assign necessary Smarty variables and return a template name to
	 * load in order to display a summary of the item suitable for use in
	 * search results.
	 *
	 * @access  public
	 * @return  string              Name of Smarty template file to display.
	 */
	public function getCombinedResult()
	{
		global $interface;
		$formats = $this->getFormats();
		$id = $this->getUniqueID();

		$redirectUrl = $this->getPatronUrl();
		$interface->assign('summId', $id);
		$interface->assign('summShortId', $id);
		$interface->assign('module', $this->getModule());
		$interface->assign('summFormats', $formats);
		$interface->assign('summUrl', $this->getLinkUrl(false, $redirectUrl));
		$interface->assign('summTitle', $this->getTitle());
		$interface->assign('summAuthor', $this->getAuthor());
		$interface->assign('summDescription', $this->getDescription());
		$interface->assign('bookCoverUrl', $this->getBookcoverUrl('small'));
		$interface->assign('bookCoverUrlMedium', $this->getBookcoverUrl('medium'));
		$interface->assign('summPublicationDates', $this->getPublicationDate());


		return 'RecordDrivers/CloudSource/combinedResult.tpl';
	}

	public function getSpotlightResult(CollectionSpotlight $collectionSpotlight, string $index)
	{
		global $interface;

		$redirectUrl = $this->getPatronUrl();

		$interface->assign('showRatings', $collectionSpotlight->showRatings);
		$interface->assign('key', $index);
		$interface->assign('title', $this->getTitle());
		$interface->assign('author', $this->getAuthor());
		$interface->assign('description', $this->getDescription());
		$interface->assign('shortId', $this->getUniqueID());
		$interface->assign('id', $this->getUniqueID());
		$interface->assign('titleURL', $this->getLinkUrl(false, $redirectUrl));

		if ($collectionSpotlight->coverSize == 'small') {
			$imageUrl = $this->getBookcoverUrl('small');
		} else {
			$imageUrl = $this->getBookcoverUrl('medium');
		}
		$interface->assign('imageUrl', $imageUrl);

		if ($collectionSpotlight->showRatings) {
			$interface->assign('ratingData', null);
			$interface->assign('showNotInterested', false);
		}

		$result = [
			'title' => $this->getTitle(),
			'author' => $this->getAuthor(),
		];
		if ($collectionSpotlight->style == 'text-list') {
			$result['formattedTextOnlyTitle'] = $interface->fetch('CollectionSpotlight/formattedTextOnlyTitle.tpl');
		} elseif ($collectionSpotlight->style == 'horizontal-carousel') {
			$result['formattedTitle'] = $interface->fetch('CollectionSpotlight/formattedHorizontalCarouselTitle.tpl');
		} else {
			$result['formattedTitle'] = $interface->fetch('CollectionSpotlight/formattedTitle.tpl');
		}

		return $result;
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
		return null;
	}

	/** * Get the full title of the record.
	 *
	 * @return  string
	 */
	public function getTitle()
	{
		if (isset($this->record->title)) {
			$title = $this->record->title;
		} else {
			$title = 'Unknown Title';
		}
		return $title;
	}

	public function getPublicationDate()
	{
		return $this->record->publishDate ?? null;
	}

	/**
	 * The Table of Contents extracted from the record.
	 * Returns null if no Table of Contents is available.
	 *
	 * @access  public
	 * @return  array              Array of elements in the table of contents
	 */
	public function getTableOfContents()
	{
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


	public function getId()
	{
		return $this->getUniqueID();
	}

	public function getDescription()
	{
		if (isset($this->record->abstrakt)) {
			$description = $this->record->abstrakt;
		} else {
			$description = '';
		}
		return $description;
	}

	public function getMoreDetailsOptions() : array {
		return [];
	}

	public function getFormats()
	{
		if (isset($this->record->format->label)) {
			$sourceType = (string)$this->record->format->label;
		} else {
			$sourceType = 'Unknown Source';
		}
		return $sourceType;
	}

	public function getCleanISSN()
	{
		return '';
	}

	public function getPrimaryAuthor()
	{
		return $this->getAuthor();
	}

	public function getAuthor()
	{
		if (isset($this->record->author[0]->name)) {
			$author = $this->record->author[0]->name;
		} else {
			$author = 'Unknown Author';
		}
		return $author;
	}

	public function getExploreMoreInfo()
	{
		return [];
	}

	public function getPermanentId()
	{
		return $this->getUniqueID();
	}

	/**
	 * Assign necessary Smarty variables and return a template name to
	 * load in order to display a summary of the item suitable for use in
	 * user's favorites list.
	 *
	 * @access  public
	 * @param int $listId ID of list containing desired tags/notes (or null to show tags/notes from all user's lists).
	 * @param bool $allowEdit Should we display edit controls?
	 * @param bool $allowEdit Should we display edit controls?
	 * @return  string              Name of Smarty template file to display.
	 */
	public function getListEntry($listId = null, $allowEdit = true)
	{
		$this->getSearchResult('list');
		//Switch template
		return 'RecordDrivers/CloudSource/listEntry.tpl';
	}

	/**
	 * Assign necessary Smarty variables and return a template name
	 * to load in order to display the requested citation format.
	 * For legal values, see getCitationFormats().  Returns null if
	 * format is not supported.
	 *
	 * @param string $format Citation format to display.
	 * @access  public
	 * @return  string              Name of Smarty template file to display.
	 */
	public function getCitation($format)
	{
		require_once ROOT_DIR . '/sys/CitationBuilder.php';

		// Build author list:
		$authors = [];
		$primary = $this->getAuthor();
		if (!empty($primary)) {
			$authors[] = $primary;
		}
		//TODO: - Make get places of publication function
		//$pubPlaces = $this->getPlacesOfPublication();
		$details = [
			'authors' => $authors,
			'title' => $this->getTitle(),
			'subtitle' => '',
			'pubName' => null,
			'pubDate' => null,
			'edition' => null,
			'format' => $this->getFormats(),
		];

		// Build the citation:
		$citation = new CitationBuilder($details);
		switch ($format) {
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
			case 'Harvard':
				return $citation->getHarvard();
		}
		return '';
	}
}