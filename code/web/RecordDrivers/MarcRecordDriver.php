<?php

require_once ROOT_DIR . '/sys/File/MARC.php';

require_once ROOT_DIR . '/RecordDrivers/IndexRecordDriver.php';
require_once ROOT_DIR . '/RecordDrivers/GroupedWorkSubDriver.php';

/**
 * MARC Record Driver
 *
 * This class is designed to handle MARC records.  Much of its functionality
 * is inherited from the default index-based driver.
 */
class MarcRecordDriver extends GroupedWorkSubDriver
{
	/** @var File_MARC_Record $marcRecord */
	protected $marcRecord = null;

	protected $profileType;
	protected $id;
	/** @var  IndexingProfile $indexingProfile */
	protected $indexingProfile;
	protected $valid = null;

	/**
	 * Constructor.  We build the object using all the data retrieved
	 * from the (Solr) index.  Since we have to
	 * make a search call to find out which record driver to construct,
	 * we will already have this data available, so we might as well
	 * just pass it into the constructor.
	 *
	 * @param   array|File_MARC_Record|string   $recordData     Data to construct the driver from
	 * @param  GroupedWork $groupedWork ;
	 * @access  public
	 */
	public function __construct($recordData, $groupedWork = null)
	{
		// Call the parent's constructor...
		global $timer;
		if ($recordData instanceof File_MARC_Record) {
			//Full MARC record
			$this->marcRecord = $recordData;
			$this->valid = true;
		} elseif (is_string($recordData)) {
        	//Just the id
			require_once ROOT_DIR . '/sys/MarcLoader.php';
			if (strpos($recordData, ':') !== false) {
				$recordInfo = explode(':', $recordData);
				$this->profileType = $recordInfo[0];
				$this->id = $recordInfo[1];
			} else {
				$this->profileType = 'ils';
				$this->id = $recordData;
			}

			global $indexingProfiles;
			global $sideLoadSettings;
			if (array_key_exists($this->profileType, $indexingProfiles)) {
				$this->indexingProfile = $indexingProfiles[$this->profileType];
			}else if (array_key_exists(strtolower($this->profileType), $sideLoadSettings)) {
				$this->indexingProfile = $sideLoadSettings[strtolower($this->profileType)];
			} else {
				//Try to infer the indexing profile from the module
				global $activeRecordProfile;
				if ($activeRecordProfile) {
					$this->indexingProfile = $activeRecordProfile;
				} else {
					$this->indexingProfile = $indexingProfiles['ils'];
				}
			}
			if ($groupedWork == null) {
				//Check if it's valid by checking if the marc record exists,
				//but don't load for performance.
				$this->valid = MarcLoader::marcExistsForILSId($this->getIdWithSource());
				$timer->logTime("Finished checking if marc file exists");
			}else{
				//If we are loading based on a grouped work, it is part of the index so assume that it
				//does exits.
				$this->valid = true;
			}
			//$this->getMarcRecord($this->getUniqueID());
		} else {
			//Array of information, this likely never happens
			// Also process the MARC record:
			require_once ROOT_DIR . '/sys/MarcLoader.php';
			$this->marcRecord = MarcLoader::loadMarcRecordFromRecord($recordData);
			if (!$this->marcRecord) {
				$this->valid = false;
			}
		}
		if (!isset($this->id) && $this->valid) {
			/** @var File_MARC_Data_Field $idField */
			global $configArray;
			$idField = $this->marcRecord->getField($configArray['Reindex']['recordNumberTag']);
			if ($idField) {
				$this->id = $idField->getSubfield('a')->getData();
			}
		}
		if ($this->valid){
			parent::__construct($groupedWork);
		}
		$timer->logTime("Initialization of MarcRecord Driver");
	}

	public function __destruct()
	{
		$this->marcRecord = null;
		$this->indexingProfile = null;
		parent::__destruct();
	}

	public function getModule() : string{
		return isset($this->indexingProfile) ? $this->indexingProfile->recordUrlComponent : 'Record';
	}

	public function getIndexingProfile(){
		return $this->indexingProfile;
	}

	public function isValid()
	{
		if ($this->valid === null) {
			$this->valid = MarcLoader::marcExistsForILSId($this->getIdWithSource());
		}
		return $this->valid;
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
        return $this->id;
	}

	public function getIdWithSource()
	{
		return $this->profileType . ':' . $this->id;
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
		return $this->id;
	}

	/**
	 * Return the unique identifier of this record within the Solr index;
	 * useful for retrieving additional information (like tags and user
	 * comments) from the external MySQL database.
	 *
	 * @access  public
	 * @return  string              Unique identifier.
	 */
	public function getShortId()
	{
		$shortId = '';
		if (isset($this->id)) {
			$shortId = $this->id;
			if (strpos($shortId, '.b') === 0) {
				$shortId = str_replace('.b', 'b', $shortId);
				$shortId = substr($shortId, 0, strlen($shortId) - 1);
			}
		}
		return $shortId;
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
		global $configArray;
		if ($configArray['Catalog']['ils'] == 'Millennium' || $configArray['Catalog']['ils'] == 'Sierra'){
			$classicId = substr($this->id, 1, strlen($this->id) -2);
			$interface->assign('classicId', $classicId);
			$millenniumScope = $interface->getVariable('millenniumScope');
			if(isset($configArray['Catalog']['linking_url'])){
				$linkingUrl = $configArray['Catalog']['linking_url'];
				if (substr($linkingUrl, -1, 1) == '/'){
					$linkingUrl = substr($linkingUrl, 0, -1);
				}
				$interface->assign('classicUrl', $linkingUrl . "/record=$classicId&amp;searchscope={$millenniumScope}");
			}

		}elseif ($configArray['Catalog']['ils'] == 'Koha'){
			$interface->assign('classicId', $this->id);
			$interface->assign('classicUrl', $configArray['Catalog']['url'] . '/cgi-bin/koha/opac-detail.pl?biblionumber=' . $this->id);
			$interface->assign('staffClientUrl', $configArray['Catalog']['staffClientUrl'] . '/cgi-bin/koha/catalogue/detail.pl?biblionumber=' . $this->id);
		}elseif ($configArray['Catalog']['ils'] == 'CarlX'){
			$shortId = str_replace('CARL', '', $this->id);
			$shortId = ltrim($shortId, '0');
			$interface->assign('staffClientUrl', $configArray['Catalog']['staffClientUrl'] . '/Items/' . $shortId);
		}elseif ($configArray['Catalog']['ils'] == 'Evergreen'){
			$baseUrl = $configArray['Catalog']['url'];
			if (substr($baseUrl, -1, 1) == '/'){
				$baseUrl = substr($baseUrl, 0, -1);
			}
			$interface->assign('classicId', $this->id);
			$interface->assign('classicUrl', $baseUrl . '/eg/opac/record/' . $this->id);
		}

		$groupedWorkDriver = $this->getGroupedWorkDriver();
		if ($groupedWorkDriver != null) {
			if ( $groupedWorkDriver->isValid()){
				$interface->assign('hasValidGroupedWork', true);
				$this->getGroupedWorkDriver()->assignGroupedWorkStaffView();

				require_once ROOT_DIR . '/sys/Grouping/NonGroupedRecord.php';
				$nonGroupedRecord = new NonGroupedRecord();
				$nonGroupedRecord->source = $this->getRecordType();
				$nonGroupedRecord->recordId = $this->getId();
				if ($nonGroupedRecord->find(true)){
					$interface->assign('isUngrouped', true);
					$interface->assign('ungroupingId', $nonGroupedRecord->id);
				}else{
					$interface->assign('isUngrouped', false);
				}
			}else{
				$interface->assign('hasValidGroupedWork', false);
			}
		}else{
			$interface->assign('hasValidGroupedWork', false);
		}

		//Look for an IlsRecord for this MARC
		require_once ROOT_DIR . '/sys/Indexing/IlsRecord.php';
		$ilsRecord = new IlsRecord();
		$ilsRecord->source = $this->getRecordType();
		$ilsRecord->ilsId = $this->getId();
		if ($ilsRecord->find(true)){
			$interface->assign('ilsRecord', $ilsRecord);
		}

		$interface->assign('bookcoverInfo', $this->getBookcoverInfo());

		$marcRecord = $this->getMarcRecord();
		$marcRecord->sortFields();
		$interface->assign('marcRecord', $marcRecord);

		$lastMarcModificationTime = MarcLoader::lastModificationTimeForIlsId("{$this->profileType}:{$this->id}");
		$interface->assign('lastMarcModificationTime', $lastMarcModificationTime);

		$interface->assign('uploadedPDFs', $this->getUploadedPDFs());

		$interface->assign('uploadedSupplementalFiles', $this->getUploadedSupplementalFiles());

		return 'RecordDrivers/Marc/staff.tpl';
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
		$tableOfContents = array();
		$marcRecord = $this->getMarcRecord();
		if ($marcRecord != null) {
			$marcFields505 = $marcRecord->getFields('505');
			if ($marcFields505) {
				$tableOfContents = $this->processTableOfContentsFields($marcFields505);
			}
		}

		return $tableOfContents;
	}

	/**
	 * Get all subject headings associated with this record.  Each heading is
	 * returned as an array of chunks, increasing from least specific to most
	 * specific.
	 *
	 * @access  protected
	 * @return array
	 */
	/** @noinspection PhpUnused */
	public function getAllSubjectHeadings()
	{
		// These are the fields that may contain subject headings:
		$fields = array('600', '610', '630', '650', '651', '655');

		// This is all the collected data:
		$retVal = array();

		// Try each MARC field one at a time:
		foreach ($fields as $field) {
			// Do we have any results for the current field?  If not, try the next.
			/** @var File_MARC_Data_Field[] $results */
			$results = $this->getMarcRecord()->getFields($field);
			if (!$results) {
				continue;
			}

			// If we got here, we found results -- let's loop through them.
			foreach ($results as $result) {
				// Start an array for holding the chunks of the current heading:
				$current = array();

				// Get all the chunks and collect them together:
				/** @var File_MARC_Subfield[] $subfields */
				$subfields = $result->getSubfields();
				if ($subfields) {
					foreach ($subfields as $subfield) {
						//Add unless this is 655 subfield 2
						if ($subfield->getCode() != 2) {
							$current[] = $subfield->getData();
						}
					}
					// If we found at least one chunk, add a heading to our $result:
					if (!empty($current)) {
						$retVal[] = $current;
					}
				}
			}
		}

		// Send back everything we collected:
		return $retVal;
	}

