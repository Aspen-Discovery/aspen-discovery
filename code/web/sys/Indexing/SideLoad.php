<?php /** @noinspection PhpMissingFieldTypeInspection */

require_once ROOT_DIR . '/sys/Indexing/TranslationMap.php';
require_once ROOT_DIR . '/sys/Indexing/FormatMapValue.php';
require_once ROOT_DIR . '/sys/Indexing/StatusMapValue.php';
require_once ROOT_DIR . '/sys/Indexing/SideLoadScope.php';

class SideLoad extends DataObject {
	public $__table = 'sideloads';    // table name

	public $id;
	public $name;
	public /** @noinspection PhpUnused */
		$accessButtonLabel;
	public /** @noinspection PhpUnused */
		$useLinkTextForButtonLabel;
	public $showStatus;
	public $marcPath;
	public $owningLibrary;
	public $sharing;

	public /** @noinspection PhpUnused */
		$filenamesToInclude;

	public /** @noinspection PhpUnused */
		$deletedRecordsIds;

	public /** @noinspection PhpUnused */
		$marcEncoding;
	public /** @noinspection PhpUnused */
		$indexingClass;
	public /** @noinspection PhpUnused */
		$recordUrlComponent;
	public /** @noinspection PhpUnused */
		$recordNumberTag;
	public /** @noinspection PhpUnused */
		$recordNumberSubfield;
	public /** @noinspection PhpUnused */
		$recordNumberPrefix;

	public /** @noinspection PhpUnused */
		$treatUnknownLanguageAs;
	public /** @noinspection PhpUnused */
		$treatUndeterminedLanguageAs;

	public /** @noinspection PhpUnused */
		$itemTag;
	public /** @noinspection PhpUnused */
		$itemRecordNumber;
	public $location;
	public /** @noinspection PhpUnused */
		$locationsToSuppress;
	public /** @noinspection PhpUnused */
		$itemUrl;
	public $format;

	public /** @noinspection PhpUnused */
		$formatSource;
	public /** @noinspection PhpUnused */
		$convertFormatToEContent;
	public /** @noinspection PhpUnused */
		$specifiedFormat;
	public /** @noinspection PhpUnused */
		$specifiedFormatCategory;
	public /** @noinspection PhpUnused */
		$specifiedFormatBoost;

	public /** @noinspection PhpUnused */ $includePersonalAndCorporateNamesInTopics;

	public $runFullUpdate;
	public $lastUpdateOfChangedRecords;
	public $lastUpdateOfAllRecords;

	protected $_scopes;

