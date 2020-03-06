<?php

require_once ROOT_DIR . '/sys/Indexing/TranslationMap.php';
require_once ROOT_DIR . '/sys/Indexing/FormatMapValue.php';
require_once ROOT_DIR . '/sys/Indexing/StatusMapValue.php';
require_once ROOT_DIR . '/sys/Indexing/TimeToReshelve.php';
require_once ROOT_DIR . '/sys/Indexing/SierraExportFieldMapping.php';

class IndexingProfile extends DataObject
{
	public $__table = 'indexing_profiles';    // table name
	public $__displayNameColumn = 'name';

	public $id;
	public $name;
	public $marcPath;
	public $marcEncoding;
	public $filenamesToInclude;
	public $individualMarcPath;
	public $numCharsToCreateFolderFrom;
	public $createFolderFromLeadingCharacters;
	public $groupingClass;
	public $indexingClass;
	public $recordDriver;
	public $catalogDriver;
	public $recordUrlComponent;
	public $treatUnknownLanguageAs;
	public $treatUndeterminedLanguageAs;
	public $formatSource;
	public $specifiedFormat;
	public $specifiedFormatCategory;
	public $specifiedFormatBoost;
	public $recordNumberTag;
	public $recordNumberSubfield;
	public $recordNumberPrefix;
	public $suppressItemlessBibs;
	public $itemTag;
	public $itemRecordNumber;
	public $useItemBasedCallNumbers;
	public $callNumberPrestamp;
	public $callNumber;
	public $callNumberCutter;
	public $callNumberPoststamp;
	public $location;
	public $nonHoldableLocations;
	public $locationsToSuppress;
	public $subLocation;
	public $shelvingLocation;
	public $collection;
	public $collectionsToSuppress;
	public $volume;
	public $itemUrl;
	public $barcode;
	public $status;
	public $nonHoldableStatuses;
	public $statusesToSuppress;
	public $totalCheckouts;
	public $lastYearCheckouts;
	public $yearToDateCheckouts;
	public $totalRenewals;
	public $iType;
	public $nonHoldableITypes;
	public $dueDate;
	public $dueDateFormat;
	public $dateCreated;
	public $dateCreatedFormat;
	public $lastCheckinDate;
	public $lastCheckinFormat;
	public $iCode2;
	public $useICode2Suppression;
	public $format;
	public $eContentDescriptor;
	public $orderTag;
	public $orderStatus;
	public $orderLocation;
	public $orderLocationSingle;
	public $orderCopies;
	public $orderCode3;
	public $doAutomaticEcontentSuppression;
	public $groupUnchangedFiles;
	public $runFullUpdate;
	public $lastUpdateOfChangedRecords;
	public $lastUpdateOfAllRecords;
	public $lastUpdateFromMarcExport;

