<?php
require_once ROOT_DIR . '/sys/DB/DataObject.php';

class EventsFacet extends FacetSetting {
	public $__table = 'events_facet';
	public $facetGroupId;

	public function getNumericColumnNames(): array {
		$numericColumns = parent::getNumericColumnNames();
		$numericColumns[] = 'facetGroupId';
		return $numericColumns;
	}


	public static function getAvailableFacets() {
		$availableFacets = [
			"age_group_facet" => "Age Group/Audience",
			"program_type_facet" => "Program Type",
			"branch" => "Branch",
			"room" => "Room",
			"internal_category" => "Category",
			"event_state" => "State",
			"reservation_state" => "Reservation State",
			"registration_required" => "Registration Required?",
			"event_type" => "Event Type",
			"start_date" => "Event Date",
			"custom_facet_1" => "Custom Facet 1",
			"custom_facet_2" => "Custom Facet 2",
			"custom_facet_3" => "Custom Facet 3",
		];

		asort($availableFacets);
		return $availableFacets;
	}

	static function getObjectStructure($context = '') {

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
				'values' => self::getAvailableFacets(),
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
//			'showAboveResults' => [
//				'property' => 'showAboveResults',
//				'type' => 'checkbox',
//				'label' => 'Show Above Results',
//				'description' => 'Whether or not the facets should be shown above the results',
//				'default' => 0,
//			],
//			'showInResults' => [
//				'property' => 'showInResults',
//				'type' => 'checkbox',
//				'label' => 'Show on Results Page',
//				'description' => 'Whether or not the facets should be shown in regular search results',
//				'default' => 1,
//			],
//			'showInAdvancedSearch' => [
//				'property' => 'showInAdvancedSearch',
//				'type' => 'checkbox',
//				'label' => 'Show on Advanced Search',
//				'description' => 'Whether or not the facet should be an option on the Advanced Search Page',
//				'default' => 1,
//			],
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
//			'showAsDropDown' => [
//				'property' => 'showAsDropDown',
//				'type' => 'checkbox',
//				'label' => 'Drop Down?',
//				'description' => 'Whether or not the facets should be shown in a drop down list',
//				'default' => '0',
//			],
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
			'numEntriesToShowByDefault' => [
				'property' => 'numEntriesToShowByDefault',
				'type' => 'integer',
				'label' => 'Num Entries',
				'description' => 'The number of values to show by default.',
				'default' => '5',
			],
		];
	}
}