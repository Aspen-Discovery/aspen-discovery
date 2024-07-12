<?php

require_once ROOT_DIR . '/sys/Indexing/TranslationMap.php';
require_once ROOT_DIR . '/sys/Indexing/FormatMapValue.php';
require_once ROOT_DIR . '/sys/Indexing/StatusMapValue.php';
require_once ROOT_DIR . '/sys/Indexing/TimeToReshelve.php';
require_once ROOT_DIR . '/sys/Indexing/SierraExportFieldMapping.php';

class IndexingProfile extends DataObject {
	public $__table = 'indexing_profiles';    // table name
	public $__displayNameColumn = 'name';

	public $id;
	public $name;
	public $marcPath;
	public /** @noinspection PhpUnused */
		$marcEncoding;
	public /** @noinspection PhpUnused */
		$filenamesToInclude;
	public /** @noinspection PhpUnused */
		$groupingClass;
	public /** @noinspection PhpUnused */
		$indexingClass;
	public $recordDriver;
	public $catalogDriver;
	public $recordUrlComponent;
	public $processRecordLinking;
	public /** @noinspection PhpUnused */
		$treatUnknownLanguageAs;
	public /** @noinspection PhpUnused */
		$treatUndeterminedLanguageAs;
	public /** @noinspection PhpUnused */
		$formatSource;
	public /** @noinspection PhpUnused */
		$fallbackFormatField;
	public /** @noinspection PhpUnused */
		$specifiedFormat;
	public /** @noinspection PhpUnused */
		$specifiedFormatCategory;
	public /** @noinspection PhpUnused */
		$specifiedFormatBoost;
	public /** @noinspection PhpUnused */
		$checkRecordForLargePrint;
	public /** @noinspection PhpUnused */
		$recordNumberTag;
	public /** @noinspection PhpUnused */
		$recordNumberSubfield;
	public /** @noinspection PhpUnused */
		$recordNumberPrefix;
	public /** @noinspection PhpUnused */
		$customMarcFieldsToIndexAsKeyword;
	public $itemTag;
	public /** @noinspection PhpUnused */
		$itemRecordNumber;
	public /** @noinspection PhpUnused */
		$useItemBasedCallNumbers;
	public /** @noinspection PhpUnused */
		$callNumberPrestamp;
	public /** @noinspection PhpUnused */
		$callNumberPrestamp2;
	public $callNumber;
	public /** @noinspection PhpUnused */
		$callNumberCutter;
	public /** @noinspection PhpUnused */
		$callNumberPoststamp;
	public $location;
	public /** @noinspection PhpUnused */
		$includeLocationNameInDetailedLocation;
	public /** @noinspection PhpUnused */
		$nonHoldableLocations;
	public /** @noinspection PhpUnused */
		$locationsToSuppress;
	public $subLocation;
	public /** @noinspection PhpUnused */
		$shelvingLocation;
	public $collection;
	public /** @noinspection PhpUnused */
		$collectionsToSuppress;
	public $volume;
	public /** @noinspection PhpUnused */
		$itemUrl;
	public $itemUrlDescription;
	public $barcode;
	public $status;
	public /** @noinspection PhpUnused */
		$nonHoldableStatuses;
	public /** @noinspection PhpUnused */
		$statusesToSuppress;
	public /** @noinspection PhpUnused */
		$treatLibraryUseOnlyGroupedStatusesAsAvailable;
	public /** @noinspection PhpUnused */
		$totalCheckouts;
	public /** @noinspection PhpUnused */
		$lastYearCheckouts;
	public /** @noinspection PhpUnused */
		$yearToDateCheckouts;
	public /** @noinspection PhpUnused */
		$totalRenewals;
	public $iType;
	public /** @noinspection PhpUnused */
		$nonHoldableITypes;
	public /** @noinspection PhpUnused */
		$iTypesToSuppress;
	public $noteSubfield;
	public $dueDate;
	public $dueDateFormat;
	public $dateCreated;
	public /** @noinspection PhpUnused */
		$dateCreatedFormat;
	public /** @noinspection PhpUnused */
		$lastCheckinDate;
	public /** @noinspection PhpUnused */
		$lastCheckinFormat;
	public /** @noinspection PhpUnused */
		$iCode2;
	public /** @noinspection PhpUnused */
		$useICode2Suppression;
	public /** @noinspection PhpUnused */
		$iCode2sToSuppress;
	public /** @noinspection PhpUnused */
		$bCode3sToSuppress;
	public $format;
	public $useSierraMatTypeForFormat;
	public /** @noinspection PhpUnused */
		$eContentDescriptor;
	public /** @noinspection PhpUnused */
		$orderTag;
	public /** @noinspection PhpUnused */
		$orderStatus;
	public /** @noinspection PhpUnused */
		$orderLocation;
	public /** @noinspection PhpUnused */
		$orderLocationSingle;
	public /** @noinspection PhpUnused */
		$orderCopies;
	public /** @noinspection PhpUnused */
		$orderCode3;
	public /** @noinspection PhpUnused */
		$doAutomaticEcontentSuppression;
	public /** @noinspection PhpUnused */
		$suppressRecordsWithUrlsMatching;
	public /** @noinspection PhpUnused */
		$determineAudienceBy;
	public /** @noinspection PhpUnused */
		$audienceSubfield;
	public /** @noinspection PhpUnused */
		$treatUnknownAudienceAs;
	public /** @noinspection PhpUnused */
		$determineLiteraryFormBy;
	public /** @noinspection PhpUnused */
		$literaryFormSubfield;
	public /** @noinspection PhpUnused */
		$hideUnknownLiteraryForm;
	public /** @noinspection PhpUnused */
		$hideNotCodedLiteraryForm;
	public /** @noinspection PhpUnused */
		$regroupAllRecords;
	public $runFullUpdate;
	public $lastUpdateOfChangedRecords;
	public $lastUpdateOfAllRecords;
	public /** @noinspection PhpUnused */
		$lastChangeProcessed;
	public /** @noinspection PhpUnused */
		$fullMarcExportRecordIdThreshold;
	public /** @noinspection PhpUnused */
		$lastUpdateFromMarcExport;
	public /** @noinspection PhpUnused */
		$lastVolumeExportTimestamp;
	public /** @noinspection PhpUnused */
		$lastUpdateOfAuthorities;

	public $evergreenOrgUnitSchema;
	public $index856Links;
	public /** @noinspection PhpUnused */ $includePersonalAndCorporateNamesInTopics;

	public $orderRecordsStatusesToInclude;
	public $orderRecordStatusToTreatAsUnderConsideration;
	public $hideOrderRecordsForBibsWithPhysicalItems;
	public $orderRecordsToSuppressByDate;

	public $checkSierraMatTypeForFormat;

