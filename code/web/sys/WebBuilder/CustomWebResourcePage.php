<?php
require_once ROOT_DIR . '/sys/WebBuilder/LibraryCustomWebResourcePage.php';
require_once ROOT_DIR . '/sys/WebBuilder/WebBuilderAudience.php';
require_once ROOT_DIR . '/sys/WebBuilder/WebBuilderCategory.php';
require_once ROOT_DIR . '/sys/WebBuilder/CustomWebResourcePageAudience.php';
require_once ROOT_DIR . '/sys/WebBuilder/CustomWebResourcePageCategory.php';
require_once ROOT_DIR . '/sys/WebBuilder/CustomWebResourcePageAccess.php';
require_once ROOT_DIR . '/sys/DB/LibraryLinkedObject.php';

class CustomWebResourcePage extends DB_LibraryLinkedObject {
	public $__table = 'web_builder_custom_web_resource_page';
	public $id;
	public $title;
	public $urlAlias;
	public $requireLogin;
	public $requireLoginUnlessInLibrary;
	public $lastUpdate;

	private $_libraries;
	private $_audiences;
	private $_categories;
	private $_allowAccess;
	protected $_description;

	public function getNumericColumnNames(): array
	{
		return [
			'id',
			'lastUpdate',
		];
	}

	static function getObjectStructure($context = ''): array
	{
		$libraryList = Library::getLibraryListWithWebBuilderStatus(!UserAccount::userHasPermission('Administer All Basic Pages'));
		$audiencesList = WebBuilderAudience::getAudiences();
		$categoriesList = WebBuilderCategory::getCategories();
		$patronTypeList = PType::getPatronTypeList();

		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id within the database',
			],
			'title' => [
				'property' => 'title',
				'type' => 'text',
				'label' => 'Title',
				'description' => 'The title of the resource page',
				'size' => '40',
				'maxLength' => 100,
			],
			'urlAlias' => [
				'property' => 'urlAlias',
				'type' => 'text',
				'label' => 'URL Alias (no domain, should start with /)',
				'description' => 'The url of the page (no domain name)',
				'size' => '40',
				'maxLength' => 100,
			],
			'customWebResourceDescription' => [
				'property' => 'customWebResourceDescription',
				'type' => 'translatableTextBlock',
				'label' => 'Description',
				'description' => 'A description for the resource page.',
				'defaultTextFile' => '',
				'hideInLists' => true,
			],
			'requireLogin' => [
				'property' => 'requireLogin',
				'type' => 'checkbox',
				'label' => 'Require login to access',
				'description' => 'Require login to access page',
				'onchange' => 'return AspenDiscovery.WebBuilder.updateWebBuilderFields();',
				'default' => 0,
			],
			'requireLoginUnlessInLibrary' => [
				'property' => 'requireLoginUnlessInLibrary',
				'type' => 'checkbox',
				'label' => 'Allow access without logging in while in library',
				'description' => 'Require login to access page unless in library',
				'default' => 0,
			],
			'allowAccess' => [
				'property' => 'allowAccess',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Allow Access to patrons with these PTypes',
				'description' => 'Define what patron types should have access to the page',
				'values' => $patronTypeList,
				'hideInLists' => false,
			],
			'audiences' => [
				'property' => 'audiences',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Audience',
				'description' => 'Define audiences for the page',
				'values' => $audiencesList,
				'hideInLists' => true,
			],
			'categories' => [
				'property' => 'categories',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Categories',
				'description' => 'Define categories for the page',
				'values' => $categoriesList,
				'hideInLists' => true,
			],
			'lastUpdate' => [
				'property' => 'lastUpdate',
				'type' => 'timestamp',
				'label' => 'Last Update',
				'description' => 'When the resource was changed last',
				'default' => 0,
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

	public function insert($context = '')
	{
		$this->lastUpdate = time();
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveAudiences();
			$this->saveCategories();
			$this->saveAccess();
			$this->saveTextBlockTranslations('customWebResourceDescription');
		}
		return $ret;
	}