	/**
	 * Return an array of all values extracted from the specified field/subfield
	 * combination.  If multiple subfields are specified and $concat is true, they
	 * will be concatenated together in the order listed -- each entry in the array
	 * will correspond with a single MARC field.  If $concat is false, the return
	 * array will contain separate entries for separate subfields.
	 *
	 * @param   string $field The MARC field number to read
	 * @param   array $subfields The MARC subfield codes to read
	 * @param   bool $concat Should we concatenate subfields?
	 * @access  private
	 * @return  array
	 */
	private function getFieldArray($field, $subfields = null, $concat = true)
	{
		// Default to subfield a if nothing is specified.
		if (!is_array($subfields)) {
			$subfields = array('a');
		}

		// Initialize return array
		$matches = array();

		if ($this->isValid()) {
			$marcRecord = $this->getMarcRecord();
			if ($marcRecord != false) {
				// Try to look up the specified field, return empty array if it doesn't exist.
				$fields = $marcRecord->getFields($field);
				if (!is_array($fields)) {
					return $matches;
				}

				// Extract all the requested subfields, if applicable.
				foreach ($fields as $currentField) {
					$next = $this->getSubfieldArray($currentField, $subfields, $concat);
					$matches = array_merge($matches, $next);
				}
			}
		}

		return $matches;
	}

	/**
	 * Get the edition of the current record.
	 *
	 * @access  public
	 * @return  string[]
	 */
	public function getEditions()
	{
		return $this->getFieldArray('250');
	}

	/**
	 * Get the first value matching the specified MARC field and subfields.
	 * If multiple subfields are specified, they will be concatenated together.
	 *
	 * @param   string $field The MARC field to read
	 * @param   array $subfields The MARC subfield codes to read
	 * @access  private
	 * @return  string
	 */
	private function getFirstFieldValue($field, $subfields = null)
	{
		$matches = $this->getFieldArray($field, $subfields);
		return (is_array($matches) && count($matches) > 0) ? $matches[0] : null;
	}

	/**
	 * Get the item's places of publication.
	 *
	 * @access  protected
	 * @return  array
	 */
	function getPlacesOfPublication()
	{
		$placesOfPublication = $this->getFieldArray('260', array('a'));
		$placesOfPublication2 = $this->getFieldArray('264', array('a'));
		return array_merge($placesOfPublication, $placesOfPublication2);
	}


	/**
	 * Get an array of all series names containing the record.  Array entries may
	 * be either the name string, or an associative array with 'name' and 'number'
	 * keys.
	 *
	 * @access  public
	 * @return  array
	 */
	public function getSeries()
	{
		$seriesInfo = $this->getGroupedWorkDriver()->getSeries();
		if ($seriesInfo == null || count($seriesInfo) == 0) {
			// First check the 440, 800 and 830 fields for series information:
			$primaryFields = array(
					'440' => array('a', 'p'),
					'800' => array('a', 'b', 'c', 'd', 'f', 'p', 'q', 't'),
					'830' => array('a', 'p'));
			$matches = $this->getSeriesFromMARC($primaryFields);
			if (!empty($matches)) {
				return $matches;
			}

			// Now check 490 and display it only if 440/800/830 were empty:
			$secondaryFields = array('490' => array('a'));
			$matches = $this->getSeriesFromMARC($secondaryFields);
			if (!empty($matches)) {
				return $matches;
			}
		}
		return $seriesInfo;
	}

	/**
	 * Support method for getSeries() -- given a field specification, look for
	 * series information in the MARC record.
	 *
	 * @access  private
	 * @param   $fieldInfo  array           Associative array of field => subfield
	 *                                      information (used to find series name)
	 * @return  array                       Series data (may be empty)
	 */
	private function getSeriesFromMARC($fieldInfo)
	{
		$matches = array();

		// Loop through the field specification....
		foreach ($fieldInfo as $field => $subfields) {
			// Did we find any matching fields?
			$series = $this->getMarcRecord()->getFields($field);
			if (is_array($series)) {
				foreach ($series as $currentField) {
					// Can we find a name using the specified subfield list?
					$name = $this->getSubfieldArray($currentField, $subfields);
					if (isset($name[0])) {
						$currentArray = array('seriesTitle' => $name[0]);

						// Can we find a number in subfield v?  (Note that number is
						// always in subfield v regardless of whether we are dealing
						// with 440, 490, 800 or 830 -- hence the hard-coded array
						// rather than another parameter in $fieldInfo).
						$number = $this->getSubfieldArray($currentField, array('v'));
						if (isset($number[0])) {
							$currentArray['volume'] = $number[0];
						}

						// Save the current match:
						$matches[] = $currentArray;
					}
				}
			}
		}

		return $matches;
	}

	/**
	 * Return an array of non-empty subfield values found in the provided MARC
	 * field.  If $concat is true, the array will contain either zero or one
	 * entries (empty array if no subfields found, subfield values concatenated
	 * together in specified order if found).  If concat is false, the array
	 * will contain a separate entry for each subfield value found.
	 *
	 * @access  private
	 * @param   object $currentField $result from File_MARC::getFields.
	 * @param   array $subfields The MARC subfield codes to read
	 * @param   bool $concat Should we concatenate subfields?
	 * @return  string[]
	 */
	private function getSubfieldArray($currentField, $subfields, $concat = true)
	{
		// Start building a line of text for the current field
		$matches = array();
		$currentLine = '';

		// Loop through all specified subfields, collecting results:
		foreach ($subfields as $subfield) {
			/** @var File_MARC_Subfield[] $subfieldsResult */
			$subfieldsResult = $currentField->getSubfields($subfield);
			if (is_array($subfieldsResult)) {
				foreach ($subfieldsResult as $currentSubfield) {
					// Grab the current subfield value and act on it if it is
					// non-empty:
					$data = trim($currentSubfield->getData());
					if (!empty($data)) {
						// Are we concatenating fields or storing them separately?
						if ($concat) {
							$currentLine .= $data . ' ';
						} else {
							$matches[] = $data;
						}
					}
				}
			}
		}

		// If we're in concat mode and found data, it will be in $currentLine and
		// must be moved into the matches array.  If we're not in concat mode,
		// $currentLine will always be empty and this code will be ignored.
		if (!empty($currentLine)) {
			$matches[] = trim($currentLine);
		}

		// Send back our $result array:
		return $matches;
	}

	/**
	 * @param File_MARC_Data_Field $marcField
	 * @param string $subField
	 * @return string
	 */
	public function getSubfieldData($marcField, $subField)
	{
		if ($marcField) {
			return $marcField->getSubfield($subField) ? $marcField->getSubfield($subField)->getData() : '';
		} else {
			return '';
		}
	}

	/**
	 * Get the full title of the record.
	 *
	 * @return  string
	 */
	public function getTitle()
	{
		return $this->getFirstFieldValue('245', array('a', 'b', 'f', 'g', 'n', 'p'));
	}

	/**
	 * Get the uniform title of the record.
	 *
	 * @return  array
	 */
	/** @noinspection PhpUnused */
	public function getUniformTitle()
	{
		return $this->getFieldArray('240', array('a', 'd', 'f', 'g', 'h', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's'));
	}

	/**
	 * Get the full title of the record.
	 *
	 * @return  string
	 */
	public function getShortTitle()
	{
		return $this->getFirstFieldValue('245', array('a'));
	}

	/**
	 * Get the full title of the record.
	 *
	 * @return  string
	 */
	public function getSortableTitle()
	{
		/** @var File_MARC_Data_Field $titleField */
		if ($this->getMarcRecord() != null) {
			$titleField = $this->getMarcRecord()->getField('245');
			if ($titleField != null) {
				$subFieldA = $titleField->getSubfield('a');
				if ($subFieldA != null && $titleField->getSubfield('a') != false) {
					$untrimmedTitle = $subFieldA->getData();
					$charsToTrim = $titleField->getIndicator(2);
					if (is_numeric($charsToTrim)) {
						return substr($untrimmedTitle, $charsToTrim);
					} else {
						return $untrimmedTitle;
					}
				}
			}
		}
		return 'Unknown';
	}

	/**
	 * Get the title of the record.
	 *
	 * @return  string
	 */
	public function getSubtitle()
	{
		return $this->getFirstFieldValue('245', array('b'));
	}

	/**
	 * Get the text of the part/section portion of the title.
	 *
	 * @access  protected
	 * @return  string
	 */
	/** @noinspection PhpUnused */
	public function getTitleSection()
	{
		return $this->getFirstFieldValue('245', array('n', 'p'));
	}

	public function getPrimaryAuthor()
	{
		return $this->getAuthor();
	}

	public function getAuthor()
	{
		$author = $this->getFirstFieldValue('100', array('a', 'd'));
		if (empty($author)) {
			$author = $this->getFirstFieldValue('110', array('a', 'b'));
		}
		return $author;
	}

	public function getContributors()
	{
		return $this->getFieldArray(700, array('a', 'b', 'c', 'd'));
	}

	private $detailedContributors = null;

	/** @noinspection PhpUnused */
	public function getDetailedContributors()
	{
		if ($this->detailedContributors == null) {
			$this->detailedContributors = array();
			/** @var File_MARC_Data_Field[] $sevenHundredFields */
			$sevenHundredFields = $this->getMarcRecord()->getFields('700|710', true);
			foreach ($sevenHundredFields as $field) {
				$nameSubfieldArray = $this->getSubfieldArray($field, array('a', 'b', 'c', 'd'), true);
				$titleSubfieldArray = $this->getSubfieldArray($field, array('t', 'm', 'n', 'r'), true);
				$curContributor = array(
					'name' => reset($nameSubfieldArray),
					'title' => reset($titleSubfieldArray),
					'roles' => []
				);
				if ($field->getSubfield('4') != null) {
					$contributorRole = $field->getSubfield('4')->getData();
					$contributorRole = preg_replace('/[\s,.;]+$/', '', $contributorRole);
					$curContributor['roles'][] = mapValue('contributor_role', $contributorRole);
				} elseif ($field->getSubfield('e') != null) {
					$curContributor['roles'][] = $field->getSubfield('e')->getData();
				}
				ksort($curContributor['roles']);
				$this->detailedContributors[] = $curContributor;
			}
		}
		return $this->detailedContributors;
	}