	public $customFacet1SourceField;
	public $customFacet1ValuesToInclude;
	public $customFacet1ValuesToExclude;
	public $customFacet2SourceField;
	public $customFacet2ValuesToInclude;
	public $customFacet2ValuesToExclude;
	public $customFacet3SourceField;
	public $customFacet3ValuesToInclude;
	public $customFacet3ValuesToExclude;

	public $numRetriesForBibLookups;
	public $numMillisecondsToPauseAfterBibLookups;
	public $numExtractionThreads;

	private $_translationMaps;
	private $_timeToReshelve;
	private $_sierraFieldMappings;
	private $_statusMap;
	private $_formatMap;

	public function getNumericColumnNames(): array {
		return [
			'processRecordLinking',
			'index856Links',
			'determineAudienceBy',
			'determineLiteraryFormBy',
			'hideUnknownLiteraryForm',
			'hideNotCodedLiteraryForm',
			'includePersonalAndCorporateNamesInTopics',
			'useItemBasedCallNumbers',
			'includeLocationNameInDetailedLocation',
			'treatLibraryUseOnlyGroupedStatusesAsAvailable',
			'useICode2Suppression',
			'doAutomaticEcontentSuppression',
			'checkRecordForLargePrint',
			'regroupAllRecords',
			'runFullUpdate',
			'evergreenOrgUnitSchema',
			'orderRecordsToSuppressByDate',
			'numRetriesForBibLookups',
			'numMillisecondsToPauseAfterBibLookups',
			'numExtractionThreads'
		];
	}

	static function getObjectStructure($context = ''): array {

		$showSierraFieldMappings = false;
		$showTimeToReshelve = true;
		$showLastUpdateOfAuthorities = false;
		$showItemFieldFormatInfo = false;
		$showMatTypeFormatInfo = false;
		foreach (UserAccount::getAccountProfiles() as $accountProfileInfo) {
			/** @var AccountProfile $accountProfile */
			$accountProfile = $accountProfileInfo['accountProfile'];
			if ($accountProfile->ils == 'sierra') {
				$showSierraFieldMappings = true;
				$showMatTypeFormatInfo = true;
			}elseif ($accountProfile->ils == 'koha') {
				$showTimeToReshelve = false;
				$showLastUpdateOfAuthorities = true;
				$showItemFieldFormatInfo = true;
			}
		}

		$translationMapStructure = TranslationMap::getObjectStructure($context);
		unset($translationMapStructure['indexingProfileId']);

		$sierraMappingStructure = SierraExportFieldMapping::getObjectStructure($context);
		unset($sierraMappingStructure['indexingProfileId']);

		$statusMapStructure = StatusMapValue::getObjectStructure($context);
		unset($statusMapStructure['indexingProfileId']);

		$formatMapStructure = FormatMapValue::getObjectStructure($context);
		unset($formatMapStructure['indexingProfileId']);

		if (!$showItemFieldFormatInfo) {
			unset($formatMapStructure['appliesToItemShelvingLocation']);
			unset($formatMapStructure['appliesToItemSublocation']);
			unset($formatMapStructure['appliesToItemCollection']);
			unset($formatMapStructure['appliesToItemType']);
		}
		if (!$showMatTypeFormatInfo){
			unset($formatMapStructure['appliesToMatType']);
		}

		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id within the database',
			],

			'indexingClass' => [
				'property' => 'indexingClass',
				'type' => 'enum',
				'label' => 'Indexing Class',
				'values' => [
					'' => '...',
					'Koha' => 'Koha',
					'ArlingtonKoha' => 'Arlington Koha',
					'CarlX' => 'Carl.X',
					'Evergreen' => 'Evergreen',
					'Evolve' => 'Evolve',
					'Folio' => 'Folio',
					'III' => 'III',
					'NashvilleCarlX' => 'Nashville Carl.X',
					'Polaris' => 'Polaris',
					'Symphony' => 'Symphony'
				],
				'description' => 'The class to use while indexing the records',
				'required' => true,
				'onchange' => 'return AspenDiscovery.IndexingClass.indexingClassSelect();',
				'default' => 'IlsRecord',
				'forcesReindex' => true,
			],

			'name' => [
				'property' => 'name',
				'hiddenByDefault' => true,
				'type' => 'text',
				'label' => 'Name',
				'maxLength' => 50,
				'description' => 'A name for this indexing profile',
				'required' => true,
			],

			'marcPath' => [
				'property' => 'marcPath',
				'hiddenByDefault' => true,
				'type' => 'text',
				'label' => 'MARC Path',
				'maxLength' => 100,
				'description' => 'The path on the server where MARC records can be found',
				'required' => true,
				'forcesReindex' => true,
			],

			'filenamesToInclude' => [
				'property' => 'filenamesToInclude',
				'hiddenByDefault' => true,
				'type' => 'regularExpression',
				'label' => 'Filenames to Include',
				'maxLength' => 250,
				'description' => 'A regular expression to determine which files should be grouped and indexed',
				'required' => true,
				'default' => '.*\.ma?rc',
				'forcesReindex' => true,
			],

			'marcEncoding' => [
				'property' => 'marcEncoding',
				'hiddenByDefault' => true,
				'type' => 'enum',
				'label' => 'MARC Encoding',
				'values' => [
					'MARC8' => 'MARC8',
					'UTF8' => 'UTF8',
					'UNIMARC' => 'UNIMARC',
					'ISO8859_1' => 'ISO8859_1',
					'BESTGUESS' => 'BESTGUESS',
				],
				'default' => 'MARC8',
				'forcesReindex' => true,
			],

			'groupingClass' => [
				'property' => 'groupingClass',
				'hiddenByDefault' => true,
				'type' => 'text',
				'label' => 'Grouping Class',
				'maxLength' => 50,
				'description' => 'The class to use while grouping the records',
				'required' => true,
				'default' => 'MarcRecordGrouper',
				'forcesReindex' => true,
			],

			'recordDriver' => [
				'property' => 'recordDriver',
				'hiddenByDefault' => true,
				'type' => 'text',
				'label' => 'Record Driver',
				'maxLength' => 50,
				'description' => 'The record driver to use while displaying information in Aspen Discovery',
				'required' => true,
				'default' => 'MarcRecordDriver',
			],

			'catalogDriver' => [
				'property' => 'catalogDriver',
				'hiddenByDefault' => true,
				'type' => 'text',
				'label' => 'Catalog Driver',
				'maxLength' => 50,
				'description' => 'The driver to use for ILS integration',
				'required' => true,
				'default' => 'AbstractIlsDriver',
				'forcesReindex' => true,
			],

			'recordUrlComponent' => [
				'property' => 'recordUrlComponent',
				'hiddenByDefault' => true,
				'type' => 'text',
				'label' => 'Record URL Component',
				'maxLength' => 50,
				'description' => 'The Module to use within the URL',
				'required' => true,
				'default' => 'Record',
			],