	static function getObjectStructure()
	{
		$translationMapStructure = TranslationMap::getObjectStructure();
		unset($translationMapStructure['indexingProfileId']);

		$sierraMappingStructure = SierraExportFieldMapping::getObjectStructure();
		unset($sierraMappingStructure['indexingProfileId']);

		$statusMapStructure = StatusMapValue::getObjectStructure();
		unset($statusMapStructure['indexingProfileId']);

		$formatMapStructure = FormatMapValue::getObjectStructure();
		unset($formatMapStructure['indexingProfileId']);

		$structure = array(
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id within the database'),
			'name' => array('property' => 'name', 'type' => 'text', 'label' => 'Name', 'maxLength' => 50, 'description' => 'A name for this indexing profile', 'required' => true),
			'marcPath' => array('property' => 'marcPath', 'type' => 'text', 'label' => 'MARC Path', 'maxLength' => 100, 'description' => 'The path on the server where MARC records can be found', 'required' => true),
			'filenamesToInclude' => array('property' => 'filenamesToInclude', 'type' => 'text', 'label' => 'Filenames to Include', 'maxLength' => 250, 'description' => 'A regular expression to determine which files should be grouped and indexed', 'required' => true, 'default' => '.*\.ma?rc'),
			'groupUnchangedFiles' => array('property' => 'groupUnchangedFiles', 'type' => 'checkbox', 'label' => 'Group unchanged files', 'description' => 'Whether or not files that have not changed since the last time grouping has run will be processed.'),
			'marcEncoding' => array('property' => 'marcEncoding', 'type' => 'enum', 'label' => 'MARC Encoding', 'values' => array('MARC8' => 'MARC8', 'UTF8' => 'UTF8', 'UNIMARC' => 'UNIMARC', 'ISO8859_1' => 'ISO8859_1', 'BESTGUESS' => 'BESTGUESS'), 'default' => 'MARC8'),
			'individualMarcPath' => array('property' => 'individualMarcPath', 'type' => 'text', 'label' => 'Individual MARC Path', 'maxLength' => 100, 'description' => 'The path on the server where individual MARC records can be found', 'required' => true),
			'numCharsToCreateFolderFrom' => array('property' => 'numCharsToCreateFolderFrom', 'type' => 'integer', 'label' => 'Number of characters to create folder from', 'maxLength' => 50, 'description' => 'The number of characters to use when building a sub folder for individual marc records', 'required' => false, 'default' => '4'),
			'createFolderFromLeadingCharacters' => array('property' => 'createFolderFromLeadingCharacters', 'type' => 'checkbox', 'label' => 'Create Folder From Leading Characters', 'description' => 'Whether we should look at the start or end of the folder when .', 'hideInLists' => true, 'default' => 0),

			'groupingClass' => array('property' => 'groupingClass', 'type' => 'text', 'label' => 'Grouping Class', 'maxLength' => 50, 'description' => 'The class to use while grouping the records', 'required' => true, 'default' => 'MarcRecordGrouper'),
			'indexingClass' => array('property' => 'indexingClass', 'type' => 'text', 'label' => 'Indexing Class', 'maxLength' => 50, 'description' => 'The class to use while indexing the records', 'required' => true, 'default' => 'IlsRecord'),
			'recordDriver' => array('property' => 'recordDriver', 'type' => 'text', 'label' => 'Record Driver', 'maxLength' => 50, 'description' => 'The record driver to use while displaying information in Pika', 'required' => true, 'default' => 'MarcRecordDriver'),
			'catalogDriver' => array('property' => 'catalogDriver', 'type' => 'text', 'label' => 'Catalog Driver', 'maxLength' => 50, 'description' => 'The driver to use for ILS integration', 'required' => true, 'default' => 'AbstractIlsDriver'),

			'recordUrlComponent' => array('property' => 'recordUrlComponent', 'type' => 'text', 'label' => 'Record URL Component', 'maxLength' => 50, 'description' => 'The Module to use within the URL', 'required' => true, 'default' => 'Record'),

			'recordNumberTag' => array('property' => 'recordNumberTag', 'type' => 'text', 'label' => 'Record Number Tag', 'maxLength' => 3, 'description' => 'The MARC tag where the record number can be found', 'required' => true),
			'recordNumberSubfield' => array('property' => 'recordNumberSubfield', 'type' => 'text', 'label' => 'Record Number Subfield', 'maxLength' => 1, 'description' => 'The subfield where the record number is stored', 'required' => true, 'default' => 'a'),
			'recordNumberPrefix' => array('property' => 'recordNumberPrefix', 'type' => 'text', 'label' => 'Record Number Prefix', 'maxLength' => 10, 'description' => 'A prefix to identify the bib record number if multiple MARC tags exist'),

			'treatUnknownLanguageAs' => ['property' => 'treatUnknownLanguageAs', 'type'=>'text', 'label' => 'Treat Unknown Language As', 'maxLength' => 50, 'description' => 'Records with an Unknown Language will use this language instead.  Leave blank for Unknown', 'default' => 'English'],
			'treatUndeterminedLanguageAs' => ['property' => 'treatUndeterminedLanguageAs', 'type'=>'text', 'label' => 'Treat Undetermined Language As', 'maxLength' => 50, 'description' => 'Records with an Undetermined Language will use this language instead.  Leave blank for Unknown', 'default' => 'English'],

			'itemSection' => ['property' => 'itemSection', 'type' => 'section', 'label' => 'Item Information', 'hideInLists' => true, 'properties' => [
				'suppressItemlessBibs' => array('property' => 'suppressItemlessBibs', 'type' => 'checkbox', 'label' => 'Suppress Itemless Bibs', 'description' => 'Whether or not Itemless Bibs can be suppressed'),
				'itemTag' => array('property' => 'itemTag', 'type' => 'text', 'label' => 'Item Tag', 'maxLength' => 3, 'description' => 'The MARC tag where items can be found'),
				'itemRecordNumber' => array('property' => 'itemRecordNumber', 'type' => 'text', 'label' => 'Item Record Number', 'maxLength' => 1, 'description' => 'Subfield for the record number for the item'),
				'useItemBasedCallNumbers' => array('property' => 'useItemBasedCallNumbers', 'type' => 'checkbox', 'label' => 'Use Item Based Call Numbers', 'description' => 'Whether or not we should use call number information from the bib or from the item records'),
				'callNumberPrestamp' => array('property' => 'callNumberPrestamp', 'type' => 'text', 'label' => 'Call Number Prestamp', 'maxLength' => 1, 'description' => 'Subfield for call number pre-stamp'),
				'callNumber' => array('property' => 'callNumber', 'type' => 'text', 'label' => 'Call Number', 'maxLength' => 1, 'description' => 'Subfield for call number'),
				'callNumberCutter' => array('property' => 'callNumberCutter', 'type' => 'text', 'label' => 'Call Number Cutter', 'maxLength' => 1, 'description' => 'Subfield for call number cutter'),
				'callNumberPoststamp' => array('property' => 'callNumberPoststamp', 'type' => 'text', 'label' => 'Call Number Poststamp', 'maxLength' => 1, 'description' => 'Subfield for call number pre-stamp'),
				'location' => array('property' => 'location', 'type' => 'text', 'label' => 'Location', 'maxLength' => 1, 'description' => 'Subfield for location'),
				'nonHoldableLocations' => array('property' => 'nonHoldableLocations', 'type' => 'text', 'label' => 'Non Holdable Locations', 'maxLength' => 255, 'description' => 'A regular expression for any locations that should not allow holds'),
				'locationsToSuppress' => array('property' => 'locationsToSuppress', 'type' => 'text', 'label' => 'Locations To Suppress', 'maxLength' => 255, 'description' => 'A regular expression for any locations that should be suppressed'),
				'subLocation' => array('property' => 'subLocation', 'type' => 'text', 'label' => 'Sub Location', 'maxLength' => 1, 'description' => 'A secondary subfield to divide locations'),
				'shelvingLocation' => array('property' => 'shelvingLocation', 'type' => 'text', 'label' => 'Shelving Location', 'maxLength' => 1, 'description' => 'A subfield for shelving location information'),
				'collection' => array('property' => 'collection', 'type' => 'text', 'label' => 'Collection', 'maxLength' => 1, 'description' => 'A subfield for collection information'),
				'collectionsToSuppress' => array('property' => 'collectionsToSuppress', 'type' => 'text', 'label' => 'Collections To Suppress', 'maxLength' => 100, 'description' => 'A regular expression for any collections that should be suppressed'),
				'volume' => array('property' => 'volume', 'type' => 'text', 'label' => 'Volume', 'maxLength' => 1, 'description' => 'A subfield for volume information'),
				'itemUrl' => array('property' => 'itemUrl', 'type' => 'text', 'label' => 'Item URL', 'maxLength' => 1, 'description' => 'Subfield for a URL specific to the item'),
				'barcode' => array('property' => 'barcode', 'type' => 'text', 'label' => 'Barcode', 'maxLength' => 1, 'description' => 'Subfield for barcode'),
				'status' => array('property' => 'status', 'type' => 'text', 'label' => 'Status', 'maxLength' => 1, 'description' => 'Subfield for status'),
				'nonHoldableStatuses' => array('property' => 'nonHoldableStatuses', 'type' => 'text', 'label' => 'Non Holdable Statuses', 'maxLength' => 255, 'description' => 'A regular expression for any statuses that should not allow holds'),
				'statusesToSuppress' => array('property' => 'statusesToSuppress', 'type' => 'text', 'label' => 'Statuses To Suppress', 'maxLength' => 100, 'description' => 'A regular expression for any statuses that should be suppressed'),
				'totalCheckouts' => array('property' => 'totalCheckouts', 'type' => 'text', 'label' => 'Total Checkouts', 'maxLength' => 1, 'description' => 'Subfield for total checkouts'),
				'lastYearCheckouts' => array('property' => 'lastYearCheckouts', 'type' => 'text', 'label' => 'Last Year Checkouts', 'maxLength' => 1, 'description' => 'Subfield for checkouts done last year'),
				'yearToDateCheckouts' => array('property' => 'yearToDateCheckouts', 'type' => 'text', 'label' => 'Year To Date', 'maxLength' => 1, 'description' => 'Subfield for checkouts so far this year'),
				'totalRenewals' => array('property' => 'totalRenewals', 'type' => 'text', 'label' => 'Total Renewals', 'maxLength' => 1, 'description' => 'Subfield for number of times this record has been renewed'),
				'iType' => array('property' => 'iType', 'type' => 'text', 'label' => 'iType', 'maxLength' => 1, 'description' => 'Subfield for iType'),
				'nonHoldableITypes' => array('property' => 'nonHoldableITypes', 'type' => 'text', 'label' => 'Non Holdable ITypes', 'maxLength' => 255, 'description' => 'A regular expression for any ITypes that should not allow holds'),
				'dueDate' => array('property' => 'dueDate', 'type' => 'text', 'label' => 'Due Date', 'maxLength' => 1, 'description' => 'Subfield for when the item is due'),
				'dueDateFormat' => array('property' => 'dueDateFormat', 'type' => 'text', 'label' => 'Due Date Format', 'maxLength' => 20, 'description' => 'Subfield for when the item is due'),
				'dateCreated' => array('property' => 'dateCreated', 'type' => 'text', 'label' => 'Date Created', 'maxLength' => 1, 'description' => 'The format of the due date.  I.e. yyMMdd see SimpleDateFormat for Java'),
				'dateCreatedFormat' => array('property' => 'dateCreatedFormat', 'type' => 'text', 'label' => 'Date Created Format', 'maxLength' => 20, 'description' => 'The format of the date created.  I.e. yyMMdd see SimpleDateFormat for Java'),
				'lastCheckinDate' => array('property' => 'lastCheckinDate', 'type' => 'text', 'label' => 'Last Check in Date', 'maxLength' => 1, 'description' => 'Subfield for when the item was last checked in'),
				'lastCheckinFormat' => array('property' => 'lastCheckinFormat', 'type' => 'text', 'label' => 'Last Check In Format', 'maxLength' => 20, 'description' => 'The format of the date the item was last checked in.  I.e. yyMMdd see SimpleDateFormat for Java'),
				'iCode2' => array('property' => 'iCode2', 'type' => 'text', 'label' => 'iCode2', 'maxLength' => 1, 'description' => 'Subfield for iCode2'),
				'useICode2Suppression' => array('property' => 'useICode2Suppression', 'type' => 'checkbox', 'label' => 'Use iCode2 suppression for items', 'description' => 'Whether or not we should suppress items based on iCode2'),
				'format' => array('property' => 'format', 'type' => 'text', 'label' => 'Format', 'maxLength' => 1, 'description' => 'The subfield to use when determining format based on item information'),
				'eContentDescriptor' => array('property' => 'eContentDescriptor', 'type' => 'text', 'label' => 'eContent Descriptor', 'maxLength' => 1, 'description' => 'Subfield to indicate that the item should be processed as eContent and how to process it'),
				'doAutomaticEcontentSuppression' => array('property' => 'doAutomaticEcontentSuppression', 'type' => 'checkbox', 'label' => 'Do Automatic eContent Suppression', 'description' => 'Whether or not eContent suppression for overdrive and hoopla records is done automatically', 'default' => false),
			]],

			'formatSection' => ['property' => 'formatMappingSection', 'type' => 'section', 'label' => 'Format Information', 'hideInLists' => true, 'properties' => [
				'formatSource' => array('property' => 'formatSource', 'type' => 'enum', 'label' => 'Load Format from', 'values' => array('bib' => 'Bib Record', 'item' => 'Item Record', 'specified' => 'Specified Value'), 'default' => 'bib'),
				'specifiedFormat' => array('property' => 'specifiedFormat', 'type' => 'text', 'label' => 'Specified Format', 'maxLength' => 50, 'description' => 'The format to set when using a defined format', 'required' => false, 'default' => ''),
				'specifiedFormatCategory' => array('property' => 'specifiedFormatCategory', 'type' => 'enum', 'values' => array('', 'Books' => 'Books', 'eBook' => 'eBook', 'Audio Books' => 'Audio Books', 'Movies' => 'Movies', 'Music' => 'Music', 'Other' => 'Other'), 'label' => 'Specified Format Category', 'maxLength' => 50, 'description' => 'The format category to set when using a defined format', 'required' => false, 'default' => ''),
				'specifiedFormatBoost' => array('property' => 'specifiedFormatBoost', 'type' => 'integer', 'label' => 'Specified Format Boost', 'maxLength' => 50, 'description' => 'The format boost to set when using a defined format', 'required' => false, 'default' => '8'),
				'formatMap' => array(
					'property' => 'formatMap',
					'type' => 'oneToMany',
					'label' => 'Format Map',
					'description' => 'The format maps for the profile.',
					'keyThis' => 'id',
					'keyOther' => 'indexingProfileId',
					'subObjectType' => 'FormatMapValue',
					'structure' => $formatMapStructure,
					'sortable' => false,
					'storeDb' => true,
					'allowEdit' => false,
					'canEdit' => false,
				),
			]],

			'statusMappingSection' => ['property' => 'statusMappingSection', 'type' => 'section', 'label' => 'Status Mappings', 'hideInLists' => true, 'properties' => [
				'statusMap' => array(
					'property' => 'statusMap',
					'type' => 'oneToMany',
					'label' => 'Status Map',
					'description' => 'The status maps for the profile.',
					'keyThis' => 'id',
					'keyOther' => 'indexingProfileId',
					'subObjectType' => 'StatusMapValue',
					'structure' => $statusMapStructure,
					'sortable' => false,
					'storeDb' => true,
					'allowEdit' => false,
					'canEdit' => false,
				),
			]],

			'orderSection' => ['property' => 'orderSection', 'type' => 'section', 'label' => 'Order Record Fields', 'hideInLists' => true, 'properties' => [
				'orderTag' => array('property' => 'orderTag', 'type' => 'text', 'label' => 'Order Tag', 'maxLength' => 3, 'description' => 'The MARC tag where order records can be found'),
				'orderStatus' => array('property' => 'orderStatus', 'type' => 'text', 'label' => 'Order Status', 'maxLength' => 1, 'description' => 'Subfield for status of the order item'),
				'orderLocationSingle' => array('property' => 'orderLocationSingle', 'type' => 'text', 'label' => 'Order Location Single', 'maxLength' => 1, 'description' => 'Subfield for location of the order item when the order applies to a single location'),
				'orderLocation' => array('property' => 'orderLocation', 'type' => 'text', 'label' => 'Order Location Multi', 'maxLength' => 1, 'description' => 'Subfield for location of the order item when the order applies to multiple locations'),
				'orderCopies' => array('property' => 'orderCopies', 'type' => 'text', 'label' => 'Order Copies', 'maxLength' => 1, 'description' => 'The number of copies if not shown within location'),
				'orderCode3' => array('property' => 'orderCode3', 'type' => 'text', 'label' => 'Order Code3', 'maxLength' => 1, 'description' => 'Code 3 for the order record'),
			]],

			'runFullUpdate' => array('property' => 'runFullUpdate', 'type' => 'checkbox', 'label' => 'Run Full Update', 'description' => 'Whether or not a full update of all records should be done on the next pass of indexing', 'default' => 0),
			'lastUpdateOfChangedRecords' => array('property' => 'lastUpdateOfChangedRecords', 'type' => 'timestamp', 'label' => 'Last Update of Changed Records', 'description' => 'The timestamp when just changes were loaded', 'default' => 0),
			'lastUpdateOfAllRecords' => array('property' => 'lastUpdateOfAllRecords', 'type' => 'timestamp', 'label' => 'Last Update of All Records', 'description' => 'The timestamp when all records were loaded from the API', 'default' => 0),
			'lastUpdateFromMarcExport' => array('property' => 'lastUpdateFromMarcExport', 'type' => 'timestamp', 'label' => 'Last Update from MARC Export', 'description' => 'The timestamp when all records were loaded from a MARC export', 'default' => 0),

			'translationMaps' => array(
				'property' => 'translationMaps',
				'type' => 'oneToMany',
				'label' => 'Translation Maps',
				'description' => 'The translation maps for the profile.',
				'keyThis' => 'id',
				'keyOther' => 'indexingProfileId',
				'subObjectType' => 'TranslationMap',
				'structure' => $translationMapStructure,
				'sortable' => false,
				'storeDb' => true,
				'allowEdit' => true,
				'canEdit' => true,
			),

			'timeToReshelve' => array(
				'property' => 'timeToReshelve',
				'type' => 'oneToMany',
				'label' => 'Time to Reshelve',
				'description' => 'Overrides for time to reshelve.',
				'keyThis' => 'id',
				'keyOther' => 'indexingProfileId',
				'subObjectType' => 'TimeToReshelve',
				'structure' => TimeToReshelve::getObjectStructure(),
				'sortable' => true,
				'storeDb' => true,
				'allowEdit' => true,
				'canEdit' => false,
			),

			'sierraFieldMappings' => array(
				'property' => 'sierraFieldMappings',
				'type' => 'oneToMany',
				'label' => 'Sierra Field Mappings (Sierra Systems only)',
				'description' => 'Field Mappings for exports from Sierra.',
				'keyThis' => 'id',
				'keyOther' => 'indexingProfileId',
				'subObjectType' => 'SierraExportFieldMapping',
				'structure' => $sierraMappingStructure,
				'sortable' => false,
				'storeDb' => true,
				'allowEdit' => true,
				'canEdit' => false,
			),
		);

		global $configArray;
		$ils = $configArray['Catalog']['ils'];
		if ($ils != 'Millennium' && $ils != 'Sierra') {
			unset($structure['sierraFieldMappings']);
		}
		return $structure;
	}