	function getDescriptionFast()
	{
		/** @var File_MARC_Data_Field $descriptionField */
		if ($this->getMarcRecord()) {
			$descriptionField = $this->getMarcRecord()->getField('520');
			if ($descriptionField != null && $descriptionField->getSubfield('a') != null) {
				return $descriptionField->getSubfield('a')->getData();
			}
		}
		return null;
	}

	function getDescription()
	{
		global $interface;
		global $library;

		$useMarcSummary = true;
		$summary = '';
		$isbn = $this->getCleanISBN();
		$upc = $this->getCleanUPC();
		if ($isbn || $upc) {
			if ($library->getGroupedWorkDisplaySettings()->preferSyndeticsSummary == 1) {
				require_once ROOT_DIR . '/Drivers/marmot_inc/GoDeeperData.php';
				$summaryInfo = GoDeeperData::getSummary($this->getPermanentId(), $isbn, $upc);
				if (isset($summaryInfo['summary'])) {
					$summary = $summaryInfo['summary'];
					$useMarcSummary = false;
				}
			}
		}
		if ($useMarcSummary && $this->marcRecord != false) {
			if ($summaryFields = $this->marcRecord->getFields('520')) {
				$summaries = array();
				$summary = '';
				foreach ($summaryFields as $summaryField) {
					//Check to make sure we don't have an exact duplicate of this field
					$curSummary = $this->getSubfieldData($summaryField, 'a');
					$okToAdd = true;
					foreach ($summaries as $existingSummary) {
						if ($existingSummary == $curSummary) {
							$okToAdd = false;
							break;
						}
					}
					if ($okToAdd) {
						$summaries[] = $curSummary;
						$summary .= '<p>' . $curSummary . '</p>';
					}
				}
				$interface->assign('summary', $summary);
				$interface->assign('summaryTeaser', strip_tags($summary));
			} elseif ($library->getGroupedWorkDisplaySettings()->preferSyndeticsSummary == 0) {
				require_once ROOT_DIR . '/Drivers/marmot_inc/GoDeeperData.php';
				$summaryInfo = GoDeeperData::getSummary($this->getPermanentId(), $isbn, $upc);
				if (isset($summaryInfo['summary'])) {
					$summary = $summaryInfo['summary'];
				}
			}
		}
		if (strlen($summary) == 0) {
			$summary = $this->getGroupedWorkDriver()->getDescriptionFast();
		}

		return $summary;
	}

	/**
	 * @param File_MARC_Record $marcRecord
	 * @param bool $allowExternalDescription
	 *
	 * @return array|string
	 */
	/** @noinspection PhpUnused */
	function loadDescriptionFromMarc($marcRecord, $allowExternalDescription = true)
	{
		global $memCache;
		global $configArray;

		if (!$this->getMarcRecord()) {
			$descriptionArray = array();
			$description = translate(['text' => "Description Not Provided", 'isPublicFacing'=>true]);
			$descriptionArray['description'] = $description;
			return $descriptionArray;
		}

		// Get ISBN for cover and review use
		$isbn = null;
		/** @var File_MARC_Data_Field[] $isbnFields */
		if ($isbnFields = $marcRecord->getFields('020')) {
			//Use the first good ISBN we find.
			foreach ($isbnFields as $isbnField) {
				if ($isbnSubfieldA = $isbnField->getSubfield('a')) {
					$tmpIsbn = trim($isbnSubfieldA->getData());
					if (strlen($tmpIsbn) > 0) {
						$pos = strpos($tmpIsbn, ' ');
						if ($pos > 0) {
							$tmpIsbn = substr($tmpIsbn, 0, $pos);
						}
						$tmpIsbn = trim($tmpIsbn);
						if (strlen($tmpIsbn) > 0) {
							if (strlen($tmpIsbn) < 10) {
								$tmpIsbn = str_pad($tmpIsbn, 10, "0", STR_PAD_LEFT);
							}
							$isbn = $tmpIsbn;
							break;
						}
					}
				}
			}
		}

		$upc = null;
		/** @var File_MARC_Data_Field $upcField */
		if ($upcField = $marcRecord->getField('024')) {
			if ($upcSubfield = $upcField->getSubfield('a')) {
				$upc = trim($upcSubfield->getData());
			}
		}

		$descriptionArray = $memCache->get("record_description_{$isbn}_{$upc}_{$allowExternalDescription}");
		if (!$descriptionArray) {
			$marcDescription = null;
			/** @var File_MARC_Data_Field $descriptionField */
			if ($descriptionField = $marcRecord->getField('520')) {
				if ($descriptionSubfield = $descriptionField->getSubfield('a')) {
					$description = trim($descriptionSubfield->getData());
					$marcDescription = $this->trimDescription($description);
				}
			}

			//Load the description
			//Check to see if there is a description in Syndetics and use that instead if available
			$useMarcSummary = true;
			if ($allowExternalDescription) {
				if (!is_null($isbn) || !is_null($upc)) {
					require_once ROOT_DIR . '/Drivers/marmot_inc/GoDeeperData.php';
					$summaryInfo = GoDeeperData::getSummary($this->getPermanentId(), $isbn, $upc);
					if (isset($summaryInfo['summary'])) {
						$descriptionArray['description'] = $this->trimDescription($summaryInfo['summary']);
						$useMarcSummary = false;
					}
				}
			}

			if ($useMarcSummary) {
				if ($marcDescription != null) {
					$descriptionArray['description'] = $marcDescription;
				} else {
					$description = translate(['text' => "Description Not Provided", 'isPublicFacing'=>true]);
					$descriptionArray['description'] = $description;
				}
			}

			$memCache->set("record_description_{$isbn}_{$upc}_{$allowExternalDescription}", $descriptionArray, $configArray['Caching']['record_description']);
		}
		return $descriptionArray;
	}

	private function trimDescription($description)
	{
		$chars = 300;
		if (strlen($description) > $chars) {
			$description = $description . " ";
			$description = substr($description, 0, $chars);
			$description = substr($description, 0, strrpos($description, ' '));
			$description = $description . "...";
		}
		return $description;
	}

	function getLanguage()
	{
		/** @var File_MARC_Control_Field $field008 */
		$field008 = $this->getMarcRecord()->getField('008');
		if ($field008 != null && strlen($field008->getData() >= 37)) {
			$languageCode = substr($field008->getData(), 35, 3);
			if ($languageCode == 'eng') {
				$languageCode = "English";
			} elseif ($languageCode == 'spa') {
				$languageCode = "Spanish";
			}
			return $languageCode;
		} else {
			return 'English';
		}
	}

	function getFormats()
	{
		return $this->getFormat();
	}

	/**
	 * Load the format for the record based off of information stored within the grouped work.
	 * Which was calculated at index time.
	 *
	 * @return string[]
	 */
	function getFormat()
	{
		//Rather than loading formats here, let's leverage the work we did at index time
		$relatedRecord = $this->getGroupedWorkDriver()->getRelatedRecord($this->getIdWithSource());
		if ($relatedRecord != null){
			return array($relatedRecord->format);
		} else {
			$recordDetails = $this->getGroupedWorkDriver()->getSolrField('record_details');
			if ($recordDetails) {
				if (!is_array($recordDetails)) {
					$recordDetails = array($recordDetails);
				}
				foreach ($recordDetails as $recordDetailRaw) {
					$recordDetail = explode('|', $recordDetailRaw);
					if ($recordDetail[0] == $this->getIdWithSource()) {
						return array($recordDetail[1]);
					}
				}
				//We did not find a record for this in the index.  It's probably been deleted.
				return array('Unknown');
			} else {
				return array('Unknown');
			}
		}
	}

	function isClosedCaptioned() {
		$relatedRecord = $this->getGroupedWorkDriver()->getRelatedRecord($this->getIdWithSource());
		if ($relatedRecord != null){
			return $relatedRecord->closedCaptioned;
		} else {
			return false;
		}
	}

	function getFormatCategory()
	{
		return $this->getGroupedWorkDriver()->getFormatCategory();
	}

    function getRecordUrl()
	{
		$recordId = $this->getUniqueID();
		return "/" . $this->getModule() . "/$recordId";
	}