			'processRecordLinking' => [
				'property' => 'processRecordLinking',
				'hiddenByDefault' => true ,
				'type' => 'checkbox',
				'label' => 'Process Record Linking',
				'description' => 'Whether or not record linking between MARC records (in 760-787 fields) should be processed',
				'forcesReindex' => true,
				'default' => false,
			],

			'recordNumberTag' => [
				'property' => 'recordNumberTag',
				'hiddenByDefault' => true ,
				'type' => 'text',
				'label' => 'Record Number Tag',
				'maxLength' => 3,
				'description' => 'The MARC tag where the record number can be found',
				'required' => true,
				'forcesReindex' => true,
			],
			'recordNumberSubfield' => [
				'property' => 'recordNumberSubfield',
				'hiddenByDefault' => true ,
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
				'hiddenByDefault' => true ,
				'type' => 'text',
				'label' => 'Record Number Prefix',
				'maxLength' => 10,
				'description' => 'A prefix to identify the bib record number if multiple MARC tags exist',
				'forcesReindex' => true,
			],
			'customMarcFieldsToIndexAsKeyword' => [
				'property' => 'customMarcFieldsToIndexAsKeyword',
				'hiddenByDefault' => true ,
				'type' => 'text',
				'label' => 'MARC 0XX and 9XX Fields to Index as Keyword',
				'maxLength' => 255,
				'description' => 'This is a series of marc tags (3 chars identifying a marc field, e.g., 099), optionally followed by characters identifying which subfields to use. Separator of colon indicates a separate value, rather than concatenation (e.g., 901a:902ab is different than 901a:902a:902b). 008[5-7] denotes bytes 5-7 of the 008 field (0 based counting), 100[a-cf-z] denotes the bracket pattern is a regular expression indicating which subfields to include. Note: if the characters in the brackets are digits, it will be interpreted as particular bytes, NOT a pattern. 100abcd denotes subfields a, b, c, d are desired. MARC tags 100-899 are automatically included in the keyword index.',
				'forcesReindex' => true,
			],

			'treatUnknownLanguageAs' => [
				'property' => 'treatUnknownLanguageAs',
				'hiddenByDefault' => true ,
				'type' => 'text',
				'label' => 'Treat Unknown Language As',
				'maxLength' => 50,
				'description' => 'Records with an Unknown Language will use this language instead.  Leave blank for Unknown',
				'default' => 'English',
				'forcesReindex' => true,
			],
			'treatUndeterminedLanguageAs' => [
				'property' => 'treatUndeterminedLanguageAs',
				'hiddenByDefault' => true ,
				'type' => 'text',
				'label' => 'Treat Undetermined Language As',
				'maxLength' => 50,
				'description' => 'Records with an Undetermined Language will use this language instead.  Leave blank for Unknown',
				'default' => 'English',
				'forcesReindex' => true,
			],

			'index856Links' => [
				'property' => 'index856Links',
				'type' => 'checkbox',
				'label' => 'Index 856 links',
				'description' => 'Whether or not 856 links with a first indicator of 4 and second indicator of 0 or 1 are indexed and treated as items.',
				'defaultValue' => false,
				'hideInLists' => true,
				'forcesReindex' => true,
			],

			'suppressRecordsWithUrlsMatching' => [
				'property' => 'suppressRecordsWithUrlsMatching',
				'hiddenByDefault' => true ,
				'type' => 'regularExpression',
				'label' => 'Suppress Records With Urls Matching',
				'description' => 'Any records with an 856u matching the pattern will be suppressed',
				'defaultValue' => 'overdrive\.com|contentreserve\.com|hoopla|yourcloudlibrary|axis360\.baker-taylor\.com',
				'hideInLists' => true,
				'forcesReindex' => true,
			],

			'bCode3sToSuppress' => [
				'property' => 'bCode3sToSuppress',
				'hiddenByDefault' => true ,
				'type' => 'text',
				'label' => 'bCode3 values to suppress',
				'description' => 'A regular expression containing the bCode3 values to suppress (Sierra Only).',
				'forcesReindex' => true,
			],

			'determineAudienceBy' => [
				'property' => 'determineAudienceBy',
				'hiddenByDefault' => true ,
				'type' => 'enum',
				'values' => [
					'0' => 'By Bib Record Data',
					'1' => 'Item Collection using audience map',
					'2' => 'Item Shelf Location using audience map',
					'3' => 'Specified Item subfield using audience map',
				],
				'label' => 'Determine Audience By',
				'description' => 'How to determine the audience for each record',
				'default' => '0',
				'onchange' => 'return AspenDiscovery.Admin.updateIndexingProfileFields();',
				'forcesReindex' => true,
			],
			'audienceSubfield' => [
				'property' => 'audienceSubfield',
				'hiddenByDefault' => true ,
				'type' => 'text',
				'label' => 'Audience Subfield',
				'maxLength' => 1,
				'description' => 'Subfield to use when determining the audience',
				'default' => '',
			],
			'treatUnknownAudienceAs' => [
				'property' => 'treatUnknownAudienceAs',
				'hiddenByDefault' => true ,
				'type' => 'enum',
				'label' => 'Treat Unknown Audience As',
				'values' => [
					'General' => 'General',
					'Adult' => 'Adult',
					'Unknown' => 'Unknown',
				],
				'description' => 'Records with an Unknown Audience will use this audience instead.',
				'default' => 'General',
				'forcesReindex' => true,
			],