	public function __get($name)
	{
		if ($name == "translationMaps") {
			if (!isset($this->_data['translationMaps'])) {
				//Get the list of translation maps
				$this->_data['translationMaps'] = array();
				if ($this->id) { // When this is a new Indexing Profile, there are no maps yet.
					$translationMap = new TranslationMap();
					$translationMap->indexingProfileId = $this->id;
					$translationMap->orderBy('name ASC');
					$translationMap->find();
					while ($translationMap->fetch()) {
						$this->_data['translationMaps'][$translationMap->id] = clone($translationMap);
					}
				}
			}
			return $this->_data['translationMaps'];
		} else if ($name == "timeToReshelve") {
			if (!isset($this->_data['timeToReshelve'])) {
				//Get the list of translation maps
				$this->_data['timeToReshelve'] = array();
				if ($this->id) { // When this is a new Indexing Profile, there are no maps yet.
					$timeToReshelve = new TimeToReshelve();
					$timeToReshelve->indexingProfileId = $this->id;
					$timeToReshelve->orderBy('weight ASC');
					$timeToReshelve->find();
					while ($timeToReshelve->fetch()) {
						$this->_data['timeToReshelve'][$timeToReshelve->id] = clone($timeToReshelve);
					}
				}
			}
			return $this->_data['timeToReshelve'];
		} else if ($name == "sierraFieldMappings") {
			if (!isset($this->_data['sierraFieldMappings'])) {
				//Get the list of translation maps
				$this->_data['sierraFieldMappings'] = array();
				if ($this->id) { // When this is a new Indexing Profile, there are no maps yet.
					$sierraFieldMapping = new SierraExportFieldMapping();
					$sierraFieldMapping->indexingProfileId = $this->id;
					$sierraFieldMapping->find();
					while ($sierraFieldMapping->fetch()) {
						$this->_data['sierraFieldMappings'][$sierraFieldMapping->id] = clone($sierraFieldMapping);
					}
				}
			}
			return $this->_data['sierraFieldMappings'];
		} else if ($name == "statusMap") {
			if (!isset($this->_data['statusMap'])) {
				//Get the list of translation maps
				$this->_data['statusMap'] = array();
				if ($this->id) { // When this is a new Indexing Profile, there are no maps yet.
					$statusMap = new StatusMapValue();
					$statusMap->indexingProfileId = $this->id;
					$statusMap->find();
					while ($statusMap->fetch()) {
						$this->_data['statusMap'][$statusMap->id] = clone($statusMap);
					}
				}
			}
			return $this->_data['statusMap'];
		} else if ($name == "formatMap") {
			if (!isset($this->_data['formatMap'])) {
				//Get the list of translation maps
				$this->_data['formatMap'] = array();
				if ($this->id) { // When this is a new Indexing Profile, there are no maps yet.
					$formatMap = new FormatMapValue();
					$formatMap->indexingProfileId = $this->id;
					$formatMap->orderBy('value');
					$formatMap->find();
					while ($formatMap->fetch()) {
						$this->_data['formatMap'][$formatMap->id] = clone($formatMap);
					}
				}
			}
			return $this->_data['formatMap'];
		}
		return null;
	}

