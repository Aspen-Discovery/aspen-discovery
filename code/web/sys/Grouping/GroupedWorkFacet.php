<?php
require_once ROOT_DIR . '/sys/DB/DataObject.php';
class GroupedWorkFacet extends FacetSetting {
	public $__table = 'grouped_work_facet';
	public $facetGroupId;

	public function getNumericColumnNames() : array
	{
		$numericColumns = parent::getNumericColumnNames();
		$numericColumns[] = 'facetGroupId';
		return $numericColumns;
	}


	public static function getAvailableFacets()
	{
		$availableFacets = array(
			"owning_library" => "Library System",
			"owning_location" => "Branch",
			"available_at" => "Available At",
			"availability_toggle" => "Available?",
			"collection_group" => "Collection",
			"rating_facet" => "Rating",
			"publishDate" => "Publication Year",
			"publishDateSort" => "Earliest Publication Year",
			"format" => "Format",
			"format_category" => "Format Category",
			"econtent_source" => "E-Content Collection",
			"subject_facet" => "Subjects",
			"topic_facet" => "Topics",
			"target_audience" => "Audience",
			"mpaa_rating" => "Movie Rating",
			"literary_form" => "Form",
			"authorStr" => "Author",
			"language" => "Language",
			"translation" => "Translations",
			"genre_facet" => "Genre",
			"era" => "Era",
			"geographic_facet" => "Region",
			"target_audience_full" => "Reading Level",
			"literary_form_full" => "Literary Form",
			"lexile_code" => "Lexile code",
			"lexile_score" => "Lexile measure",
			"itype" => "Item Type",
			"time_since_added" => "Added In The Last",
			"callnumber-first" => "LC Call Number",
			"awards_facet" => "Awards",
			"shelf_location" => "Shelf Location",
			"detailed_location" => "Detailed Location",
			"lc_subject" => "LC Subject",
			"bisac_subject" => "Bisac Subject",
			"accelerated_reader_interest_level" => "AR Interest Level",
			"accelerated_reader_reading_level" => "AR Reading Level",
			"accelerated_reader_point_value" => "AR Point Value",
			"fountas_pinnell" => "Fountas &amp; Pinnell",
			"series_facet" => "Series",
			"publisherStr" => "Publisher"
		);

		asort($availableFacets);
		return $availableFacets;
	}

	static function getObjectStructure($availableFacets = null){

		return array(
			'id' => array('property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id'),
			'weight' => array('property'=>'weight', 'type'=>'integer', 'label'=>'Weight', 'description'=>'The sort order', 'default' => 0),
			'facetName' => array('property'=>'facetName', 'type'=>'enum', 'label'=>'Facet', 'values' => empty($availableFacets) ? self::getAvailableFacets() : $availableFacets, 'description'=>'The facet to include'),
			'displayName' => array('property'=>'displayName', 'type'=>'text', 'label'=>'Display Name', 'description'=>'The full name of the facet for display to the user'),
			'displayNamePlural' => array('property'=>'displayNamePlural', 'type'=>'text', 'label'=>'Plural Display Name', 'description'=>'The full name pluralized of the facet for display to the user'),
			'showAboveResults' => array('property' => 'showAboveResults', 'type' => 'checkbox', 'label' => 'Show Above Results', 'description'=>'Whether or not the facets should be shown above the results', 'default'=>0),
			'showInResults' => array('property' => 'showInResults', 'type' => 'checkbox', 'label' => 'Show on Results Page', 'description'=>'Whether or not the facets should be shown in regular search results', 'default'=>1),
			'showInAdvancedSearch' => array('property' => 'showInAdvancedSearch', 'type' => 'checkbox', 'label' => 'Show on Advanced Search', 'description'=>'Whether or not the facet should be an option on the Advanced Search Page', 'default'=>1),
			'multiSelect' => array('property' => 'multiSelect', 'type' => 'checkbox', 'label' => 'Multi Select?', 'description'=>'Whether or not to allow patrons to select multiple values', 'default'=>'0'),
			'canLock' => array('property' => 'canLock', 'type' => 'checkbox', 'label' => 'Can Lock?', 'description'=>'Whether or not to allow patrons can lock the facet', 'default'=>'0'),
			'collapseByDefault' => array('property' => 'collapseByDefault', 'type' => 'checkbox', 'label' => 'Collapse by Default', 'description'=>'Whether or not the facet should be an collapsed by default.', 'default'=>1),
			'useMoreFacetPopup' => array('property' => 'useMoreFacetPopup', 'type' => 'checkbox', 'label' => 'Use More Facet Popup', 'description'=>'Whether or not more facet options are shown in a popup box.', 'default'=>1),
			'showAsDropDown' => array('property' => 'showAsDropDown', 'type' => 'checkbox', 'label' => 'Drop Down?', 'description'=>'Whether or not the facets should be shown in a drop down list', 'default'=>'0'),
			'translate' => array('property' => 'translate', 'type' => 'checkbox', 'label' => 'Translate?', 'description'=>'Whether or not values are translated when displayed', 'default'=>'0'),
			'sortMode' => array('property'=>'sortMode', 'type'=>'enum', 'label'=>'Sort', 'values' => array('alphabetically' => 'Alphabetically', 'num_results' => 'By number of results'), 'description'=>'How the facet values should be sorted.', 'default'=>'num_results'),
			'numEntriesToShowByDefault' => array('property'=>'numEntriesToShowByDefault', 'type'=>'integer', 'label'=>'Num Entries', 'description'=>'The number of values to show by default.', 'default' => '5'),
		);
	}
}