			'determineLiteraryFormBy' => [
				'property' => 'determineLiteraryFormBy',
				'hiddenByDefault' => true ,
				'type' => 'enum',
				'values' => [
					'0' => 'By Bib Record Data',
					'1' => 'Item Subfield with literary_form map',
				],
				'label' => 'Determine Literary Form By',
				'description' => 'How to determine the literary for each record',
				'default' => '0',
				'onchange' => 'return AspenDiscovery.Admin.updateIndexingProfileFields();',
				'forcesReindex' => true,
			],
			'literaryFormSubfield' => [
				'property' => 'literaryFormSubfield',
				'hiddenByDefault' => true ,
				'type' => 'text',
				'label' => 'Literary Form Subfield',
				'maxLength' => 1,
				'description' => 'Subfield to use when determining the literary form',
				'default' => '',
				'forcesReindex' => true,
			],
			'hideUnknownLiteraryForm' => [
				'property' => 'hideUnknownLiteraryForm',
				'hiddenByDefault' => true ,
				'type' => 'checkbox',
				'label' => 'Hide Unknown Literary Forms',
				'description' => 'Whether or not Literary Form Facets of Unknown are shown',
				'forcesReindex' => true,
				'default' => true,
			],
			'hideNotCodedLiteraryForm' => [
				'property' => 'hideNotCodedLiteraryForm',
				'hiddenByDefault' => true ,
				'type' => 'checkbox',
				'label' => 'Hide Not Coded Literary Forms',
				'description' => 'Whether or not Literary Form Facets of Not Coded are shown',
				'forcesReindex' => true,
				'default' => true,
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
				'hiddenByDefault' => true ,
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
					'useItemBasedCallNumbers' => [
						'property' => 'useItemBasedCallNumbers',
						'type' => 'checkbox',
						'label' => 'Use Item Based Call Numbers',
						'description' => 'Whether or not we should use call number information from the bib or from the item records',
						'forcesReindex' => true,
					],
					'callNumberPrestamp' => [
						'property' => 'callNumberPrestamp',
						'type' => 'text',
						'label' => 'Call Number Prestamp',
						'maxLength' => 1,
						'description' => 'Subfield for call number pre-stamp',
						'forcesReindex' => true,
					],
					'callNumberPrestamp2' => [
						'property' => 'callNumberPrestamp2',
						'type' => 'text',
						'label' => 'Call Number Prestamp 2',
						'maxLength' => 1,
						'description' => 'Subfield for secondary call number pre-stamp',
						'forcesReindex' => true,
					],
					'callNumber' => [
						'property' => 'callNumber',
						'type' => 'text',
						'label' => 'Call Number',
						'maxLength' => 1,
						'description' => 'Subfield for call number',
						'forcesReindex' => true,
					],
					'callNumberCutter' => [
						'property' => 'callNumberCutter',
						'type' => 'text',
						'label' => 'Call Number Cutter',
						'maxLength' => 1,
						'description' => 'Subfield for call number cutter',
						'forcesReindex' => true,
					],
					'callNumberPoststamp' => [
						'property' => 'callNumberPoststamp',
						'type' => 'text',
						'label' => 'Call Number Poststamp',
						'maxLength' => 1,
						'description' => 'Subfield for call number post-stamp',
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
					'includeLocationNameInDetailedLocation' => [
						'property' => 'includeLocationNameInDetailedLocation',
						'type' => 'checkbox',
						'label' => 'Include Location Name in Detailed Location',
						'If disabled, the detailed location will only include the shelf location.  Only suggested for single branch locations.',
						'default' => 1,
						'forcesReindex' => true,
					],
					'nonHoldableLocations' => [
						'property' => 'nonHoldableLocations',
						'type' => 'text',
						'label' => 'Non Holdable Locations',
						'maxLength' => 255,
						'description' => 'A regular expression for any locations that should not allow holds',
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
					'subLocation' => [
						'property' => 'subLocation',
						'type' => 'text',
						'label' => 'Sub Location',
						'maxLength' => 1,
						'description' => 'A secondary subfield to divide locations',
						'forcesReindex' => true,
					],
					'shelvingLocation' => [
						'property' => 'shelvingLocation',
						'type' => 'text',
						'label' => 'Shelving Location',
						'maxLength' => 1,
						'description' => 'A subfield for shelving location information',
						'forcesReindex' => true,
					],
					'collection' => [
						'property' => 'collection',
						'type' => 'text',
						'label' => 'Collection',
						'maxLength' => 1,
						'description' => 'A subfield for collection information',
						'forcesReindex' => true,
					],
					'collectionsToSuppress' => [
						'property' => 'collectionsToSuppress',
						'type' => 'text',
						'label' => 'Collections To Suppress',
						'maxLength' => 100,
						'description' => 'A regular expression for any collections that should be suppressed',
						'forcesReindex' => true,
					],
					'volume' => [
						'property' => 'volume',
						'type' => 'text',
						'label' => 'Volume',
						'maxLength' => 1,
						'description' => 'A subfield for volume information',
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
					'itemUrlDescription' => [
						'property' => 'itemUrlDescription',
						'type' => 'text',
						'label' => 'Item URL Description',
						'maxLength' => 1,
						'description' => 'Subfield for a URL description specific to the item',
						'forcesReindex' => true,
					],
					'barcode' => [
						'property' => 'barcode',
						'type' => 'text',
						'label' => 'Barcode',
						'maxLength' => 1,
						'description' => 'Subfield for barcode',
						'forcesReindex' => true,
					],
					'status' => [
						'property' => 'status',
						'type' => 'text',
						'label' => 'Status',
						'maxLength' => 1,
						'description' => 'Subfield for status',
						'forcesReindex' => true,
					],
					'nonHoldableStatuses' => [
						'property' => 'nonHoldableStatuses',
						'type' => 'text',
						'label' => 'Non Holdable Statuses',
						'maxLength' => 255,
						'description' => 'A regular expression for any statuses that should not allow holds',
						'forcesReindex' => true,
					],
					'statusesToSuppress' => [
						'property' => 'statusesToSuppress',
						'type' => 'text',
						'label' => 'Statuses To Suppress',
						'maxLength' => 255,
						'description' => 'A regular expression for any statuses that should be suppressed',
						'forcesReindex' => true,
					],
					'treatLibraryUseOnlyGroupedStatusesAsAvailable' => [
						'property' => 'treatLibraryUseOnlyGroupedStatusesAsAvailable',
						'type' => 'checkbox',
						'label' => 'Treat Library Use Only Grouped Statuses As Available',
						'description' => 'Should items that have a grouped status of Library Use Only be treated as Available',
						'forcesReindex' => true,
						'default' => 1,
					],
					'totalCheckouts' => [
						'property' => 'totalCheckouts',
						'type' => 'text',
						'label' => 'Total Checkouts',
						'maxLength' => 1,
						'description' => 'Subfield for total checkouts',
						'forcesReindex' => true,
					],
					'lastYearCheckouts' => [
						'property' => 'lastYearCheckouts',
						'type' => 'text',
						'label' => 'Last Year Checkouts',
						'maxLength' => 1,
						'description' => 'Subfield for checkouts done last year',
						'forcesReindex' => true,
					],
					'yearToDateCheckouts' => [
						'property' => 'yearToDateCheckouts',
						'type' => 'text',
						'label' => 'Year To Date',
						'maxLength' => 1,
						'description' => 'Subfield for checkouts so far this year',
						'forcesReindex' => true,
					],
					'totalRenewals' => [
						'property' => 'totalRenewals',
						'type' => 'text',
						'label' => 'Total Renewals',
						'maxLength' => 1,
						'description' => 'Subfield for number of times this record has been renewed',
						'forcesReindex' => true,
					],
					'iType' => [
						'property' => 'iType',
						'type' => 'text',
						'label' => 'iType',
						'maxLength' => 1,
						'description' => 'Subfield for iType',
						'forcesReindex' => true,
					],
					'nonHoldableITypes' => [
						'property' => 'nonHoldableITypes',
						'type' => 'text',
						'label' => 'Non Holdable ITypes',
						'maxLength' => 600,
						'description' => 'A regular expression for any ITypes that should not allow holds',
						'forcesReindex' => true,
					],
					'iTypesToSuppress' => [
						'property' => 'iTypesToSuppress',
						'type' => 'text',
						'label' => 'ITypes To Suppress',
						'maxLength' => 100,
						'description' => 'A regular expression for any ITypes that should be suppressed',
						'forcesReindex' => true,
					],
					'dueDate' => [
						'property' => 'dueDate',
						'type' => 'text',
						'label' => 'Due Date',
						'maxLength' => 1,
						'description' => 'Subfield for when the item is due',
						'forcesReindex' => true,
					],
					'dueDateFormat' => [
						'property' => 'dueDateFormat',
						'type' => 'text',
						'label' => 'Due Date Format',
						'maxLength' => 20,
						'description' => 'Subfield for when the item is due',
						'forcesReindex' => true,
					],
					'dateCreated' => [
						'property' => 'dateCreated',
						'type' => 'text',
						'label' => 'Date Created',
						'maxLength' => 1,
						'description' => 'The format of the due date.  I.e. yyMMdd see SimpleDateFormat for Java',
						'forcesReindex' => true,
					],
					'dateCreatedFormat' => [
						'property' => 'dateCreatedFormat',
						'type' => 'text',
						'label' => 'Date Created Format',
						'maxLength' => 20,
						'description' => 'The format of the date created.  I.e. yyMMdd see SimpleDateFormat for Java',
						'forcesReindex' => true,
					],
					'lastCheckinDate' => [
						'property' => 'lastCheckinDate',
						'type' => 'text',
						'label' => 'Last Check in Date',
						'maxLength' => 1,
						'description' => 'Subfield for when the item was last checked in',
						'forcesReindex' => true,
					],
					'lastCheckinFormat' => [
						'property' => 'lastCheckinFormat',
						'type' => 'text',
						'label' => 'Last Check In Format',
						'maxLength' => 20,
						'description' => 'The format of the date the item was last checked in.  I.e. yyMMdd see SimpleDateFormat for Java',
						'forcesReindex' => true,
					],
					'iCode2' => [
						'property' => 'iCode2',
						'type' => 'text',
						'label' => 'iCode2',
						'maxLength' => 1,
						'description' => 'Subfield for iCode2',
						'forcesReindex' => true,
					],
					'useICode2Suppression' => [
						'property' => 'useICode2Suppression',
						'type' => 'checkbox',
						'label' => 'Use iCode2 suppression for items',
						'description' => 'Whether or not we should suppress items based on iCode2',
						'forcesReindex' => true,
					],
					'iCode2sToSuppress' => [
						'property' => 'iCode2sToSuppress',
						'type' => 'text',
						'label' => 'iCode2 values to suppress',
						'description' => 'A regular expression containing the iCode2 values to suppress (Sierra Only).',
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
					'eContentDescriptor' => [
						'property' => 'eContentDescriptor',
						'type' => 'text',
						'label' => 'eContent Descriptor',
						'maxLength' => 1,
						'description' => 'Subfield to indicate that the item should be processed as eContent and how to process it',
						'forcesReindex' => true,
					],
					'doAutomaticEcontentSuppression' => [
						'property' => 'doAutomaticEcontentSuppression',
						'type' => 'checkbox',
						'label' => 'Do Automatic eContent Suppression',
						'description' => 'Whether or not eContent suppression for overdrive and hoopla records is done automatically',
						'default' => false,
						'forcesReindex' => true,
					],
					'noteSubfield' => [
						'property' => 'noteSubfield',
						'type' => 'text',
						'label' => 'Note',
						'maxLength' => 1,
						'description' => 'The subfield to use when loading notes for an item',
						'forcesReindex' => true,
					],
				],
			],

			'formatSection' => [
				'property' => 'formatMappingSection',
				'hiddenByDefault' => true ,
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
							//'specified' => 'Specified Value',
						],
						'default' => 'bib',
						'forcesReindex' => true,
						'onchange' => 'return AspenDiscovery.Admin.updateIndexingProfileFields();',
					],
					'fallbackFormatField' => [
						'property' => 'fallbackFormatField',
						'type' => 'text',
						'label' => 'Fallback Format Field',
						'maxLength' => 5,
						'description' => 'A fallback field to to load format from if format cannot be clearly determined',
						'required' => false,
						'default' => '',
						'forcesReindex' => true,
					],
					'checkRecordForLargePrint' => [
						'property' => 'checkRecordForLargePrint',
						'type' => 'checkbox',
						'label' => 'Check Record for Large Print',
						'default' => true,
						'description' => 'Check metadata within the record to see if a book is large print',
						'forcesReindex' => true,
					],
					'formatMap' => [
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
						'canAddNew' => true,
						'canDelete' => true,
						'forcesReindex' => true,
					],
				],
			],

