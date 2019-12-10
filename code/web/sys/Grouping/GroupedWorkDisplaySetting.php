<?php
require_once ROOT_DIR . '/sys/LibraryLocation/LibraryFacetSetting.php';

/**
 * Class GroupedWorkDisplaySetting
 * Stores information about display settings for Grouped Work searches and full records
 * so they can be configured once and applied to different libraries and locations
 */
class GroupedWorkDisplaySetting extends DataObject
{
	public $__table = 'grouped_work_display_settings';
	public $id;
	public $name;

	//Processing search
	public $applyNumberOfHoldingsBoost;

	//Search Results Display
	public $showSearchTools;
	public $showQuickCopy;
	public $showInSearchResultsMainDetails;
	public $alwaysShowSearchResultsMainDetails;

	//Contents of search
	public $includeOutOfSystemExternalLinks;

	//Availability Toggles
	public $availabilityToggleLabelSuperScope;
	public $availabilityToggleLabelLocal;
	public $availabilityToggleLabelAvailable;
	public $availabilityToggleLabelAvailableOnline;
	public $baseAvailabilityToggleOnLocalHoldingsOnly;
	public $includeOnlineMaterialsInAvailableToggle;

	//Faceting
	public $includeAllRecordsInShelvingFacets;
	public $includeAllRecordsInDateAddedFacets;
	public $_facets;

	// Use this to set which details will be shown in the the Main Details section of the record in the search results.
	// You should be able to add options here without needing to change the database.
	// set the key to the desired SMARTY template variable name, set the value to the label to show in the library configuration page
	static $searchResultsMainDetailsOptions = array(
		'showSeries'               => 'Show Series',
		'showPublisher'            => 'Publisher',
		'showPublicationDate'      => 'Publisher Date',
		'showEditions'             => 'Editions',
		'showPhysicalDescriptions' => 'Physical Descriptions',
		'showLanguages'            => 'Show Language',
		'showArInfo'               => 'Show Accelerated Reader Information',
		'showLexileInfo'           => 'Show Lexile Information',
		'showFountasPinnell'       => 'Show Fountas &amp; Pinnell Information  (This data must be present in MARC records)',
	);

