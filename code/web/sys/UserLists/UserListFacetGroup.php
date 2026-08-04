<?php /** @noinspection PhpMissingFieldTypeInspection */
require_once ROOT_DIR . '/sys/UserLists/LibraryUserListFacetSetting.php';
require_once ROOT_DIR . '/sys/UserLists/UserListFacet.php';

class UserListFacetGroup extends DataObject {
	public $__table = 'user_list_facet_groups';
	public $id;
	public $name;

	public $_facets;
    private $_libraries;

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context]) && self::$_objectStructure[$context] !== null) {
			return self::$_objectStructure[$context];
		}
		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer User List Facet Settings'));

		$facetSettingStructure = UserListFacet::getObjectStructure($context);
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
				'subObjectType' => 'UserListFacet',
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
				'description' => 'Define libraries that use this user list facet group',
				'values' => $libraryList,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}
	public function update(string $context = '') : int|bool {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveFacets();
			$this->saveLibraries();
		}
		return $ret;
	}

	public function insert(string $context = '') : int|bool {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveFacets();
			$this->saveLibraries();
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
		} if ($name == "libraries") {
            return $this->getLibraries();
        }else {
			return parent::__get($name);
		}
	}

	public function __set($name, $value) {
		if ($name == 'facets') {
			$this->setFacets($value);
		}if ($name == "libraries") {
            $this->_libraries = $value;
        }  else {
			parent::__set($name, $value);
		}
	}

	/** @return ?UserListFacet[] */
	public function getFacets(): ?array {
		if (!isset($this->_facets) && $this->id) {
			$this->_facets = [];
			$facet = new UserListFacet();
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
		$this->clearOneToManyOptions('UserListFacet', 'facetGroupId');
		/** @noinspection PhpUndefinedFieldInspection */
		$this->facets = [];
	}

	public function getLibraries() : ?array {
		if (!isset($this->_libraries) && $this->id) {
			$this->_libraries = [];
			$library = new LibraryUserListFacetSetting();
			$library->userListFacetGroupId = $this->id;
			$library->find();
			while ($library->fetch()) {
				$this->_libraries[$library->libraryId] = $library->libraryId;
			}
		}
		return $this->_libraries;
	}
	private function clearLibraries() : void {
		//Delete links to the libraries
		$libraryUserListSetting = new LibraryUserListFacetSetting();
		$libraryUserListSetting->userListFacetGroupId = $this->id;
		$libraryUserListSetting->find();
		while ($libraryUserListSetting->fetch()){
			$libraryUserListSetting->delete();
		}
	}
	public function saveLibraries() : void {
		if (isset($this->_libraries) && is_array($this->_libraries)) {
			$this->clearLibraries();

			foreach ($this->_libraries as $libraryId) {
				//Make sure the library is not linked to another facet group
				$libraryUserListSetting = new LibraryUserListFacetSetting();
				$libraryUserListSetting->libraryId = $libraryId;
				$libraryUserListSetting->find();
				$foundExisting = false;
				while ($libraryUserListSetting->fetch()) {
					if ($libraryUserListSetting->userListFacetGroupId != $this->id) {
						$libraryUserListSetting->delete();
					}elseif ($libraryUserListSetting->userListFacetGroupId == $this->id) {
						$foundExisting = true;
					}
				}

				if (!$foundExisting) {
					$libraryUserListSetting = new LibraryUserListFacetSetting();
					$libraryUserListSetting->libraryId = $libraryId;
					$libraryUserListSetting->userListFacetGroupId = $this->id;
					$libraryUserListSetting->update();
				}
			}
			unset($this->_libraries);
		}
	}

	function getAdditionalListJavascriptActions(): array {
		$objectActions[] = [
			'text' => 'Copy',
			'onClick' => "return AspenDiscovery.Admin.showCopyUserListFacetGroupForm('$this->id')",
			'icon' => 'fas fa-copy',
		];

		return $objectActions;
	}
}