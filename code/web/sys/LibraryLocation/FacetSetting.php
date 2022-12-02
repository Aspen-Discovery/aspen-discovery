<?php
require_once ROOT_DIR . '/sys/DB/DataObject.php';

abstract class FacetSetting extends DataObject {
	public $__displayNameColumn = 'displayName';
	public $id;                      //int(25)
	public $displayName;                    //varchar(255)
	public $displayNamePlural;
	public $facetName;
	public $weight;
	public $numEntriesToShowByDefault; //
	public $showAsDropDown;   //True or false
	public $multiSelect;
	public $canLock;
	public $sortMode;         //alphabetically = alphabetically, num_results = by number of results
	public $showAboveResults;
	public $showInResults;
	public $showInAdvancedSearch;
	public $collapseByDefault;
	public $useMoreFacetPopup;
	public $translate;

	public function getNumericColumnNames(): array {
		return [
			'weight',
			'showAsDropDown',
			'multiSelect',
			'canLock',
			'showAboveResults',
			'showInResults',
			'showInAdvancedSearch',
			'translate',
		];
	}

	/** @return string[] */
	public abstract static function getAvailableFacets();

	static function getObjectStructure(array $availableFacets = null) {
		if (empty($availableFacets)) {
			AspenError::raiseError("The list of available facets must be provided");
		}
		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'weight' => [
				'property' => 'weight',
				'type' => 'integer',
				'label' => 'Weight',
				'description' => 'The sort order',
				'default' => 0,
			],
			'facetName' => [
				'property' => 'facetName',
				'type' => 'enum',
				'label' => 'Facet',
				'values' => $availableFacets,
				'description' => 'The facet to include',
			],
			'displayName' => [
				'property' => 'displayName',
				'type' => 'text',
				'label' => 'Display Name',
				'description' => 'The full name of the facet for display to the user',
			],
			'displayNamePlural' => [
				'property' => 'displayNamePlural',
				'type' => 'text',
				'label' => 'Plural Display Name',
				'description' => 'The full name pluralized of the facet for display to the user',
			],
			'numEntriesToShowByDefault' => [
				'property' => 'numEntriesToShowByDefault',
				'type' => 'integer',
				'label' => 'Num Entries',
				'description' => 'The number of values to show by default.',
				'default' => '5',
			],
			'showAsDropDown' => [
				'property' => 'showAsDropDown',
				'type' => 'checkbox',
				'label' => 'Drop Down?',
				'description' => 'Whether or not the facets should be shown in a drop down list',
				'default' => '0',
			],
			'multiSelect' => [
				'property' => 'multiSelect',
				'type' => 'checkbox',
				'label' => 'Multi Select?',
				'description' => 'Whether or not to allow patrons to select multiple values',
				'default' => '0',
			],
			'canLock' => [
				'property' => 'canLock',
				'type' => 'checkbox',
				'label' => 'Can Lock?',
				'description' => 'Whether or not to allow patrons can lock the facet',
				'default' => '0',
			],
			'translate' => [
				'property' => 'translate',
				'type' => 'checkbox',
				'label' => 'Translate?',
				'description' => 'Whether or not values are translated when displayed',
				'default' => '0',
			],
			'sortMode' => [
				'property' => 'sortMode',
				'type' => 'enum',
				'label' => 'Sort',
				'values' => [
					'alphabetically' => 'Alphabetically',
					'num_results' => 'By number of results',
				],
				'description' => 'How the facet values should be sorted.',
				'default' => 'num_results',
			],
			'showAboveResults' => [
				'property' => 'showAboveResults',
				'type' => 'checkbox',
				'label' => 'Show Above Results',
				'description' => 'Whether or not the facets should be shown above the results',
				'default' => 0,
			],
			'showInResults' => [
				'property' => 'showInResults',
				'type' => 'checkbox',
				'label' => 'Show on Results Page',
				'description' => 'Whether or not the facets should be shown in regular search results',
				'default' => 1,
			],
			'showInAdvancedSearch' => [
				'property' => 'showInAdvancedSearch',
				'type' => 'checkbox',
				'label' => 'Show on Advanced Search',
				'description' => 'Whether or not the facet should be an option on the Advanced Search Page',
				'default' => 1,
			],
			'collapseByDefault' => [
				'property' => 'collapseByDefault',
				'type' => 'checkbox',
				'label' => 'Collapse by Default',
				'description' => 'Whether or not the facet should be an collapsed by default.',
				'default' => 1,
			],
			'useMoreFacetPopup' => [
				'property' => 'useMoreFacetPopup',
				'type' => 'checkbox',
				'label' => 'Use More Facet Popup',
				'description' => 'Whether or not more facet options are shown in a popup box.',
				'default' => 1,
			],
		];
	}

	function setupTopFacet($facetName, $displayName) {
		$this->facetName = $facetName;
		$this->displayName = $displayName;
		$this->showAsDropDown = false;
		$this->sortMode = 'num_results';
		$this->showAboveResults = true;
		$this->showInResults = true;
		$this->showInAdvancedSearch = true;
		$this->numEntriesToShowByDefault = 0;
	}

	function setupSideFacet($facetName, $displayName, $collapseByDefault) {
		$this->facetName = $facetName;
		$this->displayName = $displayName;
		$this->showAsDropDown = false;
		$this->sortMode = 'num_results';
		$this->showAboveResults = false;
		$this->showInResults = true;
		$this->showInAdvancedSearch = true;
		$this->collapseByDefault = $collapseByDefault;
		$this->useMoreFacetPopup = true;
	}

	function setupAdvancedFacet($facetName, $displayName) {
		$this->facetName = $facetName;
		$this->displayName = $displayName;
		$this->showAsDropDown = false;
		$this->sortMode = 'num_results';
		$this->showAboveResults = false;
		$this->showInResults = false;
		$this->showInAdvancedSearch = true;
		$this->collapseByDefault = true;
		$this->useMoreFacetPopup = true;
	}

	function getFacetName($searchVersion) {
		if ($searchVersion == 2 && $this->facetName == 'collection_group') {
			return 'collection';
		} else {
			return $this->facetName;
		}
	}
}