	static function getObjectStructure(){
		$facetSettingStructure = LibraryFacetSetting::getObjectStructure();
		unset($facetSettingStructure['weight']);
		unset($facetSettingStructure['libraryId']);
		unset($facetSettingStructure['numEntriesToShowByDefault']);
		unset($facetSettingStructure['showAsDropDown']);

		$structure = [
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id within the database'),
			'name' => array('property' => 'name', 'type' => 'text', 'label' => 'Display Name', 'description' => 'The name of the settings', 'size' => '40', 'maxLength'=>255),

			'searchingSection' => ['property'=>'searchingSection', 'type' => 'section', 'label' =>'Searching', 'hideInLists' => true,
				'helpLink'=>'https://docs.google.com/document/d/1QQ7bNfGx75ImTguxEOmf7eCtdrVN9vi8FpWtWY_O3OU',
				'properties' => [
					'applyNumberOfHoldingsBoost'               => array('property' => 'applyNumberOfHoldingsBoost', 'type'=>'checkbox', 'label'=>'Apply Number Of Holdings Boost', 'description'=>'Whether or not the relevance will use boosting by number of holdings in the catalog.', 'hideInLists' => true, 'default' => 1),
					'includeOutOfSystemExternalLinks'          => array('property' => 'includeOutOfSystemExternalLinks', 'type'=>'checkbox', 'label'=>'Include Out Of System External Links', 'description'=>'Whether or not to include external links from other library systems.  Should only be enabled for global scope.', 'hideInLists' => true, 'default'=>0),
					'searchResultsSection' => array('property' => 'searchResultsSection', 'type' => 'section', 'label' => 'Search Results', 'hideInLists' => true, 'properties' => array(
						'showSearchTools'                        => array('property' => 'showSearchTools',                    'type' => 'checkbox',    'label' => 'Show Search Tools',                                          'description' => 'Turn on to activate search tools (save search, export to excel, rss feed, etc).', 'hideInLists' => true),
						'showQuickCopy'                          => array('property' => 'showQuickCopy',                      'type' => 'checkbox',    'label' => 'Show Quick Copy',                                            'description' => 'Turn on to to show Quick Copy Link in search results.', 'hideInLists' => true),
						'showInSearchResultsMainDetails'         => array('property' => 'showInSearchResultsMainDetails',     'type' => 'multiSelect', 'label' => 'Optional details to show for a record in search results : ', 'description' => 'Selected details will be shown in the main details section of a record on a search results page.', 'listStyle' => 'checkboxSimple', 'values' => self::$searchResultsMainDetailsOptions),
						'alwaysShowSearchResultsMainDetails'     => array('property' => 'alwaysShowSearchResultsMainDetails', 'type' => 'checkbox',    'label' => 'Always Show Selected Search Results Main Details',           'description' => 'Turn on to always show the selected details even when there is no info supplied for a detail, or the detail varies due to multiple formats and/or editions). Does not apply to Series & Language', 'hideInLists' => true),
					)),
					'searchFacetsSection' => array('property' => 'searchFacetsSection', 'type' => 'section', 'label' => 'Search Facets', 'hideInLists' => true, 'properties' => array(
						'availabilityToggleLabelSuperScope'        => array('property' => 'availabilityToggleLabelSuperScope',        'type' => 'text',     'label' => 'SuperScope Toggle Label',                                  'description' => 'The label to show when viewing super scope i.e. Consortium Name / Entire Collection / Everything.  Does not show if super scope is not enabled.', 'default' => 'Entire Collection'),
						'availabilityToggleLabelLocal'             => array('property' => 'availabilityToggleLabelLocal',             'type' => 'text',     'label' => 'Local Collection Toggle Label',                            'description' => 'The label to show when viewing the local collection i.e. Library Name / Local Collection.  Leave blank to hide the button.', 'default' => ''),
						'availabilityToggleLabelAvailable'         => array('property' => 'availabilityToggleLabelAvailable',         'type' => 'text',     'label' => 'Available Toggle Label',                                   'description' => 'The label to show when viewing available items i.e. Available Now / Available Locally / Available Here.', 'default' => 'Available Now'),
						'availabilityToggleLabelAvailableOnline'   => array('property' => 'availabilityToggleLabelAvailableOnline',   'type' => 'text', 'label' => 'Available Online Toggle Label', 'description' => 'The label to show when viewing available items i.e. Available Online.', 'default' => 'Available Online'),
						'includeOnlineMaterialsInAvailableToggle'  => array('property' => 'includeOnlineMaterialsInAvailableToggle',  'type'=>'checkbox', 'label'=>'Include Online Materials in Available Toggle', 'description'=>'Turn on to include online materials in both the Available Now and Available Online Toggles.', 'hideInLists' => true, 'default'=>false),
						'includeAllRecordsInShelvingFacets'        => array('property' => 'includeAllRecordsInShelvingFacets',        'type' => 'checkbox', 'label' => 'Include All Records In Shelving Facets',                   'description'=>'Turn on to include all records (owned and included) in shelving related facets (detailed location, collection).', 'hideInLists' => true, 'default'=>false),
						'includeAllRecordsInDateAddedFacets'       => array('property' => 'includeAllRecordsInDateAddedFacets',       'type' => 'checkbox', 'label' => 'Include All Records In Date Added Facets',                 'description'=>'Turn on to include all records (owned and included) in date added facets.', 'hideInLists' => true, 'default'=>false),

						'facets' => array(
							'property' => 'facets',
							'type' => 'oneToMany',
							'label' => 'Facets',
							'description' => 'A list of facets to display in search results',
							'helpLink' => 'https://docs.google.com/document/d/1DIOZ-HCqnrBAMFwAomqwI4xv41bALk0Z1Z2fMrhQ3wY',
							'keyThis' => 'libraryId',
							'keyOther' => 'libraryId',
							'subObjectType' => 'LibraryFacetSetting',
							'structure' => $facetSettingStructure,
							'sortable' => true,
							'storeDb' => true,
							'allowEdit' => true,
							'canEdit' => true,
							'additionalOneToManyActions' => array(
								array(
									'text' => 'Copy Library Facets',
									'url' => '/Admin/Libraries?id=$id&amp;objectAction=copyFacetsFromLibrary',
								),
								array(
									'text' => 'Reset Facets To Default',
									'url' => '/Admin/Libraries?id=$id&amp;objectAction=resetFacetsToDefault',
									'class' => 'btn-warning',
								),
							)
						),
					)),
				]
			],
		];
		return $structure;
	}

	/**
	 * Override the fetch functionality to fetch related objects
	 *
	 * @see DB/DB_DataObject::fetch()
	 */
	public function fetch(){
		$return = parent::fetch();
		if ($return) {
			if (isset($this->showInSearchResultsMainDetails) && is_string($this->showInSearchResultsMainDetails) && !empty($this->showInSearchResultsMainDetails)) {
				// convert to array retrieving from database
				$this->showInSearchResultsMainDetails = unserialize($this->showInSearchResultsMainDetails);
				if (!$this->showInSearchResultsMainDetails) $this->showInSearchResultsMainDetails = array();
			}
		}
		return $return;
	}

	/**
	 * Override the update functionality to save related objects
	 *
	 * @see DB/DB_DataObject::update()
	 */
	public function update()
	{
		if (isset($this->showInSearchResultsMainDetails) && is_array($this->showInSearchResultsMainDetails)) {
			// convert array to string before storing in database
			$this->showInSearchResultsMainDetails = serialize($this->showInSearchResultsMainDetails);
		}

		$ret = parent::update();
		return $ret;
	}

	/**
	 * Override the insert functionality to save the related objects
	 *
	 * @see DB/DB_DataObject::insert()
	 */
	public function insert(){
		if (isset($this->showInSearchResultsMainDetails) && is_array($this->showInSearchResultsMainDetails)) {
			// convert array to string before storing in database
			$this->showInSearchResultsMainDetails = serialize($this->showInSearchResultsMainDetails);
		}

		$ret = parent::insert();
		return $ret;
	}
}