			'statusMappingSection' => [
				'property' => 'statusMappingSection',
				'hiddenByDefault' => true ,
				'type' => 'section',
				'label' => 'Status Mappings',
				'hideInLists' => true,
				'properties' => [
					'statusMap' => [
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
						'canAddNew' => true,
						'canDelete' => true,
						'forcesReindex' => true,
					],
				],
			],

			'customFacetSection' => [
				'property' => 'customFacetSection',
				'hiddenByDefault' => true ,
				'type' => 'section',
				'label' => 'Custom Facets',
				'hideInLists' => true,
				'properties' => [
					'customFacet1SourceField' => [
						'property' => 'customFacet1SourceField',
						'type' => 'text',
						'label' => 'Custom Facet 1 Source',
						'maxLength' => 50,
						'description' => 'A descriptor of the field(s) and subfield(s) to load data for the facet from',
						'required' => false,
						'default' => '',
						'forcesReindex' => true,
					],
					'customFacet1ValuesToInclude' => [
						'property' => 'customFacet1ValuesToInclude',
						'type' => 'regularExpression',
						'label' => 'Custom Facet 1 Values to Include (Regex)',
						'description' => 'A regular expression for values to include, leave blank or use .* to include everything',
						'maxLength' => '500',
						'required' => false,
						'default' => '.*',
						'forcesReindex' => true,
					],
					'customFacet1ValuesToExclude' => [
						'property' => 'customFacet1ValuesToExclude',
						'type' => 'regularExpression',
						'label' => 'Custom Facet 1 Values to Exclude (Regex)',
						'description' => 'A regular expression for values to exclude, leave blank to not apply exclusions',
						'maxLength' => '500',
						'required' => false,
						'forcesReindex' => true,
					],

					'customFacet2SourceField' => [
						'property' => 'customFacet2SourceField',
						'type' => 'text',
						'label' => 'Custom Facet 2 Source',
						'maxLength' => 50,
						'description' => 'A descriptor of the field(s) and subfield(s) to load data for the facet from',
						'required' => false,
						'default' => '',
						'forcesReindex' => true,
					],
					'customFacet2ValuesToInclude' => [
						'property' => 'customFacet2ValuesToInclude',
						'type' => 'regularExpression',
						'label' => 'Custom Facet 2 Values to Include (Regex)',
						'description' => 'A regular expression for values to include, leave blank or use .* to include everything',
						'maxLength' => '500',
						'required' => false,
						'default' => '.*',
						'forcesReindex' => true,
					],
					'customFacet2ValuesToExclude' => [
						'property' => 'customFacet2ValuesToExclude',
						'type' => 'regularExpression',
						'label' => 'Custom Facet 2 Values to Exclude (Regex)',
						'description' => 'A regular expression for values to exclude, leave blank to not apply exclusions',
						'maxLength' => '500',
						'required' => false,
						'forcesReindex' => true,
					],

					'customFacet3SourceField' => [
						'property' => 'customFacet3SourceField',
						'type' => 'text',
						'label' => 'Custom Facet 3 Source',
						'maxLength' => 50,
						'description' => 'A descriptor of the field(s) and subfield(s) to load data for the facet from',
						'required' => false,
						'default' => '',
						'forcesReindex' => true,
					],
					'customFacet3ValuesToInclude' => [
						'property' => 'customFacet3ValuesToInclude',
						'type' => 'regularExpression',
						'label' => 'Custom Facet 3 Values to Include (Regex)',
						'description' => 'A regular expression for values to include, leave blank or use .* to include everything',
						'maxLength' => '500',
						'required' => false,
						'default' => '.*',
						'forcesReindex' => true,
					],
					'customFacet3ValuesToExclude' => [
						'property' => 'customFacet3ValuesToExclude',
						'type' => 'regularExpression',
						'label' => 'Custom Facet 3 Values to Exclude (Regex)',
						'description' => 'A regular expression for values to exclude, leave blank to not apply exclusions',
						'maxLength' => '500',
						'required' => false,
						'forcesReindex' => true,
					],
				],
			],

