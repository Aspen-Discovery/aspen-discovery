<?php
require_once ROOT_DIR . '/sys/LibraryLocation/Library.php';
require_once ROOT_DIR . '/sys/WebsiteIndexing/WebsiteFacet.php';

class WebsiteFacetGroup extends DataObject
{
    public $__table = 'website_facet_groups';
    public $id;
    public $name;

    public $_facets;
    private $_libraries;
    private $_locations;

    static function getObjectStructure($context = ''): array
    {
        $libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Website Facet Settings'));
        $locationList = Location::getLocationList(!UserAccount::userHasPermission('Administer All Website Facet Settings'));


        $facetSettingStructure = WebsiteFacet::getObjectStructure($context);
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
                'subObjectType' => 'WebsiteFacet',
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
                'description' => 'Define libraries that use this website facet group',
                'values' => $libraryList,
            ],
            'locations' => [
                'property' => 'locations',
                'type' => 'multiSelect',
                'listStyle' => 'checkboxSimple',
                'label' => 'Locations',
                'description' => 'Define locations that use this browse category group',
                'values' => $locationList,
            ],
        ];
    }

    public function update($context = '')
    {
        $ret = parent::update();
        if ($ret !== FALSE) {
            $this->saveFacets();
            $this->saveLibraries();
            $this->saveLocations();
        }
        return $ret;
    }

    public function insert($context = '')
    {
        $ret = parent::insert();
        if ($ret !== FALSE) {
            $this->saveFacets();
            $this->saveLibraries();
            $this->saveLocations();
        }
        return $ret;
    }

    public function saveFacets()
    {
        if (isset ($this->_facets) && is_array($this->_facets)) {
            $this->saveOneToManyOptions($this->_facets, 'facetGroupId');
            unset($this->facets);
        }
    }

    public function __get($name)
    {
        if ($name == 'facets') {
            return $this->getFacets();
        }
        if ($name == "libraries") {
            return $this->getLibraries();
        }
        if ($name == "locations") {
            return $this->getLocations();
        } else {
            return parent::__get($name);
        }
    }

    public function __set($name, $value)
    {
        if ($name == 'facets') {
            $this->setFacets($value);
        }
        if ($name == "libraries") {
            $this->_libraries = $value;
        }
        if ($name == "locations") {
            $this->_locations = $value;
        } else {
            parent::__set($name, $value);
        }
    }

    /** @return WebsiteFacet[] */
    public function getFacets(): ?array
    {
        if (!isset($this->_facets) && $this->id) {
            $this->_facets = [];
            $facet = new WebsiteFacet();
            $facet->facetGroupId = $this->id;
            $facet->orderBy('weight');
            $facet->find();
            while ($facet->fetch()) {
                $this->_facets[$facet->id] = clone($facet);
            }
        }
        return $this->_facets;
    }

    public function getFacetByIndex($index): ?WebsiteFacet
    {
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

    public function setFacets($value)
    {
        $this->_facets = $value;
    }

    public function clearFacets()
    {
        $this->clearOneToManyOptions('WebsiteFacet', 'facetGroupId');
        /** @noinspection PhpUndefinedFieldInspection */
        $this->facets = [];
    }

    public function getLibraries()
    {
        if (!isset($this->_libraries) && $this->id) {
            $this->_libraries = [];
            $library = new Library();
            $library->websiteIndexingFacetSettingId = $this->id;
            $library->find();
            while ($library->fetch()) {
                $this->_libraries[$library->libraryId] = $library->libraryId;
            }
        }
        return $this->_libraries;
    }
    public function getLocations()
    {
        if (!isset($this->_locations) && $this->id) {
            $this->_locations = [];
            $location = new Location();
            $location->websiteIndexingFacetSettingId = $this->id;
            $location->find();
            while ($location->fetch()) {
                $this->_locations[$location->locationId] = $location->locationId;
            }
        }
        return $this->_locations;
    }

    private function clearLibraries()
    {
        //Delete links to the libraries
        $libraryOAFacetSetting = new Library();
        $libraryOAFacetSetting->websiteIndexingFacetSettingId = $this->id;
        $libraryOAFacetSetting->find();

        while ($libraryOAFacetSetting->fetch()) {
            $libraryOAFacetSetting->websiteIndexingFacetSettingId = "0";
            $libraryOAFacetSetting->update();
        }
    }

    public function saveLibraries()
    {
        if (isset ($this->_libraries) && is_array($this->_libraries)) {
            $libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Website Facet Settings'));
            foreach ($libraryList as $libraryId => $displayName) {
                $library = new Library();
                $library->libraryId = $libraryId;
                $library->find(true);
                if (in_array($libraryId, $this->_libraries)) {
                    //We want to apply the scope to this library
                    if ($library->websiteIndexingFacetSettingId != $this->id) {
                        $library->websiteIndexingFacetSettingId = $this->id;
                        $library->update();
                    }
                } else {
                    //It should not be applied to this scope. Only change if it was applied to the scope
                    if ($library->websiteIndexingFacetSettingId == $this->id) {
                        $library->websiteIndexingFacetSettingId = -1;
                        $library->update();
                    }
                }
            }
            unset($this->_libraries);
        }
    }

    public function saveLocations() {
        if (isset ($this->_locations) && is_array($this->_locations)) {
            $locationList = Location::getLocationList(!UserAccount::userHasPermission('Administer All Website Facet Settings'));
            /**
             * @var int $locationId
             * @var Location $location
             */
            foreach ($locationList as $locationId => $displayName) {
                $location = new Location();
                $location->locationId = $locationId;
                $location->find(true);
                if (in_array($locationId, $this->_locations)) {
                    //We want to apply the scope to this library
                    if ($location->websiteIndexingFacetSettingId != $this->id) {
                        $location->websiteIndexingFacetSettingId = $this->id;
                        $location->update();
                    }
                } else {
                    //It should not be applied to this scope. Only change if it was applied to the scope
                    if ($location->websiteIndexingFacetSettingId == $this->id) {
                        $library = new Library();
                        $library->libraryId = $location->libraryId;
                        $library->find(true);
                        $location->websiteIndexingFacetSettingId = -1;
                        $location->update();
                    }
                }
            }
            unset($this->_locations);
        }
    }
}