	public function __set($name, $value)
	{
		if ($name == "translationMaps") {
			/** @noinspection PhpUndefinedFieldInspection */
			$this->_data['translationMaps'] = $value;
		} else if ($name == "timeToReshelve") {
			/** @noinspection PhpUndefinedFieldInspection */
			$this->_data['timeToReshelve'] = $value;
		} else if ($name == "sierraFieldMappings") {
			/** @noinspection PhpUndefinedFieldInspection */
			$this->_data['sierraFieldMappings'] = $value;
		} else if ($name == "statusMap") {
			/** @noinspection PhpUndefinedFieldInspection */
			$this->_data['statusMap'] = $value;
		} else if ($name == "formatMap") {
			/** @noinspection PhpUndefinedFieldInspection */
			$this->_data['formatMap'] = $value;
		}
	}

	/**
	 * Override the update functionality to save the associated translation maps
	 *
	 * @see DB/DB_DataObject::update()
	 */
	public function update()
	{
		$ret = parent::update();
		if ($ret === FALSE) {
			global $logger;
			$logger->log('Failed to update indexing profile for ' . $this->name, Logger::LOG_ERROR);
			return $ret;
		} else {
			$this->saveTranslationMaps();
			$this->saveTimeToReshelve();
			$this->saveSierraFieldMappings();
			$this->saveStatusMap();
			$this->saveFormatMap();
		}
		return true;
	}