			'regroupAllRecords' => [
				'property' => 'regroupAllRecords',
				'hiddenByDefault' => true ,
				'type' => 'checkbox',
				'label' => 'Regroup all Records',
				'description' => 'Whether or not all existing records should be regrouped',
				'default' => 0,
			],
			'runFullUpdate' => [
				'property' => 'runFullUpdate',
				'hiddenByDefault' => true ,
				'type' => 'checkbox',
				'label' => 'Run Full Update',
				'description' => 'Whether or not a full update of all records should be done on the next pass of indexing',
				'default' => 0,
			],
			'lastUpdateOfChangedRecords' => [
				'property' => 'lastUpdateOfChangedRecords',
				'hiddenByDefault' => true ,
				'type' => 'timestamp',
				'label' => 'Last Update of Changed Records',
				'description' => 'The timestamp when just changes were loaded',
				'default' => 0,
			],
			'lastUpdateOfAllRecords' => [
				'property' => 'lastUpdateOfAllRecords',
				'hiddenByDefault' => true ,
				'type' => 'timestamp',
				'label' => 'Last Update of All Records',
				'description' => 'The timestamp when all records were loaded from the API',
				'default' => 0,
			],
			'lastChangeProcessed' => [
				'property' => 'lastChangeProcessed',
				'hiddenByDefault' => true ,
				'type' => 'integer',
				'label' => 'Last Change Processed',
				'description' => 'The index of the last change that was processed. Can be used for resuming API extracts if errors are generated.  (Koha only)',
				'default' => 0,
			],
			'fullMarcExportRecordIdThreshold' => [
				'property' => 'fullMarcExportRecordIdThreshold',
				'hiddenByDefault' => true ,
				'type' => 'integer',
				'label' => 'Full MARC Export Record Id Threshold',
				'description' => 'When indexing a full MARC export, verify that the maximum MARC record id in the export is at least this value',
				'default' => 0,
			],
			'lastUpdateFromMarcExport' => [
				'property' => 'lastUpdateFromMarcExport',
				'hiddenByDefault' => true ,
				'type' => 'timestamp',
				'label' => 'Last Update from MARC Export',
				'description' => 'The timestamp when all records were loaded from a MARC export',
				'default' => 0,
			],
			'lastVolumeExportTimestamp' => [
				'property' => 'lastVolumeExportTimestamp',
				'hiddenByDefault' => true ,
				'type' => 'timestamp',
				'label' => 'Last Volume Export Timestamp (Symphony Only)',
				'description' => 'The timestamp of the last volume export file used',
				'default' => 0,
			],
			'lastUpdateOfAuthorities' => [
				'property' => 'lastUpdateOfAuthorities',
				'hiddenByDefault' => true ,
				'type' => 'timestamp',
				'label' => 'Last Authority Export Timestamp (Koha Only)',
				'description' => 'The timestamp when authorities were last loaded',
				'default' => 0,
			],

			'translationMaps' => [
				'property' => 'translationMaps',
				'hiddenByDefault' => true ,
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
				'canAddNew' => true,
				'canDelete' => true,
				'forcesReindex' => true,
			],

			'timeToReshelve' => [
				'property' => 'timeToReshelve',
				'hiddenByDefault' => true ,
				'type' => 'oneToMany',
				'label' => 'Time to Reshelve',
				'description' => 'Overrides for time to reshelve.',
				'keyThis' => 'id',
				'keyOther' => 'indexingProfileId',
				'subObjectType' => 'TimeToReshelve',
				'structure' => TimeToReshelve::getObjectStructure($context),
				'sortable' => true,
				'storeDb' => true,
				'allowEdit' => true,
				'canEdit' => false,
				'canAddNew' => true,
				'canDelete' => true,
				'forcesReindex' => true,
			],

