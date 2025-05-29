<?php

require_once ROOT_DIR . '/RecordDrivers/RecordInterface.php';

class TalpaRecordDriver extends RecordInterface {
	private $record;
	private $isn;
	private $upc;
	/**
	 * Constructor.  We build the object using all the data retrieved
	 * @param array|File_MARC_Record||string   $recordData     Data to construct the driver from
	 * @access  public
	 */
	public function __construct($record) {

		if(is_array($record)) {
			$this->record= $record;
		}else{

			$_record = json_decode($record, true);

			$passedRecord = array();
			$passedRecord['isbns'][] = $_record['isbn']; //formatting the data for later use
			$passedRecord['upcs'][] = $_record['upc'];
			$passedRecord['title'] = urldecode($_record['title']);
			$passedRecord['author'] = urldecode($_record['author']);

			$this->record = $passedRecord;
		//from bookcover
		}

	}

	public function isValid()
	{
		return (!empty($this->record['isbns']) || !empty($this->record['upcs']));
	}

	public function getBookcoverUrl($size='medium', $absolutePath = false) {
		// require_once ROOT_DIR . '/sys/LibraryLocation/Library.php';
		global $library;

		global $configArray;
		if ($size == 'small' || $size == 'medium'){
			$sizeInArray = 'thumbnail_m';
		}else{
			$sizeInArray = 'thumbnail_l';
		}
		if ($absolutePath) {
			$bookCoverUrl = $configArray['Site']['url'];
		} else {
			$bookCoverUrl = '';
		}

		$params = array(
			'id'=> $this->getUniqueID(),
			'isbn'=> $this->isn,
			'upc'=> $this->upc,
			'author' => urlencode($this->author),
			'title' => urlencode($this->title),
			'size' => $size,
			'type' => 'talpa'
		);
//		$bookCoverUrl .= "/bookcover.php?id={$this->getUniqueID()}&size={$size}&type=talpa";
		$bookCoverUrl .= "/bookcover.php?".http_build_query($params);
		return $bookCoverUrl;
	}

	public function getRecord() {
		return $this->record;
	}



	/**
	 * @param bool $unscoped
	 * @return string
	 */
	public function getLinkUrl($unscoped = false) {
		return $this->getRecordUrl();
	}

	/**
	 * @return string
	 */

	public function getUniqueID() {
		if (isset($this->record['ID'])) {
			return (string)$this->record['ID'][0];
		} elseif ($this->isn) {
			return (string)$this->isn;
		} elseif ($this->upc) {
			return (string)$this->upc;
		}else{
			return null;
		}
	}

	public function getModule(): string {
		return 'Talpa';
	}