	/**
	 * Override the update functionality to save the associated translation maps
	 *
	 * @see DB/DB_DataObject::insert()
	 */
	public function insert()
	{
		$ret = parent::insert();
		if ($ret === FALSE) {
			global $logger;
			$logger->log('Failed to add new indexing profile for ' . $this->name, Logger::LOG_ERROR);
			return $ret;
		} else {
			$this->saveTranslationMaps();
			$this->saveTimeToReshelve();
			$this->saveSierraFieldMappings();
			$this->saveStatusMap();
			$this->saveFormatMap();
		}
		return true;
	}

	public function saveTranslationMaps()
	{
		if (isset ($this->_data['translationMaps'])) {
			/** @var TranslationMap $translationMap */
			foreach ($this->_data['translationMaps'] as $translationMap) {
				if (isset($translationMap->deleteOnSave) && $translationMap->deleteOnSave == true) {
					$translationMap->delete();
				} else {
					if (isset($translationMap->id) && is_numeric($translationMap->id)) {
						$translationMap->update();
					} else {
						$translationMap->indexingProfileId = $this->id;
						$translationMap->insert();
					}
				}
			}
			//Clear array so it is reloaded the next time
			unset($this->translationMaps);
		}
	}

	public function saveTimeToReshelve()
	{
		if (isset ($this->_data['timeToReshelve'])) {
			/** @var TimeToReshelve $timeToReshelve */
			foreach ($this->_data['timeToReshelve'] as $timeToReshelve) {
				if (isset($timeToReshelve->deleteOnSave) && $timeToReshelve->deleteOnSave == true) {
					$timeToReshelve->delete();
				} else {
					if (isset($timeToReshelve->id) && is_numeric($timeToReshelve->id)) {
						$timeToReshelve->update();
					} else {
						$timeToReshelve->indexingProfileId = $this->id;
						$timeToReshelve->insert();
					}
				}
			}
			//Clear array so it is reloaded the next time
			unset($this->_data['timeToReshelve']);
		}
	}

