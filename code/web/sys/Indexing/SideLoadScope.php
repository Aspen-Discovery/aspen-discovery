<?php

require_once ROOT_DIR . '/sys/Indexing/SideLoad.php';

class SideLoadScope extends DataObject {
	public $__table = 'sideload_scopes';
	public $id;
	public $name;
	public $sideLoadId;
	public $includeAdult;
	public $includeTeen;
	public $includeKids;

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
		$sideLoad = new SideLoad();
		$sideLoad->orderBy('name');
		$allSideLoads = $sideLoad->fetchAll('id', 'name');
		$sideLoad = new SideLoad();
		$sideLoad->orderBy('name');
		if ((UserAccount::userHasPermission('Administer Side Loads for Home Library') || UserAccount::userHasPermission('Administer Side Load Scopes for Home Library')) && !UserAccount::userHasPermission('Administer Side Loads')) {
			$libraryList = Library::getLibraryList(true);
			$sideLoad->whereAddIn("owningLibrary", array_keys($libraryList), false, "OR");
			$sideLoad->whereAdd("sharing = 1", "OR");
		}
		$validSideLoads = $sideLoad->fetchAll('id', 'name');

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
				'allValues' => $allSideLoads,
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
			'includeAdult' => [
				'property' => 'includeAdult',
				'type' => 'checkbox',
				'label' => 'Include Adult Titles',
				'description' => 'Whether or not adult titles from the Side Load collection should be included in searches',
				'default' => true,
				'forcesReindex' => true,
			],
			'includeTeen' => [
				'property' => 'includeTeen',
				'type' => 'checkbox',
				'label' => 'Include Teen Titles',
				'description' => 'Whether or not teen titles from the Side Load collection should be included in searches',
				'default' => true,
				'forcesReindex' => true,
			],
			'includeKids' => [
				'property' => 'includeKids',
				'type' => 'checkbox',
				'label' => 'Include Kids Titles',
				'description' => 'Whether or not kids titles from the Side Load collection should be included in searches',
				'default' => true,
				'forcesReindex' => true,
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
				'canAddNew' => true,
				'canDelete' => true,
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
				'canAddNew' => true,
				'canDelete' => true,
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

	public function updateStructureForEditingObject($structure) : array {
		if ($this->isReadOnly()) {
			$structure['sideLoadId']['readOnly'] = true;
			$structure['name']['readOnly'] = true;
			$structure['includeAdult']['readOnly'] = true;
			$structure['includeTeen']['readOnly'] = true;
			$structure['includeKids']['readOnly'] = true;
			$structure['marcTagToMatch']['readOnly'] = true;
			$structure['marcValueToMatch']['readOnly'] = true;
			$structure['includeExcludeMatches']['readOnly'] = true;
			$structure['urlToMatch']['readOnly'] = true;
			$structure['urlReplacement']['readOnly'] = true;
		}
		return $structure;
	}

	function getEditLink($context): string {
		return '/SideLoads/Scopes?objectAction=edit&id=' . $this->id;
	}

	public function __get($name) {
		if ($name == "libraries") {
			if (!isset($this->_libraries) && $this->id) {
				$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer Side Loads'));
				$this->_libraries = [];
				$obj = new LibrarySideLoadScope();
				$obj->whereAddIn('libraryId', array_keys($libraryList), false);
				$obj->sideLoadScopeId = $this->id;
				$obj->find();
				while ($obj->fetch()) {
					$this->_libraries[$obj->id] = clone($obj);
				}
			}
			return $this->_libraries;
		} elseif ($name == "locations") {
			if (!isset($this->_locations) && $this->id) {
				$locationList = Location::getLocationList(!UserAccount::userHasPermission('Administer Side Loads'));
				$this->_locations = [];
				$obj = new LocationSideLoadScope();
				$obj->whereAddIn('locationId', array_keys($locationList), false);
				$obj->sideLoadScopeId = $this->id;
				$obj->find();
				while ($obj->fetch()) {
					$this->_locations[$obj->id] = clone($obj);
				}
			}
			return $this->_locations;
		} else {
			return parent::__get($name);
		}
	}

	public function __set($name, $value) {
		if ($name == "libraries") {
			$this->_libraries = $value;
		} elseif ($name == "locations") {
			$this->_locations = $value;
		} else {
			parent::__set($name, $value);
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

	public function delete($useWhere = false) : int {
		$ret = parent::delete($useWhere);
		if ($ret !== FALSE) {
			$this->clearLocations(true);
			$this->clearLocations(true);
		}
		return $ret;
	}

	public function saveLibraries() : void {
		if (isset ($this->_libraries) && is_array($this->_libraries)) {
			$this->saveOneToManyOptions($this->_libraries, 'sideLoadScopeId');
			unset($this->_libraries);
		}
	}

	public function saveLocations() : void {
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

	public function clearLibraries($forceClearAll) : void {
		if (!$forceClearAll && UserAccount::userHasPermission('Administer Side Load Scopes for Home Library') && !UserAccount::userHasPermission('Administer Side Loads')) {
			$librarySideLoadScopes = [];
			$library = Library::getPatronHomeLibrary(UserAccount::getActiveUserObj());
			$librarySideLoadScope = new LibrarySideLoadScope();
			$librarySideLoadScope->libraryId = $library->libraryId;
			$librarySideLoadScope->sideLoadScopeId = $this->id;
			$librarySideLoadScope->find();
			while ($librarySideLoadScope->fetch()) {
				$librarySideLoadScopes[$librarySideLoadScope->id] = $librarySideLoadScope;
			}
			$this->clearOneToManyOptions('LibrarySideLoadScope', 'sideLoadScopeId', $librarySideLoadScopes);
		} else {
			$this->clearOneToManyOptions('LibrarySideLoadScope', 'sideLoadScopeId');
		}
		unset($this->_libraries);
	}

	public function clearLocations($forceClearAll) : void {
		if (!$forceClearAll && UserAccount::userHasPermission('Administer Side Load Scopes for Home Library') && !UserAccount::userHasPermission('Administer Side Loads')) {
			$locationList = Location::getLocationList(!UserAccount::userHasPermission('Administer Side Loads'));
			$locationSideLoadScopes = [];
			foreach ($locationList as $locationId => $value) {
				$locationSideLoadScope = new LocationSideLoadScope();
				$locationSideLoadScope->locationId = $locationId;
				$locationSideLoadScope->find();
				while ($locationSideLoadScope->fetch()) {
					$locationSideLoadScopes[$locationSideLoadScope->id] = $locationSideLoadScope;
				}
			}
			$this->clearOneToManyOptions('LocationSideLoadScope', 'sideLoadScopeId', $locationSideLoadScopes);
		} else {
			$this->clearOneToManyOptions('LocationSideLoadScope', 'sideLoadScopeId');
		}
		unset($this->_locations);
	}

	private SideLoad|null|false $_parentSideLoad = false;
	public function getParentSideLoad() : SideLoad|null {
		if ($this->_parentSideLoad === false) {
			$this->_parentSideLoad = new SideLoad();
			$this->_parentSideLoad->id = $this->sideLoadId;
			if (!$this->_parentSideLoad->find(true)) {
				$this->_parentSideLoad = null;
			}
		}
		return $this->_parentSideLoad;
	}
	/**
	 * Determine if the active user can view the side load scope in the edit form.
	 * The form may still be largely read-only depending on how it is shared.
	 * @return bool
	 */
	public function canActiveUserEdit() : bool {
		$parentSideLoad = $this->getParentSideLoad();
		if ($parentSideLoad == null) {
			return false;
		}else{
			return $parentSideLoad->canActiveUserEdit();
		}
	}

	public function canActiveUserDelete() {
		return !$this->isReadOnly();
	}

	private ?bool $_isReadOnly = null;
	/**
	 * Determine whether the SideLoad can be changed by the active user.
	 * This is slightly different from canActiveUserEdit because we want the user to be able to view
	 * but not change the side load and access the scope(s) they have access to
	 *
	 * @return bool
	 */
	public function isReadOnly() : bool {
		if ($this->_isReadOnly === null) {
			//Active user can edit if they have permission to edit everything or this is for their home location or sharing allows editing
			if (UserAccount::userHasPermission('Administer Side Loads')) {
				$this->_isReadOnly = false;
			}elseif (UserAccount::userHasPermission('Administer Side Loads for Home Library') || UserAccount::userHasPermission('Administer Side Load Scopes for Home Library')){
				$parentSideLoad = $this->getParentSideLoad();
				if ($parentSideLoad == null) {
					$this->_isReadOnly = true;
				}else{
					$allowableLibraries = Library::getLibraryList(true);
					if (array_key_exists($parentSideLoad->owningLibrary, $allowableLibraries)) {
						$this->_isReadOnly = false;
					}else{
						//Ok if shared by everyone
						if ($parentSideLoad->sharing == 1) {
							$this->_isReadOnly = false;
						}else{
							$this->_isReadOnly = true;
						}
					}
				}
			}else{ //Administer Scopes for Home Library ONly
				$this->_isReadOnly = true;
			}
		}
		return $this->_isReadOnly;
	}
}
