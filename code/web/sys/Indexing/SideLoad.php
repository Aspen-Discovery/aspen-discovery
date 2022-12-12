<?php

require_once ROOT_DIR . '/sys/Indexing/TranslationMap.php';
require_once ROOT_DIR . '/sys/Indexing/FormatMapValue.php';
require_once ROOT_DIR . '/sys/Indexing/StatusMapValue.php';
require_once ROOT_DIR . '/sys/Indexing/SideLoadScope.php';

class SideLoad extends DataObject {
	public $__table = 'sideloads';    // table name

	public $id;
	public $name;
	public $accessButtonLabel;
	public $showStatus;
	public $marcPath;
	public /** @noinspection PhpUnused */
		$filenamesToInclude;

	public $deletedRecordsIds;

	public /** @noinspection PhpUnused */
		$marcEncoding;
	public $individualMarcPath;
	public $numCharsToCreateFolderFrom;
	public $createFolderFromLeadingCharacters;
	public /** @noinspection PhpUnused */
		$groupingClass;
	public /** @noinspection PhpUnused */
		$indexingClass;
	public $recordDriver;
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
		$suppressItemlessBibs;
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
		$specifiedFormat;
	public /** @noinspection PhpUnused */
		$specifiedFormatCategory;
	public /** @noinspection PhpUnused */
		$specifiedFormatBoost;

	public $runFullUpdate;
	public $lastUpdateOfChangedRecords;
	public $lastUpdateOfAllRecords;

	private $_scopes;

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

		global $serverName;
		return [
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
			'showStatus' => [
				'property' => 'showStatus',
				'type' => 'checkbox',
				'label' => 'Show Status',
				'description' => 'Whether or not status should be shown for the record',
				'default' => 1,
			],
			'recordUrlComponent' => [
				'property' => 'recordUrlComponent',
				'type' => 'text',
				'label' => 'Record URL Component',
				'maxLength' => 50,
				'description' => 'The Module to use within the URL',
				'required' => true,
				'default' => '{Change based on name}',
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
				'default' => "/data/aspen-discovery/{$serverName}/{sideload_name}/marc",
				'forcesReindex' => true,
			],
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
			'individualMarcPath' => [
				'property' => 'individualMarcPath',
				'type' => 'text',
				'label' => 'Individual MARC Path',
				'maxLength' => 100,
				'description' => 'The path on the server where individual MARC records can be found',
				'required' => true,
				'default' => "/data/aspen-discovery/{$serverName}/{sideload_name}/marc_recs",
				'forcesReindex' => true,
			],
			'numCharsToCreateFolderFrom' => [
				'property' => 'numCharsToCreateFolderFrom',
				'type' => 'integer',
				'label' => 'Number of characters to create folder from',
				'maxLength' => 50,
				'description' => 'The number of characters to use when building a sub folder for individual marc records',
				'required' => false,
				'default' => '4',
				'forcesReindex' => true,
			],
			'createFolderFromLeadingCharacters' => [
				'property' => 'createFolderFromLeadingCharacters',
				'type' => 'checkbox',
				'label' => 'Create Folder From Leading Characters',
				'description' => 'Whether we should look at the start or end of the folder when .',
				'hideInLists' => true,
				'default' => 0,
				'forcesReindex' => true,
			],

			'groupingClass' => [
				'property' => 'groupingClass',
				'type' => 'text',
				'label' => 'Grouping Class',
				'maxLength' => 50,
				'description' => 'The class to use while grouping the records',
				'required' => true,
				'hideInLists' => true,
				'default' => 'SideLoadedRecordGrouper',
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
			'recordDriver' => [
				'property' => 'recordDriver',
				'type' => 'text',
				'label' => 'Record Driver',
				'maxLength' => 50,
				'description' => 'The record driver to use while displaying information in Aspen Discovery',
				'required' => true,
				'hideInLists' => true,
				'default' => 'SideLoadedRecord',
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

			'itemSection' => [
				'property' => 'itemSection',
				'type' => 'section',
				'label' => 'Item Information',
				'hideInLists' => true,
				'properties' => [
					'suppressItemlessBibs' => [
						'property' => 'suppressItemlessBibs',
						'type' => 'checkbox',
						'label' => 'Suppress Itemless Bibs',
						'description' => 'Whether or not Itemless Bibs can be suppressed',
						'default' => false,
						'forcesReindex' => true,
					],
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
			],

			'scopes' => [
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
				'additionalOneToManyActions' => [],
				'forcesReindex' => true,
			],
		];
	}

	public function update($context = '') {
		if (in_array('deletedRecordsIds', $this->_changedFields)) {
			$this->runFullUpdate = true;
		}
		$ret = parent::update();
		if ($ret !== FALSE) {
			if (!file_exists($this->marcPath)) {
				mkdir($this->marcPath, 0774, true);
				chgrp($this->marcPath, 'aspen_apache');
				chmod($this->marcPath, 0775);
			}
			if (!file_exists($this->individualMarcPath)) {
				mkdir($this->individualMarcPath, 0775, true);
				chgrp($this->individualMarcPath, 'aspen_apache');
				chmod($this->individualMarcPath, 0775);
			}
			$this->saveScopes();
		}
		return true;
	}

	public function insert($context = '') {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			if (!file_exists($this->marcPath)) {
				mkdir($this->marcPath, 0775, true);
				chgrp($this->marcPath, 'aspen_apache');
				chmod($this->marcPath, 0775);
			}
			if (!file_exists($this->individualMarcPath)) {
				mkdir($this->individualMarcPath, 0775, true);
				chgrp($this->individualMarcPath, 'aspen_apache');
				chmod($this->individualMarcPath, 0775);
			}

			if (empty($this->_scopes)) {
				$this->_scopes = [];
				$allScope = new SideLoadScope();
				$allScope->sideLoadId = $this->id;
				$allScope->name = "All Records";
				$this->_scopes[] = $allScope;
			}
			$this->saveScopes();
		}
		return $ret;
	}

	public function saveScopes() {
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
			return $this->_data[$name];
		}
	}

	public function __set($name, $value) {
		if ($name == "scopes") {
			$this->_scopes = $value;
		} else {
			$this->_data[$name] = $value;
		}
	}

}