<?php /** @noinspection PhpMissingFieldTypeInspection */
require_once ROOT_DIR . '/sys/Grouping/GroupedWorkFacet.php';

class GroupedWorkFacetGroup extends DataObject {
	public $__table = 'grouped_work_facet_groups';
	public $id;
	public $name;

	public $_facets;

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context])) {
			return self::$_objectStructure[$context];
		}

		$facetSettingStructure = GroupedWorkFacet::getObjectStructure($context);
		unset($facetSettingStructure['weight']);
		unset($facetSettingStructure['facetGroupId']);
		unset($facetSettingStructure['showAsDropDown']);

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
				'label' => 'Display Name',
				'description' => 'The name of the settings',
				'size' => '40',
				'maxLength' => 255,
			],
			'facets' => [
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
				'canAddNew' => true,
				'canDelete' => true,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	public function update(string $context = '') : int|bool {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveFacets();
		}
		return $ret;
	}

	public function insert(string $context = '') : int|bool {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveFacets();
		}
		return $ret;
	}

	public function saveFacets() : void {
		if (isset ($this->_facets) && is_array($this->_facets)) {
			$this->saveOneToManyOptions($this->_facets, 'facetGroupId');
			unset($this->facets);
		}
	}

	public function __get($name) {
		if ($name == 'facets') {
			return $this->getFacets();
		} else {
			return parent::__get($name);
		}
	}

	public function __set($name, $value) {
		if ($name == 'facets') {
			$this->setFacets($value);
		} else {
			parent::__set($name, $value);
		}
	}

	/** @return ?GroupedWorkFacet[] */
	public function getFacets(): ?array {
		if (!isset($this->_facets) && $this->id) {
			$this->_facets = [];
			$facet = new GroupedWorkFacet();
			$facet->facetGroupId = $this->id;
			$facet->orderBy('weight');
			$facet->find();
			while ($facet->fetch()) {
				$this->_facets[$facet->id] = clone($facet);
			}
		}
		return $this->_facets;
	}

	public function setFacets($value) : void {
		$this->_facets = $value;
	}

	public function clearFacets() : void {
		$this->clearOneToManyOptions('GroupedWorkFacet', 'facetGroupId');
		/** @noinspection PhpUndefinedFieldInspection */
		$this->facets = [];
	}

	function getAdditionalListJavascriptActions(): array {
		$objectActions[] = [
			'text' => 'Copy',
			'onClick' => "return AspenDiscovery.Admin.showCopyFacetGroupForm('$this->id')",
			'icon' => 'fas fa-copy',
		];

		return $objectActions;
	}

	public function getLinkedObjectStructure() : array {
		return [
			[
				'object' => 'GroupedWorkDisplaySetting',
				'class' => ROOT_DIR . '/sys/Grouping/GroupedWorkDisplaySetting.php',
				'linkingProperty' => 'facetGroupId',
				'objectName' => 'Grouped Work Display Setting',
				'objectNamePlural' => 'Grouped Work Display Settings',
			],
		];
	}
}