	static function getObjectStructure($context = ''): array {
		$translationMapStructure = TranslationMap::getObjectStructure($context);
		unset($translationMapStructure['indexingProfileId']);

		$sierraMappingStructure = SierraExportFieldMapping::getObjectStructure($context);
		unset($sierraMappingStructure['indexingProfileId']);

		$statusMapStructure = StatusMapValue::getObjectStructure($context);
		unset($statusMapStructure['indexingProfileId']);

		$formatMapStructure = FormatMapValue::getObjectStructure($context);
		unset($formatMapStructure['indexingProfileId']);

		$sideLoadScopeStructure = SideLoadScope::getObjectStructure($context);
		unset($sideLoadScopeStructure['sideLoadId']);

		$allSharingOptions = [
			0 => 'Not Shared',
			1 => 'Shared with All Libraries',
			2 => 'Shared with All Libraries, Editable by Owning Library Only'
		];
		$allowableSharingOptions = $allSharingOptions;
		$allLibraryList[-1] = 'All Libraries';
		$allLibraryList = $allLibraryList + Library::getLibraryList(false);
		if (!UserAccount::userHasPermission('Administer Side Loads') && (UserAccount::userHasPermission('Administer Side Loads for Home Library') || UserAccount::userHasPermission('Administer Side Load Scopes for Home Library'))) {
			$libraryList = Library::getLibraryList(true);
			unset($allowableSharingOptions[1]);
		}else{
			$libraryList = $allLibraryList;
		}

		global $serverName;
		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id within the database',
			],
			'name' => [
				'property' => 'name',
				'type' => 'text',
				'label' => 'Name',
				'maxLength' => 50,
				'description' => 'A name for this side load',
				'required' => true,
				'serverValidation' => 'validateName',
			],
			'owningLibrary' => [
				'property' => 'owningLibrary',
				'type' => 'enum',
				'values' => $libraryList,
				'allValues' => $allLibraryList,
				'label' => 'Owning Library',
				'description' => 'Which library owns this side load',
			],
			'sharing' => [
				'property' => 'sharing',
				'type' => 'enum',
				'values' => $allowableSharingOptions,
				'allValues' => $allSharingOptions,
				'label' => 'Share With',
				'description' => 'Who the category should be shared with',
			],
			'accessButtonLabel' => [
				'property' => 'accessButtonLabel',
				'type' => 'text',
				'label' => 'Access Button Label',
				'maxLength' => 50,
				'description' => 'A label for the button to use when accessing the record',
				'required' => true,
				'default' => 'Access Online',
			],
			'useLinkTextForButtonLabel' => [
				'property' => 'useLinkTextForButtonLabel',
				'type' => 'checkbox',
				'label' => 'Use Link Text For Button Label',
				'description' => 'Whether the link text in the 856 should be used for the button text',
				'default' => false,
				'forcesReindex' => true,
			],
			'showStatus' => [
				'property' => 'showStatus',
				'type' => 'checkbox',
				'label' => 'Show Status',
				'description' => 'Whether or not status should be shown for the record',
				'default' => 1,
			],
		];
		if ($context != 'addNew') {
			$structure += [
				'recordUrlComponent' => [
					'property' => 'recordUrlComponent',
					'type' => 'text',
					'label' => 'Record URL Component',
					'maxLength' => 50,
					'description' => 'The Module to use within the URL',
					'required' => true,
					'default' => '{Change based on name}',
					'readOnly' => !UserAccount::userHasPermission(['Administer Side Loads']),
				],
				'deletedRecordsIds' => [
					'property' => 'deletedRecordsIds',
					'type' => 'textarea',
					'label' => 'Deleted Records',
					'description' => 'A list of records to that have been deleted, can be separated by commas or line breaks',
					'forcesReindex' => true,
				],
				'marcPath' => [
					'property' => 'marcPath',
					'type' => 'text',
					'label' => 'MARC Path',
					'maxLength' => 100,
					'description' => 'The path on the server where MARC records can be found',
					'required' => true,
					'default' => "/data/aspen-discovery/$serverName/{sideload_name}/marc",
					'forcesReindex' => true,
					'readOnly' => !UserAccount::userHasPermission(['Administer Side Loads']),
				],
			];
		}
		$structure += [
			'filenamesToInclude' => [
				'property' => 'filenamesToInclude',
				'type' => 'text',
				'label' => 'Filenames to Include',
				'maxLength' => 250,
				'description' => 'A regular expression to determine which files should be grouped and indexed',
				'required' => true,
				'default' => '.*\.ma?rc',
				'forcesReindex' => true,
			],
			'marcEncoding' => [
				'property' => 'marcEncoding',
				'type' => 'enum',
				'label' => 'MARC Encoding',
				'values' => [
					'MARC8' => 'MARC8',
					'UTF8' => 'UTF8',
					'UNIMARC' => 'UNIMARC',
					'ISO8859_1' => 'ISO8859_1',
					'BESTGUESS' => 'BESTGUESS',
				],
				'default' => 'UTF8',
				'forcesReindex' => true,
			],
			'indexingClass' => [
				'property' => 'indexingClass',
				'type' => 'text',
				'label' => 'Indexing Class',
				'maxLength' => 50,
				'description' => 'The class to use while indexing the records',
				'required' => true,
				'hideInLists' => true,
				'default' => 'SideLoadedEContentProcessor',
				'forcesReindex' => true,
			],

			'recordNumberTag' => [
				'property' => 'recordNumberTag',
				'type' => 'text',
				'label' => 'Record Number Tag',
				'maxLength' => 3,
				'description' => 'The MARC tag where the record number can be found',
				'required' => true,
				'default' => '001',
				'forcesReindex' => true,
			],
			'recordNumberSubfield' => [
				'property' => 'recordNumberSubfield',
				'type' => 'text',
				'label' => 'Record Number Subfield',
				'maxLength' => 1,
				'description' => 'The subfield where the record number is stored',
				'required' => true,
				'default' => 'a',
				'forcesReindex' => true,
			],
			'recordNumberPrefix' => [
				'property' => 'recordNumberPrefix',
				'type' => 'text',
				'label' => 'Record Number Prefix',
				'maxLength' => 10,
				'description' => 'A prefix to identify the bib record number if multiple MARC tags exist',
				'forcesReindex' => true,
			],

			'treatUnknownLanguageAs' => [
				'property' => 'treatUnknownLanguageAs',
				'type' => 'text',
				'label' => 'Treat Unknown Language As',
				'maxLength' => 50,
				'description' => 'Records with an Unknown Language will use this language instead.  Leave blank for Unknown',
				'default' => 'English',
				'forcesReindex' => true,
			],
			'treatUndeterminedLanguageAs' => [
				'property' => 'treatUndeterminedLanguageAs',
				'type' => 'text',
				'label' => 'Treat Undetermined Language As',
				'maxLength' => 50,
				'description' => 'Records with an Undetermined Language will use this language instead.  Leave blank for Unknown',
				'default' => 'English',
				'forcesReindex' => true,
			],
			'includePersonalAndCorporateNamesInTopics' => [
				'property' => 'includePersonalAndCorporateNamesInTopics',
				'type' => 'checkbox',
				'label' => 'Include Personal And Corporate Names In Topics Facet',
				'description' => 'Whether or not personal and corporate names are included in the topics facet',
				'default' => true,
				'forcesReindex' => true,
			],

			'itemSection' => [
				'property' => 'itemSection',
				'type' => 'section',
				'label' => 'Item Information',
				'hideInLists' => true,
				'properties' => [
					'itemTag' => [
						'property' => 'itemTag',
						'type' => 'text',
						'label' => 'Item Tag',
						'maxLength' => 3,
						'description' => 'The MARC tag where items can be found',
						'forcesReindex' => true,
					],
					'itemRecordNumber' => [
						'property' => 'itemRecordNumber',
						'type' => 'text',
						'label' => 'Item Record Number',
						'maxLength' => 1,
						'description' => 'Subfield for the record number for the item',
						'forcesReindex' => true,
					],
					'location' => [
						'property' => 'location',
						'type' => 'text',
						'label' => 'Location',
						'maxLength' => 1,
						'description' => 'Subfield for location',
						'forcesReindex' => true,
					],
					'locationsToSuppress' => [
						'property' => 'locationsToSuppress',
						'type' => 'text',
						'label' => 'Locations To Suppress',
						'maxLength' => 255,
						'description' => 'A regular expression for any locations that should be suppressed',
						'forcesReindex' => true,
					],
					'itemUrl' => [
						'property' => 'itemUrl',
						'type' => 'text',
						'label' => 'Item URL',
						'maxLength' => 1,
						'description' => 'Subfield for a URL specific to the item',
						'forcesReindex' => true,
					],
					'format' => [
						'property' => 'format',
						'type' => 'text',
						'label' => 'Format',
						'maxLength' => 1,
						'description' => 'The subfield to use when determining format based on item information',
						'forcesReindex' => true,
					],
				],
			],

			'formatSection' => [
				'property' => 'formatMappingSection',
				'type' => 'section',
				'label' => 'Format Information',
				'hideInLists' => true,
				'properties' => [
					'formatSource' => [
						'property' => 'formatSource',
						'type' => 'enum',
						'label' => 'Load Format from',
						'values' => [
							'bib' => 'Bib Record',
							'item' => 'Item Record',
							'specified' => 'Specified Value',
						],
						'default' => 'bib',
						'forcesReindex' => true,
					],
					'convertFormatToEContent' => [
						'property' => 'convertFormatToEContent',
						'type' => 'checkbox',
						'label' => 'Convert Format to eContent',
						'description' => 'Whether the format from the bib record or item record should be converted to eContent',
						'default' => true,
						'forcesReindex' => true,
					],
					'specifiedFormat' => [
						'property' => 'specifiedFormat',
						'type' => 'text',
						'label' => 'Specified Format',
						'maxLength' => 50,
						'description' => 'The format to set when using a defined format',
						'required' => false,
						'default' => '',
						'forcesReindex' => true,
					],
					'specifiedFormatCategory' => [
						'property' => 'specifiedFormatCategory',
						'type' => 'enum',
						'values' => [
							'',
							'Books' => 'Books',
							'eBook' => 'eBook',
							'Audio Books' => 'Audio Books',
							'Movies' => 'Movies',
							'Music' => 'Music',
							'Other' => 'Other',
						],
						'label' => 'Specified Format Category',
						'maxLength' => 50,
						'description' => 'The format category to set when using a defined format',
						'required' => false,
						'default' => '',
						'forcesReindex' => true,
					],
					'specifiedFormatBoost' => [
						'property' => 'specifiedFormatBoost',
						'type' => 'enum',
						'values' => [
							1 => 'None',
							'3' => 'Low',
							6 => 'Medium',
							9 => 'High',
							'12' => 'Very High',
						],
						'label' => 'Specified Format Boost',
						'description' => 'The format boost to set when using a defined format',
						'default' => '8',
						'required' => false,
						'forcesReindex' => true,
					],
				],
			],
		];

		if ($context != 'addNew') {
			$structure += [
				'runFullUpdate' => [
					'property' => 'runFullUpdate',
					'type' => 'checkbox',
					'label' => 'Run Full Update',
					'description' => 'Whether or not a full update of all records should be done on the next pass of indexing',
					'default' => 0,
				],
				'lastUpdateOfChangedRecords' => [
					'property' => 'lastUpdateOfChangedRecords',
					'type' => 'timestamp',
					'label' => 'Last Update of Changed Records',
					'description' => 'The timestamp when just changes were loaded',
					'default' => 0,
				],
				'lastUpdateOfAllRecords' => [
					'property' => 'lastUpdateOfAllRecords',
					'type' => 'timestamp',
					'label' => 'Last Update of All Records',
					'description' => 'The timestamp when all records were loaded from the API',
					'default' => 0,
				]
			];
		}

		$structure['scopes'] = [
			'property' => 'scopes',
			'type' => 'oneToMany',
			'label' => 'Scopes',
			'description' => 'Define scopes for the sideload',
			'keyThis' => 'id',
			'keyOther' => 'sideLoadId',
			'subObjectType' => 'SideLoadScope',
			'structure' => $sideLoadScopeStructure,
			'sortable' => false,
			'storeDb' => true,
			'allowEdit' => true,
			'canEdit' => true,
			'canAddNew' => true,
			'canDelete' => true,
			'additionalOneToManyActions' => [],
			'forcesReindex' => true,
		];
		return $structure;
	}

	/** @noinspection PhpUnused */
	function validateName() : array {
		$validationResults = [
			'validatedOk' => true,
			'errors' => [],
		];

		//Check to see if the name is unique
		$sideLoad = new SideLoad();
		$sideLoad->name = $this->name;
		$sideLoad->owningLibrary = $this->owningLibrary;
		if (!empty($this->id)) {
			$sideLoad->whereAdd("id != " . $this->id);
		}
		if ($sideLoad->count() > 0) {
			$validationResults['errors'][] = "A Side Load has already been created with that name for this library.  Please select another name.";
		}

		//Make sure there aren't errors
		if (count($validationResults['errors']) > 0) {
			$validationResults['validatedOk'] = false;
		}
		return $validationResults;
	}

	public function updateStructureForEditingObject($structure) : array {
		if ($this->isReadOnly()) {
			$structure['name']['readOnly'] = true;
			$structure['owningLibrary']['readOnly'] = true;
			$structure['sharing']['readOnly'] = true;
			$structure['accessButtonLabel']['readOnly'] = true;
			$structure['useLinkTextForButtonLabel']['readOnly'] = true;
			$structure['showStatus']['readOnly'] = true;
			if (isset($structure['recordUrlComponent'])) {
				$structure['recordUrlComponent']['readOnly'] = true;
			}
			if (isset($structure['deletedRecordsIds'])) {
				$structure['deletedRecordsIds']['readOnly'] = true;
			}
			if (isset($structure['marcPath'])) {
				$structure['marcPath']['readOnly'] = true;
			}
			$structure['filenamesToInclude']['readOnly'] = true;
			$structure['marcEncoding']['readOnly'] = true;
			$structure['indexingClass']['readOnly'] = true;
			$structure['recordNumberTag']['readOnly'] = true;
			$structure['recordNumberSubfield']['readOnly'] = true;
			$structure['recordNumberPrefix']['readOnly'] = true;
			$structure['treatUnknownLanguageAs']['readOnly'] = true;
			$structure['treatUndeterminedLanguageAs']['readOnly'] = true;
			$structure['includePersonalAndCorporateNamesInTopics']['readOnly'] = true;
			$structure['itemSection']['properties']['itemTag']['readOnly'] = true;
			$structure['itemSection']['properties']['itemRecordNumber']['readOnly'] = true;
			$structure['itemSection']['properties']['location']['readOnly'] = true;
			$structure['itemSection']['properties']['locationsToSuppress']['readOnly'] = true;
			$structure['itemSection']['properties']['itemUrl']['readOnly'] = true;
			$structure['itemSection']['properties']['format']['readOnly'] = true;
			$structure['formatSection']['properties']['formatSource']['readOnly'] = true;
			$structure['formatSection']['properties']['convertFormatToEContent']['readOnly'] = true;
			$structure['formatSection']['properties']['specifiedFormat']['readOnly'] = true;
			$structure['formatSection']['properties']['specifiedFormatCategory']['readOnly'] = true;
			$structure['formatSection']['properties']['specifiedFormatBoost']['readOnly'] = true;
			if (isset($structure['runFullUpdate'])) {
				$structure['runFullUpdate']['readOnly'] = true;
			}
			if (isset($structure['lastUpdateOfChangedRecords'])) {
				$structure['lastUpdateOfChangedRecords']['readOnly'] = true;
			}
			if (isset($structure['lastUpdateOfAllRecords'])) {
				$structure['lastUpdateOfAllRecords']['readOnly'] = true;
			}
			$structure['scopes']['readOnly'] = true;
		}
		return $structure;
	}

	public function delete($useWhere = false) : int {
		//Delete all scopes for the sideload
		if (!$useWhere) {
			if (!empty($this->id)) {
				$sideLoadScope = new SideLoadScope();
				$sideLoadScope->sideLoadId = $this->id;
				$sideLoadScope->find();
				while ($sideLoadScope->fetch()) {
					$sideLoadScope->delete($useWhere);
				}
			}
		}
		return parent::delete($useWhere);
	}

	public function update($context = '') : bool|int {
		if (!empty($this->_changedFields) && in_array('deletedRecordsIds', $this->_changedFields)) {
			$this->runFullUpdate = true;
		}
		$ret = parent::update();
		if ($ret !== FALSE) {
			if (!file_exists($this->marcPath)) {
				mkdir($this->marcPath, 0774, true);
				chgrp($this->marcPath, 'aspen_apache');
				chmod($this->marcPath, 0775);
			}
			$this->saveScopes();
		}
		return true;
	}

	public function insert($context = ''): int|bool {
		//Generate the default record url component
		global $serverName;
		$defaultUrlComponent = $this->name;
		if ($this->owningLibrary != -1) {
			//Add the library code for the owning library
			$library = new Library();
			if ($library->get($this->owningLibrary)){
				$defaultUrlComponent .= '_' . $library->displayName;
			}
		}
		$this->recordUrlComponent = preg_replace('/[^a-zA-Z0-9_]/', '', $defaultUrlComponent);
		$this->marcPath = "/data/aspen-discovery/$serverName/" . strtolower($this->recordUrlComponent) . "/marc";

		$ret = parent::insert();
		if ($ret !== FALSE) {
			if (!file_exists($this->marcPath)) {
				mkdir($this->marcPath, 0775, true);
				chgrp($this->marcPath, 'aspen_apache');
				chmod($this->marcPath, 0775);
			}

			if (empty($this->_scopes)) {
				$this->_scopes = [];
				$allScope = new SideLoadScope();
				$allScope->sideLoadId = $this->id;
				$allScope->name = "All Records";
				$allScope->includeAdult = 1;
				$allScope->includeTeen = 1;
				$allScope->includeKids = 1;
				$allScope->insert();
				
				//If the user has access to only create side loads for their own library, automatically assign to all libraries and locations. 
				if (!UserAccount::userHasPermission('Administer Side Loads')) {
					$libraryList = Library::getLibraryList(true);
					$libraryScopeLinks = [];
					foreach ($libraryList as $libraryId => $name) {
						$libraryScopeLink = new LibrarySideLoadScope();
						$libraryScopeLink->sideLoadScopeId = $allScope->id;
						$libraryScopeLink->libraryId = $libraryId;
						$libraryScopeLink->insert();
						$libraryScopeLinks[] = $libraryScopeLink;
					}
					$allScope->setLibraries($libraryScopeLinks);
					$locationList = Location::getLocationList(true);
					$locationScopeLinks = [];
					foreach ($locationList as $locationId => $name) {
						$locationScopeLink = new LocationSideLoadScope();
						$locationScopeLink->sideLoadScopeId = $allScope->id;
						$locationScopeLink->locationId = $locationId;
						$locationScopeLink->insert();
						$locationScopeLinks[] = $locationScopeLink;
					}
					$allScope->setLocations($locationScopeLinks);
				}
				
				$this->_scopes[] = $allScope;
			}
			$this->saveScopes();
		}
		return $ret;
	}

	public function saveScopes() : void  {
		if (isset ($this->_scopes) && is_array($this->_scopes)) {
			$this->saveOneToManyOptions($this->_scopes, 'sideLoadId');
			unset($this->_scopes);
		}
	}

	public function __get($name) {
		if ($name == "scopes") {
			if (!isset($this->_scopes) && $this->id) {
				$this->_scopes = [];
				$scope = new SideLoadScope();
				$scope->sideLoadId = $this->id;
				$scope->find();
				while ($scope->fetch()) {
					$this->_scopes[$scope->id] = clone($scope);
				}
			}
			return $this->_scopes;
		} else {
			return parent::__get($name);
		}
	}

	public function __set($name, $value) {
		if ($name == "scopes") {
			$this->_scopes = $value;
		} else {
			parent::__set($name, $value);
		}
	}

	/**
	 * Determine if the active user can view the side load details in the edit form.
	 * The form may still be largely read-only depending on how it is shared.
	 * @return bool
	 */
	public function canActiveUserEdit() : bool {
		//Active user can edit if they have permission to edit everything or this is for their home location or sharing allows editing
		if (UserAccount::userHasPermission('Administer Side Loads')) {
			return true;
		}elseif (UserAccount::userHasPermission('Administer Side Loads for Home Library') || UserAccount::userHasPermission('Administer Side Load Scopes for Home Library')){
			//If we see it, we can edit it, but it might be read-only
			return true;
		}else{
			return false;
		}
	}

	private ?bool $_isReadOnly = null;
	/**
	 * Determine whether the SideLoad can be changed by the active user.
	 * This is slightly different from canActiveUserEdit because we want the user to be able to view
	 * but not change the side load and access the scope(s) they have access to
	 *
	 * @return bool
	 */
	public function isReadOnly() : bool {
		if ($this->_isReadOnly === null) {
			//Active user can edit if they have permission to edit everything or this is for their home location or sharing allows editing
			if (UserAccount::userHasPermission('Administer Side Loads')) {
				$this->_isReadOnly = false;
			}elseif (UserAccount::userHasPermission('Administer Side Loads for Home Library')){
				$allowableLibraries = Library::getLibraryList(true);
				if (array_key_exists($this->owningLibrary, $allowableLibraries)) {
					$this->_isReadOnly = false;
				}else{
					//Ok if shared by everyone
					if ($this->sharing == 1) {
						$this->_isReadOnly = false;
					}else{
						$this->_isReadOnly = true;
					}
				}
			}else{ //Administer Scopes for Home Library Only
				$this->_isReadOnly = true;
			}
		}
		return $this->_isReadOnly;
	}

}