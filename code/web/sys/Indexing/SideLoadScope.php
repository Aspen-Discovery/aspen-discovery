<?php

require_once ROOT_DIR . '/sys/Indexing/SideLoad.php';

class SideLoadScope extends DataObject {
	public $__table = 'sideload_scopes';
	public $id;
	public $name;
	public $sideLoadId;
	public /** @noinspection PhpUnused */
		$restrictToChildrensMaterial;

	//The next 3 fields allow inclusion or exclusion of records based on a marc tag
	public /** @noinspection PhpUnused */
		$marcTagToMatch;
	public /** @noinspection PhpUnused */
		$marcValueToMatch;
	public /** @noinspection PhpUnused */
		$includeExcludeMatches;
	//The next 2 fields determine how urls are constructed
	public /** @noinspection PhpUnused */
		$urlToMatch;
	public /** @noinspection PhpUnused */
		$urlReplacement;

	private $_libraries;
	private $_locations;

	public static function getObjectStructure($context = ''): array {
		$validSideLoads = [];
		$sideLoad = new SideLoad();
		$sideLoad->orderBy('name');
		$sideLoad->find();
		while ($sideLoad->fetch()) {
			$validSideLoads[$sideLoad->id] = $sideLoad->name;
		}

		$librarySideLoadScopeStructure = LibrarySideLoadScope::getObjectStructure($context);
		unset($librarySideLoadScopeStructure['sideLoadScopeId']);

		$locationSideLoadScopeStructure = LocationSideLoadScope::getObjectStructure($context);
		unset($locationSideLoadScopeStructure['sideLoadScopeId']);

		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'sideLoadId' => [
				'property' => 'sideLoadId',
				'type' => 'enum',
				'values' => $validSideLoads,
				'label' => 'Side Load',
				'description' => 'The Side Load to apply the scope to',
			],
			'name' => [
				'property' => 'name',
				'type' => 'text',
				'label' => 'Name',
				'description' => 'The Name of the scope',
				'maxLength' => 50,
			],
			'restrictToChildrensMaterial' => [
				'property' => 'restrictToChildrensMaterial',
				'type' => 'checkbox',
				'label' => 'Include Children\'s Materials Only',
				'description' => 'If checked only includes titles identified as children',
				'default' => 0,
			],
			'marcTagToMatch' => [
				'property' => 'marcTagToMatch',
				'type' => 'text',
				'label' => 'Tag To Match',
				'description' => 'MARC tag(s) to match',
				'maxLength' => '100',
				'required' => false,
			],
			'marcValueToMatch' => [
				'property' => 'marcValueToMatch',
				'type' => 'regularExpression',
				'label' => 'Value To Match (Regular Expression)',
				'description' => 'The value to match within the MARC tag(s) if multiple tags are specified, a match against any tag will count as a match of everything',
				'maxLength' => '100',
				'required' => false,
			],
			'includeExcludeMatches' => [
				'property' => 'includeExcludeMatches',
				'type' => 'enum',
				'values' => [
					'1' => 'Include Matches',
					'0' => 'Exclude Matches',
				],
				'label' => 'Include Matches?',
				'description' => 'Whether or not matches are included or excluded',
				'default' => 1,
			],
			'urlToMatch' => [
				'property' => 'urlToMatch',
				'type' => 'regularExpression',
				'label' => 'URL To Match (Regular Expression)',
				'description' => 'URL to match when rewriting urls, supports capturing groups.',
				'maxLength' => '255',
				'required' => false,
			],
			'urlReplacement' => [
				'property' => 'urlReplacement',
				'type' => 'regularExpression',
				'label' => 'URL Replacement (Regular Expression)',
				'description' => 'The replacement pattern for url rewriting, supports capturing groups: use $1, $2, etc as placeholders for the group.',
				'maxLength' => '255',
				'required' => false,
			],

			'libraries' => [
				'property' => 'libraries',
				'type' => 'oneToMany',
				'label' => 'Libraries',
				'description' => 'Define libraries that use this scope',
				'keyThis' => 'id',
				'keyOther' => 'sideLoadScopeId',
				'subObjectType' => 'LibrarySideLoadScope',
				'structure' => $librarySideLoadScopeStructure,
				'sortable' => false,
				'storeDb' => true,
				'allowEdit' => false,
				'canEdit' => false,
				'additionalOneToManyActions' => [
					[
						'text' => 'Apply To All Libraries',
						'url' => '/SideLoads/Scopes?id=$id&amp;objectAction=addToAllLibraries',
					],
					[
						'text' => 'Clear Libraries',
						'url' => '/SideLoads/Scopes?id=$id&amp;objectAction=clearLibraries',
						'class' => 'btn-warning',
					],
				],
			],

			'locations' => [
				'property' => 'locations',
				'type' => 'oneToMany',
				'label' => 'Locations',
				'description' => 'Define locations that use this scope',
				'keyThis' => 'id',
				'keyOther' => 'sideLoadScopeId',
				'subObjectType' => 'LocationSideLoadScope',
				'structure' => $locationSideLoadScopeStructure,
				'sortable' => false,
				'storeDb' => true,
				'allowEdit' => false,
				'canEdit' => false,
				'additionalOneToManyActions' => [
					[
						'text' => 'Apply To All Locations',
						'url' => '/SideLoads/Scopes?id=$id&amp;objectAction=addToAllLocations',
					],
					[
						'text' => 'Clear Locations',
						'url' => '/SideLoads/Scopes?id=$id&amp;objectAction=clearLocations',
						'class' => 'btn-warning',
					],
				],
				'forcesReindex' => true,
			],
		];
	}

	function getEditLink($context): string {
		return '/SideLoads/Scopes?objectAction=edit&id=' . $this->id;
	}

	public function __get($name) {
		if ($name == "libraries") {
			if (!isset($this->_libraries) && $this->id) {
				$this->_libraries = [];
				$obj = new LibrarySideLoadScope();
				$obj->sideLoadScopeId = $this->id;
				$obj->find();
				while ($obj->fetch()) {
					$this->_libraries[$obj->id] = clone($obj);
				}
			}
			return $this->_libraries;
		} elseif ($name == "locations") {
			if (!isset($this->_locations) && $this->id) {
				$this->_locations = [];
				$obj = new LocationSideLoadScope();
				$obj->sideLoadScopeId = $this->id;
				$obj->find();
				while ($obj->fetch()) {
					$this->_locations[$obj->id] = clone($obj);
				}
			}
			return $this->_locations;
		} else {
			return $this->_data[$name] ?? null;
		}
	}

	public function __set($name, $value) {
		if ($name == "libraries") {
			$this->_libraries = $value;
		} elseif ($name == "locations") {
			$this->_locations = $value;
		} else {
			$this->_data[$name] = $value;
		}
	}

	public function update($context = '') {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveLocations();
		}
		return true;
	}

	public function insert($context = '') {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveLocations();
		}
		return $ret;
	}

	public function delete($useWhere = false) {
		$ret = parent::delete($useWhere);
		if ($ret !== FALSE) {
			$this->clearLocations();
			$this->clearLocations();
		}
		return $ret;
	}

	public function saveLibraries() {
		if (isset ($this->_libraries) && is_array($this->_libraries)) {
			$this->saveOneToManyOptions($this->_libraries, 'sideLoadScopeId');
			unset($this->_libraries);
		}
	}

	public function saveLocations() {
		if (isset ($this->_locations) && is_array($this->_locations)) {
			$this->saveOneToManyOptions($this->_locations, 'sideLoadScopeId');
			unset($this->_locations);
		}
	}

	/** @return LibrarySideLoadScope[] */
	public function getLibraries() {
		return $this->_libraries;
	}

	/** @return LocationSideLoadScope[] */
	public function getLocations() {
		return $this->_locations;
	}

	public function setLibraries($val) {
		$this->_libraries = $val;
	}

	public function setLocations($val) {
		$this->_locations = $val;
	}

	public function clearLibraries() {
		$this->clearOneToManyOptions('LibrarySideLoadScope', 'sideLoadScopeId');
		unset($this->_libraries);
	}

	public function clearLocations() {
		$this->clearOneToManyOptions('LocationSideLoadScope', 'sideLoadScopeId');
		unset($this->_locations);
	}
}
