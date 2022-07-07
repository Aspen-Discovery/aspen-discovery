<?php

require_once ROOT_DIR . '/RecordDrivers/RecordInterface.php';

class EbscoRecordDriver extends RecordInterface
{
	private $recordData;

	/**
	 * Constructor.  We build the object using all the data retrieved
	 * from the (Solr) index.  Since we have to
	 * make a search call to find out which record driver to construct,
	 * we will already have this data available, so we might as well
	 * just pass it into the constructor.
	 *
	 * @param array|File_MARC_Record||string   $recordData     Data to construct the driver from
	 * @access  public
	 */
	public function __construct($recordData)
	{
		if (is_string($recordData)) {
			/** @var SearchObject_EbscoEdsSearcher $edsSearcher */
			$edsSearcher = SearchObjectFactory::initSearchObject("EbscoEds");
			list($dbId, $an) = explode(':', $recordData);
			$this->recordData = $edsSearcher->retrieveRecord($dbId, $an);
		} else {
			$this->recordData = $recordData;
		}
	}

	public function isValid()
	{
		return true;
	}

	public function getBookcoverUrl($size = 'small', $absolutePath = false)
	{
		if (!empty($this->recordData->ImageInfo)) {
			if (is_array($this->recordData->ImageInfo)) {
				$imageUrl = '';

				/** @var stdClass $coverArtElement */
				foreach ($this->recordData->ImageInfo as $coverArtElement) {
					if ($size == 'small' && $coverArtElement->Size == 'thumb') {
						return $coverArtElement->Target;
					} elseif ($size == 'medium' && $coverArtElement->Size == 'medium') {
						return $coverArtElement->Target;
					} else {
						$imageUrl = $coverArtElement->Target;
					}
				}
				return $imageUrl;
			} else {
				return $this->recordData->ImageInfo->Target;
			}
		} else {
			global $configArray;

			if ($absolutePath) {
				$bookCoverUrl = $configArray['Site']['url'];
			} else {
				$bookCoverUrl = '';
			}
			$bookCoverUrl .= "/bookcover.php?id={$this->getUniqueID()}&size={$size}&type=ebsco_eds";
			return $bookCoverUrl;
		}
	}

	/**
	 * Overridden because we are linking straight to EBSCO
	 * @param bool $unscoped
	 * @return string
	 */
	public function getLinkUrl($unscoped = false)
	{
		return $this->getRecordUrl();
	}

	/**
	 * Overridden because we are linking straight to EBSCO
	 * @return string
	 */
	public function getAbsoluteUrl()
	{
		return $this->getRecordUrl();
	}

	public function getRecordUrl()
	{
		//TODO: Switch back to an internal link once we do a full EBSCO implementation
		//global $configArray;
		//return '/EBSCO/Home?id=' . urlencode($this->getUniqueID());
		return $this->recordData->PLink;
	}

	/** @noinspection PhpUnused */
	public function getEbscoUrl()
	{
		return $this->recordData->PLink;
	}

	public function getModule() : string
	{
		return 'EBSCO';
	}

	public function getSearchResult($view = 'list', $showListsAppearingOn = true)
	{
		if ($view == 'covers') { // Displaying Results as bookcover tiles
			return $this->getBrowseResult();
		}

		global $interface;

		$id = $this->getUniqueID();
		$interface->assign('summId', $id);
		$interface->assign('summShortId', $id);
		$interface->assign('module', $this->getModule());

		$formats = $this->getFormats();
		$interface->assign('summFormats', $formats);

		$interface->assign('summUrl', $this->getLinkUrl());
		$interface->assign('summTitle', $this->getTitle());
		$interface->assign('summAuthor', $this->getAuthor());
		$interface->assign('summSourceDatabase', $this->getSourceDatabase());
		$interface->assign('summHasFullText', $this->hasFullText());

		//Check to see if there are lists the record is on
		if ($showListsAppearingOn) {
			require_once ROOT_DIR . '/sys/UserLists/UserList.php';
			$appearsOnLists = UserList::getUserListsForRecord('EbscoEds', $this->getId());
			$interface->assign('appearsOnLists', $appearsOnLists);
		}

		$interface->assign('summDescription', $this->getDescription());

		$interface->assign('bookCoverUrl', $this->getBookcoverUrl('small'));
		$interface->assign('bookCoverUrlMedium', $this->getBookcoverUrl('medium'));

		require_once ROOT_DIR . '/sys/Ebsco/EbscoEdsRecordUsage.php';
		$recordUsage = new EbscoEdsRecordUsage();
		$recordUsage->instance = $_SERVER['SERVER_NAME'];
		$recordUsage->ebscoId = $this->getUniqueID();
		$recordUsage->year = date('Y');
		$recordUsage->month = date('n');
		if ($recordUsage->find(true)) {
			$recordUsage->timesViewedInSearch++;
			$recordUsage->update();
		} else {
			$recordUsage->timesViewedInSearch = 1;
			$recordUsage->timesUsed = 0;
			$recordUsage->insert();
		}

		return 'RecordDrivers/EBSCO/result.tpl';
	}

