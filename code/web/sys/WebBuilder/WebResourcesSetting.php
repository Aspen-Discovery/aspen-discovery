<?php /** @noinspection PhpMissingFieldTypeInspection */
require_once ROOT_DIR . '/sys/WebBuilder/CustomWebResourcePage.php';
require_once ROOT_DIR . '/sys/WebBuilder/WebBuilderAudience.php';
require_once ROOT_DIR . '/sys/WebBuilder/WebBuilderCategory.php';
require_once ROOT_DIR . '/sys/WebBuilder/WebResourcesToIndex.php';
class WebResourcesSetting extends DataObject
{
	public $__table = 'web_builder_web_resources_settings';    // table name
	public $id;
	public $name;
	public $indexAtoZ;

	private $_libraries;
	private $_customWebResourcesList;
	private $_resourceAudiences;
	private $_resourceCategories;

	public static function getObjectStructure($context = ''): array {
		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Web Resources'));
		$audiencesList = WebBuilderAudience::getAudiences();
		$categoriesList = WebBuilderCategory::getCategories();
		$customWebResourcesList = CustomWebResourcePage::getCustomPages();

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
				'description' => 'A name for the settings',
				'maxLength' => 100,
			],
			'resourceListingSection' => [
				'property' => 'resourceListingSection',
				'type' => 'section',
				'label' => 'Web Resource Listing Page',
				'expandByDefault' => true,
				'properties' => [
					'resourceListingLink' => [
						'property' => 'resourceListingLink',
						'type' => 'url',
						'label' => 'The Resource Listing Page can be found at',
						'readOnly' => true,
					],
				]
			],
			'aToZSection' => [
				'property' => 'aToZSection',
				'type' => 'section',
				'label' => 'A-Z Listing Page',
				'expandByDefault' => true,
				'properties' => [
					'aToZListingLink' => [
						'property' => 'aToZListingLink',
						'type' => 'url',
						'label' => 'The A-Z Resources Page can be found at',
						'readOnly' => true,
					],
					'descriptionAtoZ' => [
						'property' => 'descriptionAtoZ',
						'type' => 'translatableTextBlock',
						'label' => 'Description for A to Z Web Resources',
						'description' => 'A description for the AtoZ resource page.',
						'defaultTextFile' => '',
						'hideInLists' => true,
					],
					'indexAtoZ' => [
						'property' => 'indexAtoZ',
						'type' => 'checkbox',
						'label' => 'Index A to Z',
						'default' => false,
						'hideInLists' => true,
					],
				],
			],
			'customWebResourcesToIndex' => [
				'property' => 'customWebResourcesToIndex',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Custom Web Resource Pages To Index',
				'description' => 'Define which custom web resource pages get indexed.',
				'values' => $customWebResourcesList,
				'hideInLists' => true,
			],
			'webResourceAudiencesToIndex' => [
				'property' => 'webResourceAudiencesToIndex',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Audience Resource Pages To Index',
				'description' => 'Define which audience resource pages get indexed.',
				'values' => $audiencesList,
				'hideInLists' => true,
			],
			'webResourceCategoriesToIndex' => [
				'property' => 'webResourceCategoriesToIndex',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Category Resource Pages To Index',
				'description' => 'Define which category resource pages get indexed.',
				'values' => $categoriesList,
				'hideInLists' => true,
			],
			'libraries' => [
				'property' => 'libraries',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Libraries',
				'description' => 'Define libraries that use these settings',
				'values' => $libraryList,
				'hideInLists' => true,
			]
		];
	}

	public function insert($context = '') {
		$this->lastUpdate = time();
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveAtoZIndexOption();
			$this->saveCustomWebResourcesToIndex();
			$this->saveWebResourceAudiencesToIndex();
			$this->saveWebResourceCategoriesToIndex();
			$this->saveTextBlockTranslations('descriptionAtoZ');
		}
		return $ret;
	}
	public function update($context = '') {
		$this->lastUpdate = time();
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveAtoZIndexOption();
			$this->saveCustomWebResourcesToIndex();
			$this->saveWebResourceAudiencesToIndex();
			$this->saveWebResourceCategoriesToIndex();
			$this->saveTextBlockTranslations('descriptionAtoZ');
		}
		return $ret;
	}

	public function __get($name) {
		if ($name == "libraries") {
			return $this->getLibraries();
		} elseif ($name == "customWebResourcesToIndex") {
			return $this->getCustomWebResourcesToIndex();
		} elseif ($name == "webResourceAudiencesToIndex") {
			return $this->getWebResourceAudiencesToIndex();
		} elseif ($name == "webResourceCategoriesToIndex") {
			return $this->getWebResourceCategoriesToIndex();
		} elseif ($name == 'resourceListingLink') {
			global $configArray;
			$baseUrl = $configArray['Site']['url'];
			return "$baseUrl/WebBuilder/ResourcesList";
		} elseif ($name == 'aToZListingLink') {
			global $configArray;
			$baseUrl = $configArray['Site']['url'];
			return "$baseUrl/WebBuilder/ResourcesAtoZ";
		} else {
			return parent::__get($name);
		}
	}

	public function __set($name, $value) {
		if ($name == "libraries") {
			$this->_libraries = $value;
		} elseif ($name == "customWebResourcesToIndex") {
			$this->_customWebResourcesList = $value;
		} elseif ($name == "webResourceAudiencesToIndex") {
			$this->_resourceAudiences = $value;
		} elseif ($name == "webResourceCategoriesToIndex") {
			$this->_resourceCategories = $value;
		}
		else {
			parent::__set($name, $value);
		}
	}

	public function delete($useWhere = false): int {
		$ret = parent::delete($useWhere);
		if ($ret && !empty($this->id)) {
			$this->clearLibraries();
		}
		return $ret;
	}

	public function getLibraries(): ?array {
		if (!isset($this->_libraries) && $this->id) {
			$this->_libraries = [];
			$obj = new Library();
			$obj->webResourcesSettingId = $this->id;
			$obj->find();
			while ($obj->fetch()) {
				$this->_libraries[$obj->libraryId] = $obj->libraryId;
			}
		}
		return $this->_libraries;
	}


	public function getCustomWebResourcesToIndex()
	{
		if (!isset($this->_customWebResourcesList) && $this->id) {
			$this->_customWebResourcesList = [];
			$webResourcesToIndex = new WebResourcesToIndex();
			$webResourcesToIndex->webResourcesSettingId = $this->id;
			$webResourcesToIndex->find();
			while ($webResourcesToIndex->fetch()) {
				$this->_customWebResourcesList[$webResourcesToIndex->customWebResourcePageId] = $webResourcesToIndex->customWebResourcePageId;
			}
		}
		return $this->_customWebResourcesList;
	}

	public function getWebResourceAudiencesToIndex()
	{
		if (!isset($this->_resourceAudiences) && $this->id) {
			$this->_resourceAudiences = [];
			$resourceAudiencesToIndex = new WebResourcesToIndex();
			$resourceAudiencesToIndex->webResourcesSettingId = $this->id;
			$resourceAudiencesToIndex->find();
			while ($resourceAudiencesToIndex->fetch()) {
				$this->_resourceAudiences[$resourceAudiencesToIndex->webResourceAudienceId] = $resourceAudiencesToIndex->webResourceAudienceId;
			}

		}
		return $this->_resourceAudiences;
	}

	public function getWebResourceCategoriesToIndex()
	{
		if (!isset($this->_resourceCategories) && $this->id) {
			$this->_resourceCategories = [];
			$resourceCategoriesToIndex = new WebResourcesToIndex();
			$resourceCategoriesToIndex->webResourcesSettingId = $this->id;
			$resourceCategoriesToIndex->find();
			while ($resourceCategoriesToIndex->fetch()) {
				$this->_resourceCategories[$resourceCategoriesToIndex->webResourceCategoryId] = $resourceCategoriesToIndex->webResourceCategoryId;
			}
		}
		return $this->_resourceCategories;
	}

	public function saveLibraries(): void {
		if (isset($this->_libraries) && is_array($this->_libraries)) {
			$this->clearLibraries();

			foreach ($this->_libraries as $libraryId) {
				$library = new Library();
				$library->libraryId = $libraryId;
				$library->find();
				while ($library->fetch()){
					$library->webResourcesSettingId = $this->id;
					$library->update();
				}
			}
			unset($this->_libraries);
		}
	}

	public function saveAtoZIndexOption(): void {
		$obj = new WebResourcesToIndex();
		$obj->webResourcesSettingId = $this->id;
		$obj->webResourcePageType = "AtoZ";
		if (isset($this->indexAtoZ) && $this->indexAtoZ) {
			$obj->webResourcePageURL = "/WebBuilder/ResourcesAtoZ";
			$obj->update();
		} else {
			$obj->find();
			while ($obj->fetch()){
				$obj->delete(true);
			}
		}
	}
	public function saveCustomWebResourcesToIndex(): void {
		if (isset($this->_customWebResourcesList) && is_array($this->_customWebResourcesList)) {
			$this->clearCustomWebResourcesToIndex();

			foreach ($this->_customWebResourcesList as $customWebResourcePageId) {
				$customWebResourcePage = new CustomWebResourcePage();
				$customWebResourcePage->id = $customWebResourcePageId;
				$customWebResourcePage->find();
				while ($customWebResourcePage->fetch()) {
					$customResourcesToIndex = new WebResourcesToIndex();
					$customResourcesToIndex->customWebResourcePageId = $customWebResourcePageId;
					$customResourcesToIndex->webResourcesSettingId = $this->id;
					$customResourcesToIndex->webResourcePageURL = $customWebResourcePage->urlAlias;
					$customResourcesToIndex->webResourcePageType = "custom";
					$customResourcesToIndex->update();
				}
			}
		}
	}
	public function saveWebResourceAudiencesToIndex(): void {
		if (isset($this->_resourceAudiences) && is_array($this->_resourceAudiences)) {
			$this->clearWebResourceAudiencesToIndex();

			foreach ($this->_resourceAudiences as $resourceAudience) {
				$audienceToIndex = new WebBuilderAudience();
				$audienceToIndex->id = $resourceAudience;
				$audienceToIndex->find();
				while ($audienceToIndex->fetch()) {
					$customResourcesToIndex = new WebResourcesToIndex();
					$customResourcesToIndex->webResourceAudienceId = $resourceAudience;
					$customResourcesToIndex->webResourcesSettingId = $this->id;
					$customResourcesToIndex->webResourcePageURL = "/WebBuilder/ResourceAudience?id=" . $resourceAudience;
					$customResourcesToIndex->webResourcePageType = "audience";
					$customResourcesToIndex->update();
				}
			}
		}
	}
	public function saveWebResourceCategoriesToIndex(): void {
		if (isset($this->_resourceCategories) && is_array($this->_resourceCategories)) {
			$this->clearWebResourceCategoriesToIndex();

			foreach ($this->_resourceCategories as $resourceCategory) {
				$categoryToIndex = new WebBuilderCategory();
				$categoryToIndex->id = $resourceCategory;
				$categoryToIndex->find();
				while ($categoryToIndex->fetch()) {
					$customResourcesToIndex = new WebResourcesToIndex();
					$customResourcesToIndex->webResourceCategoryId = $resourceCategory;
					$customResourcesToIndex->webResourcesSettingId = $this->id;
					$customResourcesToIndex->webResourcePageURL = "/WebBuilder/ResourceCategory?id=" . $resourceCategory;
					$customResourcesToIndex->webResourcePageType = "category";
					$customResourcesToIndex->update();
				}
			}
		}
	}

	private function clearLibraries(): void {
		//Delete links to the libraries
		$library = new Library();
		$library->webResourcesSettingId = $this->id;
		while ($library->fetch()){
			$library->webResourcesSettingId = "-1";
			$library->update();
		}
	}

	private function clearCustomWebResourcesToIndex(): void {
		//Delete custom web resources from web_builder_web_resources_to_index
		$customResourcesToIndex = new WebResourcesToIndex();
		$customResourcesToIndex->webResourcesSettingId = $this->id;
		$customResourcesToIndex->webResourcePageType = "custom";
		$customResourcesToIndex->find();
		while ($customResourcesToIndex->fetch()){
			$customResourcesToIndex->delete(true);
		}
	}

	private function clearWebResourceAudiencesToIndex(): void {
		//Delete audience web resource pages from web_builder_web_resources_to_index
		$customResourcesToIndex = new WebResourcesToIndex();
		$customResourcesToIndex->webResourcesSettingId = $this->id;
		$customResourcesToIndex->webResourcePageType = "audience";
		$customResourcesToIndex->find();
		while ($customResourcesToIndex->fetch()){
			$customResourcesToIndex->delete(true);
		}
	}

	private function clearWebResourceCategoriesToIndex(): void {
		//Delete category web resource pages from web_builder_web_resources_to_index
		$customResourcesToIndex = new WebResourcesToIndex();
		$customResourcesToIndex->webResourcesSettingId = $this->id;
		$customResourcesToIndex->webResourcePageType = "category";
		$customResourcesToIndex->find();
		while ($customResourcesToIndex->fetch()){
			$customResourcesToIndex->delete(true);
		}
	}

}