	protected $_actions = null;
	public function getRecordActions($relatedRecord, $isAvailable, $isHoldable, $volumeData = null)
	{
		if ($this->_actions === null) {
			$this->_actions = array();
			global $interface;

			if (UserAccount::isLoggedIn()) {
				$user = UserAccount::getActiveUserObj();
				$this->_actions = array_merge($this->_actions, $user->getCirculatedRecordActions($this->getIndexingProfile()->name, $this->id));
			}

			$treatVolumeHoldsAsItemHolds = $this->getCatalogDriver()->treatVolumeHoldsAsItemHolds();

			if (isset($interface)) {
				global $library;
				$searchLocation = Location::getSearchLocation(null);

				if ($searchLocation) {
					$show856LinksAsAccessOnlineButtons = $searchLocation->getGroupedWorkDisplaySettings()->show856LinksAsAccessOnlineButtons;
				} else {
					$show856LinksAsAccessOnlineButtons = $library->getGroupedWorkDisplaySettings()->show856LinksAsAccessOnlineButtons;
				}

				if ($show856LinksAsAccessOnlineButtons){
					//Get any 856 links for the marc record
					$validUrls = $this->getViewable856Links();
					if (count($validUrls) == 1){
						$this->_actions[] = array(
							'title' => translate(['text' => 'Access Online', 'isPublicFacing'=>true]),
							'url' => $validUrls[0]['url'],
							'requireLogin' => false,
							'type' => 'marc_access_online',
							'target' => '_blank'
						);
					}elseif (count($validUrls) >= 2){
//						$this->_actions[] = array(
//							'title' => translate(['text' => 'Access Online', 'isPublicFacing'=>true]),
//							'url' => $validUrls[0],
//							'requireLogin' => false,
//							'type' => 'marc_access_online',
//							'target' => '_blank'
//						);
						$this->_actions[] = array(
							'title' => translate(['text' => 'Access Online', 'isPublicFacing'=>true]),
							'url' => "",
							'onclick' => "return AspenDiscovery.Record.select856Link('{$this->getId()}')",
							'requireLogin' => false,
							'type' => 'marc_access_online'
						);
					}
				}

				if ($interface->getVariable('displayingSearchResults')) {
					$showHoldButton = $interface->getVariable('showHoldButtonInSearchResults');
				} else {
					$showHoldButton = $interface->getVariable('showHoldButton');
				}

				if ($showHoldButton && $interface->getVariable('offline')) {
					$showHoldButton = false;
				}

				if ($showHoldButton && $isAvailable) {
					$showHoldButton = !$interface->getVariable('showHoldButtonForUnavailableOnly');
				}
			} else {
				$showHoldButton = false;
			}

			if ($isHoldable && $showHoldButton) {
				$source = $this->profileType;
				$id = $this->id;
				if ($volumeData == null) {
					$volumeData = $relatedRecord->getVolumeData();
				}
				//See if we have VDX integration. If so, we will either be placing a hold or requesting depending on if there is a copy local to the hold group (whether available or not)
				$useVdxForRecord = false;
				$vdxGroupsForLocation = [];
				try {
					require_once ROOT_DIR . '/sys/VDX/VdxSetting.php';
					$vdxSettings = new VdxSetting();
					if ($vdxSettings->find(true)) {
						require_once ROOT_DIR . '/sys/VDX/VdxHoldGroup.php';
						require_once ROOT_DIR . '/sys/VDX/VdxHoldGroupLocation.php';
						$useVdxForRecord = true;
						$homeLocation = Location::getDefaultLocationForUser();
						if ($homeLocation != null) {
							//Get the VDX Group(s) that we will interact with
							$vdxGroupsForLocation = new VdxHoldGroupLocation();
							$vdxGroupsForLocation->locationId = $homeLocation->locationId;
							$vdxGroupIds = $vdxGroupsForLocation->fetchAll('vdxHoldGroupId');
							$vdxGroups = [];
							foreach ($vdxGroupIds as $vdxGroupId) {
								$vdxGroup = new VdxHoldGroup();
								$vdxGroup->id = $vdxGroupId;
								if ($vdxGroup->find(true)) {
									$vdxGroups[] = clone $vdxGroup;
								}
							}

							//Check to see if we have any items that are owned by any of the records in any of the groups.
							//If we do, we don't need to use VDX
							foreach ($relatedRecord->getItems() as $itemDetail) {
								//Only check holdable items
								if ($itemDetail->holdable) {
									//The patron's home location is always valid!
									if ($itemDetail->locationCode == $homeLocation->code){
										$useVdxForRecord = false;
										break;
									}

									foreach ($vdxGroups as $vdxGroup) {
										if (in_array($itemDetail->locationCode, $vdxGroup->getLocationCodes())) {
											$useVdxForRecord = false;
											break;
										}
									}
								}
								if (!$useVdxForRecord) {
									break;
								}
							}
						}
					}
				}catch (Exception $e){
					//This happens if the tables are not installed yet
				}
				if (!$useVdxForRecord){
					if (!is_null($volumeData) && count($volumeData) > 0 && !$treatVolumeHoldsAsItemHolds) {
						//Check the items to see which volumes are holdable
						$hasItemsWithoutVolumes = false;
						$holdableVolumes = [];
						foreach ($relatedRecord->getItems() as $itemDetail) {
							if ($itemDetail->holdable) {
								if (!empty($itemDetail->volumeId)) {
									$holdableVolumes[str_pad($itemDetail->volumeOrder, 10, '0', STR_PAD_LEFT) . $itemDetail->volumeId] = ['volumeName' => $itemDetail->volume, 'volumeId'=>$itemDetail->volumeId];
								} else {
									$hasItemsWithoutVolumes = true;
								}
							}
						}
						if (count($holdableVolumes) > 3 || $hasItemsWithoutVolumes) {
							//Show a dialog to enable the patron to select a volume to place a hold on
							$this->_actions[] = array(
								'title' => translate(['text' => 'Place Hold', 'isPublicFacing'=>true]),
								'url' => '',
								'onclick' => "return AspenDiscovery.Record.showPlaceHoldVolumes('{$this->getModule()}', '$source', '$id');",
								'requireLogin' => false,
								'type' => 'ils_hold'
							);
						} else {
							ksort($holdableVolumes);
							foreach ($holdableVolumes as $volumeId => $volumeInfo) {
								$this->_actions[] = array(
									'title' => translate(['text' => 'Hold %1%', 1=> $volumeInfo['volumeName'], 'isPublicFacing'=>true]),
									'url' => '',
									'onclick' => "return AspenDiscovery.Record.showPlaceHold('{$this->getModule()}', '$source', '$id', '{$volumeInfo['volumeId']}');",
									'requireLogin' => false,
									'type' => 'ils_hold'
								);
							}
						}
					} else {
						$this->_actions[] = array(
							'title' => translate(['text' => 'Place Hold', 'isPublicFacing' => true]),
							'url' => '',
							'onclick' => "return AspenDiscovery.Record.showPlaceHold('{$this->getModule()}', '$source', '$id');",
							'requireLogin' => false,
							'type' => 'ils_hold'
						);
					}
				}else{
					$this->_actions[] = array(
						'title' => translate(['text' => 'Request', 'isPublicFacing'=>true]),
						'url' => '',
						'onclick' => "return AspenDiscovery.Record.showVdxRequest('{$this->getModule()}', '$source', '$id');",
						'requireLogin' => false,
						'type' => 'vdx_request'
					);
				}
			}

			//Check to see if a PDF has been uploaded for the record
			$uploadedPDFs = $this->getUploadedPDFs();
			if (count($uploadedPDFs) > 0) {
				if (count($uploadedPDFs) == 1) {
					$recordFile = reset($uploadedPDFs);
					$this->_actions[] = array(
						'title' => translate(['text' => 'View PDF', 'isPublicFacing'=>true]),
						'url' => "/Files/{$recordFile->id}/ViewPDF",
						'requireLogin' => false,
						'type' => 'view_pdf'
					);
					$this->_actions[] = array(
						'title' => translate(['text' => 'Download PDF', 'isPublicFacing'=>true]),
						'url' => "/Record/{$this->getId()}/DownloadPDF?fileId={$recordFile->id}",
						'requireLogin' => false,
						'type' => 'download_pdf'
					);
				} else {
					$this->_actions[] = array(
						'title' => 'View PDF',
						'url' => '',
						'onclick' => "return AspenDiscovery.Record.selectFileToView('{$this->getId()}', 'RecordPDF');",
						'requireLogin' => false,
						'type' => 'view_pdf'
					);
					$this->_actions[] = array(
						'title' => 'Download PDF',
						'url' => '',
						'onclick' => "return AspenDiscovery.Record.selectFileDownload('{$this->getId()}', 'RecordPDF');",
						'requireLogin' => false,
						'type' => 'download_pdf'
					);
				}
			}

			//Check to see if a Supplemental Files have been uploaded for the record
			$supplementalFiles = $this->getUploadedSupplementalFiles();
			if (count($supplementalFiles) > 0) {
				if (count($supplementalFiles) == 1) {
					$recordFile = reset($supplementalFiles);
					$this->_actions[] = array(
						'title' => 'Download Supplemental File',
						'url' => "/Record/{$this->getId()}/DownloadSupplementalFile?fileId={$recordFile->id}",
						'requireLogin' => false,
						'type' => 'download_supplemental_file'
					);
				} else {
					$this->_actions[] = array(
						'title' => 'Download Supplemental File',
						'url' => '',
						'onclick' => "return AspenDiscovery.Record.selectFileDownload('{$this->getId()}', 'RecordSupplementalFile');",
						'requireLogin' => false,
						'type' => 'download_supplemental_file'
					);
				}
			}

			global $timer;
			$timer->logTime("Done loading actions for MarcRecordDriver");
		}

		return $this->_actions;
	}

	private $catalogDriver = null;

	/**
	 * @return AbstractIlsDriver
	 */
	public function getCatalogDriver()
	{
		if ($this->catalogDriver == null) {
			try {
				$indexingProfile = $this->getIndexingProfile();
				$accountProfileForSource = new AccountProfile();
				$accountProfileForSource->recordSource = $indexingProfile->name;
				require_once ROOT_DIR . '/CatalogFactory.php';
				if ($accountProfileForSource->find(true)){
					$this->catalogDriver = CatalogFactory::getCatalogConnectionInstance($accountProfileForSource->driver, $accountProfileForSource);
				}else {
					$this->catalogDriver = CatalogFactory::getCatalogConnectionInstance();
				}
			} catch (PDOException $e) {
				// What should we do with this error?
				if (IPAddress::showDebuggingInformation()) {
					echo '<pre>';
					echo 'DEBUG: ' . $e->getMessage();
					echo '</pre>';
				}
				return null;
			}
		}
		return $this->catalogDriver->driver;
	}

	/**
	 * Get an array of physical descriptions of the item.
	 *
	 * @access  protected
	 * @return  array
	 */
	/** @noinspection PhpUnused */
	public function getPhysicalDescriptions()
	{
		$physicalDescription1 = $this->getFieldArray("300", array('a', 'b', 'c', 'e', 'f', 'g'));
		$physicalDescription2 = $this->getFieldArray("530", array('a', 'b', 'c', 'd'));
		return array_merge($physicalDescription1, $physicalDescription2);
	}