	public function saveSierraFieldMappings()
	{
		if (isset ($this->_data['sierraFieldMappings'])) {
			/** @var SierraExportFieldMapping $sierraFieldMapping */
			foreach ($this->_data['sierraFieldMappings'] as $sierraFieldMapping) {
				if (isset($sierraFieldMapping->deleteOnSave) && $sierraFieldMapping->deleteOnSave == true) {
					$sierraFieldMapping->delete();
				} else {
					if (isset($sierraFieldMapping->id) && is_numeric($sierraFieldMapping->id)) {
						$sierraFieldMapping->update();
					} else {
						$sierraFieldMapping->indexingProfileId = $this->id;
						$sierraFieldMapping->insert();
					}
				}
			}
			//Clear array so it is reloaded the next time
			unset($this->_data['sierraFieldMappings']);
		}
	}

	public function saveStatusMap()
	{
		if (isset ($this->_data['statusMap'])) {
			/** @var StatusMapValue $statusMapValue */
			foreach ($this->_data['statusMap'] as $statusMapValue) {
				if (isset($statusMapValue->deleteOnSave) && $statusMapValue->deleteOnSave == true) {
					$statusMapValue->delete();
				} else {
					if (isset($statusMapValue->id) && is_numeric($statusMapValue->id)) {
						$statusMapValue->update();
					} else {
						$statusMapValue->indexingProfileId = $this->id;
						$statusMapValue->insert();
					}
				}
			}
			//Clear array so it is reloaded the next time
			unset($this->_data['statusMap']);
		}
	}