	public function getBrowseResult()
	{
		global $interface;

		$id = $this->getUniqueID();
		$interface->assign('summId', $id);


		$interface->assign('summUrl', $this->getLinkUrl());
		$interface->assign('summTitle', $this->getTitle());

        //Get cover image size
        global $interface;
        $appliedTheme = $interface->getAppliedTheme();

        $interface->assign('bookCoverUrl', $this->getBookcoverUrl('small'));

        if ($appliedTheme != null && $appliedTheme->browseCategoryImageSize == 1) {
            $interface->assign('bookCoverUrlMedium', $this->getBookcoverUrl('large'));
        }
        else {
            $interface->assign('bookCoverUrlMedium', $this->getBookcoverUrl('medium'));
        }

		return 'RecordDrivers/EBSCO/browse_result.tpl';
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

		$id = $this->getUniqueID();
		$interface->assign('summId', $id);
		$interface->assign('summShortId', $id);
		$interface->assign('module', $this->getModule());

		$formats = $this->getFormats();
		$interface->assign('summFormats', $formats);

		$interface->assign('summUrl', $this->getLinkUrl());
		$interface->assign('summTitle', $this->getTitle());
		$interface->assign('summAuthor', $this->getAuthor());
		$interface->assign('summSourceDatabase', $this->getSourceDatabase());
		$interface->assign('summHasFullText', $this->hasFullText());

		$interface->assign('summDescription', $this->getDescription());

		$interface->assign('bookCoverUrl', $this->getBookcoverUrl('small'));
		$interface->assign('bookCoverUrlMedium', $this->getBookcoverUrl('medium'));

		return 'RecordDrivers/EBSCO/combinedResult.tpl';
	}

