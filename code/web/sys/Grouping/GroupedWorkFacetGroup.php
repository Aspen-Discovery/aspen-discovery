<?php
require_once ROOT_DIR . '/sys/Grouping/GroupedWorkFacet.php';

class GroupedWorkFacetGroup extends DataObject
{
	public $__table = 'grouped_work_facet_groups';
	public $id;
	public $name;

	public $_facets;

	static function getObjectStructure() : array{
		$facetSettingStructure = GroupedWorkFacet::getObjectStructure();
		unset($facetSettingStructure['weight']);
		unset($facetSettingStructure['facetGroupId']);
		unset($facetSettingStructure['showAsDropDown']);

		return [
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id within the database'),
			'name' => array('property' => 'name', 'type' => 'text', 'label' => 'Display Name', 'description' => 'The name of the settings', 'size' => '40', 'maxLength'=>255),
			'facets' => array(
				'property' => 'facets',
				'type' => 'oneToMany',
				'label' => 'Facets',
				'description' => 'A list of facets to display in search results',
				'keyThis' => 'libraryId',
				'keyOther' => 'libraryId',
				'subObjectType' => 'GroupedWorkFacet',
				'structure' => $facetSettingStructure,
				'sortable' => true,
				'storeDb' => true,
				'allowEdit' => true,
				'canEdit' => false,
			),
		];
	}

	function setupDefaultFacets($type){
		$defaultFacets = array();

		$facet = new GroupedWorkFacet();
		$facet->setupTopFacet('format_category', 'Format Category');
		$facet->facetGroupId = $this->id;
		$facet->weight = 1;
		$defaultFacets[] = $facet;

		$facet = new GroupedWorkFacet();
		$facet->setupTopFacet('availability_toggle', 'Available?');
		$facet->facetGroupId = $this->id;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		/** @noinspection PhpIfWithCommonPartsInspection */
		if ($type == 'academic'){
			$facet = new GroupedWorkFacet();
			$facet->setupSideFacet('literary_form', 'Literary Form', true);
			$facet->facetGroupId = $this->id;
			$facet->weight = count($defaultFacets) + 1;
			$facet->canLock = true;
			$defaultFacets[] = $facet;
		}else{
			$facet = new GroupedWorkFacet();
			$facet->setupSideFacet('literary_form', 'Fiction / Non-Fiction', true);
			$facet->facetGroupId = $this->id;
			$facet->weight = count($defaultFacets) + 1;
			$facet->multiSelect = true;
			$facet->canLock = true;
			$defaultFacets[] = $facet;
		}

		$facet = new GroupedWorkFacet();
		$facet->setupSideFacet('target_audience', 'Reading Level', true);
		$facet->facetGroupId = $this->id;
		$facet->weight = count($defaultFacets) + 1;
		$facet->numEntriesToShowByDefault = 8;
		$facet->multiSelect = true;
		$facet->canLock = true;
		$defaultFacets[] = $facet;

		$facet = new GroupedWorkFacet();
		$facet->setupSideFacet('available_at', 'Available Now At', true);
		$facet->facetGroupId = $this->id;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		$facet = new GroupedWorkFacet();
		$facet->setupSideFacet('econtent_source', 'eContent Collection', true);
		$facet->facetGroupId = $this->id;
		$facet->weight = count($defaultFacets) + 1;
		$facet->multiSelect = true;
		$defaultFacets[] = $facet;

		$facet = new GroupedWorkFacet();
		$facet->setupSideFacet('format', 'Format', true);
		$facet->facetGroupId = $this->id;
		$facet->weight = count($defaultFacets) + 1;
		$facet->multiSelect = true;
		$facet->canLock = true;
		$defaultFacets[] = $facet;

		$facet = new GroupedWorkFacet();
		$facet->setupSideFacet('authorStr', 'Author', true);
		$facet->facetGroupId = $this->id;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		$facet = new GroupedWorkFacet();
		$facet->setupSideFacet('series_facet', 'Series', true);
		$facet->facetGroupId = $this->id;
		$facet->multiSelect = true;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		if ($type != 'academic'){
			$facet = new GroupedWorkFacet();
			$facet->setupSideFacet('accelerated_reader_interest_level', 'AR Interest Level', true);
			$facet->facetGroupId = $this->id;
			$facet->weight = count($defaultFacets) + 1;
			$defaultFacets[] = $facet;

			$facet = new GroupedWorkFacet();
			$facet->setupSideFacet('accelerated_reader_reading_level', 'AR Reading Level', true);
			$facet->facetGroupId = $this->id;
			$facet->weight = count($defaultFacets) + 1;
			$defaultFacets[] = $facet;

			$facet = new GroupedWorkFacet();
			$facet->setupSideFacet('accelerated_reader_point_value', 'AR Point Value', true);
			$facet->facetGroupId = $this->id;
			$facet->weight = count($defaultFacets) + 1;
			$defaultFacets[] = $facet;
		}

		/** @noinspection PhpIfWithCommonPartsInspection */
		if ($type == 'academic') {
			$facet = new GroupedWorkFacet();
			$facet->setupSideFacet('topic_facet', 'Subject', true);
			$facet->facetGroupId = $this->id;
			$facet->weight = count($defaultFacets) + 1;
			$facet->multiSelect = true;
			$defaultFacets[] = $facet;

			$facet = new GroupedWorkFacet();
			$facet->setupAdvancedFacet('geographic_facet', 'Region');
			$facet->facetGroupId = $this->id;
			$facet->weight = count($defaultFacets) + 1;
			$defaultFacets[] = $facet;

			$facet = new GroupedWorkFacet();
			$facet->setupAdvancedFacet('era', 'Era');
			$facet->facetGroupId = $this->id;
			$facet->weight = count($defaultFacets) + 1;
			$defaultFacets[] = $facet;

			$facet = new GroupedWorkFacet();
			$facet->setupSideFacet('genre_facet', 'Genre', true);
			$facet->facetGroupId = $this->id;
			$facet->multiSelect = true;
			$facet->weight = count($defaultFacets) + 1;
			$defaultFacets[] = $facet;
		}else{
			$facet = new GroupedWorkFacet();
			$facet->setupSideFacet('subject_facet', 'Subject', true);
			$facet->facetGroupId = $this->id;
			$facet->multiSelect = true;
			$facet->weight = count($defaultFacets) + 1;
			$defaultFacets[] = $facet;
		}

		$facet = new GroupedWorkFacet();
		$facet->setupSideFacet('time_since_added', 'Added in the Last', true);
		$facet->facetGroupId = $this->id;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		$facet = new GroupedWorkFacet();
		$facet->setupAdvancedFacet('awards_facet', 'Awards');
		$facet->facetGroupId = $this->id;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		$facet = new GroupedWorkFacet();
		$facet->setupAdvancedFacet('itype', 'Item Type');
		$facet->facetGroupId = $this->id;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		$facet = new GroupedWorkFacet();
		$facet->setupSideFacet('language', 'Language', true);
		$facet->facetGroupId = $this->id;
		$facet->multiSelect = true;
		$facet->canLock = true;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		$facet = new GroupedWorkFacet();
		$facet->setupAdvancedFacet('mpaa_rating', 'Movie Rating');
		$facet->facetGroupId = $this->id;
		$facet->weight = count($defaultFacets) + 1;
		$facet->multiSelect = true;
		$defaultFacets[] = $facet;

		if ($type == 'consortium') {
			$facet = new GroupedWorkFacet();
			$facet->setupAdvancedFacet('owning_library', 'Owning System');
			$facet->facetGroupId = $this->id;
			$facet->weight = count($defaultFacets) + 1;
			$defaultFacets[] = $facet;

			$facet = new GroupedWorkFacet();
			$facet->setupAdvancedFacet('owning_location', 'Owning Branch');
			$facet->facetGroupId = $this->id;
			$facet->weight = count($defaultFacets) + 1;
			$defaultFacets[] = $facet;
		}

		$facet = new GroupedWorkFacet();
		$facet->setupSideFacet('publishDateSort', 'Publication Date', true);
		$facet->facetGroupId = $this->id;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		$facet = new GroupedWorkFacet();
		$facet->setupSideFacet('rating_facet', 'User Rating', true);
		$facet->facetGroupId = $this->id;
		$facet->weight = count($defaultFacets) + 1;
		$defaultFacets[] = $facet;

		$this->_facets = $defaultFacets;
		$this->update();
	}

