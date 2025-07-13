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
class MarcRecordDriver extends GroupedWorkSubDriver {
	/** @var File_MARC_Record $marcRecord */
	protected $marcRecord = null;

	protected $profileType;
	protected $id;
	/** @var  IndexingProfile $indexingProfile */
	protected $indexingProfile;
	protected $valid = null;
	private $placesOfPublication = [];

	/**
	 * Constructor.  We build the object using all the data retrieved
	 * from the (Solr) index.  Since we have to
	 * make a search call to find out which record driver to construct,
	 * we will already have this data available, so we might as well
	 * just pass it into the constructor.
	 *
	 * @param array|File_MARC_Record|string $recordData Data to construct the driver from
	 * @param GroupedWork $groupedWork ;
	 * @access  public
	 */
	public function __construct($recordData, $groupedWork = null) {

		// Call the parent's constructor...
		global $timer;
		if ($recordData instanceof File_MARC_Record) {
			//Full MARC record
			$this->marcRecord = $recordData;
			$this->valid = true;
		} elseif (is_string($recordData) || is_numeric($recordData)) {
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
			} elseif (array_key_exists(strtolower($this->profileType), $sideLoadSettings)) {
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
			} else {
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
			/** @var File_MARC_Data_Field $idField */ global $configArray;
			$idField = $this->marcRecord->getField($configArray['Reindex']['recordNumberTag']);
			if ($idField) {
				$this->id = $idField->getSubfield('a')->getData();
			}
		}
		if ($this->valid) {
			parent::__construct($groupedWork);
		}
		$timer->logTime("Initialization of MarcRecord Driver");
	}

	public function __destruct() {
		$this->marcRecord = null;
		$this->indexingProfile = null;
		parent::__destruct();
	}

	public function getModule(): string {
		return isset($this->indexingProfile) ? $this->indexingProfile->recordUrlComponent : 'Record';
	}

	public function getIndexingProfile() {
		return $this->indexingProfile;
	}

