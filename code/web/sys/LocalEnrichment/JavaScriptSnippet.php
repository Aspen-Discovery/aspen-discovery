<?php /** @noinspection PhpMissingFieldTypeInspection */

require_once ROOT_DIR . '/sys/DB/LibraryLocationLinkedObject.php';
require_once ROOT_DIR . '/sys/LocalEnrichment/JavaScriptSnippetLibrary.php';
require_once ROOT_DIR . '/sys/LocalEnrichment/JavaScriptSnippetLocation.php';

class JavaScriptSnippet extends DB_LibraryLocationLinkedObject {
	public $__table = 'javascript_snippets';
	public $id;
	public $name;
	public $snippet;
	public $containsAnalyticsCookies;

	protected $_libraries;
	protected $_locations;

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context])) {
			return self::$_objectStructure[$context];
		}

		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All JavaScript Snippets'));
		$locationList = Location::getLocationList(!UserAccount::userHasPermission('Administer All JavaScript Snippets'));

		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'name' => [
				'property' => 'name',
				'type' => 'text',
				'label' => 'Name',
				'description' => 'The Name of the snippet',
				'maxLength' => 50,
			],
			'snippet' => [
				'property' => 'snippet',
				'type' => 'javascript',
				'label' => 'Snippet (include script tags)',
				'description' => 'The JavaScript Snippet to add to pages',
				'hideInLists' => true,
			],
			'libraries' => [
				'property' => 'libraries',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Libraries',
				'description' => 'Define libraries that use this snippet',
				'values' => $libraryList,
				'hideInLists' => true,
			],

			'locations' => [
				'property' => 'locations',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Locations',
				'description' => 'Define locations that use this snippet',
				'values' => $locationList,
				'hideInLists' => true,
			],

			'containsAnalyticsCookies' => [
				'property' => 'containsAnalyticsCookies',
				'type' => 'checkbox',
				'label' => 'Contains Analytics Cookies',
				'description' => 'This snippet contains analytics cookies',
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	/**
	 * @return string[]
	 */
	public function getUniquenessFields(): array {
		return ['name'];
	}

	/**
	 * Override the update functionality to save related objects
	 *
	 * @see DB/DB_DataObject::update()
	 */
	public function update(string $context = '') : int|bool {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveLocations();
		}
		return $ret;
	}

	public function insert(string $context = '') : int|bool {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveLocations();
		}
		return $ret;
	}

	public function delete(bool $useWhere = false, bool $hardDelete = false) : bool|int {
		$ret = parent::delete($useWhere, $hardDelete);
		if ($ret && !empty($this->id)) {
			$javascriptSnippetLibrary = new JavaScriptSnippetLibrary();
			$javascriptSnippetLibrary->javascriptSnippetId = $this->id;
			$javascriptSnippetLibrary->delete(true);

			$javascriptSnippetLocation = new JavaScriptSnippetLocation();
			$javascriptSnippetLocation->javascriptSnippetId = $this->id;
			$javascriptSnippetLocation->delete(true);
		}
		return $ret;
	}

	public function __get($name) {
		if ($name == "libraries") {
			return $this->getLibraries();
		} elseif ($name == "locations") {
			return $this->getLocations();
		} else {
			return parent::__get($name);
		}
	}

	/**
	 * @return ?int[]
	 */
	public function getLibraries(): ?array {
		if (!isset($this->_libraries) && $this->id) {
			$this->_libraries = [];
			$obj = new JavaScriptSnippetLibrary();
			$obj->javascriptSnippetId = $this->id;
			$obj->find();
			while ($obj->fetch()) {
				$this->_libraries[$obj->libraryId] = $obj->libraryId;
			}
		}
		return $this->_libraries;
	}

	/**
	 * @return ?int[]
	 */
	public function getLocations(): ?array {
		if (!isset($this->_locations) && $this->id) {
			$this->_locations = [];
			$obj = new JavaScriptSnippetLocation();
			$obj->javascriptSnippetId = $this->id;
			$obj->find();
			while ($obj->fetch()) {
				$this->_locations[$obj->locationId] = $obj->locationId;
			}
		}
		return $this->_locations;
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

	public function saveLibraries() : void {
		if (isset ($this->_libraries) && is_array($this->_libraries)) {
			$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All JavaScript Snippets'));
			foreach ($libraryList as $libraryId => $displayName) {
				$obj = new JavaScriptSnippetLibrary();
				$obj->javascriptSnippetId = $this->id;
				$obj->libraryId = $libraryId;
				if (in_array($libraryId, $this->_libraries)) {
					if (!$obj->find(true)) {
						$obj->insert();
					}
				} else {
					if ($obj->find(true)) {
						$obj->delete();
					}
				}
			}
		}
	}

	public function saveLocations() : void {
		if (isset ($this->_locations) && is_array($this->_locations)) {
			$locationList = Location::getLocationList(!UserAccount::userHasPermission('Administer All JavaScript Snippets'));
			foreach ($locationList as $locationId => $displayName) {
				$obj = new JavaScriptSnippetLocation();
				$obj->javascriptSnippetId = $this->id;
				$obj->locationId = $locationId;
				if (in_array($locationId, $this->_locations)) {
					if (!$obj->find(true)) {
						$obj->insert();
					}
				} else {
					if ($obj->find(true)) {
						$obj->delete();
					}
				}
			}
		}
	}
}