	/**
	 * Get the publication dates of the record.
	 *
	 * @access  public
	 * @return  array
	 */
	public function getPublicationDates()
	{
		$publicationDates = array();
		if ($this->isValid()) {
			$publicationDates = $this->getFieldArray('260', array('c'));
			$marcRecord = $this->getMarcRecord();
			if ($marcRecord != false) {
				/** @var File_MARC_Data_Field[] $rdaPublisherFields */
				$rdaPublisherFields = $marcRecord->getFields('264');
				foreach ($rdaPublisherFields as $rdaPublisherField) {
					if (($rdaPublisherField->getIndicator(2) == 1 || $rdaPublisherField->getIndicator(2) == ' ') && $rdaPublisherField->getSubfield('c') != null) {
						$publicationDates[] = $rdaPublisherField->getSubfield('c')->getData();
					}
				}
				foreach ($publicationDates as $key => $publicationDate) {
					$publicationDates[$key] = preg_replace('/[.,]$/', '', $publicationDate);
				}
			}
		}

		return $publicationDates;
	}

    /**
     * Get the publishers of the record.
     *
     * @return  array
     */
	function getPublishers()
	{
		$marcRecord = $this->getMarcRecord();
		if ($marcRecord != null) {
			$publishers = $this->getFieldArray('260', array('b'));
			/** @var File_MARC_Data_Field[] $rdaPublisherFields */
			$rdaPublisherFields = $marcRecord->getFields('264');
			foreach ($rdaPublisherFields as $rdaPublisherField) {
				if (($rdaPublisherField->getIndicator(2) == 1 || $rdaPublisherField->getIndicator(2) == ' ') && $rdaPublisherField->getSubfield('b') != null) {
					$publishers[] = $rdaPublisherField->getSubfield('b')->getData();
				}
			}
			foreach ($publishers as $key => $publisher) {
				$publishers[$key] = preg_replace('/[.,]$/', '', $publisher);
			}
		} else {
			$publishers = array();
		}
		return $publishers;
	}

	private $isbns = null;

	/**
	 * Get an array of all ISBNs associated with the record (may be empty).
	 *
	 * @access  protected
	 * @return  array
	 */
	public function getISBNs()
	{
		if ($this->isbns == null) {
			// If ISBN is in the index, it should automatically be an array... but if
			// it's not set at all, we should normalize the value to an empty array.
			$isbns = array();
			/** @var File_MARC_Data_Field[] $isbnFields */
			if ($this->isValid()) {
				$marcRecord = $this->getMarcRecord();
				if ($marcRecord != null) {
					$isbnFields = $this->getMarcRecord()->getFields('020');
					foreach ($isbnFields as $isbnField) {
						if ($isbnField->getSubfield('a') != null) {
							$isbns[] = $isbnField->getSubfield('a')->getData();
						}
					}
				}
			}
			$this->isbns = $isbns;
		}
		return $this->isbns;
	}

	private $issns = null;

	/**
	 * Get an array of all ISSNs associated with the record (may be empty).
	 *
	 * @access  protected
	 * @return  array
	 */
	public function getISSNs()
	{
		if ($this->issns == null) {
			$issns = array();
			/** @var File_MARC_Data_Field[] $isbnFields */
			if ($this->isValid()) {
				$marcRecord = $this->getMarcRecord();
				if ($marcRecord != null) {
					$isbnFields = $this->getMarcRecord()->getFields('022');
					foreach ($isbnFields as $isbnField) {
						if ($isbnField->getSubfield('a') != null) {
							$issns[] = $isbnField->getSubfield('a')->getData();
						}
					}
				}
			}
			$this->issns = $issns;
		}
		return $this->issns;
	}

	private $upcs = null;
	/**
	 * Get the UPC associated with the record (may be empty).
	 *
	 * @return  array
	 */
	public function getUPCs()
	{
		if ($this->upcs == null) {
			// If UPCs is in the index, it should automatically be an array... but if
			// it's not set at all, we should normalize the value to an empty array.
			$this->upcs = array();
			/** @var File_MARC_Data_Field[] $upcFields */
			$marcRecord = $this->getMarcRecord();
			if ($marcRecord != false) {
				$upcFields = $marcRecord->getFields('024');
				foreach ($upcFields as $upcField) {
					if ($upcField->getSubfield('a') != null) {
						$this->upcs[] = $upcField->getSubfield('a')->getData();
					}
				}
			}
		}

		return $this->upcs;
	}

	public function getMoreDetailsOptions()
	{
		global $interface;
		global $library;

		$isbn = $this->getCleanISBN();

		//Load table of contents
		$tableOfContents = $this->getTableOfContents();
		$interface->assign('tableOfContents', $tableOfContents);

		//Load more details options
		$moreDetailsOptions = $this->getBaseMoreDetailsOptions($isbn);

		//Get copies for the record
		$this->assignCopiesInformation();

		//If this is a periodical we may have additional information
		$isPeriodical = false;
		foreach ($this->getFormats() as $format) {
			if ($format == 'Journal' || $format == 'Newspaper' || $format == 'Print Periodical' || $format == 'Magazine') {
				$isPeriodical = true;
				break;
			}
		}
		if ($isPeriodical) {
			global $library;
			$interface->assign('showCheckInGrid', $library->getGroupedWorkDisplaySettings()->showCheckInGrid);
			$issues = $this->loadPeriodicalInformation();
			$interface->assign('periodicalIssues', $issues);
		}
		$links = $this->getLinks();
		$interface->assign('links', $links);
		$interface->assign('show856LinksAsTab', $library->getGroupedWorkDisplaySettings()->show856LinksAsTab);
		$interface->assign('showItemDueDates', $library->getGroupedWorkDisplaySettings()->showItemDueDates);

		if ($library->getGroupedWorkDisplaySettings()->show856LinksAsTab && count($links) > 0) {
			$moreDetailsOptions['links'] = array(
				'label' => 'Links',
				'body' => $interface->fetch('Record/view-links.tpl'),
			);
		}
		$moreDetailsOptions['copies'] = array(
			'label' => 'Copies',
			'body' => $interface->fetch('Record/view-holdings.tpl'),
			'openByDefault' => true
		);
		//Other editions if applicable (only if we aren't the only record!)
		$groupedWorkDriver = $this->getGroupedWorkDriver();
		if ($groupedWorkDriver != null){
			$relatedRecords = $groupedWorkDriver->getRelatedRecords();
			if (count($relatedRecords) > 1) {
				$interface->assign('relatedManifestations', $groupedWorkDriver->getRelatedManifestations());
				$interface->assign('workId',$groupedWorkDriver->getPermanentId());
				$moreDetailsOptions['otherEditions'] = array(
					'label' => 'Other Editions and Formats',
					'body' => $interface->fetch('GroupedWork/relatedManifestations.tpl'),
					'hideByDefault' => false
				);
			}
		}

		$moreDetailsOptions['moreDetails'] = array(
			'label' => 'More Details',
			'body' => $interface->fetch('Record/view-more-details.tpl'),
		);
		$this->loadSubjects();
		$moreDetailsOptions['subjects'] = array(
			'label' => 'Subjects',
			'body' => $interface->fetch('Record/view-subjects.tpl'),
		);
		$moreDetailsOptions['citations'] = array(
			'label' => 'Citations',
			'body' => $interface->fetch('Record/cite.tpl'),
		);

		//Check to see if the record has parents
		$parentRecords = $this->getParentRecords();
		if (count($parentRecords) > 0){
			$interface->assign('parentRecords', $parentRecords);
			$moreDetailsOptions['parentRecords'] = array(
				'label' => 'Part Of',
				'body' => $interface->fetch('Record/view-containing-records.tpl'),
			);
		}

		//Check to see if the record has children
		$childRecords = $this->getChildRecords();
		if (count($childRecords) > 0){
			$interface->assign('childRecords', $childRecords);
			$moreDetailsOptions['childRecords'] = array(
				'label' => 'Contains',
				'body' => $interface->fetch('Record/view-contained-records.tpl'),
			);
		}

		//Check to see if the record has marc holdings (in 852, 853, 866)
		$marcHoldings = $this->getMarcHoldings();
		if (count($marcHoldings) > 0){
			$interface->assign('marcHoldings', $marcHoldings);
			$moreDetailsOptions['marcHoldings'] = array(
				'label' => 'Library Holdings',
				'body' => $interface->fetch('Record/view-marc-holdings.tpl'),
			);
		}

		if ($interface->getVariable('showStaffView')) {
			$moreDetailsOptions['staff'] = array(
				'label' => 'Staff View',
				'onShow' => "AspenDiscovery.Record.getStaffView('{$this->getModule()}', '{$this->id}');",
				'body' => '<div id="staffViewPlaceHolder">' . translate(['text'=>'Loading Staff View.', 'isPublicFacing' => true]) . '</div>',
			);
		}

		return $this->filterAndSortMoreDetailsOptions($moreDetailsOptions);
	}

	public function getChildRecords(){
		require_once ROOT_DIR . '/sys/ILS/RecordParent.php';
		$parentChildRecords = new RecordParent();
		$parentChildRecords->parentRecordId = $this->id;
		$parentChildRecords->orderBy('childTitle ASC');
		$parentChildRecords->find();
		$childRecords = [];
		if ($parentChildRecords->getNumResults() > 0){
			while ($parentChildRecords->fetch()){
				$childRecords[] = [
					'id' => $parentChildRecords->childRecordId,
					'label' => empty($parentChildRecords->childTitle) ? $parentChildRecords->childRecordId : $parentChildRecords->childTitle,
					'link' => '/Record/' . $parentChildRecords->childRecordId . '/Home',
				];
			}
		}
		return $childRecords;
	}