	public function getSearchResult($inLibrary = false) {

		global $interface;
		global $configArray;

		if($inLibrary) {
			if($this->record['groupedWork']['id']){
				require_once ROOT_DIR.'/RecordDrivers/GroupedWorkDriver.php';
				$groupedWorkDriver = new GroupedWorkDriver($this->record['groupedWork']['id']);
				if ($groupedWorkDriver->isValid()) {
					$interface->assign('summId', $groupedWorkDriver->getId());
					$interface->assign('talpaResult', 0);
					$interface->assign('groupedWorkDriver', $groupedWorkDriver);

					$relatedRecords = $groupedWorkDriver->getRelatedRecords();
					$summPublisher = null;
					$summPubDate = null;
					$summPlaceOfPublication =  null;
					$summPhysicalDesc = null;
					$summEdition = null;
					$summLanguage = null;
					$summClosedCaptioned = null;
					$isFirst = true;
					foreach ($relatedRecords as $relatedRecord) {
						if ($isFirst) {
							$summPublisher = $relatedRecord->publisher;
							$summPubDate = $relatedRecord->publicationDate;
							$summPlaceOfPublication = $relatedRecord->placeOfPublication;
							$summPhysicalDesc = $relatedRecord->physical;
							$summEdition = $relatedRecord->edition;
							$summLanguage = $relatedRecord->language;
							$summClosedCaptioned = $relatedRecord->closedCaptioned;
						} else {
							if ($summPublisher != $relatedRecord->publisher) {
								$summPublisher = null;
							}
							if ($summPubDate != $relatedRecord->publicationDate) {
								$summPubDate = null;
							}
							if ($summPlaceOfPublication != $relatedRecord->placeOfPublication) {
								$summPlaceOfPublication = null;
							}
							if ($summPhysicalDesc != $relatedRecord->physical) {
								$summPhysicalDesc = null;
							}
							if ($summEdition != $relatedRecord->edition) {
								$summEdition = null;
							}
							if ($summLanguage != $relatedRecord->language) {
								$summLanguage = null;
							}
							if ($summClosedCaptioned != $relatedRecord->closedCaptioned) {
								$summClosedCaptioned = null;
							}
						}
						$isFirst = false;
					}

					$interface->assign('summUrl', $groupedWorkDriver->getLinkUrl());
					$interface ->assign('summRating', $groupedWorkDriver ->getRatingData());


					$shortTitle = $groupedWorkDriver->getShortTitle();
					if (empty($shortTitle)) {
						$interface->assign('summTitle', $groupedWorkDriver->getTitle());
						$interface->assign('summSubTitle', '');
					} else {
						$interface->assign('summTitle', $shortTitle);
						$interface->assign('summSubTitle', $groupedWorkDriver->getSubtitle());
					}

					$interface->assign('summAuthor', rtrim($groupedWorkDriver->getPrimaryAuthor(true), ','));
					$interface->assign('summPublisher', $summPublisher);
					$interface->assign('summPubDate', $summPubDate);
					$interface->assign('summPlaceOfPublication', $summPlaceOfPublication);
					$interface->assign('summPhysicalDesc', $summPhysicalDesc);
					$interface->assign('summEdition', $summEdition);
					$interface->assign('summClosedCaptioned', $summClosedCaptioned);
					$interface ->assign('summLanguage', $summLanguage);
					$interface->assign('relatedManifestations', $groupedWorkDriver->getRelatedManifestations());
					$interface->assign('summDescription', $groupedWorkDriver->getDescription());
					$interface->assign('bookCoverUrl', $groupedWorkDriver->getBookcoverUrl('small'));
					$interface->assign('bookCoverUrlMedium', $groupedWorkDriver->getBookcoverUrl('medium'));
	//						$interface->assign('summDescription', $this->getDescriptionFast(true));
					if ($groupedWorkDriver->hasCachedSeries()) {
						$interface->assign('ajaxSeries', false);
						$interface->assign('summSeries', $groupedWorkDriver->getSeries(false));
					} else {
						$interface->assign('ajaxSeries', true);
						$interface->assign('summSeries', null);
					}


					$isbn = $groupedWorkDriver->getCleanISBN();
					$interface->assign('summISBN', $isbn);
					$interface->assign('summFormats', $groupedWorkDriver->getFormats());
					$interface->assign('numRelatedRecords', count($relatedRecords));

					$acceleratedReaderInfo = $groupedWorkDriver->getAcceleratedReaderDisplayString();
					$interface->assign('summArInfo', $acceleratedReaderInfo);

					$lexileInfo = $groupedWorkDriver->getLexileDisplayString();
					$interface->assign('summLexileInfo', $lexileInfo);

					$interface->assign('summFountasPinnell', $groupedWorkDriver->getFountasPinnellLevel());
				}
			}
		}
		else{ //Not a library result
			$interface->assign('summId', $this->record['work_id']);
			$this->isn = $this->record['isbns'][0];
			$this->upc = $this->record['upcs'][0];
			$this->title = $this->record['title'];
			$this->author = $this->record['author'];
			$interface->assign('summTitle', $this->record['title']);
			$interface->assign('bookCoverUrlMedium',$this->getBookcoverUrl());
			$interface->assign('summAuthor', $this->record['author']);
			$interface->assign('summPublisher',null);
			$interface->assign('summPubDate', $this->record['date']);
			$interface->assign('summFormats', $this->record['media']);
			$interface->assign('talpaResult', 1);
			$interface->assign('talpaIsbn', $this->isn);
		}



		return 'RecordDrivers/Talpa/result.tpl';
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
		return null;
	}

	/** * Get the full title of the record.
	 *
	 * @return  string
	 */
	public function getTitle() {
		if (isset($this->record['Title'])) {
			$title=$this->record['Title'][0];
			if (isset($this->record['Subtitle'])) {
				$title .= ': ' . $this->record['Subtitle'][0];
			}
		} else {
			$title=$this->record['title'];
		}
		return $title;
	}

	/**
	 * The Table of Contents extracted from the record.
	 * Returns null if no Table of Contents is available.
	 *
	 * @access  public
	 * @return  array              Array of elements in the table of contents
	 */
	public function getTableOfContents() {
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


	public function getId() {
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
	public function hasFullText() {
		if(isset($this->record['hasFullText'])){
			return $this->record['hasFullText'];
		}
		return false;
	}

	/**
	 * Does this record have reviews available?
	 *
	 * @access  public
	 * @return  bool
	 */
	public function hasReviews() {
		return false;
	}

	public function getDescription() {
		if(isset($this->record['Abstract'][0])) {
			$description = $this->record['Abstract'][0];
		} else {
			$description = '';
		}
		return $description;
	}

	public function getMoreDetailsOptions() {
		// TODO: Implement getMoreDetailsOptions() method.
	}

	public function getFormats() {
		if(isset($this->record['ContentType'][0])){
			$sourceType = (string)$this->record['ContentType'][0];
		} else {
			$sourceType = 'Unknown Source';
		}
		return $sourceType;
	}

	public function getCleanISSN() {
		return '';
	}

	public function getSourceDatabase() {
		if(isset($this->record['DatabaseTitle'][0])) {
			$databaseTitle = $this->record['DatabaseTitle'][0];
		} else {
			$databaseTitle = '';
		}
		return $databaseTitle;
	}

	public function getPrimaryAuthor() {
		return $this->getAuthor();
	}

	public function getAuthor() {

		if(isset($this->record['Author_xml'][0]['fullname'])) {
			$author=$this->record['Author_xml'][0]['fullname'];
		} else {
			$author=$this->record['author'];
		}
		return $author;
	}

	public function getExploreMoreInfo() {
		return [];
	}

	public function getPermanentId() {
		return $this->getUniqueID();
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
	public function getCitation($format) {
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
		}
		return '';
	}
}