	public function isValid() {
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
	public function getUniqueID() {
		return $this->id;
	}

	public function getIdWithSource() {
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
	public function getId() {
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
	public function getShortId() {
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
	public function getStaffView() {
		global $interface;
		global $configArray;
		//Indexing Profile is null for side loads
		if ($this->getIndexingProfile() != null && $this->indexingProfile instanceof IndexingProfile) {
			$accountProfile = $this->indexingProfile->getAccountProfile();
			if ($accountProfile != null) {
				if ($accountProfile->ils == 'millennium' || $accountProfile->ils == 'sierra') {
					$classicId = substr($this->id, 1, strlen($this->id) - 2);
					$interface->assign('classicId', $classicId);
					$millenniumScope = $interface->getVariable('millenniumScope');
					if (isset($configArray['Catalog']['linking_url'])) {
						$linkingUrl = $configArray['Catalog']['linking_url'];
						if (substr($linkingUrl, -1, 1) == '/') {
							$linkingUrl = substr($linkingUrl, 0, -1);
						}
						$interface->assign('classicUrl', $linkingUrl . "/record=$classicId&amp;searchscope={$millenniumScope}");
					}

				} elseif ($accountProfile->ils == 'koha') {
					$interface->assign('classicId', $this->id);
					$interface->assign('classicUrl', $configArray['Catalog']['url'] . '/cgi-bin/koha/opac-detail.pl?biblionumber=' . $this->id);
					$interface->assign('staffClientUrl', $configArray['Catalog']['staffClientUrl'] . '/cgi-bin/koha/catalogue/detail.pl?biblionumber=' . $this->id);
				} elseif ($accountProfile->ils == 'carlx') {
					$shortId = str_replace('CARL', '', $this->id);
					$shortId = ltrim($shortId, '0');
					$interface->assign('staffClientUrl', $configArray['Catalog']['staffClientUrl'] . '/Items/' . $shortId);
				} elseif ($accountProfile->ils == 'evergreen') {
					$baseUrl = $configArray['Catalog']['url'];
					if (substr($baseUrl, -1, 1) == '/') {
						$baseUrl = substr($baseUrl, 0, -1);
					}
					$interface->assign('classicId', $this->id);
					$interface->assign('classicUrl', $baseUrl . '/eg/opac/record/' . $this->id);
				}
			}
		}

		$groupedWorkDriver = $this->getGroupedWorkDriver();
		if ($groupedWorkDriver != null) {
			if ($groupedWorkDriver->isValid()) {
				$interface->assign('hasValidGroupedWork', true);
				$this->getGroupedWorkDriver()->assignGroupedWorkStaffView();

				require_once ROOT_DIR . '/sys/Grouping/NonGroupedRecord.php';
				$nonGroupedRecord = new NonGroupedRecord();
				$nonGroupedRecord->source = $this->getRecordType();
				$nonGroupedRecord->recordId = $this->getId();
				if ($nonGroupedRecord->find(true)) {
					$interface->assign('isUngrouped', true);
					$interface->assign('ungroupingId', $nonGroupedRecord->id);
				} else {
					$interface->assign('isUngrouped', false);
				}
			} else {
				$interface->assign('hasValidGroupedWork', false);
			}
		} else {
			$interface->assign('hasValidGroupedWork', false);
		}

		//Look for an IlsRecord for this MARC
		require_once ROOT_DIR . '/sys/Indexing/IlsRecord.php';
		$ilsRecord = new IlsRecord();
		$ilsRecord->source = $this->getRecordType();
		$ilsRecord->ilsId = $this->getId();
		if ($ilsRecord->find(true)) {
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

		$readerName = new OverDriveDriver();
		$readerName = $readerName->getReaderName();
		$interface->assign('readerName', $readerName);

		return 'RecordDrivers/Marc/staff.tpl';
	}

	/**
	 * The Table of Contents extracted from the record.
	 * Returns null if no Table of Contents is available.
	 *
	 * @access  public
	 * @return  array              Array of elements in the table of contents
	 */
	public function getTableOfContents() {
		$tableOfContents = [];
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
	public function getAllSubjectHeadings() {
		// These are the fields that may contain subject headings:
		$fields = [
			'600',
			'610',
			'630',
			'650',
			'651',
			'655',
		];

		// This is all the collected data:
		$retVal = [];

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
				$current = [];

				// Get all the chunks and collect them together:
				/** @var File_MARC_Subfield[] $subfields */
				$subfields = $result->getSubfields();
				if ($subfields) {
					foreach ($subfields as $subfield) {
						//Add unless this is 655 subfield 2
						if ($subfield->getCode() != 2 && $subfield->getCode() != 1) {
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
	 * @param string $field The MARC field number to read
	 * @param array $subfields The MARC subfield codes to read
	 * @param bool $concat Should we concatenate subfields?
	 * @access  private
	 * @return  array
	 */
	private function getFieldArray(string $field, ?array $subfields = null, bool $concat = true) : array {
		// Default to subfield a if nothing is specified.
		if (!is_array($subfields)) {
			$subfields = ['a'];
		}

		// Initialize return array
		$matches = [];

		if ($this->isValid()) {
			$marcRecord = $this->getMarcRecord();
			if ($marcRecord !== false) {
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
	 * @param string $field
	 * @return File_MARC_Data_Field[]
	 */
	private function getFields(string $field, bool $useRegularExpression = false) : array {
		$matches = [];

		if ($this->isValid()) {
			$marcRecord = $this->getMarcRecord();
			if ($marcRecord !== false) {
				$matches = $marcRecord->getFields($field, $useRegularExpression);
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
	public function getEditions() {
		$editions = $this->getFieldArray('250');

		return $editions;
	}

	public function getPlacesOfPublication() {
		$marcRecord = $this->getMarcRecord();
		if ($marcRecord != null) {
			$placesOfPublication =  $this->getFieldArray('260', ['a']);
			/** @var File_MARC_Data_Field[] $rdaPublisherFields */
			$rdaPublisherFields = $marcRecord->getFields('264');
			foreach ($rdaPublisherFields as $rdaPublisherField) {
				if (($rdaPublisherField->getIndicator(2) == 1 || $rdaPublisherField->getIndicator(2) == ' ') && $rdaPublisherField->getSubfield('a') != null) {
					$placesOfPublication[] = $rdaPublisherField->getSubfield('a')->getData();
				}
			}
			foreach ($placesOfPublication as $key => $placeOfPublication) {
				$placesOfPublication[$key] = preg_replace('/[.,]$/', '', $placeOfPublication);
			}
		}
		return $placesOfPublication;

	}
	/**
	 * Get the first value matching the specified MARC field and subfields.
	 * If multiple subfields are specified, they will be concatenated together.
	 *
	 * @param string $field The MARC field to read
	 * @param array $subfields The MARC subfield codes to read
	 * @access  private
	 * @return  string
	 */
	private function getFirstFieldValue($field, $subfields = null) {
		$matches = $this->getFieldArray($field, $subfields);
		return (is_array($matches) && count($matches) > 0) ? $matches[0] : null;
	}

	/**
	 * Get an array of all series names containing the record.  Array entries may
	 * be either the name string, or an associative array with 'name' and 'number'
	 * keys.
	 *
	 * @access  public
	 * @return  array
	 */
	public function getSeries() {
		$seriesInfo = $this->getGroupedWorkDriver()->getSeries();
		if ($seriesInfo == null || count($seriesInfo) == 0) {
			// First check the 440, 800 and 830 fields for series information:
			$primaryFields = [
				'440' => [
					'a',
					'p',
				],
				'800' => [
					'a',
					'b',
					'c',
					'd',
					'f',
					'p',
					'q',
					't',
				],
				'830' => [
					'a',
					'p',
				],
			];
			$matches = $this->getSeriesFromMARC($primaryFields);
			if (!empty($matches)) {
				return $matches;
			}

			// Now check 490 and display it only if 440/800/830 were empty:
			$secondaryFields = ['490' => ['a']];
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
	private function getSeriesFromMARC($fieldInfo) {
		$matches = [];

		// Loop through the field specification....
		foreach ($fieldInfo as $field => $subfields) {
			// Did we find any matching fields?
			$series = $this->getMarcRecord()->getFields($field);
			if (is_array($series)) {
				foreach ($series as $currentField) {
					// Can we find a name using the specified subfield list?
					$name = $this->getSubfieldArray($currentField, $subfields);
					if (isset($name[0])) {
						$currentArray = ['seriesTitle' => $name[0]];

						// Can we find a number in subfield v?  (Note that number is
						// always in subfield v regardless of whether we are dealing
						// with 440, 490, 800 or 830 -- hence the hard-coded array
						// rather than another parameter in $fieldInfo).
						$number = $this->getSubfieldArray($currentField, ['v']);
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
	 * @param object $currentField $result from File_MARC::getFields.
	 * @param array $subfields The MARC subfield codes to read
	 * @param bool $concat Should we concatenate subfields?
	 * @return  string[]
	 */
	private function getSubfieldArray($currentField, $subfields, $concat = true) {
		// Start building a line of text for the current field
		$matches = [];
		$currentLine = '';

		//Loop through all subfields within the field to ensure the order of subfields is preserved even if not all subfields are shown
		/** @var File_MARC_Subfield[] $subfieldsResult */
		$subfieldsResult = $currentField->getSubfields();
		foreach ($subfieldsResult as $currentSubfield) {
			// Loop through all specified subfields, collecting results:
			foreach ($subfields as $subfield) {
				if ($currentSubfield->getCode() == $subfield) {
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
					break;
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
	public function getSubfieldData($marcField, $subField) {
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
	public function getTitle() {
		return $this->getFirstFieldValue('245', [
			'a',
			'b',
			'f',
			'g',
			'n',
			'p',
		]);
	}

	private $_alternateGraphicRepresentations = null;

	/** @noinspection PhpUnused */
	public function get880Title() : string {
		$this->loadAlternateGraphicRepresentations();
		return $this->_alternateGraphicRepresentations['title'];
	}

	/** @noinspection PhpUnused */
	public function get880Authors() : array {
		$this->loadAlternateGraphicRepresentations();
		return $this->_alternateGraphicRepresentations['authors'];
	}

	/** @noinspection PhpUnused */
	public function get880Description() : string {
		$this->loadAlternateGraphicRepresentations();
		return $this->_alternateGraphicRepresentations['description'];
	}

	public function get880Contributors() : array {
		$this->loadAlternateGraphicRepresentations();
		return $this->_alternateGraphicRepresentations['contributors'];
	}

	public function get880Subjects() : array {
		$this->loadAlternateGraphicRepresentations();
		return $this->_alternateGraphicRepresentations['subjects'];
	}

	public function get880Notes() : array {
		$this->loadAlternateGraphicRepresentations();
		return $this->_alternateGraphicRepresentations['notes'];
	}

	public function get880PublicationDetails() : array {
		$this->loadAlternateGraphicRepresentations();
		return $this->_alternateGraphicRepresentations['publicationDetails'];
	}

	public function get880PhysicalDescriptions() : array {
		$this->loadAlternateGraphicRepresentations();
		return $this->_alternateGraphicRepresentations['physicalDescriptions'];
	}

	public function loadAlternateGraphicRepresentations() : void{
		if ($this->_alternateGraphicRepresentations === null) {
			$this->_alternateGraphicRepresentations = [
				'title' => '',
				'authors' => [],
				'contributors' => [],
				'publicationDetails' => [],
				'physicalDescriptions' => [],
				'description' => '',
				'subjects' => [],
				'notes' => []
			];
			$fields880 = $this->getFields(880);
			foreach ($fields880 as $field880) {
				$subfield6 = $field880->getSubfield('6');
				if ($subfield6 !== false) {
					$subfield6Data = $subfield6->getData();
					if (str_starts_with($subfield6Data, '245-')) {
						$this->_alternateGraphicRepresentations['title'] = $this->getSubfieldArray($field880, ['a', 'b', 'f', 'g', 'n', 'p'], true)[0];
					}elseif (str_starts_with($subfield6Data, '100-')) {
						$this->_alternateGraphicRepresentations['authors'][] = $this->getSubfieldArray($field880, ['a', 'c', 'd', 'q'], true)[0];
					}elseif (str_starts_with($subfield6Data, '110-')) {
						$this->_alternateGraphicRepresentations['authors'][] = $this->getSubfieldArray($field880, ['a', 'b'], true)[0];
					}elseif (str_starts_with($subfield6Data, '264-')) {
						$index = preg_replace('/\D/m', '', substr($subfield6Data, 4, 2));
						$this->_alternateGraphicRepresentations['publicationDetails'][] = [
							'index' => $index,
							'value' => $this->getSubfieldArray($field880, ['a', 'b', 'c'], true)[0]
						];
					}elseif (str_starts_with($subfield6Data, '300-')) {
						$index = preg_replace('/\D/m', '', substr($subfield6Data, 4, 2));
						$this->_alternateGraphicRepresentations['physicalDescriptions'][] = [
							'index' => $index,
							'value' => $this->getSubfieldArray($field880, ['a', 'b', 'c', 'e', 'f', 'g'], true)[0]
						];
					}elseif (str_starts_with($subfield6Data, '530-')) {
						$index = preg_replace('/\D/m', '', substr($subfield6Data, 4, 2));
						$this->_alternateGraphicRepresentations['physicalDescriptions'][] = [
							'index' => $index,
							'value' => $this->getSubfieldArray($field880, ['a', 'b', 'c', 'd'], true)[0]
						];
					}elseif (str_starts_with($subfield6Data, '700-') || str_starts_with($subfield6Data, '710-')) {
						$index = preg_replace('/\D/m', '', substr($subfield6Data, 4, 2));
						$name = $this->getSubfieldArray($field880, ['a', 'c', 'd', 'q'], true);
						$title = $this->getSubfieldArray($field880, ['t', 'm', 'n', 'r'], true);
						$role = $this->getSubfieldArray($field880, ['e'], true);
						$contributorInfo = [
							'name' => empty($name) ? '' : $name[0],
							'title' => empty($title) ? '' : $title[0],
							'roles' => array(empty($role) ? '' : $role[0]),
							'index' => $index,
							'isAgr' => true,
						];
						$this->_alternateGraphicRepresentations['contributors'][] = $contributorInfo;
					}elseif (str_starts_with($subfield6Data, '520-')) {
						$this->_alternateGraphicRepresentations['description'] = $this->getSubfieldArray($field880, ['a', 'c'], true)[0];
					}elseif (preg_match('/(600|610|611|630|650|651|655|690)-/', $subfield6Data)) {
						$index = preg_replace('/\D/m', '', substr($subfield6Data, 4, 2));
						[$type, $search, $title] = $this->getSubjectInfoFromField($field880);
						$this->_alternateGraphicRepresentations['subjects'][] = ['index' => $index, 'type' => $type, 'search' => $search, 'title' => $title];
					}elseif (preg_match('/(310|321|351|362|5\d\d)-/', $subfield6Data)) {
						$index = preg_replace('/\D/m', '', substr($subfield6Data, 4, 2));
						$noteText = [];
						foreach ($field880->getSubFields() as $subfield) {
							if ($subfield->getCode() != '6') {
								/** @var File_MARC_Subfield $subfield */
								$noteText[] = $subfield->getData();
							}
						}
						$note = implode(' ', $noteText);
						$this->_alternateGraphicRepresentations['notes'][] = ['index' => $index, 'note' => $note];
					}
				}
			}
		}
	}

	/**
	 * Get the uniform title of the record.
	 *
	 * @return  array
	 */
	/** @noinspection PhpUnused */
	public function getUniformTitle() {
		return $this->getFieldArray('240', [
			'a',
			'd',
			'f',
			'g',
			'h',
			'k',
			'l',
			'm',
			'n',
			'o',
			'p',
			'r',
			's',
		]);
	}

	/**
	 * Get the full title of the record.
	 *
	 * @return  string
	 */
	public function getShortTitle() {
		return $this->getFirstFieldValue('245', ['a']);
	}

	/**
	 * Get the full title of the record.
	 *
	 * @return  string
	 */
	public function getSortableTitle() {
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
	public function getSubtitle() {
		return $this->getFirstFieldValue('245', ['b']);
	}

	/**
	 * Get the text of the part/section portion of the title.
	 *
	 * @access  protected
	 * @return  string
	 */
	/** @noinspection PhpUnused */
	public function getTitleSection() {
		return $this->getFirstFieldValue('245', [
			'n',
			'p',
		]);
	}

	public function getPrimaryAuthor() {
		return $this->getAuthor();
	}

	private $author = null;
	public function getAuthor() {
		if ($this->author == null) {
			$author = $this->getFirstFieldValue('100', [
				'a',
				'c',
				'd',
				'q'
			]);
			if (empty($author)) {
				$author = $this->getFirstFieldValue('110', [
					'a',
					'b',
				]);
			}
			if (str_ends_with($author, ',')) {
				$author = substr($author, 0, strlen($author) -1);
			}
			$author = preg_replace('/(\d)\.$/', '$1', $author);
			$this->author = $author;
		}

		return $this->author;
	}

	public function getContributors() {
		return $this->getFieldArray(700, [
			'a',
			'b',
			'c',
			'd',
		]);
	}

	private $detailedContributors = null;

	/** @noinspection PhpUnused */
	public function getDetailedContributors() {
		if ($this->detailedContributors == null) {
			$this->detailedContributors = [];
			//Get alternate graphic representations
			$agrContributors = $this->get880Contributors();
			/** @var File_MARC_Data_Field[] $sevenHundredFields */
			$sevenHundredFields = $this->getMarcRecord()->getFields('700|710', true);
			foreach ($sevenHundredFields as $field) {
				$nameSubfieldArray = $this->getSubfieldArray($field, [
					'a',
					'b',
					'c',
					'd',
				], true);
				$titleSubfieldArray = $this->getSubfieldArray($field, [
					't',
					'm',
					'n',
					'r',
				], true);
				$curContributor = [
					'name' => reset($nameSubfieldArray),
					'title' => reset($titleSubfieldArray),
					'roles' => [],
					'isAgr' => false
				];
				if ($field->getSubfield('4') != null) {
					$contributorRole = $field->getSubfield('4')->getData();
					$contributorRole = preg_replace('/[\s,.;]+$/', '', $contributorRole);
					$curContributor['roles'][] = mapValue('contributor_role', $contributorRole);
				} elseif ($field->getSubfield('e') != null) {
					$curContributor['roles'][] = $field->getSubfield('e')->getData();
				}
				//Try to match to an AGR Contributor
				if ($field->getSubfield('6') != null) {
					$subfield6Data = $field->getSubfield('6')->getData();
					$index = preg_replace('/\D/m', '', substr($subfield6Data, 4, 2));
					foreach ($agrContributors as $contributorIndex => $contributor) {
						if ($contributor['index'] == $index) {
							$curContributor['agr'] = $contributor;
							unset($agrContributors[$contributorIndex]);
							break;
						}
					}
				}
				ksort($curContributor['roles']);
				$this->detailedContributors[] = $curContributor;
			}
			$this->detailedContributors = array_merge($this->detailedContributors);
		}
		return $this->detailedContributors;
	}

	function getDescriptionFast() {
		/** @var File_MARC_Data_Field $descriptionField */
		if ($this->getMarcRecord()) {
			$descriptionField = $this->getMarcRecord()->getField('520');
			if ($descriptionField != null && $descriptionField->getSubfield('a') != null) {
				return $descriptionField->getSubfield('a')->getData();
			}
		}
		return null;
	}

	function getDescription() {
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
				$summaries = [];
				$summary = '';
				foreach ($summaryFields as $summaryField) {
					//Check to make sure we don't have an exact duplicate of this field
					$curSummary = $this->getSubfieldData($summaryField, 'a');
					$summarySource = $this->getSubfieldData($summaryField, 'c');
					if (!empty($summarySource)) {
						$curSummary .= $summarySource;
					}
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
	function loadDescriptionFromMarc($marcRecord, $allowExternalDescription = true) {
		global $memCache;
		global $configArray;

		if (!$this->getMarcRecord()) {
			$descriptionArray = [];
			$description = translate([
				'text' => "Description Not Provided",
				'isPublicFacing' => true,
			]);
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
					$description = $descriptionSubfield->getData();
					if ($descriptionSourceSubfield = $descriptionField->getSubfield('c')) {
						$marcDescription = $description . $descriptionSourceSubfield;
					}else{
						$marcDescription = $this->trimDescription($description);
					}
					$description = trim($description);
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
					$description = translate([
						'text' => "Description Not Provided",
						'isPublicFacing' => true,
					]);
					$descriptionArray['description'] = $description;
				}
			}

			$memCache->set("record_description_{$isbn}_{$upc}_{$allowExternalDescription}", $descriptionArray, $configArray['Caching']['record_description']);
		}
		return $descriptionArray;
	}

	private function trimDescription($description) {
		$chars = 300;
		if (strlen($description) > $chars) {
			$description = $description . " ";
			$description = substr($description, 0, $chars);
			$description = substr($description, 0, strrpos($description, ' '));
			$description = $description . "...";
		}
		return $description;
	}

	function getLanguage() {
		/** @var File_MARC_Control_Field $field008 */
		$field008 = $this->getMarcRecord()->getField('008');
		if ($field008 != false) {
			$datalength = strlen($field008->getData());

			if ($field008 != null && $datalength >= 37) {
				$languageCode = substr($field008->getData(), 35, 3);
				/** @var TranslationMap $translationMap **/
				$translatedValue = mapValue('language', $languageCode);
				if (!empty($translatedValue)){
					return $translatedValue;
				}else{
					return $languageCode;
				}

			} else {
				return 'English';
			}
		}else{
			return 'English';
		}
	}

	function getFormats() {
		return $this->getFormat();
	}

	/**
	 * Load the format for the record based off of information stored within the grouped work.
	 * Which was calculated at index time.
	 *
	 * @return string[]
	 */
	function getFormat() {
		//Rather than loading formats here, let's leverage the work we did at index time
		$groupedWorkDriver = $this->getGroupedWorkDriver();
		if (!$groupedWorkDriver->isValid()) {
			return ['Unknown'];
		} else {
			$relatedRecord = $groupedWorkDriver->getRelatedRecord($this->getIdWithSource());
			if ($relatedRecord != null) {
				if (count($relatedRecord->recordVariations) > 1){
					foreach ($relatedRecord->recordVariations as $variation){
						$formats[] = $variation->manifestation->format;
					}
					return $formats;
				}
				return [$relatedRecord->format];
			} else {
				$recordDetails = $this->getGroupedWorkDriver()->getSolrField('record_details');
				if ($recordDetails) {
					if (!is_array($recordDetails)) {
						$recordDetails = [$recordDetails];
					}
					foreach ($recordDetails as $recordDetailRaw) {
						$recordDetail = explode('|', $recordDetailRaw);
						if ($recordDetail[0] == $this->getIdWithSource()) {
							return [$recordDetail[1]];
						}
					}
					//We did not find a record for this in the index.  It's probably been deleted.
					return ['Unknown'];
				} else {
					return ['Unknown'];
				}
			}
		}
	}

	function isClosedCaptioned() {
		$relatedRecord = $this->getGroupedWorkDriver()->getRelatedRecord($this->getIdWithSource());
		if ($relatedRecord != null) {
			return $relatedRecord->closedCaptioned;
		} else {
			return false;
		}
	}

	function hasMultipleVariations() {
		$relatedRecord = $this->getGroupedWorkDriver()->getRelatedRecord($this->getIdWithSource());
		if ($relatedRecord != null && count($relatedRecord->recordVariations) > 1) {
			return true;
		} else {
			return false;
		}
	}

	function getRecordVariations() {
		require_once ROOT_DIR . '/sys/Grouping/Variation.php';
		$relatedRecord = $this->getGroupedWorkDriver()->getRelatedRecord($this->getIdWithSource());
		$records = [];
		foreach($relatedRecord->recordVariations as $variation){
			$allVariationRecords = $variation->getRecords();
			//make sure we're showing the correct record, not all records that have this format
			foreach($allVariationRecords as $recordToShow){
				if ($recordToShow->databaseId == $relatedRecord->databaseId){
					$records[] = $recordToShow;
				}
			}
		}
		$sorter = function ($a, $b) {
			return strcasecmp($a->variationFormat, $b->variationFormat);
		};
		uasort($records, $sorter);
		return $records;
	}

	function getFormatCategory() {
		return $this->getGroupedWorkDriver()->getFormatCategory();
	}

	function getRecordUrl() {
		$recordId = $this->getUniqueID();
		return "/" . $this->getModule() . "/$recordId";
	}

	protected ?array $_actions = [];

	public function getRecordActions($relatedRecord, $variationId, $isAvailable, $isHoldable, $volumeData = null) : array {
		require_once ROOT_DIR . '/RecordDrivers/RecordActionGenerator.php';
		if (!array_key_exists($variationId, $this->_actions)) {
			$this->_actions[$variationId] = [];
			global $interface;

			if (UserAccount::isLoggedIn()) {
				$user = UserAccount::getActiveUserObj();
				$this->_actions[$variationId] = array_merge($this->_actions[$variationId], $user->getCirculatedRecordActions($this->getIndexingProfile()->name, $this->id));
			}

			$treatVolumeHoldsAsItemHolds = $this->getCatalogDriver()->treatVolumeHoldsAsItemHolds();

			if (isset($interface)) {
				$allItems = $relatedRecord->getItems();
				$relatedUrls = [];
				if ($allItems != null) {
					foreach ($allItems as $item) {
						if ($item->variationId == $variationId || $variationId == 'any') {
							$relatedUrls = array_merge($relatedUrls, $item->getRelatedUrls());
						}
					}
				}

				$this->_actions[$variationId] = array_merge($this->_actions[$variationId], $this->createActionsFromUrls($relatedUrls, $relatedRecord, $variationId));

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

			$id = $this->id;
			if ($isHoldable && $showHoldButton) {
				$source = $this->profileType;
				if ($volumeData == null) {
					$volumeData = $relatedRecord->getUnsuppressedVolumeData();
				}
				list($interLibraryLoanType, $treatHoldAsInterLibraryLoanRequest, $homeLocation, $holdGroups) = $this->getInterLibraryLoanIntegrationInformation($relatedRecord, $variationId);

				//Figure out what needs to happen with volumes (if anything)
				$needsVolumeHold = false;
				$holdableVolumes = [];
				$itemsWithoutVolumes = [];
				$itemsWithoutVolumesNeedIllRequest = false;
				if (!is_null($volumeData) && count($volumeData) > 0 && !$treatVolumeHoldsAsItemHolds) {
					//We do have volumes, check the items to see which volumes are holdable
					$needsVolumeHold = true;
					if ($relatedRecord->getItems() != null) {
						foreach ($relatedRecord->getItems() as $itemDetail) {
							if ($itemDetail->variationId == $variationId || $variationId == 'any') {
								if ($itemDetail->holdable) {
									if (!empty($itemDetail->volumeId)) {
										$volumeKey = str_pad($itemDetail->volumeOrder, 10, '0', STR_PAD_LEFT) . $itemDetail->volumeId;
										if (!array_key_exists($volumeKey, $holdableVolumes)) {
											$holdableVolumes[$volumeKey] = [
												'volumeName' => $itemDetail->volume,
												'volumeId' => $itemDetail->volumeId,
												'relatedItems' => [],
												'needsIllRequest' => false,
											];
										}
										$holdableVolumes[$volumeKey]['relatedItems'][] = $itemDetail;
									} else {
										$itemsWithoutVolumes[] = $itemDetail;
									}
								}
							}
						}
						//Figure out which volumes require requests
						if ($interLibraryLoanType != 'none' && $homeLocation != null) {
							foreach ($holdableVolumes as &$holdableVolume) {
								$holdableVolume['needsIllRequest'] = !$this->oneOrMoreHoldableItemsOwnedByPatronHoldGroups($holdableVolume['relatedItems'], $holdGroups, $variationId, $homeLocation->code);
							}
							$itemsWithoutVolumesNeedIllRequest = !$this->oneOrMoreHoldableItemsOwnedByPatronHoldGroups($itemsWithoutVolumes, $holdGroups, $variationId, $homeLocation->code);
						}
					}
				}
				$interface->assign('itemsWithoutVolumesNeedIllRequest', $itemsWithoutVolumesNeedIllRequest);

				//Figure out the actions to add
				if ($needsVolumeHold) {
					//We can just show buttons for each volume if there are 3 or fewer volumes and no items without a volume
					if (count($holdableVolumes) > 3 || count($itemsWithoutVolumes) > 0) {
						//Check to see if all items require a request
						$allVolumesRequireIll = true;
						if ($interLibraryLoanType !== 'none') {
							foreach ($holdableVolumes as $volumeInfo) {
								if (!$volumeInfo['needsIllRequest']) {
									$allVolumesRequireIll = false;
								}
							}
						}else{
							$allVolumesRequireIll = false;
						}
						if ($allVolumesRequireIll) {
							if (count($itemsWithoutVolumes) > 0) {
								//Check to see if a title level request is possible and if so show a request or hold button as appropriate
								if ($itemsWithoutVolumesNeedIllRequest) {
									if ($interLibraryLoanType == 'vdx') {
										//VDX does not support volumes, we'll just prompt for a regular VDX
										$this->_actions[$variationId][] = getVdxRequestAction($this->getModule(), $source, $id);
									} elseif ($interLibraryLoanType == 'localIll') {
										$this->_actions[$variationId][] = getMultiVolumeRequestAction($this->getModule(), $source, $id);
									}
								}else{
									$this->_actions[$variationId][] = getUntitledVolumeHoldAction($this->getModule(), $source, $id, $variationId);
								}
							} else {
								//The button will show a message to the patron no volumes can be requested
								$this->_actions[$variationId][] = getNoVolumesCanBeRequestedAction($this->getModule(), $source, $id);
							}
						}else{
							//We will need to show a popup to select the volume
							$interface->assign('itemsWithoutVolumesNeedIllRequest', $itemsWithoutVolumesNeedIllRequest);
							$this->_actions[$variationId][] = getMultiVolumeHoldAction($this->getModule(), $source, $id);
						}
					}else{
						//We show a button per volume
						ksort($holdableVolumes);
						foreach ($holdableVolumes as $volumeInfo) {
							if ($volumeInfo['needsIllRequest']) {
								if ($interLibraryLoanType == 'vdx') {
									//VDX does not support volumes, we'll just prompt for a regular VDX
									$this->_actions[$variationId][] = getVdxRequestAction($this->getModule(), $source, $id);
								}elseif ($interLibraryLoanType == 'localIll') {
									$this->_actions[$variationId][] = getSpecificVolumeLocalIllRequestAction($this->getModule(), $source, $id, $volumeInfo);
								}
							}else{
								$this->_actions[$variationId][] = getSpecificVolumeHoldAction($this->getModule(), $source, $id, $volumeInfo);
							}
						}
					}
				}else{
					//No volumes, just get the proper action based on interlibrary loan type required
					if ($treatHoldAsInterLibraryLoanRequest) {
						if ($interLibraryLoanType == 'vdx') {
							$this->_actions[$variationId][] = getVdxRequestAction($this->getModule(), $source, $id);
						} else if ($interLibraryLoanType == 'localIll') {
							$this->_actions[$variationId][] = getLocalIllRequestAction($this->getModule(), $source, $id);
						}
					} else {
						$this->_actions[$variationId][] = getHoldRequestAction($this->getModule(), $source, $id, $variationId);
					}
				}
			}

			//Check to see if a PDF has been uploaded for the record
			$uploadedPDFs = $this->getUploadedPDFs();
			if (count($uploadedPDFs) > 0) {
				if (count($uploadedPDFs) == 1) {
					$recordFile = reset($uploadedPDFs);
					$this->_actions[$variationId][] = getViewSinglePdfAction($recordFile->id);
					$this->_actions[$variationId][] = getDownloadSinglePdfAction($id, $recordFile->id);
				} else {
					$this->_actions[$variationId][] = getViewMultiPdfAction($id);
					$this->_actions[$variationId][] = getDownloadMultiPdfAction($this->getId());
				}
			}

			//Check to see if a Supplemental Files have been uploaded for the record
			$supplementalFiles = $this->getUploadedSupplementalFiles();
			if (count($supplementalFiles) > 0) {
				if (count($supplementalFiles) == 1) {
					$recordFile = reset($supplementalFiles);
					$this->_actions[$variationId][] = getDownloadSingleSupplementalFileAction($id, $recordFile->id);
				} else {
					$this->_actions[$variationId][] = getDownloadMultiSupplementalFileAction($id);
				}
			}

			global $timer;
			$timer->logTime("Done loading actions for MarcRecordDriver");
		}

		return $this->_actions[$variationId];
	}

	/**
	 * @param ?array $items - The items to check
	 * @param HoldGroup[] $holdGroups - The valid hold groups for the patron's hold groups
	 * @param int|string $variationId - The variation being loaded
	 * @param string $patronHomeLocationCode - The location code for the patron's home location
	 * @return bool
	 */
	public function oneOrMoreHoldableItemsOwnedByPatronHoldGroups(?array $items, array $holdGroups, int|string $variationId, string $patronHomeLocationCode) : bool {
		//If no hold groups exist, everything is valid
		if (count($holdGroups) == 0) {
			return true;
		}
		if ($items != null) {
			foreach ($items as $itemDetail) {
				if ($itemDetail->variationId == $variationId || $variationId == 'any') {
					//Only check holdable items
					if ($itemDetail->holdable) {
						//The patron's home location is always valid!
						if ($itemDetail->locationCode == $patronHomeLocationCode) {
							return true;
						}

						foreach ($holdGroups as $holdGroup) {
							if (in_array($itemDetail->locationCode, $holdGroup->getLocationCodes())) {
								return true;
							}
						}
					}
				}
			}
		}
		return false;
	}

	function createActionsFromUrls($relatedUrls, $itemInfo = null, $variationId = 'any') {
		global $configArray;
		$actions = [];
		$i = 0;
		if (count($relatedUrls) > 1) {
			//We will show a popup to let people choose the URL they want

			$title = translate([
				'text' => 'Access Online',
				'isPublicFacing' => true,
			]);
			$actions[] = [
				'title' => $title,
				'url' => '',
				'onclick' => "return AspenDiscovery.Record.selectItemLink('{$this->getId()}', '{$variationId}');",
				'requireLogin' => false,
				'type' => 'access_online',
				'id' => "accessOnline_{$this->getId()}",
				'target' => '_blank',
			];
		} elseif (count($relatedUrls)  == 1) {

			if (Library::getActiveLibrary()->libKeySettingId != -1 && !empty($relatedUrls[0]['url'])) {
				$libKeyLink = $this->getLibKeyUrl($relatedUrls[0]['url']);
				$title = translate([
					'text' => 'Access Online',
					'isPublicFacing' => true,
				]);
				$actions[] = [
					'title' => $title,
					'url' => $libKeyLink ? $libKeyLink : $relatedUrls[0]['url'],
					'requireLogin' => false,
					'type' => 'access_online',
					'id' => "accessOnline_{$this->getId()}",
					'target' => '_blank',
				];

			} else {
				$urlInfo = reset($relatedUrls);

				//Revert to access online per Karen at CCU.  If people want to switch it back, we can add a per library switch
				$title = translate([
					'text' => 'Access Online',
					'isPublicFacing' => true,
				]);
				$alt = 'Available online from ' . $urlInfo['source'];
				$action = $configArray['Site']['url'] . '/' . $this->getModule() . '/' . $this->id . "/AccessOnline?index=$i&variationId=$variationId";
				$fileOrUrl = isset($urlInfo['url']) ? $urlInfo['url'] : $urlInfo['file'];
				if (strlen($fileOrUrl) > 0) {
					if (strlen($fileOrUrl) >= 3) {
						$extension = strtolower(substr($fileOrUrl, strlen($fileOrUrl), 3));
						if ($extension == 'pdf') {
							$title = translate([
								'text' => 'Access PDF',
								'isPublicFacing' => true,
							]);
						}
					}
					$actions[] = [
						'url' => $action,
						'redirectUrl' => $fileOrUrl,
						'title' => $title,
						'requireLogin' => false,
						'alt' => $alt,
						'target' => '_blank',
					];
				}
			}
		} else {
			foreach ($relatedUrls as $urlInfo) {
				$title = translate([
					'text' => 'Access Online',
					'isPublicFacing' => true,
				]);
				$alt = 'Available online from ' . $urlInfo['source'];
				$action = $configArray['Site']['url'] . '/' . $this->getModule() . '/' . $this->id . "/AccessOnline?index=$i";
				$fileOrUrl = isset($urlInfo['url']) ? $urlInfo['url'] : $urlInfo['file'];
				if (strlen($fileOrUrl) > 0) {
					if (strlen($fileOrUrl) >= 3) {
						$extension = strtolower(substr($fileOrUrl, strlen($fileOrUrl), 3));
						if ($extension == 'pdf') {
							$title = translate([
								'text' => 'Access PDF',
								'isPublicFacing' => true,
							]);
						}
					}
					$actions[] = [
						'url' => $action,
						'redirectUrl' => $fileOrUrl,
						'title' => $title,
						'requireLogin' => false,
						'alt' => $alt,
						'target' => '_blank',
					];
				}
			}
		}

		return $actions;
	}

	private function getLibKeyUrl($doiUrl) {
		require_once ROOT_DIR . "/Drivers/LibKeyDriver.php";
		$libKeyDriver = new LibKeyDriver();
		$libKeyResult = $libKeyDriver->getLibKeyResult($doiUrl);
		if (!empty($libKeyResult)) {
			return $libKeyResult["data"]["bestIntegratorLink"]["bestLink"];
		}else{
			return null;
		}
	}

	private $catalogDriver = null;

	/**
	 * @return AbstractIlsDriver
	 */
	public function getCatalogDriver() {
		if ($this->catalogDriver == null) {
			try {
				$indexingProfile = $this->getIndexingProfile();
				$accountProfileForSource = UserAccount::getAccountProfileByRecordSource($indexingProfile->name);
				if ($accountProfileForSource != null) {
					$this->catalogDriver = CatalogFactory::getCatalogConnectionInstance($accountProfileForSource->driver, $accountProfileForSource);
				}else{
					$this->catalogDriver = CatalogFactory::getCatalogConnectionInstance();
				}
//				$accountProfileForSource = new AccountProfile();
//				$accountProfileForSource->recordSource = $indexingProfile->name;
//				require_once ROOT_DIR . '/CatalogFactory.php';
//				if ($accountProfileForSource->find(true)) {
//					$this->catalogDriver = CatalogFactory::getCatalogConnectionInstance($accountProfileForSource->driver, $accountProfileForSource);
//				} else {
//					$this->catalogDriver = CatalogFactory::getCatalogConnectionInstance();
//				}
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

	private $_physicalDescriptions = null;
	/**
	 * Get an array of physical descriptions of the item.
	 *
	 * @access  protected
	 * @return  array
	 */
	/** @noinspection PhpUnused */
	public function getPhysicalDescriptions() {
		if ($this->_physicalDescriptions == null) {
			$this->_physicalDescriptions = [];
			$physicalDescriptionFields = $this->getFields('300|530', true);
			foreach ($physicalDescriptionFields as $field) {
				if ($field == '300') {
					$info = $this->getSubfieldArray($field, ['a', 'b', 'c', 'e', 'f', 'g'], true);
				}else{
					$info = $this->getSubfieldArray($field, ['a', 'b', 'c', 'd'], true);
				}
				if (count($info) > 0) {
					$physicalDescriptionInfo = $info[0];
					if ($field->getSubfield('6') != null) {
						$subfield6Data = $field->getSubfield('6')->getData();
						$index = preg_replace('/\D/m', '', substr($subfield6Data, 4, 2));
						$agrPhysicalDescriptions = $this->get880PhysicalDescriptions();
						foreach ($agrPhysicalDescriptions as $agrPhysicalDescription) {
							if ($agrPhysicalDescription['index'] == $index) {
								$physicalDescriptionInfo .= " ({$agrPhysicalDescription['value']})";
							}
						}
					}
					$this->_physicalDescriptions[] = $physicalDescriptionInfo;
				}
			}
		}
		return $this->_physicalDescriptions;
	}

	/**
	 * Get the publication dates of the record.
	 *
	 * @access  public
	 * @return  array
	 */
	public function getPublicationDates() {
		$publicationDates = [];
		if ($this->isValid()) {
			$publicationDates = $this->getFieldArray('260', ['c']);
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

	private $_publicationDetails = null;
	public function getPublicationDetails() : array {
		if ($this->_publicationDetails == null) {
			$this->_publicationDetails = [];
			if ($this->isValid()) {
				$publicationFields = $this->getFields('260|264', true);
				foreach ($publicationFields as $field) {
					if ($field->getTag() == 264) {
						if (!($field->getIndicator(2) == 1 || $field->getIndicator(2) == ' ')) {
							//Not a valid publication field
							continue;
						}
					}
					$publisherData = $this->getSubfieldArray($field, ['a', 'b', 'c'], true);
					if (count($publisherData) > 0) {
						$publisherDetails = $publisherData[0];
						if ($field->getSubfield('6') != null) {
							$subfield6Data = $field->getSubfield('6')->getData();
							$index = preg_replace('/\D/m', '', substr($subfield6Data, 4, 2));
							$agrPublishers = $this->get880PublicationDetails();
							foreach ($agrPublishers as $agrPublisher) {
								if ($agrPublisher['index'] == $index) {
									$publisherDetails .= " ({$agrPublisher['value']})";
								}
							}
						}
						$this->_publicationDetails[] = $publisherDetails;
					}
				}
			}
		}
		return $this->_publicationDetails;
	}

	/**
	 * Get the publishers of the record.
	 *
	 * @return  array
	 */
	function getPublishers() {
		$marcRecord = $this->getMarcRecord();
		if ($marcRecord != null) {
			$publishers = $this->getFieldArray('260', ['b']);
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
			$publishers = [];
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
	public function getISBNs() {
		if ($this->isbns == null) {
			// If ISBN is in the index, it should automatically be an array... but if
			// it's not set at all, we should normalize the value to an empty array.
			$isbns = [];
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

	private $_oclcNumber = null;
	public function getOCLCNumber() {
		if ($this->_oclcNumber == null) {
			$this->_oclcNumber = '';
			$marcRecord = $this->getMarcRecord();
			if ($marcRecord != null) {
				/** @var File_MARC_Control_Field $oclcNumberField */
				$oclcNumberField = $this->getMarcRecord()->getField('001');
				if ($oclcNumberField != null) {
					$oclcNumber = $oclcNumberField->getData();
					if (strpos($oclcNumber, 'ocn') === 0 || strpos($oclcNumber, 'ocm') === 0 || strpos($oclcNumber, 'on') === 0) {
						$this->_oclcNumber = $oclcNumber;
					}
				}
			}
		}
		return $this->_oclcNumber;
	}

	private $issns = null;

	/**
	 * Get an array of all ISSNs associated with the record (may be empty).
	 *
	 * @access  protected
	 * @return  array
	 */
	public function getISSNs() {
		if ($this->issns == null) {
			$issns = [];
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
	public function getUPCs() {
		if ($this->upcs == null) {
			// If UPCs is in the index, it should automatically be an array... but if
			// it's not set at all, we should normalize the value to an empty array.
			$this->upcs = [];
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

	public function getMoreDetailsOptions() {
		global $interface;
		/** @var Library $library */
		global $library;

		$isbn = $this->getCleanISBN();

		//Load table of contents
		$tableOfContents = $this->getTableOfContents();
		$interface->assign('tableOfContents', $tableOfContents);

		//Load more details options
		$moreDetailsOptions = $this->getBaseMoreDetailsOptions($isbn);

		//Override the description if we have a description form the 880
		if (!empty($this->get880Description())) {
			$moreDetailsOptions['description'] = [
				'label' => 'Description',
				'body' => '<div><div id="descriptionPlaceholder">' . translate([
						'text' => 'Loading Description...',
						'isPublicFacing' => true,
					]) . '</div><div id="agrDescription">' . $this->get880Description() . '</div>',
				'hideByDefault' => false,
				'openByDefault' => true,
			];
		}

		//Get copies for the record
		$this->assignCopiesInformation();

		$ils = 'Unknown';
		if ($this->getIndexingProfile()->getAccountProfile() != null) {
			$ils = $this->getIndexingProfile()->getAccountProfile()->ils;

		}

		//If this is a periodical we may have additional information
		$isPeriodical = false;
		if ($this->isPeriodical()) {
			global $library;
			$interface->assign('showCheckInGrid', $library->getGroupedWorkDisplaySettings()->showCheckInGrid);
			$issues = $this->loadPeriodicalInformation();
			$interface->assign('periodicalIssues', $issues);
			$isPeriodical = true;
		}
		$links = $this->getLinks();
		if (Library::getActiveLibrary()->libKeySettingId != -1  && !empty($links[0]['url'])) {
			$libKeyLink = $this->getLibKeyUrl($links[0]['url']);
			if (!empty($libKeyLink)) {
				$links[] = ['title' => $libKeyLink, 'url' => $libKeyLink];
			}
		}
		$interface->assign('links', $links);
		$interface->assign('show856LinksAsTab', $library->getGroupedWorkDisplaySettings()->show856LinksAsTab);
		$interface->assign('showItemDueDates', $library->getGroupedWorkDisplaySettings()->showItemDueDates);
		$interface->assign('showItemNotes', $library->getGroupedWorkDisplaySettings()->showItemNotes);

		if ($library->getGroupedWorkDisplaySettings()->show856LinksAsTab && count($links) > 0) {
			$moreDetailsOptions['links'] = [
				'label' => 'Links',
				'body' => $interface->fetch('Record/view-links.tpl'),
			];
		}
		$showLastCheckIn = false;
		if ($this->getIndexingProfile()->getAccountProfile() != null) {
			$ils = $this->getIndexingProfile()->getAccountProfile()->ils;
			if ($ils == 'sierra' || $ils == 'millennium') {
				$showLastCheckIn = $interface->getVariable('hasLastCheckinData');
			}
		}
		$interface->assign('showLastCheckIn', $showLastCheckIn);
		$interface->assign('showFormatInHoldings', count($this->getFormats()) > 1);
		$interface->assign('holdingsHaveUrls', $this->holdingsHaveUrls);

		// Check if there are any non-eContent holdings to display.
		$hasNonEContentHoldings = false;
		foreach ($this->holdings as $holding) {
			if (empty($holding['isEContent'])) {
				$hasNonEContentHoldings = true;
				break;
			}
		}

		// Only add copies section if there are non-eContent holdings to display.
		if ($hasNonEContentHoldings) {
			$moreDetailsOptions['copies'] = [
				'label' => 'Copies',
				'body' => $interface->fetch('Record/view-holdings.tpl'),
				'openByDefault' => true,
			];
		}

		//Other editions if applicable (only if we aren't the only record!)
		$groupedWorkDriver = $this->getGroupedWorkDriver();
		if ($groupedWorkDriver != null) {
			$relatedRecords = $groupedWorkDriver->getRelatedRecords();
			if (count($relatedRecords) > 1) {
				$interface->assign('relatedManifestations', $groupedWorkDriver->getRelatedManifestations());
				$interface->assign('workId', $groupedWorkDriver->getPermanentId());
				$moreDetailsOptions['otherEditions'] = [
					'label' => 'Other Editions and Formats',
					'body' => $interface->fetch('GroupedWork/relatedManifestations.tpl'),
					'hideByDefault' => false,
				];
			}
		}

		$moreDetailsOptions['moreDetails'] = [
			'label' => 'More Details',
			'body' => $interface->fetch('Record/view-more-details.tpl'),
		];
		$this->loadSubjects();
		$moreDetailsOptions['subjects'] = [
			'label' => 'Subjects',
			'body' => $interface->fetch('Record/view-subjects.tpl'),
		];
		$moreDetailsOptions['citations'] = [
			'label' => 'Citations',
			'body' => $interface->fetch('Record/cite.tpl'),
		];

		//Check to see if the record has parents
		$parentRecords = $this->getParentRecords();
		if (count($parentRecords) > 0) {
			$interface->assign('parentRecords', $parentRecords);
			$moreDetailsOptions['parentRecords'] = [
				'label' => 'Part Of',
				'body' => $interface->fetch('Record/view-containing-records.tpl'),
			];
		}

		//Check to see if the record has children
		$childRecords = $this->getChildRecords();
		if (count($childRecords) > 0) {
			if (count($this->holdings) == 0 && array_key_exists('copies', $moreDetailsOptions)) {
				unset($moreDetailsOptions['copies']);
			}
			$interface->assign('childRecords', $childRecords);
			$moreDetailsOptions['childRecords'] = [
				'label' => 'Contains',
				'body' => $interface->fetch('Record/view-contained-records.tpl'),
			];
		}

		//Check to see if the record has children
		$continuesRecords = $this->getContinuesRecords();
		if (count($continuesRecords) > 0) {
			$interface->assign('continuesRecords', $continuesRecords);
			$moreDetailsOptions['continuesRecords'] = [
				'label' => 'Continues',
				'body' => $interface->fetch('Record/view-continues-records.tpl'),
			];
		}

		//Check to see if the record has children
		$continuedByRecords = $this->getContinuedByRecords();
		if (count($continuedByRecords) > 0) {
			$interface->assign('continuedByRecords', $continuedByRecords);
			$moreDetailsOptions['continuedByRecords'] = [
				'label' => 'Continued By',
				'body' => $interface->fetch('Record/view-continued-by-records.tpl'),
			];
		}

		//Check to see if the record has marc holdings (in 852, 853, 856, 866)
		$marcHoldings = $this->getMarcHoldings();
		if (count($marcHoldings) > 0) {
			//Check to see if the copies are empty and if so remove copies section
			if (empty($this->holdingSections) &&
				(!$isPeriodical || empty($interface->getVariable('periodicalIssues'))) &&
				array_key_exists('copies', $moreDetailsOptions)) {
				unset($moreDetailsOptions['copies']);
			}

			$interface->assign('marcHoldings', $marcHoldings);
			$moreDetailsOptions['marcHoldings'] = [
				'label' => 'Library Holdings',
				'body' => $interface->fetch('Record/view-marc-holdings.tpl'),
			];
		}

		if ($interface->getVariable('showStaffView')) {
			$moreDetailsOptions['staff'] = [
				'label' => 'Staff View',
				'onShow' => "AspenDiscovery.Record.getStaffView('{$this->getModule()}', '{$this->id}');",
				'body' => '<div id="staffViewPlaceHolder">' . translate([
						'text' => 'Loading Staff View.',
						'isPublicFacing' => true,
					]) . '</div>',
			];
		}

		return $this->filterAndSortMoreDetailsOptions($moreDetailsOptions);
	}

	public function getChildRecords() {
		require_once ROOT_DIR . '/sys/ILS/RecordParent.php';
		require_once ROOT_DIR . '/sys/Grouping/GroupedWorkRecord.php';
		$childRecordsFromDB = $this->getGroupedWorkDriver()->getRelatedRecords();

		$parentChildRecords = new RecordParent();
		$parentChildRecords->parentRecordId = $this->id;
		$parentChildRecords->orderBy('childTitle ASC');
		$parentChildRecords->find();
		$childRecords = [];
		if ($parentChildRecords->getNumResults() > 0) {
			while ($parentChildRecords->fetch()) {
				//Check to see if this is econtent or a regular record
				foreach ($childRecordsFromDB as $childRecordFromDB) {
					if (strpos($childRecordFromDB->id, ':' . $parentChildRecords->childRecordId) > 0) {
						$childRecords[] = [
							'id' => $parentChildRecords->childRecordId,
							'label' => empty($parentChildRecords->childTitle) ? $parentChildRecords->childRecordId : $parentChildRecords->childTitle,
							'format' => $childRecordFromDB->getFormat(),
							'link' => $childRecordFromDB->getUrl(),
							'actions' => $childRecordFromDB->getActions(),
						];
					}
				}
			}
		}
		return $childRecords;
	}

	public function getParentRecords() {
		require_once ROOT_DIR . '/sys/ILS/RecordParent.php';
		$parentChildRecords = new RecordParent();
		$parentChildRecords->childRecordId = $this->id;
		$parentChildRecords->find();
		$parentRecords = [];
		if ($parentChildRecords->getNumResults() > 0) {
			while ($parentChildRecords->fetch()) {
				//TODO: Store the title in the database so we can load it more quickly here
				require_once ROOT_DIR . '/sys/Grouping/GroupedWorkRecord.php';
				$parentTitle = $parentChildRecords->parentRecordId;
				$parentRecord = new GroupedWorkRecord();
				$parentRecord->recordIdentifier = $parentChildRecords->parentRecordId;
				if ($parentRecord->find(true)) {
					require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
					$groupedWork = new GroupedWork();
					$groupedWork->id = $parentRecord->groupedWorkId;
					if ($groupedWork->find(true)) {
						$groupedWorkDriver = new GroupedWorkDriver($groupedWork->permanent_id);
						if ($groupedWorkDriver->isValid()) {
							$parentTitle = $groupedWorkDriver->getTitle();
						} else {
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

	public function loadSubjects() {
		global $interface;
		global $library;
		$marcRecord = $this->getMarcRecord();
		$subjects = [];
		$otherSubjects = [];
		$lcSubjects = [];
		$bisacSubjects = [];
		$oclcFastSubjects = [];
		$localSubjects = [];
		$agrSubjects = $this->get880Subjects();
		if ($marcRecord) {
			$subjectFields = [
				600,
				610,
				611,
				630,
				650,
				651,
				655,
				690,
			];

			foreach ($subjectFields as $subjectField) {
				/** @var File_MARC_Data_Field[] $marcFields */
				$marcFields = $marcRecord->getFields($subjectField);
				if ($marcFields) {
					foreach ($marcFields as $marcField) {
						$subject = [];

						[$type, $search, $title] = $this->getSubjectInfoFromField($marcField);

						if (strlen($search) == 0) {
							continue;
						}
						//Check to see if there is an alternate graphic representation
						$subfield6 = $marcField->getSubfield('6');
						$agr = null;
						if ($subfield6 !== false) {
							$subfield6Data = $subfield6->getData();
							$index = preg_replace('/\D/m', '', substr($subfield6Data, 4, 2));
							foreach ($agrSubjects as $subjectIndex => $agrSubject) {
								if ($agrSubject['index'] == $index) {
									$agr = $agrSubject;
									unset($agrSubjects[$subjectIndex]);
									break;
								}
							}
						}

						$subject[$title] = [
							'search' => trim($search),
							'title' => $title,
							'agr' => $agr
						];
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

	static array $lcSubjectTagNumbers = [
		600,
		610,
		611,
		630,
		650,
		651,
		655,
	];

	private function getSubjectTypeFromField(File_MARC_Data_Field $field) : string {
		//Determine the type of the subject
		$type = 'other';
		$subjectTag = $field->getTag();
		if ($field->getTag() == '880') {
			$subfield6 = $field->getSubfield('6');
			if ($subfield6 !== false) {
				$subfield6Data = $subfield6->getData();
				$subjectTag = substr($subfield6Data, 0, 3);
			}else{
				return 'other';
			}
		}
		if (in_array($subjectTag, self::$lcSubjectTagNumbers)) {
			if ($field->getIndicator(2) == 0) {
				$type = 'lc';
			}
		}
		$subjectSource = $field->getSubfield('2');
		if ($subjectSource != null) {
			if ($subjectSource->getData() == 'lcgft') {
				$type = 'lc';
			} elseif (preg_match('/bisac/i', $subjectSource->getData())) {
				$type = 'bisac';
			} elseif (preg_match('/fast/i', $subjectSource->getData())) {
				$type = 'fast';
			}
		}
		if ($field->getTag() == '690') {
			$type = 'local';
		}
		return $type;
	}

	private function getSubjectInfoFromField($field) : array {
		$type = $this->getSubjectTypeFromField($field);
		$search = '';
		$title = '';

		foreach ($field->getSubFields() as $subField) {
			/** @var File_MARC_Subfield $subField */
			$subfieldCode = $subField->getCode();
			if (!in_array($subfieldCode, ['0', '1', '2', '6', '9'])) {
				$subFieldData = $subField->getData();
				if ($type == 'bisac' && $subField->getCode() == 'a') {
					$subFieldData = ucwords(strtolower($subFieldData));
				}
				$search .= " " . $subFieldData;
				if (strlen($title) > 0) {
					if (in_array($subfieldCode, ['v', 'x', 'y', 'z'])) {
						$title .= ' -- ';
					}else{
						$title .= ' ';
					}
				}
				$title .= $subFieldData;
			}

		}
		$search = trim($search);

		return [$type, $search, $title];
	}

	public function getRecordType() {
		if ($this->profileType) {
			return $this->profileType;
		} else {
			return 'ils';
		}
	}

	/**
	 * @return File_MARC_Record
	 */
	public function getMarcRecord() :File_MARC_Record|false {
		if ($this->marcRecord == null) {
			disableErrorHandler();
			try {
				$this->marcRecord = MarcLoader::loadMarcRecordByILSId("{$this->profileType}:{$this->id}");
				if ($this->marcRecord instanceof AspenError || $this->marcRecord == false) {
					$this->valid = false;
					$this->marcRecord = false;
				} else {
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
	function processTableOfContentsFields($tocFields) {
		$notes = [];
		foreach ($tocFields as $marcField) {
			$curNote = '';
			/** @var File_MARC_Subfield $subfield */
			foreach ($marcField->getSubfields() as $subfield) {
				$note = $subfield->getData();
				$curNote .= " " . $note;
				$curNote = trim($curNote);
				if (preg_match("/--$/", $curNote)) {
					$notes[] = str_replace('--', '', $curNote);
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

	function getNumHolds(): int {
		if ($this->numHolds != -1) {
			return $this->numHolds;
		}
		global $timer;
		if (!($this->getIndexingProfile() instanceof SideLoad)) {
			$accountProfile = $this->getIndexingProfile()->getAccountProfile();
			if ($accountProfile != null) {
				$ilsName = $accountProfile->ils;
			} else {
				$ilsName = 'Unknown';
			}
		} else {
			$ilsName = 'Sideload';
		}
		if ($ilsName == 'horizon') {
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
			$holdSummary->__destruct();
			$holdSummary = null;
		}

		$timer->logTime("Loaded number of holds");
		return $this->numHolds;
	}

	/**
	 * @param IlsVolumeInfo[] $volumeData
	 * @return array
	 */
	function getVolumeHolds($volumeData) {
		$holdInfo = null;
		if (count($volumeData) > 0) {
			$holdInfo = [];
			foreach ($volumeData as $volumeInfo) {
				$ilsHoldInfo = new IlsHoldSummary();
				$ilsHoldInfo->ilsId = $volumeInfo->volumeId;
				if ($ilsHoldInfo->find(true)) {
					$holdInfo[] = [
						'label' => $volumeInfo->displayLabel,
						'numHolds' => $ilsHoldInfo->numHolds,
					];
				}
			}
		}
		return $holdInfo;
	}

	static array $additionalNotesFields = [
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
		'532' => 'Accessibility Note',
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
	];

	function getNotes() {
		$agrNotes = $this->get880Notes();
		$notes = [];
		foreach (self::$additionalNotesFields as $tag => $label) {
			/** @var File_MARC_Data_Field[] $marcFields */
			$marcFields = $this->getMarcRecord()->getFields($tag);
			foreach ($marcFields as $marcField) {
				$noteText = [];
				foreach ($marcField->getSubFields() as $subfield) {
					if ($subfield->getCode() != '6') {
						/** @var File_MARC_Subfield $subfield */
						$noteText[] = $subfield->getData();
					}
				}
				$note = implode(' ', $noteText);
				if (strlen($note) > 0) {
					$agr = null;
					$subfield6 = $marcField->getSubfield('6');
					if ($subfield6 !== false) {
						$subfield6Data = $subfield6->getData();
						$index = preg_replace('/\D/m', '', substr($subfield6Data, 4, 2));
						foreach ($agrNotes as $noteIndex => $agrNote) {
							if ($agrNote['index'] == $index) {
								$agr = $agrNote['note'];
								unset($agrNotes[$noteIndex]);
								break;
							}
						}
					}
					$notes[] = [
						'label' => $label,
						'note' => $note,
						'agr' => $agr
					];
				}
			}
		}
		return $notes;
	}

	private $holdings;
	private $copiesInfoLoaded = false;
	private $holdingSections;
	private $statusSummary;
	private $holdingsHaveUrls = false;

	private function loadCopies() {
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
			if (!$noteTranslationMap->find(true)) {
				$noteTranslationMap = null;
			}
			//Load copy information from the grouped work rather than from the driver.
			//Since everyone is using real-time indexing now, the delays are acceptable,
			// but include when the last index was completed for reference
			$groupedWorkDriver = $this->getGroupedWorkDriver();
			if ($groupedWorkDriver->isValid) {
				$recordFromIndex = $groupedWorkDriver->getRelatedRecord($this->getIdWithSource());
				if ($recordFromIndex != null) {
					//Check if there are different variations we need to add to $this->holdings
					//Records with parents never get their variations set to avoid updating
					if (!empty($recordFromIndex->recordVariations) && (!$recordFromIndex->hasParentRecord)) {
						$holdings = [];
						foreach ($recordFromIndex->recordVariations as $variation){
							$allVariationRecords = $variation->getRecords();
							foreach($allVariationRecords as $recordToShow){
								if ($recordToShow->databaseId == $recordFromIndex->databaseId){
									//getItemDetails needs an object not an array, return the first object in the array since only one record should be attached anyway
									$holdings = array_merge($recordToShow->getItemDetails(), $holdings);
								}
							}
						}
						$this->holdings = $holdings;
					}else{
						$this->holdings = $recordFromIndex->getItemDetails();
					}
					//Divide the items into sections and create the status summary
					$this->holdingSections = [];
					$itemsFromMarc = [];
					if (!empty($indexingProfile->noteSubfield) || !empty($indexingProfile->dueDate) || !empty($indexingProfile->itemUrl)) {
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
							$this->holdingSections[$sectionName] = [
								'name' => $copyInfo['section'],
								'sectionId' => $copyInfo['sectionId'],
								'holdings' => [],
							];
						}
						if (!empty($indexingProfile->noteSubfield)) {
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
						if (!empty($indexingProfile->dueDate)) {
							//Get the item for the holding
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
						if (!empty($indexingProfile->itemUrl)) {
							//Get the item for the holding
							if (array_key_exists($copyInfo['itemId'], $itemsFromMarc)) {
								$itemField = $itemsFromMarc[$copyInfo['itemId']];
								$copyInfo['itemUrl'] = '';
								$copyInfo['itemUrlDescription'] = '';
								if (!empty($itemField)) {
									$itemUrlSubfield = $itemField->getSubfield($indexingProfile->itemUrl);
									if ($itemUrlSubfield != null && !empty($itemUrlSubfield->getData())) {
										$this->holdingsHaveUrls = true;
										$copyInfo['itemUrl'] = $itemUrlSubfield->getData();
										if (!empty($indexingProfile->itemUrlDescription)) {
											$itemUrlDescriptionSubfield = $itemField->getSubfield($indexingProfile->itemUrlDescription);
											if ($itemUrlDescriptionSubfield != null && !empty($itemUrlDescriptionSubfield->getData())) {
												$copyInfo['itemUrlDescription'] = $itemUrlDescriptionSubfield->getData();
											}
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

					$this->statusSummary->discardDriver();
					global $timer;
					$timer->logTime("Loaded Copy information");
				} else {
					$this->holdings = [];
					$this->holdingSections = [];
					$this->statusSummary = [];
				}
			} else {
				//This will happen for linked records where we are not indexing the grouped work

				$this->holdings = [];
				$this->holdingSections = [];
				$this->statusSummary = [];
			}
		}
	}

	public function assignCopiesInformation() {
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

	public function getCopies() {
		$this->loadCopies();
		return $this->holdings;
	}

	public function isPeriodical() {
		$ils = 'Unknown';
		if ($this->getIndexingProfile()->getAccountProfile() != null) {
			$ils = $this->getIndexingProfile()->getAccountProfile()->ils;

		}
		//If this is a periodical we may have additional information
		$isPeriodical = false;
		require_once ROOT_DIR . '/sys/Indexing/FormatMapValue.php';
		foreach ($this->getFormats() as $format) {
			if ($ils == 'sierra' || $ils == 'millennium') {
				$formatValue = new FormatMapValue();
				$formatValue->format = $format;
				$formatValue->displaySierraCheckoutGrid = 1;
				if ($formatValue->find(true)) {
					$isPeriodical = true;
					break;
				}
			}else{
				if ($format == 'Journal' || $format == 'Newspaper' || $format == 'Print Periodical' || $format == 'Magazine') {
					$isPeriodical = true;
					break;
				}
			}
		}
		return $isPeriodical;
	}

	public function loadPeriodicalInformation() {
		$catalogDriver = $this->getCatalogDriver();
		if ($catalogDriver->hasIssueSummaries()) {
			$issueSummaries = $catalogDriver->getIssueSummaries($this->id);
			if (!empty($issueSummaries)) {
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
				//Group holdings under the issue summary that is related.
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
						$issueSummaries[$holding['shelfLocation']] = [
							'location' => $holding['shelfLocation'],
							'type' => 'issue',
							'holdings' => [strtolower($key) => $holding],
						];
					}
				}
				foreach ($issueSummaries as $key => $issueSummary) {
					if (isset($issueSummary['holdings']) && is_array($issueSummary['holdings'])) {
						krsort($issueSummary['holdings'], SORT_NATURAL);
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

	private function getLinks() {
		$links = [];
		$marcRecord = $this->getMarcRecord();
		if ($marcRecord != false) {
			$linkFields = $marcRecord->getFields('856');
			/** @var File_MARC_Data_Field $field */
			foreach ($linkFields as $field) {
				if ($field->getSubfield('u') != null) {
					$url = $field->getSubfield('u')->getData();
					//Only include fully formed links
					if (!strpos($url, '://')) {
						$url = 'http://' . $url;
					}
					//Do not display the link if we have subfield 6 since that is a marc holding
					if ($field->getSubfield('6') != null) {
						continue;
					}
					if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
						//if (!strpos($url, 'http://')){
						if ($field->getSubfield('y') != null) {
							$title = $field->getSubfield('y')->getData();
						} elseif ($field->getSubfield('3') != null) {
							$title = $field->getSubfield('3')->getData();
						} elseif ($field->getSubfield('z') != null) {
							$title = $field->getSubfield('z')->getData();
						} else {
							$title = $url;
						}
						$links[] = [
							'title' => $title,
							'url' => $url,
						];
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
			$semanticData [] = [
				'@context' => 'http://schema.org',
				'@type' => $linkedDataRecord->getWorkType(),
				'name' => $this->getTitle(),
				'exampleOfWork' => $this->getGroupedWorkDriver()->getLinkUrl(true),
				'author' => $this->getPrimaryAuthor(),
				'bookEdition' => $this->getEditions(),
				'isAccessibleForFree' => true,
				'image' => $this->getBookcoverUrl('medium', true),
				"offers" => $linkedDataRecord->getOffers(),
			];

			//Open graph data (goes in meta tags)
			global $interface;
			$interface->assign('og_title', $this->getTitle());
			$interface->assign('og_description', $this->getDescriptionFast());
			$interface->assign('og_type', $this->getGroupedWorkDriver()->getOGType());
			$interface->assign('og_image', $this->getBookcoverUrl('medium', true));
			$interface->assign('og_url', $this->getAbsoluteUrl());
			$interface->assign('dc_creator', $this->getPrimaryAuthor());
			return $semanticData;
		} else {
			//AspenError::raiseError('MARC Record did not have an associated record in grouped work ' . $this->getPermanentId());
			return null;
		}
	}

	protected $_uploadedPDFs = null;

	function getUploadedPDFs() {
		if ($this->_uploadedPDFs === null) {
			$this->loadUploadedFileInfo();
		}
		return $this->_uploadedPDFs;
	}

	protected $_uploadedSupplementalFiles = null;

	function getUploadedSupplementalFiles() {
		if ($this->_uploadedSupplementalFiles === null) {
			$this->loadUploadedFileInfo();
		}
		return $this->_uploadedSupplementalFiles;
	}

	public function getCancelledIsbns() {
		$cancelledIsbns = [];
		if ($this->marcRecord != false) {
			$cancelledIsbnFields = $this->marcRecord->getFields('020');
			/** @var File_MARC_Data_Field $cancelledIsbnField */
			foreach ($cancelledIsbnFields as $cancelledIsbnField) {
				$cancelledIsbn = $cancelledIsbnField->getSubfield('z');
				if ($cancelledIsbn) {
					$isbnObj = new ISBN($cancelledIsbn);
					$cancelledIsbns[$isbnObj->get13()] = $isbnObj->get13();
				}
			}
		}
		return $cancelledIsbns;
	}

	public function hasMarcRecord() {
		return true;
	}

	private function loadUploadedFileInfo(): void {
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
					if (file_exists($fileUpload->fullPath)) {
						if ($fileUpload->type == 'RecordPDF') {
							$this->_uploadedPDFs[] = $fileUpload;
						} elseif ($fileUpload->type == 'RecordSupplementalFile') {

							$this->_uploadedSupplementalFiles[] = $fileUpload;
						}
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
		if ($this->getGroupedWorkDriver()->isValid()) {
			return $this->getGroupedWorkDriver()->getRelatedRecord($this->getIdWithSource());
		}else{
			return null;
		}
	}

	public function getRelatedRecordForVariation($variationId = '') {
		if ($this->getGroupedWorkDriver()->isValid()) {
			return $this->getGroupedWorkDriver()->getRelatedRecordForVariation($this->getIdWithSource(), $variationId);
		}else{
			return null;
		}
	}

	public function getContinuesRecords() {
		$continuesRecords = [];
		$marcRecord = $this->getMarcRecord();
		if (!empty($marcRecord)) {
			$marc780Fields = $marcRecord->getFields('780');
			/** @var File_MARC_Data_Field $marc780Field */
			foreach ($marc780Fields as $marc780Field) {
				//subfield w contains the bib id of the other record
				$subfieldW = $marc780Field->getSubfield('w');
				if ($subfieldW != null) {
					$continuesRecordId = $subfieldW->getData();
					$continuesRecordId = trim(preg_replace('/\(.*?\)/s', '', $continuesRecordId));
					if (!empty($continuesRecordId)) {
						$continuesRecordDriver = RecordDriverFactory::initRecordDriverById($this->profileType . ':' . $continuesRecordId);
						if ($continuesRecordDriver->isValid()) {
							$actions = [];
							if ($continuesRecordDriver->getRelatedRecord() != null) {
								$actions = $continuesRecordDriver->getRelatedRecord()->getActions();
							}
							$continuesRecords[] = [
								'id' => $continuesRecordId,
								'label' => $continuesRecordDriver->getTitle(),
								'format' => $continuesRecordDriver->getPrimaryFormat(),
								'link' => $continuesRecordDriver->getLinkUrl(),
								'actions' => $actions,
							];
							continue;
						}
					}
				}
				$title = '';
				$subfieldA = $marc780Field->getSubfield('a');
				if ($subfieldA != null) {
					$title = $subfieldA->getData();
				}
				$subfieldT = $marc780Field->getSubfield('t');
				if ($subfieldT != null) {
					$title .= ' ' . $subfieldT->getData();
					$title = trim($title);
				}
				if (!empty($title)) {
					$continuesRecords[] = [
						'id' => '',
						'label' => $title,
						'format' => '',
						'link' => '',
						'actions' => [],
					];
				}
			}
		}
		return $continuesRecords;
	}

	public function getContinuedByRecords() {
		$continuedByRecords = [];
		$marcRecord = $this->getMarcRecord();
		if (!empty($marcRecord)) {
			$marc780Fields = $marcRecord->getFields('785');
			/** @var File_MARC_Data_Field $marc780Field */
			foreach ($marc780Fields as $marc780Field) {
				//subfield w contains the bib id of the other record
				$subfieldW = $marc780Field->getSubfield('w');
				if ($subfieldW != null) {
					$continuedByRecordId = $subfieldW->getData();
					$continuedByRecordId = trim(preg_replace('/\(.*?\)/s', '', $continuedByRecordId));
					if (!empty($continuedByRecordId)) {
						$continuedByRecordDriver = RecordDriverFactory::initRecordDriverById($this->profileType . ':' . $continuedByRecordId);
						if ($continuedByRecordDriver->isValid()) {
							$continuedByRecords[] = [
								'id' => $continuedByRecordId,
								'label' => $continuedByRecordDriver->getTitle(),
								'format' => $continuedByRecordDriver->getPrimaryFormat(),
								'link' => $continuedByRecordDriver->getLinkUrl(),
								'actions' => $continuedByRecordDriver->getRelatedRecord()->getActions(),
							];
							continue;
						}
					}
				}
				$title = '';
				$subfieldA = $marc780Field->getSubfield('a');
				if ($subfieldA != null) {
					$title = $subfieldA->getData();
				}
				$subfieldT = $marc780Field->getSubfield('t');
				if ($subfieldT != null) {
					$title .= ' ' . $subfieldT->getData();
					$title = trim($title);
				}
				if (!empty($title)) {
					$continuedByRecords[] = [
						'id' => '',
						'label' => $title,
						'format' => '',
						'link' => '',
						'actions' => [],
					];
				}
			}
		}
		return $continuedByRecords;
	}

	private function getMarcHoldings() {
		$localMarcHoldings = [];
		$marcHoldings = [];
		$marcRecord = $this->getMarcRecord();
		if (!empty($marcRecord)) {
			//Get holdings information
			$marc852Fields = $marcRecord->getFields('852');
			if (count($marc852Fields) > 0) {
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
				if (!$shelfLocationTranslationMap->find(true)) {
					$shelfLocationTranslationMap = null;
				} else {
					$shelfLocationTranslationMapValue = new TranslationMapValue();
					$shelfLocationTranslationMapValue->translationMapId = $shelfLocationTranslationMap->id;
					$shelfLocationTranslationMapValues = $shelfLocationTranslationMapValue->fetchAll('value', 'translation', true);
				}

				//$marc853Fields = $marcRecord->getFields('853');
				$marc866Fields = $marcRecord->getFields('866');
				$marc856Fields = $marcRecord->getFields('856');
				//@var File_MARC_Data_Field $marc82Field
				foreach ($marc852Fields as $marc852Field) {
					$marc852subfield6 = $marc852Field->getSubfield('6');
					if ($marc852subfield6 != false) {
						$marc852subfield6Data = $marc852subfield6->getData();
						$marcHolding = [];
						$marcSubfieldB = $marc852Field->getSubfield('b');
						if ($marcSubfieldB != false) {
							//handle sierra quirks of location codes where the library can be indicated with the first part of a location code
							$owningLibraryCode = trim(strtolower($marcSubfieldB->getData()));
							$owningLibrary = $owningLibraryCode;
							for ($i = strlen($owningLibraryCode); $i >= 1; $i--) {
								$tmpOwningLibraryCode = substr($owningLibraryCode, 0, $i);
								if (array_key_exists($tmpOwningLibraryCode, $libraryCodeToDisplayName)) {
									$owningLibrary = $libraryCodeToDisplayName[$tmpOwningLibraryCode];
									break;
								//Handle sierra quirks where the actual location code is specified with a z at the end
								} elseif (array_key_exists($tmpOwningLibraryCode . 'z', $libraryCodeToDisplayName)) {
									$owningLibrary = $libraryCodeToDisplayName[$tmpOwningLibraryCode . 'z'];
									break;
								}
							}
							$marcHolding['library'] = $owningLibrary;
						} else {
							continue;
						}
						$marcSubfieldC = $marc852Field->getSubfield('c');
						if ($marcSubfieldC != false) {
							$shelfLocation = trim(strtolower($marcSubfieldC->getData()));
							if ($shelfLocationTranslationMap->usesRegularExpressions) {
								foreach ($shelfLocationTranslationMapValues as $value => $translation) {
									if (preg_match($value, $shelfLocation)) {
										$shelfLocation = $translation;
										break;
									}
								}
							}else {
								if (array_key_exists($shelfLocation, $shelfLocationTranslationMapValues)) {
									$shelfLocation = $shelfLocationTranslationMapValues[$shelfLocation];
								}
							}
							$marcHolding['shelfLocation'] = $shelfLocation;
						} else {
							$marcHolding['shelfLocation'] = '';
						}
						//Nothing super useful in 853, ignore it for now
						//Load what is held in the 866
						$is866Found = false;
						foreach ($marc866Fields as $marc866Field) {
							$marc866subfield6 = $marc866Field->getSubfield('6');
							if ($marc866subfield6 != false) {
								$marc866subfield6Data = $marc866subfield6->getData();
								if ($marc866subfield6Data == $marc852subfield6Data) {
									$marc866subfieldA = $marc866Field->getSubfield('a');
									if ($marc866subfieldA != false) {
										$marcHolding['holdings'][] = $marc866subfieldA->getData();
										$is866Found = true;
									}
								}
							}
						}
						if (!$is866Found) {
							continue;
						}
						foreach ($marc856Fields as $marc856Field) {
							$marc856subfield6 = $marc856Field->getSubfield('6');
							if ($marc856subfield6 != false) {
								$marc856subfield6Data = $marc856subfield6->getData();
								if ($marc856subfield6Data == $marc852subfield6Data) {
									$marc856subfieldU = $marc856Field->getSubfield('u');
									if ($marc856subfieldU != false) {
										$marcHolding['link'] = $marc856subfieldU->getData();
										$marcHolding['linkText'] = $marc856subfieldU->getData();
									}
									$marc856subfieldY = $marc856Field->getSubfield('y');
									if ($marc856subfieldY != false) {
										$marcHolding['linkText'] = $marc856subfieldY->getData();
									} else {
										$marc856subfieldZ = $marc856Field->getSubfield('z');
										if ($marc856subfieldZ != false) {
											$marcHolding['linkText'] = $marc856subfieldZ->getData();
										}
									}
								}
							}
						}
						if (array_key_exists($owningLibraryCode, $localLocationCodes)) {
							$localMarcHoldings[] = $marcHolding;
						} else {
							$marcHoldings[] = $marcHolding;
						}
					}
					$sorter = function ($a, $b) {
						return strcasecmp($a['library'], $b['library']);
					};
					global $interface;
					$interface->assign('localMarcHoldings', $localMarcHoldings);
					$interface->assign('otherMarcHoldings', $marcHoldings);
					uasort($marcHoldings, $sorter);
					uasort($localMarcHoldings, $sorter);
					$marcHoldings = $localMarcHoldings + $marcHoldings;
				}
			}
		}
		return $marcHoldings;
	}

	public function getValidPickupLocations($pickupAtRule): array {
		$locations = [];
		$relatedRecord = $this->getGroupedWorkDriver()->getRelatedRecord($this->getIdWithSource());
		$items = $relatedRecord->getItems();
		foreach ($items as $item) {
			if ($pickupAtRule == 2) {
				//Add all locations for the owning location's parent library
				if (!isset($locations[$item->locationCode])) {
					$location = new Location();
					$location->code = $item->locationCode;
					if ($location->find(true)) {
						$library = $location->getParentLibrary();
						foreach ($library->getLocations() as $libraryBranch) {
							$locations[strtolower($libraryBranch->code)] = strtolower($libraryBranch->code);
						}
					}else{
						//This is probably a sierra library where the location code is just a portion of the item location.  Trim off characters until we get a match.
						$tmpCode = $item->locationCode;
						while (strlen($tmpCode) >= 2) {
							$tmpCode =  substr($tmpCode, 0, strlen($tmpCode) -1);
							$location = new Location();
							$location->code = $tmpCode;
							if ($location->find(true)) {
								$library = $location->getParentLibrary();
								foreach ($library->getLocations() as $libraryBranch) {
									$locations[strtolower($libraryBranch->code)] = strtolower($libraryBranch->code);
								}
							}
						}
					}
				}
			} else {
				//Add the owning location
				if (!isset($locations[$item->locationCode])) {
					$location = new Location();
					$location->code = $item->locationCode;
					if ($location->find(true)) {
						$locations[strtolower($item->locationCode)] = strtolower($location->code);
					}else{
						//This is probably a sierra library where the location code is just a portion of the item location.  Trim off characters until we get a match.
						$tmpCode = $item->locationCode;
						while (strlen($tmpCode) >= 2) {
							$tmpCode =  substr($tmpCode, 0, strlen($tmpCode) -1);
							$location = new Location();
							$location->code = $tmpCode;
							if ($location->find(true)) {
								$locations[strtolower($item->locationCode)] = strtolower($location->code);
								break;
							}
						}
					}
				}
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
			$unloadMarc = $this->marcRecord == null;
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
					} else {
						if ($subfieldU != null) {
							$showAction = true;
						}
					}
					if ($showAction) {
						$subfieldZ = $marc856Field->getSubfield('z');
						if ($subfieldZ != null) {
							$label = $subfieldZ->getData();
						} else {
							$label = $subfieldU->getData();
						}
						$validUrls[] = [
							'url' => $subfieldU->getData(),
							'label' => $label,
						];
					}
				}
			}
			//Since this is called from search results, unload the MARC to preserve memory
			if ($unloadMarc) {
				$this->marcRecord = null;
			}

			$this->validUrls = $validUrls;
		}
		return $this->validUrls;
	}

	/**
	 * @param Grouping_Record|null $relatedRecord
	 * @param $variationId
	 * @return array
	 */
	public function getInterLibraryLoanIntegrationInformation(?Grouping_Record $relatedRecord, $variationId): array {
//See if we have InterLibrary Loan integration. If so, we will either be placing a hold or requesting depending on if there is a copy local to the hold group (whether available or not)
		$interLibraryLoanType = 'none';
		$treatHoldAsInterLibraryLoanRequest = false;
		$homeLocation = null;
		$holdGroups = [];
		try {
			$homeLocation = Location::getDefaultLocationForUser();
			if ($homeLocation != null) {
				$interLibraryLoanType = $homeLocation->getInterlibraryLoanType();
				if ($interLibraryLoanType != 'none') {
					$treatHoldAsInterLibraryLoanRequest = true;
					require_once ROOT_DIR . '/sys/InterLibraryLoan/HoldGroup.php';
					require_once ROOT_DIR . '/sys/InterLibraryLoan/HoldGroupLocation.php';

					//Get the Hold Group(s) that we will interact with
					$holdGroupsForLocation = new HoldGroupLocation();
					$holdGroupsForLocation->locationId = $homeLocation->locationId;
					$holdGroupIds = $holdGroupsForLocation->fetchAll('holdGroupId');
					foreach ($holdGroupIds as $holdGroupId) {
						$holdGroup = new HoldGroup();
						$holdGroup->id = $holdGroupId;
						if ($holdGroup->find(true)) {
							$holdGroups[] = clone $holdGroup;
						}
					}

					//Check to see if we have any items that are owned by any of the records in any of the groups.
					//If we do, we don't need to use VDX
					if ($this->oneOrMoreHoldableItemsOwnedByPatronHoldGroups($relatedRecord->getItems(), $holdGroups, $variationId, $homeLocation->code)) {
						$treatHoldAsInterLibraryLoanRequest = false;
					}
				}
			}
		} catch (Exception $e) {
			//This happens if the tables are not installed yet
		}
		return array(
			$interLibraryLoanType,
			$treatHoldAsInterLibraryLoanRequest,
			$homeLocation,
			$holdGroups
		);
	}

	public function getAudience(): string|null {
		if (!$this->getMarcRecord()) {
			return null;
		}
		$descriptionField = $this->getMarcRecord()->getField('521');
		if (!$descriptionField || !$descriptionField->getSubfield('a')) {
			return null;
		}
		return $descriptionField->getSubfield('a')->getData();
	}
}


