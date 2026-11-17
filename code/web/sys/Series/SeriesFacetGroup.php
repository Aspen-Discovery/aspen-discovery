<?php /** @noinspection PhpMissingFieldTypeInspection */
require_once ROOT_DIR . '/sys/LibraryLocation/Library.php';
require_once ROOT_DIR . '/sys/Series/LibrarySeriesFacetSetting.php';
require_once ROOT_DIR . '/sys/Series/SeriesFacet.php';

class SeriesFacetGroup extends DataObject {
	public $__table = 'series_facet_groups';
	public $id;
	public $name;

	public $_facets;
	private $_libraries;

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context])) {
			return self::$_objectStructure[$context];
		}
		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer Series Facet Settings'));

		$facetSettingStructure = SeriesFacet::getObjectStructure($context);
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
				'keyThis' => 'id',
				'keyOther' => 'facetGroupId',
				'subObjectType' => 'SeriesFacet',
				'structure' => $facetSettingStructure,
				'sortable' => true,
				'storeDb' => true,
				'allowEdit' => true,
				'canEdit' => false,
				'canAddNew' => true,
				'canDelete' => true,
			],
			'libraries' => [
				'property' => 'libraries',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Libraries',
				'description' => 'Define libraries that use this series facet group',
				'values' => $libraryList,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	public function update(string $context = '') : int|bool {
		$ret = parent::update();
		if ($ret === FALSE) {
			return $ret;
		}
		$this->saveFacets();
		$this->saveLibraries();
		return $ret;
	}

	public function insert(string $context = '') : int|bool {
		$ret = parent::insert();
		if ($ret === FALSE) {
			return $ret;
		}
		$this->saveFacets();
		$this->saveLibraries();
		return $ret;
	}

	public function delete(bool $useWhere = false, bool $hardDelete = false) : bool|int {
		if ($useWhere) {
			return parent::delete($useWhere, $hardDelete);
		}
		$this->clearOneToManyOptions('SeriesFacet', 'facetGroupId');
		$this->clearLibraries();
		return parent::delete($useWhere, $hardDelete);
	}

	public function __get($name) {
		if ($name == 'facets') {
			return $this->getFacets();
		}
		if ($name == 'libraries') {
			return $this->getLibraries();
		}
		return parent::__get($name);
	}

	public function __set($name, $value) {
		if ($name == 'facets') {
			$this->_facets = $value;
			return;
		}
		if ($name == 'libraries') {
			$this->_libraries = $value;
			return;
		}
		parent::__set($name, $value);
	}

	/** @return ?SeriesFacet[] */
	public function getFacets(): ?array {
		$facetsAreLoaded = isset($this->_facets);
		$groupIsNew = empty($this->id);
		if ($facetsAreLoaded || $groupIsNew) {
			return $this->_facets;
		}
		$this->_facets = [];
		$facet = new SeriesFacet();
		$facet->facetGroupId = $this->id;
		$facet->orderBy('weight');
		$facet->find();
		while ($facet->fetch()) {
			$this->_facets[$facet->id] = clone($facet);
		}
		return $this->_facets;
	}

	public function saveFacets() : void {
		$hasFacetsToSave = isset($this->_facets) && is_array($this->_facets);
		if (!$hasFacetsToSave) {
			return;
		}
		$this->saveOneToManyOptions($this->_facets, 'facetGroupId');
		$this->_facets = null;
	}

	public function getLibraries() : ?array {
		$librariesAreLoaded = isset($this->_libraries);
		$groupIsNew = empty($this->id);
		if ($librariesAreLoaded || $groupIsNew) {
			return $this->_libraries;
		}
		$this->_libraries = [];
		$librarySeriesFacetSetting = new LibrarySeriesFacetSetting();
		$librarySeriesFacetSetting->seriesFacetGroupId = $this->id;
		$librarySeriesFacetSetting->find();
		while ($librarySeriesFacetSetting->fetch()) {
			$this->_libraries[$librarySeriesFacetSetting->libraryId] = $librarySeriesFacetSetting->libraryId;
		}
		return $this->_libraries;
	}

	public function saveLibraries() : void {
		$hasLibrariesToSave = isset($this->_libraries) && is_array($this->_libraries);
		if (!$hasLibrariesToSave) {
			return;
		}
		$this->clearLibraries();
		foreach ($this->_libraries as $libraryId) {
			$this->clearLibraryLink($libraryId);
			$librarySeriesFacetSetting = new LibrarySeriesFacetSetting();
			$librarySeriesFacetSetting->libraryId = $libraryId;
			$librarySeriesFacetSetting->seriesFacetGroupId = $this->id;
			$librarySeriesFacetSetting->insert();
		}
		$this->_libraries = null;
	}

	private function clearLibraries() : void {
		$librarySeriesFacetSetting = new LibrarySeriesFacetSetting();
		$librarySeriesFacetSetting->seriesFacetGroupId = $this->id;
		$librarySeriesFacetSetting->find();
		while ($librarySeriesFacetSetting->fetch()) {
			$librarySeriesFacetSetting->delete();
		}
	}

	private function clearLibraryLink(int $libraryId) : void {
		$librarySeriesFacetSetting = new LibrarySeriesFacetSetting();
		$librarySeriesFacetSetting->libraryId = $libraryId;
		$librarySeriesFacetSetting->find();
		while ($librarySeriesFacetSetting->fetch()) {
			$librarySeriesFacetSetting->delete();
		}
	}
}
