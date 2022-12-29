<?php

require_once ROOT_DIR . '/sys/AspenLiDA/QuickSearch.php';

class QuickSearchSetting extends DataObject {
	public $__table = 'aspen_lida_quick_search_setting';
	public $id;
	public $settingId;
	public $name;

	private $_libraries;

	public static function getObjectStructure($context = ''): array {
		$quickSearches = QuickSearch::getObjectStructure($context);
		unset($quickSearches['libraryId']);

		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));

		return [
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
				'description' => 'The name of the setting',
				'maxLength' => 50,
			],
			'quickSearches' => [
				'property' => 'quickSearches',
				'type' => 'oneToMany',
				'label' => 'Quick Searches',
				'description' => 'Define quick searches for the library',
				'keyThis' => 'libraryId',
				'keyOther' => 'libraryId',
				'subObjectType' => 'QuickSearch',
				'structure' => $quickSearches,
				'sortable' => true,
				'storeDb' => true,
				'allowEdit' => false,
				'canEdit' => false,
				'hideInLists' => true,
				'canAddNew' => true,
				'canDelete' => true,
			],
			'libraries' => [
				'property' => 'libraries',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Libraries',
				'description' => 'Define libraries that use this scope',
				'values' => $libraryList,
			],
		];
	}

	public function __get($name) {
		if ($name == "libraries") {
			if (!isset($this->_libraries) && $this->id) {
				$this->_libraries = [];
				$obj = new Library();
				$obj->lidaQuickSearchId = $this->id;
				$obj->find();
				while ($obj->fetch()) {
					$this->_libraries[$obj->libraryId] = $obj->libraryId;
				}
			}
			return $this->_libraries;
		} elseif ($name == 'quickSearches') {
			return $this->getQuickSearches();
		} else {
			return $this->_data[$name] ?? null;
		}
	}

	public function __set($name, $value) {
		if ($name == "libraries") {
			$this->_libraries = $value;
		} elseif ($name == 'quickSearches') {
			$this->_quickSearches = $value;
		} else {
			$this->_data[$name] = $value;
		}
	}

	public function update($context = '') {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveQuickSearches();
		}
		return true;
	}

	public function insert($context = '') {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveQuickSearches();
		}
		return $ret;
	}

	public function saveLibraries() {
		if (isset ($this->_libraries) && is_array($this->_libraries)) {
			$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));
			foreach ($libraryList as $libraryId => $displayName) {
				$library = new Library();
				$library->libraryId = $libraryId;
				$library->find(true);
				if (in_array($libraryId, $this->_libraries)) {
					//We want to apply the scope to this library
					if ($library->lidaQuickSearchId != $this->id) {
						$library->lidaQuickSearchId = $this->id;
						$library->update();
					}
				} else {
					//It should not be applied to this scope. Only change if it was applied to the scope
					if ($library->lidaQuickSearchId == $this->id) {
						$library->lidaQuickSearchId = -1;
						$library->update();
					}
				}
			}
			unset($this->_libraries);
		}
	}

	private $_quickSearches;

	public function getQuickSearches() {
		if (!isset($this->_quickSearches) && $this->id) {
			$this->_quickSearches = [];

			$quickSearches = new QuickSearch();
			$quickSearches->quickSearchSettingId = $this->id;
			$quickSearches->orderBy('weight');
			if ($quickSearches->find()) {
				while ($quickSearches->fetch()) {
					$this->_quickSearches[$quickSearches->id] = clone $quickSearches;
				}
			}

		}
		return $this->_quickSearches;
	}

	public function saveQuickSearches() {
		if (isset ($this->_quickSearches) && is_array($this->_quickSearches)) {
			$this->saveOneToManyOptions($this->_quickSearches, 'quickSearchSettingId');
			unset($this->_quickSearches);
		}
	}
}