	public function saveFormatMap()
	{
		if (isset ($this->_data['formatMap'])) {
			/** @var FormatMapValue $formatMapValue */
			foreach ($this->_data['formatMap'] as $formatMapValue) {
				if (isset($formatMapValue->deleteOnSave) && $formatMapValue->deleteOnSave == true) {
					$formatMapValue->delete();
				} else {
					if (isset($formatMapValue->id) && is_numeric($formatMapValue->id)) {
						$formatMapValue->update();
					} else {
						$formatMapValue->indexingProfileId = $this->id;
						$formatMapValue->insert();
					}
				}
			}
			//Clear array so it is reloaded the next time
			unset($this->_data['formatMap']);
		}
	}

	public function translate($mapName, $value)
	{
		$translationMap = new TranslationMap();
		$translationMap->name = $mapName;
		$translationMap->indexingProfileId = $this->id;
		if ($translationMap->find(true)) {
			/** @var TranslationMapValue $mapValue */
			/** @noinspection PhpUndefinedFieldInspection */
			foreach ($translationMap->translationMapValues as $mapValue) {
				if ($mapValue->value == $value) {
					return $mapValue->translation;
				} else if (substr($mapValue->value, -1) == '*') {
					if (substr($value, 0, strlen($mapValue) - 1) == substr($mapValue->value, 0, -1)) {
						return $mapValue->translation;
					}
				}
			}
		}
		return $value;
	}