	public function update($context = '')
	{
		$this->lastUpdate = time();
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveAudiences();
			$this->saveCategories();
			$this->saveAccess();
			$this->saveTextBlockTranslations('customWebResourceDescription');
		}
		return $ret;
	}

	public function __get($name)
	{
		if ($name == "libraries") {
			return $this->getLibraries();
		} elseif ($name == "audiences") {
			return $this->getAudiences();
		} elseif ($name == "categories") {
			return $this->getCategories();
		} elseif ($name == "allowAccess") {
			return $this->getAccess();
		} else {
			return parent::__get($name);
		}
	}

	public function __set($name, $value)
	{
		if ($name == "libraries") {
			$this->_libraries = $value;
		} elseif ($name == "audiences") {
			$this->_audiences = $value;
		} elseif ($name == "categories") {
			$this->_categories = $value;
		} elseif ($name == "allowAccess") {
			$this->_allowAccess = $value;
		} else {
			parent::__set($name, $value);
		}
	}

	public function delete($useWhere = false): int
	{
		$ret = parent::delete($useWhere);
		if ($ret && !empty($this->id)) {
			$this->clearLibraries();
			$this->clearAudiences();
			$this->clearCategories();
			$this->clearAccess();
		}
		return $ret;
	}

	public function getLibraries(): ?array
	{
		if (!isset($this->_libraries) && $this->id) {
			$this->_libraries = [];
			$libraryLink = new LibraryCustomWebResourcePage();
			$libraryLink->customResourcePageId = $this->id;
			$libraryLink->find();
			while ($libraryLink->fetch()) {
				$this->_libraries[$libraryLink->libraryId] = $libraryLink->libraryId;
			}
		}
		return $this->_libraries;
	}

	public function getAudiences() {
		if (!isset($this->_audiences) && $this->id) {
			$this->_audiences = [];
			$audienceLink = new CustomWebResourcePageAudience();
			$audienceLink->customResourcePageId = $this->id;
			$audienceLink->find();
			while ($audienceLink->fetch()) {
				$this->_audiences[$audienceLink->audienceId] = $audienceLink->audienceId;
			}
		}
		return $this->_audiences;
	}

	public function getCategories()
	{
		if (!isset($this->_categories) && $this->id) {
			$this->_categories = [];
			$categoryLink = new CustomWebResourcePageCategory();
			$categoryLink->customResourcePageId = $this->id;
			$categoryLink->find();
			while ($categoryLink->fetch()) {
				$this->_categories[$categoryLink->categoryId] = $categoryLink->categoryId;
			}
		}
		return $this->_categories;
	}

	public function getAccess() {
		if (!isset($this->_allowAccess) && $this->id) {
			$this->_allowAccess = [];
			$patronTypeLink = new CustomWebResourcePageAccess();
			$patronTypeLink->customResourcePageId = $this->id;
			$patronTypeLink->find();
			while ($patronTypeLink->fetch()) {
				$this->_allowAccess[$patronTypeLink->patronTypeId] = $patronTypeLink->patronTypeId;
			}
		}
		return $this->_allowAccess;
	}

	public function saveLibraries()
	{
		if (isset($this->_libraries) && is_array($this->_libraries)) {
			$this->clearLibraries();

			foreach ($this->_libraries as $libraryId) {
				$libraryLink = new LibraryCustomWebResourcePage();

				$libraryLink->customResourcePageId = $this->id;
				$libraryLink->libraryId = $libraryId;
				$libraryLink->insert();
			}
			unset($this->_libraries);
		}
	}

	public function saveAudiences()
	{
		if (isset($this->_audiences) && is_array($this->_audiences)) {
			$this->clearAudiences();

			foreach ($this->_audiences as $audienceId) {
				$link = new CustomWebResourcePageAudience();

				$link->customResourcePageId = $this->id;
				$link->audienceId = $audienceId;
				$link->insert();
			}
			unset($this->_audiences);
		}
	}

	public function saveCategories()
	{
		if (isset($this->_categories) && is_array($this->_categories)) {
			$this->clearCategories();

			foreach ($this->_categories as $categoryId) {
				$link = new CustomWebResourcePageCategory();

				$link->customResourcePageId = $this->id;
				$link->categoryId = $categoryId;
				$link->insert();
			}
			unset($this->_categories);
		}
	}

	public function saveAccess() {
		if (isset($this->_allowAccess) && is_array($this->_allowAccess)) {
			$this->clearAccess();

			foreach ($this->_allowAccess as $patronTypeId) {
				$link = new CustomWebResourcePageAccess();

				$link->customResourcePageId = $this->id;
				$link->patronTypeId = $patronTypeId;
				$link->insert();
			}
			unset($this->_allowAccess);
		}
	}

	private function clearLibraries()
	{
		//Delete links to the libraries
		$libraryLink = new LibraryCustomWebResourcePage();
		$libraryLink->customResourcePageId = $this->id;
		return $libraryLink->delete(true);
	}

	private function clearAudiences()
	{
		//Delete links to the libraries
		$link = new CustomWebResourcePageAudience();
		$link->customResourcePageId = $this->id;
		return $link->delete(true);
	}

	private function clearCategories()
	{
		//Delete links to the libraries
		$link = new CustomWebResourcePageCategory();
		$link->customResourcePageId = $this->id;
		return $link->delete(true);
	}

	private function clearAccess() {
		//Delete links to the patron types
		$link = new CustomWebResourcePageAccess();
		$link->customResourcePageId = $this->id;
		return $link->delete(true);
	}
	public function getLinksForJSON(): array {
		$links = parent::getLinksForJSON();
		//Audiences
		$audiencesList = WebBuilderAudience::getAudiences();
		$audiences = $this->getAudiences();
		$links['audiences'] = [];
		foreach ($audiences as $audience) {
			$links['audiences'][] = $audiencesList[$audience];
		}
		//Categories
		$categoriesList = WebBuilderCategory::getCategories();
		$categories = $this->getCategories();
		$links['categories'] = [];
		foreach ($categories as $category) {
			$links['categories'][] = $categoriesList[$category];
		}
		//Allow Access
		$patronTypeList = PType::getPatronTypeList();
		$accessList = $this->getAccess();
		$links['allowAccess'] = [];
		foreach ($accessList as $accessInfo) {
			$links['allowAccess'] = $patronTypeList[$accessInfo];
		}

		return $links;
	}

	public function loadRelatedLinksFromJSON($jsonLinks, $mappings, $overrideExisting = 'keepExisting'): bool {
		$result = parent::loadRelatedLinksFromJSON($jsonLinks, $mappings, $overrideExisting);

		if (array_key_exists('audiences', $jsonLinks)) {
			$audiences = [];
			$audiencesList = WebBuilderAudience::getAudiences();
			$audiencesList = array_flip($audiencesList);
			foreach ($jsonLinks['audiences'] as $audience) {
				if (array_key_exists($audience, $audiencesList)) {
					$audiences[] = $audiencesList[$audience];
				}
			}
			$this->_audiences = $audiences;
			$result = true;
		}
		if (array_key_exists('categories', $jsonLinks)) {
			$categories = [];
			$categoriesList = WebBuilderCategory::getCategories();
			$categoriesList = array_flip($categoriesList);
			foreach ($jsonLinks['categories'] as $category) {
				if (array_key_exists($category, $categoriesList)) {
					$categories[] = $categoriesList[$category];
				}
			}
			$this->_categories = $categories;
			$result = true;
		}
		if (array_key_exists('allowAccess', $jsonLinks)) {
			$allowAccess = [];
			$allowAccessList = PType::getPatronTypeList();
			$allowAccessList = array_flip($allowAccessList);
			foreach ($jsonLinks['allowAccess'] as $pType) {
				if (array_key_exists($pType, $allowAccessList)) {
					$allowAccess[] = $allowAccessList[$pType];
				}
			}
			$this->_allowAccess = $allowAccess;
			$result = true;
		}
		return $result;
	}
	public function canView(): bool {
		global $locationSingleton;

		$requireLogin = $this->requireLogin;
		$allowInLibrary = $this->requireLoginUnlessInLibrary;

		if ($requireLogin) {
			$activeLibrary = $locationSingleton->getActiveLocation();
			$user = UserAccount::getLoggedInUser();
			if ($allowInLibrary && $activeLibrary != null) {
				return true;
			}
			if (!$user) {
				return false;
			} else {
				$okToAccess = false;
				$userPatronType = $user->patronType;

				if ($userPatronType == NULL) {
					$okToAccess = true;
				} elseif (empty($this->getAccess())) {
					//No patron types defined, everyone can access
					$okToAccess = true;
				} else {
					$patronType = new pType();
					$patronType->pType = $userPatronType;
					if ($patronType->find(true)) {
						$patronTypeId = $patronType->id;
						$patronTypeLink = new CustomWebResourcePageAccess();
						$patronTypeLink->customResourcePageId = $this->id;
						$patronTypeLink->patronTypeId = $patronTypeId;
						if ($patronTypeLink->find(true)) {
							$okToAccess = true;
						} else {
							$okToAccess = false;
						}
					} else {
						$okToAccess = false;
					}
				}

				return $okToAccess;
			}
		} else {
			return true;
		}
	}

	public function getHiddenReason(): string {
		global $locationSingleton;
		$requireLogin = $this->requireLogin;
		$allowInLibrary = $this->requireLoginUnlessInLibrary;
		if ($requireLogin) {
			$activeLibrary = $locationSingleton->getActiveLocation();
			$user = UserAccount::getLoggedInUser();
			if ($allowInLibrary && $activeLibrary != null) {
				return '';
			}
			if (!$user) {
				return translate([
					'text' => 'You must be logged in to view this page.',
					'isPublicFacing' => true,
				]);
			} else {
				$userPatronType = $user->patronType;

				if ($userPatronType == NULL) {
					return '';
				} elseif (empty($this->getAccess())) {
					//No patron types defined, everyone can access
					return '';
				} else {
					$patronType = new pType();
					$patronType->pType = $userPatronType;
					if ($patronType->find(true)) {
						$patronTypeId = $patronType->id;
					} else {
						return translate([
							'text' => 'Could not determine the type of user for you.',
							'isPublicFacing' => true,
						]);
					}

					$patronTypeLink = new CustomWebResourcePageAccess();
					$patronTypeLink->customResourcePageId = $this->id;
					$patronTypeLink->patronTypeId = $patronTypeId;
					if ($patronTypeLink->find(true)) {
						return '';
					} else {
						return translate([
							'text' => "We're sorry, but it looks like you don't have access to this page..",
							'isPublicFacing' => true,
						]);
					}
				}
			}
		} else {
			return '';
		}
	}

	public static function getCustomPages() {
		$customPages = [];
		$customPage = new CustomWebResourcePage();
		$customPage->orderBy('title');
		$customPage->find();
		while ($customPage->fetch()) {
			$customPages[$customPage->id] = $customPage->title;
		}
		return $customPages;
	}

	public function loadCopyableSubObjects() {
		$this->getCategories();
		$this->getAudiences();
		$this->getAccess();
	}
}
