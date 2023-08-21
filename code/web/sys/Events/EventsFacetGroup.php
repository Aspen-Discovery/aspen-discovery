<?php
require_once ROOT_DIR . '/sys/Events/LibraryEventsSetting.php';
require_once ROOT_DIR . '/sys/Events/EventsFacet.php';

class EventsFacetGroup extends DataObject {
	public $__table = 'events_facet_groups';
	public $id;
	public $name;

	public $_facets;
    private $_libraries;

	static function getObjectStructure($context = ''): array {
        $libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer Events Facet Settings'));

		$facetSettingStructure = EventsFacet::getObjectStructure($context);
		unset($facetSettingStructure['weight']);
		unset($facetSettingStructure['facetGroupId']);
		unset($facetSettingStructure['showAsDropDown']);

		return [
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
				'subObjectType' => 'EventsFacet',
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
                'description' => 'Define libraries that use this event facet group',
                'values' => $libraryList,
            ],
		];
	}
	public function update($context = '') {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveFacets();
            $this->saveLibraries();
		}
		return $ret;
	}

	public function insert($context = '') {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveFacets();
            $this->saveLibraries();
		}
		return $ret;
	}

	public function saveFacets() {
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

	/** @return EventsFacet[] */
	public function getFacets(): ?array {
		if (!isset($this->_facets) && $this->id) {
			$this->_facets = [];
			$facet = new EventsFacet();
			$facet->facetGroupId = $this->id;
			$facet->orderBy('weight');
			$facet->find();
			while ($facet->fetch()) {
				$this->_facets[$facet->id] = clone($facet);
			}
		}
		return $this->_facets;
	}

	public function getFacetByIndex($index): ?EventsFacet {
		$facets = $this->getFacets();

		$i = 0;
		foreach ($facets as $value) {
			if ($i == $index) {
				return $value;
			}
			$i++;
		}
		return NULL;
	}

	public function setFacets($value) {
		$this->_facets = $value;
	}

	public function clearFacets() {
		$this->clearOneToManyOptions('EventsFacet', 'facetGroupId');
		/** @noinspection PhpUndefinedFieldInspection */
		$this->facets = [];
	}

    public function getLibraries() {
        if (!isset($this->_libraries) && $this->id) {
            $this->_libraries = [];
            $library = new LibraryEventsSetting();
            $library->eventsFacetSettingsId = $this->id;
            $library->find();
            while ($library->fetch()) {
                $this->_libraries[$library->libraryId] = $library->libraryId;
            }
        }
        return $this->_libraries;
    }
    private function clearLibraries() {
        //Delete links to the libraries
        $libraryEventSetting = new LibraryEventsSetting();
        $libraryEventSetting->eventsFacetSettingsId = $this->id;
        while ($libraryEventSetting->fetch()){
            $libraryEventSetting->eventsFacetSettingsId = "0";
            $libraryEventSetting->update();
        }
    }
    public function saveLibraries() {
        if (isset($this->_libraries) && is_array($this->_libraries)) {
            $this->clearLibraries();

            foreach ($this->_libraries as $libraryId) {
                $libraryEventSetting = new LibraryEventsSetting();
                $libraryEventSetting->libraryId = $libraryId;

                while ($libraryEventSetting->fetch()){ //if there is no event setting for a library, that library won't save because there's nothing to update
                    $libraryEventSetting->eventsFacetSettingsId = $this->id;
                    $libraryEventSetting->update();
                }
            }
            unset($this->_libraries);
        }
    }

	function getAdditionalListJavascriptActions(): array {
		$objectActions[] = [
			'text' => 'Copy',
			'onClick' => "return AspenDiscovery.Admin.showCopyFacetGroupForm('$this->id')",
			'icon' => 'fas fa-copy',
		];

		return $objectActions;
	}
}