			'sierraSection' => [
				'property' => 'sierraSection',
				'type' => 'section',
				'label' => 'Sierra Settings',
				'hideInLists' => true,
				'properties' => [
					'checkSierraMatTypeForFormat' => [
						'property' => 'checkSierraMatTypeForFormat',
						'hiddenByDefault' => true,
						'type' => 'checkbox',
						'label' => 'Check Sierra Material Type during format determination',
						'description' => 'If selected, the bib Material Type from Sierra will be checked before checking bib level formats.',
						'default' => false,
					],
					'orderRecordsStatusesToInclude' => [
						'property' => 'orderRecordsStatusesToInclude',
						'hiddenByDefault' => true,
						'type' => 'text',
						'label' => 'Order Record Statuses to Include',
						'maxLength' => 25,
						'description' => 'A pipe delimited list of statuses that should be exported from Sierra for display in Aspen.',
						'default' => 'o|1',
						'required' => true,
					],
					'orderRecordStatusToTreatAsUnderConsideration' => [
						'property' => 'orderRecordStatusToTreatAsUnderConsideration',
						'hiddenByDefault' => true,
						'type' => 'text',
						'label' => 'Order Record Status to treat as Under Consideration',
						'maxLength' => 10,
						'description' => 'A order record status from Sierra to treat as Under Consideration within Aspen.',
						'default' => '',
						'required' => true,
					],
					'hideOrderRecordsForBibsWithPhysicalItems' => [
						'property' => 'hideOrderRecordsForBibsWithPhysicalItems',
						'hiddenByDefault' => true,
						'type' => 'checkbox',
						'label' => 'Hide Order Records for bibs with physical items',
						'description' => 'If selected, any bib that has physical items will not show order records.',
						'default' => false,
					],
					'orderRecordsToSuppressByDate' => [
						'property' => 'orderRecordsToSuppressByDate',
						'type' => 'enum',
						'label' => 'Order Records To Suppress by Date',
						'values' => [
							1 => 'Do not suppress by date',
							2 => 'Order Records with a Cataloged Date Set',
							3 => 'Order Records with a Received Date Set',
							4 => 'Order Records with both a Cataloged and Received Date Set'
						],
						'description' => 'Which order records should be suppressed based on dates in the record',
						'forcesReindex' => true,
						'default' => 1
					],
					'orderSection' => [
						'property' => 'orderSection',
						'hiddenByDefault' => true ,
						'type' => 'section',
						'label' => 'Order Record Fields (if order records exported in bib, not frequently used)',
						'hideInLists' => true,
						'properties' => [
							'orderTag' => [
								'property' => 'orderTag',
								'type' => 'text',
								'label' => 'Order Tag',
								'maxLength' => 3,
								'description' => 'The MARC tag where order records can be found',
								'forcesReindex' => true,
							],
							'orderStatus' => [
								'property' => 'orderStatus',
								'type' => 'text',
								'label' => 'Order Status',
								'maxLength' => 1,
								'description' => 'Subfield for status of the order item',
								'forcesReindex' => true,
							],
							'orderLocationSingle' => [
								'property' => 'orderLocationSingle',
								'type' => 'text',
								'label' => 'Order Location Single',
								'maxLength' => 1,
								'description' => 'Subfield for location of the order item when the order applies to a single location',
								'forcesReindex' => true,
							],
							'orderLocation' => [
								'property' => 'orderLocation',
								'type' => 'text',
								'label' => 'Order Location Multi',
								'maxLength' => 1,
								'description' => 'Subfield for location of the order item when the order applies to multiple locations',
								'forcesReindex' => true,
							],
							'orderCopies' => [
								'property' => 'orderCopies',
								'type' => 'text',
								'label' => 'Order Copies',
								'maxLength' => 1,
								'description' => 'The number of copies if not shown within location',
								'forcesReindex' => true,
							],
							'orderCode3' => [
								'property' => 'orderCode3',
								'type' => 'text',
								'label' => 'Order Code3',
								'maxLength' => 1,
								'description' => 'Code 3 for the order record',
								'forcesReindex' => true,
							],
						],
					],
					'sierraFieldMappings' => [
						'property' => 'sierraFieldMappings',
						'hiddenByDefault' => true ,
						'type' => 'oneToMany',
						'label' => 'Sierra Field Mappings',
						'description' => 'Field Mappings for exports from Sierra.',
						'keyThis' => 'id',
						'keyOther' => 'indexingProfileId',
						'subObjectType' => 'SierraExportFieldMapping',
						'structure' => $sierraMappingStructure,
						'sortable' => false,
						'storeDb' => true,
						'allowEdit' => true,
						'canEdit' => false,
						'canAddNew' => true,
						'canDelete' => true,
						'forcesReindex' => true,
					],
				],
			],