	public function getParentRecords(){
		require_once ROOT_DIR . '/sys/ILS/RecordParent.php';
		$parentChildRecords = new RecordParent();
		$parentChildRecords->childRecordId = $this->id;
		$parentChildRecords->find();
		$parentRecords = [];
		if ($parentChildRecords->getNumResults() > 0){
			while ($parentChildRecords->fetch()){
				//TODO: Store the title in the database so we can load it more quickly here
				require_once ROOT_DIR . '/sys/Grouping/GroupedWorkRecord.php';
				$parentTitle = $parentChildRecords->parentRecordId;
				$parentRecord = new GroupedWorkRecord();
				$parentRecord->recordIdentifier = $parentChildRecords->parentRecordId;
				if ($parentRecord->find(true)){
					require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
					$groupedWork = new GroupedWork();
					$groupedWork->id = $parentRecord->groupedWorkId;
					if ($groupedWork->find(true)){
						$groupedWorkDriver = new GroupedWorkDriver($groupedWork->permanent_id);
						if ($groupedWorkDriver->isValid()){
							$parentTitle = $groupedWorkDriver->getTitle();
						}else{
							$parentTitle = $groupedWork->full_title;
						}
					}
				}
				$parentRecords[] = [
					'id' => $parentChildRecords->parentRecordId,
					'label' => $parentTitle,
					'link' => '/Record/' . $parentChildRecords->parentRecordId . '/Home',
				];
			}
		}
		return $parentRecords;
	}

	public function loadSubjects()
	{
		global $interface;
		global $configArray;
		global $library;
		$marcRecord = $this->getMarcRecord();
		$subjects = array();
		$otherSubjects = array();
		$lcSubjects = array();
		$bisacSubjects = array();
		$oclcFastSubjects = array();
		$localSubjects = array();
		if ($marcRecord) {
			$subjectFields = [600,610,611,630,650,651,655,690];

			$lcSubjectTagNumbers = array(600, 610, 611, 630, 650, 651, 655);
			foreach ($subjectFields as $subjectField) {
				/** @var File_MARC_Data_Field[] $marcFields */
				$marcFields = $marcRecord->getFields($subjectField);
				if ($marcFields) {
					foreach ($marcFields as $marcField) {
						$subject = array();
						//Determine the type of the subject
						$type = 'other';
						if (in_array($subjectField, $lcSubjectTagNumbers)){
							if ($marcField->getIndicator(2) == 0) {
								$type = 'lc';
							}
						}
						$subjectSource = $marcField->getSubfield('2');
						if ($subjectSource != null) {
							if ($subjectSource->getData() == 'lcgft') {
								$type = 'lc';
							} elseif (preg_match('/bisac/i', $subjectSource->getData())) {
								$type = 'bisac';
							} elseif (preg_match('/fast/i', $subjectSource->getData())) {
								$type = 'fast';
							}
						}
						if ($marcField->getTag() == '690') {
							$type = 'local';
						}

						$search = '';
						$title = '';
						foreach ($marcField->getSubFields() as $subField) {
							/** @var File_MARC_Subfield $subField */
							if ($subField->getCode() != '2' && $subField->getCode() != '0' && $subField->getCode() != '9') {
								$subFieldData = $subField->getData();
								if ($type == 'bisac' && $subField->getCode() == 'a') {
									$subFieldData = ucwords(strtolower($subFieldData));
								}
								$search .= " " . $subFieldData;
								if (strlen($title) > 0) {
									$title .= ' -- ';
								}
								$title .= $subFieldData;
							}
						}
						$search = trim($search);
						if (strlen($search) == 0){
							continue;
						}
						$subject[$title] = array(
								'search' => trim($search),
								'title' => $title,
						);
						switch ($type) {
							case 'fast' :
								// Suppress fast subjects by default
								$oclcFastSubjects[] = $subject;
								break;
							case 'local' :
								$localSubjects[] = $subject;
								$subjects[] = $subject;
								break;
							case 'bisac' :
								$bisacSubjects[] = $subject;
								$subjects[] = $subject;
								break;
							case 'lc' :
								$lcSubjects[] = $subject;
								$subjects[] = $subject;
								break;
							case 'other' :
								$otherSubjects[] = $subject;
								break;
							default :
								$subjects[] = $subject;
						}

					}
				}
			}
			
			$subjectTitleCompareFunction = function ($subjectArray0, $subjectArray1) {
				return strcasecmp(key($subjectArray0), key($subjectArray1));
			};

			usort($subjects, $subjectTitleCompareFunction);
			$interface->assign('subjects', $subjects);
			if ($library->getGroupedWorkDisplaySettings()->showLCSubjects) {
				usort($lcSubjects, $subjectTitleCompareFunction);
				$interface->assign('lcSubjects', $lcSubjects);
			}
			if ($library->getGroupedWorkDisplaySettings()->showOtherSubjects) {
				usort($otherSubjects, $subjectTitleCompareFunction);
				$interface->assign('otherSubjects', $otherSubjects);
			}
			if ($library->getGroupedWorkDisplaySettings()->showBisacSubjects) {
				usort($bisacSubjects, $subjectTitleCompareFunction);
				$interface->assign('bisacSubjects', $bisacSubjects);
			}
			if ($library->getGroupedWorkDisplaySettings()->showFastAddSubjects) {
				usort($oclcFastSubjects, $subjectTitleCompareFunction);
				$interface->assign('oclcFastSubjects', $oclcFastSubjects);
			}
			usort($localSubjects, $subjectTitleCompareFunction);
			$interface->assign('localSubjects', $localSubjects);
		}
	}

	public function getRecordType()
	{
		if ($this->profileType) {
			return $this->profileType;
		} else {
			return 'ils';
		}
	}

	/**
	 * @return File_MARC_Record
	 */
	public function getMarcRecord()
	{
		if ($this->marcRecord == null) {
			disableErrorHandler();
			try {
				$this->marcRecord = MarcLoader::loadMarcRecordByILSId("{$this->profileType}:{$this->id}");
				if ($this->marcRecord instanceof AspenError || $this->marcRecord == false) {
					$this->valid = false;
					$this->marcRecord = false;
				}else{
				    $this->valid = true;
				}
			} catch (Exception $e) {
				//Unable to load record this happens from time to time
				$this->valid = false;
				$this->marcRecord = false;
			}
			enableErrorHandler();

			global $timer;
			$timer->logTime("Finished loading marc record for {$this->id}");
		}
		return $this->marcRecord;
	}

	/**
	 * @param File_MARC_Data_Field[] $tocFields
	 * @return array
	 */
	function processTableOfContentsFields($tocFields)
	{
		$notes = array();
		foreach ($tocFields as $marcField) {
			$curNote = '';
			/** @var File_MARC_Subfield $subfield */
			foreach ($marcField->getSubfields() as $subfield) {
				$note = $subfield->getData();
				$curNote .= " " . $note;
				$curNote = trim($curNote);
				if (preg_match("/--$/", $curNote)) {
					$notes[] = $curNote;
					$curNote = '';
				} elseif (strpos($curNote, '--') !== false) {
					$brokenNotes = explode('--', $curNote);
					$notes = array_merge($notes, $brokenNotes);
					$curNote = '';
				}
			}
			if ($curNote != '') {
				$notes[] = $curNote;
			}
		}
		return $notes;
	}

	private $numHolds = -1;

	function getNumHolds() : int
	{
		if ($this->numHolds != -1) {
			return $this->numHolds;
		}
		global $configArray;
		global $timer;
		if ($configArray['Catalog']['ils'] == 'Horizon') {
			require_once ROOT_DIR . '/CatalogFactory.php';
			global $logger;
			$logger->log('fetching num of Holds from MarcRecord', Logger::LOG_DEBUG);

			$catalog = CatalogFactory::getCatalogConnectionInstance();
			if (isset($catalog->status) && $catalog->status) {
			    /** @var HorizonAPI $driver */
			    $driver = $catalog->driver;
				$this->numHolds = $driver->getNumHolds($this->getUniqueID());
			} else {
				$this->numHolds = 0;
			}
		} else {
			require_once ROOT_DIR . '/sys/ILS/IlsHoldSummary.php';
			$holdSummary = new IlsHoldSummary();
			$holdSummary->ilsId = $this->getUniqueID();
			if ($holdSummary->find(true)) {
				$this->numHolds = $holdSummary->numHolds;
			} else {
				$this->numHolds = 0;
			}
		}

		$timer->logTime("Loaded number of holds");
		return $this->numHolds;
	}

	/**
	 * @param IlsVolumeInfo[] $volumeData
	 * @return array
	 */
	function getVolumeHolds($volumeData)
	{
		$holdInfo = null;
		if (count($volumeData) > 0) {
			$holdInfo = array();
			foreach ($volumeData as $volumeInfo) {
				$ilsHoldInfo = new IlsHoldSummary();
				$ilsHoldInfo->ilsId = $volumeInfo->volumeId;
				if ($ilsHoldInfo->find(true)) {
					$holdInfo[] = array(
							'label' => $volumeInfo->displayLabel,
							'numHolds' => $ilsHoldInfo->numHolds
					);
				}
			}
		}
		return $holdInfo;
	}

	function getNotes()
	{
		$additionalNotesFields = array(
				'310' => 'Current Publication Frequency',
				'321' => 'Former Publication Frequency',
				'351' => 'Organization & arrangement of materials',
				'362' => 'Dates of publication and/or sequential designation',
				'500' => 'General Note',
				'501' => '"With"',
				'502' => 'Dissertation',
				'504' => 'Bibliography',
				'506' => 'Restrictions on Access',
				'507' => 'Scale for Graphic Material',
				'508' => 'Creation/Production Credits',
				'510' => 'Citation/References',
				'511' => 'Participants/Performers',
				'513' => 'Type of Report an Period Covered',
				'515' => 'Numbering Peculiarities',
				'518' => 'Date/Time and Place of Event',
				'520' => 'Description',
				'521' => 'Target Audience',
				'522' => 'Geographic Coverage',
				'524' => 'Preferred Citation of Described Materials',
				'525' => 'Supplement',
				'526' => 'Study Program Information',
				'530' => 'Additional Physical Form',
				'533' => 'Reproduction',
				'534' => 'Original Version',
				'535' => 'Location of Originals/Duplicates',
				'536' => 'Funding Information',
				'538' => 'System Details',
				'540' => 'Terms Governing Use and Reproduction',
				'541' => 'Immediate Source of Acquisition',
				'544' => 'Location of Other Archival Materials',
				'545' => 'Biographical or Historical Data',
				'546' => 'Language',
				'547' => 'Former Title Complexity',
				'550' => 'Issuing Body',
				'555' => 'Cumulative Index/Finding Aids',
				'556' => 'Information About Documentation',
				'561' => 'Ownership and Custodial History',
				'563' => 'Binding Information',
				'580' => 'Linking Entry Complexity',
				'581' => 'Publications About Described Materials',
				'583' => 'Action',
				'584' => 'Accumulation and Frequency of Use',
				'585' => 'Exhibitions',
				'586' => 'Awards',
				'590' => 'Local note',
				'599' => 'Differentiable Local note',
		);

		$notes = array();
		foreach ($additionalNotesFields as $tag => $label) {
			/** @var File_MARC_Data_Field[] $marcFields */
			$marcFields = $this->getMarcRecord()->getFields($tag);
			foreach ($marcFields as $marcField) {
				$noteText = array();
				foreach ($marcField->getSubFields() as $subfield) {
					/** @var File_MARC_Subfield $subfield */
					$noteText[] = $subfield->getData();
				}
				$note = implode(',', $noteText);
				if (strlen($note) > 0) {
					$notes[] = array('label' => $label, 'note' => $note);
				}
			}
		}
		return $notes;
	}