	public function getSpotlightResult(CollectionSpotlight $collectionSpotlight, string $index){
		global $interface;
		$interface->assign('showRatings', $collectionSpotlight->showRatings);

		$interface->assign('key', $index);

		if ($collectionSpotlight->coverSize == 'small'){
			$imageUrl = $this->getBookcoverUrl('small');
		}else{
			$imageUrl = $this->getBookcoverUrl('medium');
		}

		$interface->assign('title', $this->getTitle());
		$interface->assign('author', $this->getAuthor());
		$interface->assign('description', $this->getDescription());
		$interface->assign('shortId', $this->getUniqueID());
		$interface->assign('id', $this->getUniqueID());
		$interface->assign('titleURL', $this->getLinkUrl());
		$interface->assign('imageUrl', $imageUrl);

		if ($collectionSpotlight->showRatings){
			$interface->assign('ratingData', null);
			$interface->assign('showNotInterested', false);
		}

		$result = [
			'title' => $this->getTitle(),
			'author' => $this->getAuthor(),
		];
		if ($collectionSpotlight->style == 'text-list'){
			$result['formattedTextOnlyTitle'] = $interface->fetch('CollectionSpotlight/formattedTextOnlyTitle.tpl');
		}elseif ($collectionSpotlight->style == 'horizontal-carousel'){
			$result['formattedTitle'] = $interface->fetch('CollectionSpotlight/formattedHorizontalCarouselTitle.tpl');
		}else{
			$result['formattedTitle']= $interface->fetch('CollectionSpotlight/formattedTitle.tpl');
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

	/**
	 * Get the full title of the record.
	 *
	 * @return  string
	 */
	public function getTitle()
	{
		if (isset($this->recordData->RecordInfo->BibRecord->BibEntity)) {
			if (isset($this->recordData->RecordInfo->BibRecord->BibEntity->Titles)) {
				return $this->recordData->RecordInfo->BibRecord->BibEntity->Titles[0]->TitleFull;
			}
		}
		if (isset($this->recordData->RecordInfo->BibRecord->BibRelationships->IsPartOfRelationships)){
			foreach ($this->recordData->RecordInfo->BibRecord->BibRelationships->IsPartOfRelationships as $relationship){
				if (isset($relationship->BibEntity->Titles)){
					return $relationship->BibEntity->Titles[0]->TitleFull;
				}
			}
		}
		return 'Unknown';
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
	public function getUniqueID()
	{
		return (string)$this->recordData->Header->DbId . ':' . (string)$this->recordData->Header->An;
	}

	public function getId()
	{
		return $this->getUniqueID();
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
		if ($this->recordData->FullText->Text->Availability == 1){
			return true;
		}elseif (!empty($this->recordData->FullText->Links)){
			return true;
		}
		return false;
	}

	public function getFullText()
	{
		$fullText = (string)$this->recordData->FullText->Text->Value;
		$fullText = html_entity_decode($fullText);
		$fullText = preg_replace('/<anid>.*?<\/anid>/', '', $fullText);
		return $fullText;
	}

	/**
	 * Does this record have reviews available?
	 *
	 * @access  public
	 * @return  bool
	 */
	public function hasReviews()
	{
		return false;
	}

	public function getDescription()
	{
		if (!empty($this->recordData->Items)) {
			/** @var stdClass $item */
			foreach ($this->recordData->Items as $item) {
				if ($item->Name == 'Abstract') {
					return strip_tags($item->Data);
				}
			}
		}
		return '';
	}

	public function getMoreDetailsOptions()
	{
		// TODO: Implement getMoreDetailsOptions() method.
	}

	public function getFormats()
	{
		return (string)$this->recordData->Header->PubType;
	}

	public function getCleanISSN()
	{
		return '';
	}

	public function getSourceDatabase()
	{
		return $this->recordData->Header->DbLabel;
	}

	public function getPrimaryAuthor()
	{
		return $this->getAuthor();
	}

	public function getAuthor()
	{
		if (!empty($this->recordData->Items)) {
			foreach ($this->recordData->Items as $item) {
				if ($item->Name == 'Author') {
					return strip_tags(html_entity_decode($item->Data));
				}
			}
		}
		return "";
	}

	public function getExploreMoreInfo()
	{
		return [];
	}

	public function getAllSubjectHeadings()
	{
		$subjectHeadings = array();
		if (count(@$this->recordData->RecordInfo->BibRecord->BibEntity->Subjects) != 0) {
			foreach ($this->recordData->RecordInfo->BibRecord->BibEntity->Subjects->Subject as $subject) {
				$subjectHeadings[] = (string)$subject->SubjectFull;
			}
		}
		return $subjectHeadings;
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
	 * @param int $listId ID of list containing desired tags/notes (or
	 *                              null to show tags/notes from all user's lists).
	 * @param bool $allowEdit Should we display edit controls?
	 * @return  string              Name of Smarty template file to display.
	 */
	public function getListEntry($listId = null, $allowEdit = true)
	{
		$this->getSearchResult('list');

		//Switch template
		return 'RecordDrivers/EBSCO/listEntry.tpl';
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
		$authors = array();
		$primary = $this->getAuthor();
		if (!empty($primary)) {
			$authors[] = $primary;
		}

		//$pubPlaces = $this->getPlacesOfPublication();
		$details = array(
			'authors' => $authors,
			'title' => $this->getTitle(),
			'subtitle' => '',
			//'pubPlace' => count($pubPlaces) > 0 ? $pubPlaces[0] : null,
			'pubName' => null,
			'pubDate' => null,
			'edition' => null,
			'format' => $this->getFormats()
		);

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
		}
		return '';
	}
}