	public function setStatusMapValue($value, $status = null, $groupedStatus = null)
	{
		$statusMap = $this->__get('statusMap');
		$statusExists = false;
		/** @var StatusMapValue $statusValue */
		foreach ($statusMap as $statusValue) {
			if (strcasecmp($statusValue->value, $value) == 0) {
				$statusExists = true;
				break;
			}
		}
		if (!$statusExists) {
			$statusValue = new StatusMapValue();
			$statusValue->value = $value;
			$statusValue->indexingProfileId = $this->id;
			$statusValue->status = ' ';
			$statusValue->groupedStatus = 'Currently Unavailable';
		}
		if ($status != null) {
			$statusValue->status = $status;
		}
		if ($groupedStatus != null) {
			$statusValue->groupedStatus = $groupedStatus;
		}
		$statusValue->update();
		$this->_data['statusMap'][$statusValue->id] = $statusValue;
	}

	public function setFormatMapValue($value, $format = null, $formatCategory = null, $formatBoost = null)
	{
		$formatMap = $this->__get('formatMap');
		$formatExists = false;
		/** @var FormatMapValue $formatValue */
		foreach ($formatMap as $formatValue) {
			if (strcasecmp($formatValue->value, $value) == 0) {
				$formatExists = true;
				break;
			}
		}
		if (!$formatExists) {
			$formatValue = new FormatMapValue();
			$formatValue->value = $value;
			$formatValue->indexingProfileId = $this->id;
			$formatValue->format = ' ';
			$formatValue->formatCategory = 'Other';
			$formatValue->formatBoost = 1;
		}
		if ($format != null) {
			$formatValue->format = $format;
		}
		if ($formatCategory != null) {
			$formatValue->formatCategory = $formatCategory;
		}
		if ($formatBoost != null) {
			$formatValue->formatBoost = $formatBoost;
		}
		$formatValue->update();
		$this->_data['formatMap'][$formatValue->id] = $formatValue;
	}
}