	private $holdings;
	private $copiesInfoLoaded = false;
	private $holdingSections;
	private $statusSummary;

	private function loadCopies()
	{
		if (!$this->copiesInfoLoaded) {
			$this->copiesInfoLoaded = true;
			$indexingProfile = $this->getIndexingProfile();
			if ($indexingProfile instanceof IndexingProfile) {
				$dueDateFormatPHP = $indexingProfile->dueDateFormat;
				$dueDateFormatPHP = str_replace('yyyy', 'Y', $dueDateFormatPHP);
				$dueDateFormatPHP = str_replace('yy', 'y', $dueDateFormatPHP);
				$dueDateFormatPHP = str_replace('MM', 'm', $dueDateFormatPHP);
				$dueDateFormatPHP = str_replace('dd', 'd', $dueDateFormatPHP);
			}
			$noteTranslationMap = new TranslationMap();
			$noteTranslationMap->indexingProfileId = $indexingProfile->id;
			$noteTranslationMap->name = 'note';
			if (!$noteTranslationMap->find(true)){
				$noteTranslationMap = null;
			}
			//Load copy information from the grouped work rather than from the driver.
			//Since everyone is using real-time indexing now, the delays are acceptable,
			// but include when the last index was completed for reference
			$groupedWorkDriver = $this->getGroupedWorkDriver();
			if ($groupedWorkDriver->isValid) {
				$recordFromIndex = $groupedWorkDriver->getRelatedRecord($this->getIdWithSource());
				if ($recordFromIndex != null) {
					//Divide the items into sections and create the status summary
					$this->holdings = $recordFromIndex->getItemDetails();
					$this->holdingSections = array();
					$itemsFromMarc = [];
					if (!empty($indexingProfile->noteSubfield) || !empty($indexingProfile->dueDate)){
						//Get items from the marc record
						$itemFields = $this->getMarcRecord()->getFields($indexingProfile->itemTag);
						/** @var File_MARC_Data_Field $field */
						foreach ($itemFields as $field) {
							$itemRecordNumberField = $field->getSubfield($indexingProfile->itemRecordNumber);
							if ($itemRecordNumberField !== false) {
								$itemsFromMarc[$itemRecordNumberField->getData()] = $field;
							}
						}
					}
					foreach ($this->holdings as &$copyInfo) {
						$sectionName = $copyInfo['sectionId'];
						if (!array_key_exists($sectionName, $this->holdingSections)) {
							$this->holdingSections[$sectionName] = array(
								'name' => $copyInfo['section'],
								'sectionId' => $copyInfo['sectionId'],
								'holdings' => array(),
							);
						}
						if (!empty($indexingProfile->noteSubfield)){
							//Get the item for the
							if (array_key_exists($copyInfo['itemId'], $itemsFromMarc)) {
								$itemField = $itemsFromMarc[$copyInfo['itemId']];
								$copyInfo['note'] = '';
								if (!empty($itemField)) {
									$noteSubfield = $itemField->getSubfield($indexingProfile->noteSubfield);
									if ($noteSubfield != null && !empty($noteSubfield->getData())) {
										//Check to see if this needs to be translated
										$note = $noteSubfield->getData();
										if ($noteTranslationMap != null) {

											foreach ($noteTranslationMap->getTranslationMapValues() as $translationMapValue) {
												if ($noteTranslationMap->usesRegularExpressions) {
													if (preg_match('~' . $translationMapValue->value . '~', $note)) {
														$note = $translationMapValue->translation;
														break;
													}
												} else {
													if ($translationMapValue->value == $note) {
														$note = $translationMapValue->translation;
														break;
													}
												}
											}
										}
										$copyInfo['note'] = $note;
									}
								}
							}
						}
						if (!empty($indexingProfile->dueDate)){
							//Get the item for the
							if (array_key_exists($copyInfo['itemId'], $itemsFromMarc)) {
								$itemField = $itemsFromMarc[$copyInfo['itemId']];
								$copyInfo['dueDate'] = '';
								if (!empty($itemField)) {
									$dueDateSubfield = $itemField->getSubfield($indexingProfile->dueDate);
									if ($dueDateSubfield != null && !empty($dueDateSubfield->getData())) {
										$dueDateTime = DateTime::createFromFormat($dueDateFormatPHP, $dueDateSubfield->getData());
										if ($dueDateTime != false) {
											$copyInfo['dueDate'] = $dueDateTime->getTimestamp();
										} else {
											$copyInfo['dueDate'] = strtotime($dueDateSubfield->getData());
										}
									}
								}
							}
						}
						//if ($copyInfo['shelfLocation'] != '') {
							$this->holdingSections[$sectionName]['holdings'][] = $copyInfo;
						//}

					}

					$this->statusSummary = $recordFromIndex;

					$this->statusSummary->_driver = null;
					global $timer;
					$timer->logTime("Loaded Copy information");
				} else {
					$this->holdings = array();
					$this->holdingSections = array();
					$this->statusSummary = array();
				}
			} else {
				//This will happen for linked records where we are not indexing the grouped work

				$this->holdings = array();
				$this->holdingSections = array();
				$this->statusSummary = array();
			}
		}
	}

	public function assignCopiesInformation()
	{
		$this->loadCopies();
		global $interface;
		$hasLastCheckinData = false;
		$hasVolume = false;
		$hasNote = false;
		$hasDueDate = false;
		foreach ($this->holdings as $holding) {
			if ($holding['lastCheckinDate']) {
				$hasLastCheckinData = true;
			}
			if ($holding['volume']) {
				$hasVolume = true;
			}
			if (!empty($holding['note'])) {
				$hasNote = true;
			}
			if (!empty($holding['dueDate'])) {
				$hasDueDate = true;
			}
		}
		$interface->assign('hasLastCheckinData', $hasLastCheckinData);
		$interface->assign('hasVolume', $hasVolume);
		$interface->assign('hasNote', $hasNote);
		$interface->assign('hasDueDate', $hasDueDate);
		$interface->assign('holdings', $this->holdings);
		$interface->assign('sections', $this->holdingSections);

		$interface->assign('statusSummary', $this->statusSummary);
		global $timer;
		$timer->logTime("Assigned copy information");
	}

	public function getCopies()
	{
		$this->loadCopies();
		return $this->holdings;
	}

	public function loadPeriodicalInformation()
	{
		$catalogDriver = $this->getCatalogDriver();
		if ($catalogDriver->hasIssueSummaries()){
			$issueSummaries = $catalogDriver->getIssueSummaries($this->id);
			if (count($issueSummaries)) {
				//Insert copies into the information about the periodicals
				$copies = $this->getCopies();
				//Remove any copies with no location to get rid of temporary items added only for scoping
				$changeMade = true;
				while ($changeMade) {
					$changeMade = false;
					foreach ($copies as $i => $copy) {
						if ($copy['shelfLocation'] == '') {
							unset($copies[$i]);
							$changeMade = true;
							break;
						}
					}
				}
				krsort($copies);
				//Group holdings under the issue issue summary that is related.
				foreach ($copies as $key => $holding) {
					//Have issue summary = false
					$haveIssueSummary = false;
					$issueSummaryKey = null;
					foreach ($issueSummaries as $issueKey => $issueSummary) {
						if ($issueSummary['location'] == $holding['shelfLocation']) {
							$haveIssueSummary = true;
							$issueSummaryKey = $issueKey;
							break;
						}
					}
					if ($haveIssueSummary) {
						$issueSummaries[$issueSummaryKey]['holdings'][strtolower($key)] = $holding;
					} else {
						//Need to automatically add a summary so we don't lose data
						$issueSummaries[$holding['shelfLocation']] = array(
								'location' => $holding['shelfLocation'],
								'type' => 'issue',
								'holdings' => array(strtolower($key) => $holding),
						);
					}
				}
				foreach ($issueSummaries as $key => $issueSummary) {
					if (isset($issueSummary['holdings']) && is_array($issueSummary['holdings'])) {
						krsort($issueSummary['holdings']);
						$issueSummaries[$key] = $issueSummary;
					}
				}
				ksort($issueSummaries);
			}
		} else {
			$issueSummaries = null;
		}
		return $issueSummaries;
	}

	private function getLinks()
	{
		$links = array();
		$marcRecord = $this->getMarcRecord();
		if ($marcRecord != false) {
			$linkFields = $marcRecord->getFields('856');
			/** @var File_MARC_Data_Field $field */
			foreach ($linkFields as $field) {
				if ($field->getSubfield('u') != null) {
					$url = $field->getSubfield('u')->getData();
					//Only include fully formed links
					if (!strpos($url, '://')){
						$url = 'http://' . $url;
					}
					if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0){
					//if (!strpos($url, 'http://')){
						if ($field->getSubfield('y') != null) {
							$title = $field->getSubfield('y')->getData();
						} else if ($field->getSubfield('3') != null) {
							$title = $field->getSubfield('3')->getData();
						} else if ($field->getSubfield('z') != null) {
							$title = $field->getSubfield('z')->getData();
						} else {
							$title = $url;
						}
						$links[] = array(
							'title' => $title,
							'url' => $url,
						);
					}
				}
			}
		}