	public function update(){
		$ret = parent::update();
		if ($ret !== FALSE ){
			$this->saveFacets();
		}
		return $ret;
	}

	public function insert(){
		$ret = parent::insert();
		if ($ret !== FALSE ){
			$this->saveFacets();
		}
		return $ret;
	}

	public function saveFacets(){
		if (isset ($this->_facets) && is_array($this->_facets)){
			$this->saveOneToManyOptions($this->_facets, 'facetGroupId');
			unset($this->facets);
		}
	}

	public function __get($name)
	{
		if ($name == 'facets'){
			return $this->getFacets();
		}else{
			return $this->_data[$name];
		}
	}

	public function __set($name, $value)
	{
		if ($name == 'facets'){
			$this->setFacets($value);
		}else{
			$this->_data[$name] = $value;
		}
	}

	/** @return GroupedWorkFacet[] */
	public function getFacets() : ?array{
		if (!isset($this->_facets) && $this->id){
			$this->_facets = array();
			$facet = new GroupedWorkFacet();
			$facet->facetGroupId = $this->id;
			$facet->orderBy('weight');
			$facet->find();
			while($facet->fetch()){
				$this->_facets[$facet->id] = clone($facet);
			}
		}
		return $this->_facets;
	}

	public function getFacetByIndex($index) : ? GroupedWorkFacet{
		$facets = $this->getFacets();

		$i=0;
		foreach ($facets as $value) {
			if($i==$index) {
				return $value;
			}
			$i++;
		}
		return NULL;
	}

	public function setFacets($value){
		$this->_facets = $value;
	}

	public function clearFacets(){
		$this->clearOneToManyOptions('GroupedWorkFacet', 'facetGroupId');
		/** @noinspection PhpUndefinedFieldInspection */
		$this->facets = array();
	}

	function getAdditionalListJavascriptActions() : array{
		$objectActions[] = array(
			'text' => 'Copy',
			'onClick' => "return AspenDiscovery.Admin.showCopyFacetGroupForm('$this->id')",
			'icon' => 'fas fa-copy'
		);

		return $objectActions;
	}
}