			'evergreenSection' => [
				'property' => 'evergreenSection',
				'type' => 'section',
				'label' => 'Evergreen Settings',
				'hideInLists' => true,
				'properties' => [
					'evergreenOrgUnitSchema' => [
						'property' => 'evergreenOrgUnitSchema',
						'type' => 'enum',
						'label' => 'Org Unit Schema',
						'values' => [
							1 => 'Level 0 = Overall instance, Level 1 = Libraries, Level 2 = Branches',
							2 => 'Level 0 = Overall instance, Level 1 = Consortium, Level 2 = Libraries, Level 3 = Branches'
						],
						'maxLength' => 3,
						'description' => 'How the schema of org units should be read by Aspen when setting up default libraries and locations',
						'forcesReindex' => true,
						'default' => 1
					],
					'numRetriesForBibLookups' => [
						'property' => 'numRetriesForBibLookups',
						'type' => 'enum',
						'label' => 'Num Retries for failed bib lookups',
						'values' => [
							0 => 'No Retries',
							1 => '1 retry',
							2 => '2 retries',
						],
						'description' => 'Controls how many retries are performed after bib lookups fail',
						'forcesReindex' => false,
						'default' => 3
					],
					'numMillisecondsToPauseAfterBibLookups' => [
						'property' => 'numMillisecondsToPauseAfterBibLookups',
						'type' => 'enum',
						'label' => 'Num Milliseconds to pause after Bib Lookups',
						'values' => [
							0 => '0 milliseconds',
							25 => '25 milliseconds',
							50 => '50 milliseconds',
							100 => '100 milliseconds',
							250 => '250 milliseconds',
							500 => '500 milliseconds',
							1000 => '1000 milliseconds',
							1500 => '1500 milliseconds',
							2000 => '2000 milliseconds',
						],
						'description' => 'Controls how long Aspen pauses after each ',
						'forcesReindex' => false,
						'default' => 0
					],
					'numExtractionThreads' => [
						'property' => 'numExtractionThreads',
						'type' => 'enum',
						'label' => 'Number of Extraction Threads',
						'values' => [
							1 => '1',
							2 => '2',
							3 => '3',
							5 => '5',
							7 => '7',
							10 => '10',
						],
						'description' => 'Controls the number of concurrent threads that can be used while extracting bibs from Evergreen ',
						'forcesReindex' => false,
						'default' => 10
					],
				],
			],

		];

		if (!$showSierraFieldMappings) {
			unset($structure['sierraFieldMappings']);
		}
		if (!$showTimeToReshelve) {
			unset($structure['timeToReshelve']);
		}
		if (!$showLastUpdateOfAuthorities) {
			unset($structure['lastUpdateOfAuthorities']);
		}

		return $structure;
	}

	public function __get($name) {
		if ($name == "translationMaps") {
			if (!isset($this->_translationMaps)) {
				//Get the list of translation maps
				$this->_translationMaps = [];
				if ($this->id) { // When this is a new Indexing Profile, there are no maps yet.
					$translationMap = new TranslationMap();
					$translationMap->indexingProfileId = $this->id;
					$translationMap->orderBy('name ASC');
					$translationMap->find();
					while ($translationMap->fetch()) {
						$this->_translationMaps[$translationMap->id] = clone($translationMap);
					}
				}
			}
			return $this->_translationMaps;
		} elseif ($name == "timeToReshelve") {
			if (!isset($this->_timeToReshelve)) {
				//Get the list of translation maps
				$this->_timeToReshelve = [];
				if ($this->id) { // When this is a new Indexing Profile, there are no maps yet.
					$timeToReshelve = new TimeToReshelve();
					$timeToReshelve->indexingProfileId = $this->id;
					$timeToReshelve->orderBy('weight ASC');
					$timeToReshelve->find();
					while ($timeToReshelve->fetch()) {
						$this->_timeToReshelve[$timeToReshelve->id] = clone($timeToReshelve);
					}
				}
			}
			return $this->_timeToReshelve;
		} elseif ($name == "sierraFieldMappings") {
			if (!isset($this->_sierraFieldMappings)) {
				//Get the list of translation maps
				$this->_sierraFieldMappings = [];
				if ($this->id) { // When this is a new Indexing Profile, there are no maps yet.
					$sierraFieldMapping = new SierraExportFieldMapping();
					$sierraFieldMapping->indexingProfileId = $this->id;
					$sierraFieldMapping->find();
					while ($sierraFieldMapping->fetch()) {
						$this->_sierraFieldMappings[$sierraFieldMapping->id] = clone($sierraFieldMapping);
					}
				}
			}
			return $this->_sierraFieldMappings;
		} elseif ($name == "statusMap") {
			if (!isset($this->_statusMap)) {
				//Get the list of translation maps
				$this->_statusMap = [];
				if ($this->id) { // When this is a new Indexing Profile, there are no maps yet.
					$statusMap = new StatusMapValue();
					$statusMap->indexingProfileId = $this->id;
					$statusMap->orderBy('value');
					$statusMap->find();
					while ($statusMap->fetch()) {
						$this->_statusMap[$statusMap->id] = clone($statusMap);
					}
				}
			}
			return $this->_statusMap;
		} elseif ($name == "formatMap") {
			if (!isset($this->_formatMap)) {
				//Get the list of translation maps
				$this->_formatMap = [];
				if ($this->id) { // When this is a new Indexing Profile, there are no maps yet.
					$formatMap = new FormatMapValue();
					$formatMap->indexingProfileId = $this->id;
					$formatMap->orderBy('value');
					$formatMap->find();
					while ($formatMap->fetch()) {
						$this->_formatMap[$formatMap->id] = clone($formatMap);
					}
				}
			}
			return $this->_formatMap;
		}
		return parent::__get($name);
	}

	public function __set($name, $value) {
		if ($name == "translationMaps") {
			$this->_translationMaps = $value;
		} elseif ($name == "timeToReshelve") {
			$this->_timeToReshelve = $value;
		} elseif ($name == "sierraFieldMappings") {
			$this->_sierraFieldMappings = $value;
		} elseif ($name == "statusMap") {
			$this->_statusMap = $value;
		} elseif ($name == "formatMap") {
			$this->_formatMap = $value;
		}else{
			parent::__set($name, $value);
		}
	}

	/**
	 * Override the update functionality to save the associated translation maps
	 *
	 * @see DB/DB_DataObject::update()
	 */
	public function update($context = '') {
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
	public function insert($context = '') {
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

	public function saveTranslationMaps() {
		if (isset ($this->_translationMaps)) {
			/** @var TranslationMap $translationMap */
			foreach ($this->_translationMaps as $translationMap) {
				if ($translationMap->_deleteOnSave == true) {
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
			unset($this->_translationMaps);
		}
	}

	public function saveTimeToReshelve() {
		if (isset ($this->_timeToReshelve)) {
			/** @var TimeToReshelve $timeToReshelve */
			foreach ($this->_timeToReshelve as $timeToReshelve) {
				if ($timeToReshelve->_deleteOnSave == true) {
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
			unset($this->_timeToReshelve);
		}
	}

	public function saveSierraFieldMappings() {
		if (isset ($this->_sierraFieldMappings)) {
			/** @var SierraExportFieldMapping $sierraFieldMapping */
			foreach ($this->_sierraFieldMappings as $sierraFieldMapping) {
				if ($sierraFieldMapping->_deleteOnSave == true) {
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
			unset($this->_sierraFieldMappings);
		}
	}

	public function saveStatusMap() {
		if (isset ($this->_statusMap)) {
			/** @var StatusMapValue $statusMapValue */
			foreach ($this->_statusMap as $statusMapValue) {
				if ($statusMapValue->_deleteOnSave == true) {
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
			unset($this->_statusMap);
		}
	}

	public function saveFormatMap() {
		if (isset ($this->_formatMap)) {
			/** @var FormatMapValue $formatMapValue */
			foreach ($this->_formatMap as $formatMapValue) {
				if ($formatMapValue->_deleteOnSave == true) {
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
			unset($this->_formatMap);
		}
	}

	public function translate($mapName, $value) {
		$translationMap = new TranslationMap();
		$translationMap->name = $mapName;
		$translationMap->indexingProfileId = $this->id;
		if ($translationMap->find(true)) {
			/** @var TranslationMapValue $mapValue */
			/** @noinspection PhpUndefinedFieldInspection */
			foreach ($translationMap->translationMapValues as $mapValue) {
				if ($mapValue->value == $value) {
					return $mapValue->translation;
				} elseif (substr($mapValue->value, -1) == '*') {
					if (substr($value, 0, strlen($mapValue) - 1) == substr($mapValue->value, 0, -1)) {
						return $mapValue->translation;
					}
				}
			}
		}
		return $value;
	}

	public function setStatusMapValue($value, $status = null, $groupedStatus = null) {
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
		$this->_statusMap[$statusValue->id] = $statusValue;
	}

	public function setFormatMapValue($value, $format = null, $formatCategory = null, $formatBoost = null) {
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
		$this->_formatMap[$formatValue->id] = $formatValue;
	}
	private $_accountProfile = false;
	public function getAccountProfile() : ?AccountProfile {
		if ($this->_accountProfile === false) {
			$this->_accountProfile = null;
			foreach (UserAccount::getAccountProfiles() as $name => $accountProfile) {
				if ($accountProfile['accountProfile']->recordSource == $this->name) {
					$this->_accountProfile = $accountProfile['accountProfile'];
					break;
				}
			}
		}
		return $this->_accountProfile;
	}
}