		return $links;
	}

	public function getSemanticData() {
		// Schema.org
		// Get information about the record
		require_once ROOT_DIR . '/RecordDrivers/LDRecordOffer.php';
		$relatedRecord = $this->getGroupedWorkDriver()->getRelatedRecord($this->getIdWithSource());
		if ($relatedRecord != null) {
			$linkedDataRecord = new LDRecordOffer($relatedRecord);
			$semanticData [] = array(
				'@context' => 'http://schema.org',
				'@type' => $linkedDataRecord->getWorkType(),
				'name' => $this->getTitle(),
				'exampleOfWork' => $this->getGroupedWorkDriver()->getLinkUrl(true),
				'author' => $this->getPrimaryAuthor(),
				'bookEdition' => $this->getEditions(),
				'isAccessibleForFree' => true,
				'image' => $this->getBookcoverUrl('medium', true),
				"offers" => $linkedDataRecord->getOffers()
			);

			//Open graph data (goes in meta tags)
			global $interface;
			$interface->assign('og_title', $this->getTitle());
			$interface->assign('og_description', $this->getDescriptionFast());
			$interface->assign('og_type', $this->getGroupedWorkDriver()->getOGType());
			$interface->assign('og_image', $this->getBookcoverUrl('medium', true));
			$interface->assign('og_url', $this->getAbsoluteUrl());
			$interface->assign('dc_creator', $this->getPrimaryAuthor());
			return $semanticData;
		}else{
			//AspenError::raiseError('MARC Record did not have an associated record in grouped work ' . $this->getPermanentId());
			return null;
		}
	}

	protected $_uploadedPDFs = null;
	function getUploadedPDFs()
	{
		if ($this->_uploadedPDFs === null) {
			$this->loadUploadedFileInfo();
		}
		return $this->_uploadedPDFs;
	}

	protected $_uploadedSupplementalFiles = null;
	function getUploadedSupplementalFiles()
	{
		if ($this->_uploadedSupplementalFiles === null) {
			$this->loadUploadedFileInfo();
		}
		return $this->_uploadedSupplementalFiles;
	}

	public function getCancelledIsbns()
	{
		$cancelledIsbns = [];
		if ($this->marcRecord != false){
			$cancelledIsbnFields = $this->marcRecord->getFields('020');
			/** @var File_MARC_Data_Field $cancelledIsbnField */
			foreach ($cancelledIsbnFields as $cancelledIsbnField) {
				$cancelledIsbn = $cancelledIsbnField->getSubfield('z');
				if ($cancelledIsbn){
					$isbnObj = new ISBN($cancelledIsbn);
					$cancelledIsbns[$isbnObj->get13()] = $isbnObj->get13();
				}
			}
		}
		return $cancelledIsbns;
	}

	public function hasMarcRecord()
	{
		return true;
	}

	private function loadUploadedFileInfo()
	{
		global $timer;
		$this->_uploadedPDFs = [];
		$this->_uploadedSupplementalFiles = [];
		require_once ROOT_DIR . '/sys/ILS/RecordFile.php';
		require_once ROOT_DIR . '/sys/File/FileUpload.php';
		$recordFile = new RecordFile();
		$recordFile->type = $this->getRecordType();
		$recordFile->identifier = $this->getUniqueID();
		if ($recordFile->find()) {
			while ($recordFile->fetch()) {
				$fileUpload = new FileUpload();
				$fileUpload->id = $recordFile->fileId;
				if ($fileUpload->find(true)) {
					if ($fileUpload->type == 'RecordPDF') {
						$this->_uploadedPDFs[] = $fileUpload;
					}elseif ($fileUpload->type == 'RecordSupplementalFile') {
						$this->_uploadedSupplementalFiles[] = $fileUpload;
					}
				}
			}
		}
		$timer->logTime("Loaded uploaded file info");
	}

	/**
	 * @return Grouping_Record|null
	 */
	public function getRelatedRecord() {
		return $this->getGroupedWorkDriver()->getRelatedRecord($this->getIdWithSource());
	}

	private function getMarcHoldings()
	{
		$localMarcHoldings = [];
		$marcHoldings = [];
		$marcRecord = $this->getMarcRecord();
		if (!empty($marcRecord)){
			//Get holdings information
			$marc852Fields = $marcRecord->getFields('852');
			if (count($marc852Fields) > 0){
				$location = new Location();
				$libraryCodeToDisplayName = $location->fetchAll('code', 'displayName', true);

				global $library;
				$location = new Location();
				$location->libraryId = $library->libraryId;
				$localLocationCodes = $location->fetchAll('code', 'displayName', true);

				$indexingProfile = $this->getIndexingProfile();
				$shelfLocationTranslationMap = new TranslationMap();
				$shelfLocationTranslationMap->indexingProfileId = $indexingProfile->id;
				$shelfLocationTranslationMap->name = 'shelf_location';
				$shelfLocationTranslationMapValues = [];
				if (!$shelfLocationTranslationMap->find(true)){
					$shelfLocationTranslationMap = null;
				}else{
					$shelfLocationTranslationMapValue = new TranslationMapValue();
					$shelfLocationTranslationMapValue->translationMapId = $shelfLocationTranslationMap->id;
					$shelfLocationTranslationMapValues = $shelfLocationTranslationMapValue->fetchAll('value', 'translation', true);
				}

				//$marc853Fields = $marcRecord->getFields('853');
				$marc866Fields = $marcRecord->getFields('866');
				//@var File_MARC_Data_Field $marc82Field
				foreach ($marc852Fields as $marc852Field){
					$marc852subfield6 = $marc852Field->getSubfield('6');
					if ($marc852subfield6 != false){
						$marc852subfield6Data = $marc852subfield6->getData();
						$marcHolding = [];
						$marcSubfieldB = $marc852Field->getSubfield('b');
						if ($marcSubfieldB != false) {
							$owningLibraryCode = strtolower($marcSubfieldB->getData());
							if (array_key_exists($owningLibraryCode, $libraryCodeToDisplayName)) {
								$owningLibrary = $libraryCodeToDisplayName[$owningLibraryCode];
							} else {
								$owningLibrary = $owningLibraryCode;
							}
							$marcHolding['library'] = $owningLibrary;
						}else{
							continue;
						}
						$marcSubfieldC = $marc852Field->getSubfield('c');
						if ($marcSubfieldC != false) {
							$shelfLocation = strtolower($marcSubfieldC->getData());
							if (array_key_exists($shelfLocation, $shelfLocationTranslationMapValues)) {
								$shelfLocation = $shelfLocationTranslationMapValues[$shelfLocation];
							}
							$marcHolding['shelfLocation'] = $shelfLocation;
						}else{
							$marcHolding['shelfLocation'] = '';
						}
						//Nothing super useful in 853, ignore it for now
						//Load what is held in the 866
						$is866Found = false;
						foreach ($marc866Fields as $marc866Field) {
							$marc866subfield6 = $marc866Field->getSubfield('6');
							if ($marc866subfield6 != false) {
								$marc866subfield6Data = $marc866subfield6->getData();
								if ($marc866subfield6Data == $marc852subfield6Data){
									$marc866subfieldA = $marc866Field->getSubfield('a');
									if ($marc866subfieldA != false) {
										$marcHolding['holdings'][] = $marc866subfieldA->getData();
										$is866Found = true;
									}
								}
							}
						}
						if (!$is866Found){
							continue;
						}
						if (array_key_exists($owningLibraryCode, $localLocationCodes)){
							$localMarcHoldings[] = $marcHolding;
						}else{
							$marcHoldings[] = $marcHolding;
						}
					}
					$sorter = function($a, $b) {
						return strcasecmp($a['library'], $b['library']);
					};
					uasort($marcHoldings, $sorter);
					uasort($localMarcHoldings, $sorter);
					$marcHoldings = $localMarcHoldings + $marcHoldings;
				}
			}
		}
		return $marcHoldings;
	}

	public function getValidPickupLocations($pickupAtRule): array
	{
		$locations = [];
		$relatedRecord = $this->getGroupedWorkDriver()->getRelatedRecord($this->getIdWithSource());
		$items = $relatedRecord->getItems();
		foreach($items as $item) {
			if($pickupAtRule == 2) {
				if(!isset($locations[$item->locationCode])) {
					$location = new Location();
					$location->code = $item->locationCode;
					if($location->find(true)) {
						$library = $location->getParentLibrary();
						foreach($library->getLocations() as $libraryBranch) {
							$locations[strtolower($libraryBranch->code)] = strtolower($libraryBranch->code);
						}
					}
				}
			} else {
				$locations[strtolower($item->locationCode)] = strtolower($item->locationCode);
			}
		}

		return $locations;
	}

	private $validUrls = null;
	/**
	 * @return array
	 */
	public function getViewable856Links(): array {
		if ($this->validUrls == null) {
			$validUrls = [];
			$marcRecord = $this->getMarcRecord();
			$marc856Fields = $marcRecord->getFields('856');
			/** @var File_MARC_Data_Field $marc856Field */
			foreach ($marc856Fields as $marc856Field) {
				if ($marc856Field->getIndicator(1) == '4' && ($marc856Field->getIndicator(2) == '0' || $marc856Field->getIndicator(2) == '1')) {
					$subfieldU = $marc856Field->getSubfield('u');
					$showAction = false;
					if ($marc856Field->getIndicator(2) == '1') {
						$subfield3 = $marc856Field->getSubfield('3');
						if ($subfield3 == null && $subfieldU != null) {
							$showAction = true;
						}
					}
					else {
						if ($subfieldU != null) {
							$showAction = true;
						}
					}
					if ($showAction) {
						$subfieldZ = $marc856Field->getSubfield('z');
						if ($subfieldZ != null) {
							$label = $subfieldZ->getData();
						}
						else {
							$label = $subfieldU->getData();
						}
						$validUrls[] = [
							'url' => $subfieldU->getData(),
							'label' => $label
						];
					}
				}
			}
			$this->validUrls = $validUrls;
		}
		return $this->validUrls;
	}
}


