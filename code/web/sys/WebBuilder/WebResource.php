<?php
require_once ROOT_DIR . '/sys/WebBuilder/LibraryWebResource.php';
require_once ROOT_DIR . '/sys/WebBuilder/WebBuilderAudience.php';
require_once ROOT_DIR . '/sys/WebBuilder/WebBuilderCategory.php';
require_once ROOT_DIR . '/sys/WebBuilder/WebResourceAudience.php';
require_once ROOT_DIR . '/sys/WebBuilder/WebResourceCategory.php';
require_once ROOT_DIR . '/sys/WebBuilder/WebResourceAccessLibrary.php';

class WebResource extends DB_LibraryLinkedObject {
	public $__table = 'web_builder_resource';
	public $id;
	public $name;
	public $logo;
	public $url;
	public $openInNewTab;
	public /** @noinspection PhpUnused */
		$featured;
	public /** @noinspection PhpUnused */
		$requiresLibraryCard;
	public /** @noinspection PhpUnused */
		$inLibraryUseOnly;
	public $requireLoginUnlessInLibrary;
	public /** @noinspection PhpUnused */
		$teaser;
	public $description;
	public $lastUpdate;
	public $generatePlacard;

	protected $_allowAccessByLibrary;

	protected $_audiences;
	protected $_categories;

	protected $_libraries;

	public function getNumericColumnNames(): array {
		return [
			'id',
			'openInNewTab',
			'featured',
			'requiresLibraryCard',
			'inLibraryUseOnly',
			'lastUpdate',
		];
	}

	static function getObjectStructure($context = ''): array {
		$libraryList = Library::getLibraryListWithWebBuilderStatus(!UserAccount::userHasPermission('Administer All Web Resources'));
		$audiencesList = WebBuilderAudience::getAudiences();
		$categoriesList = WebBuilderCategory::getCategories();
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
				'label' => 'Name',
				'description' => 'The name of the resource',
				'size' => '40',
				'maxLength' => 100,
			],
			'url' => [
				'property' => 'url',
				'type' => 'url',
				'label' => 'URL',
				'description' => 'The url of the resource',
				'size' => '40',
				'maxLength' => 255,
				'required' => true
			],
			'openInNewTab' => [
				'property' => 'openInNewTab',
				'type' => 'checkbox',
				'label' => 'Open In New Tab',
				'description' => 'Whether or not the link should open in a new tab',
				'default' => false,
				'hideInLists' => true,
			],
			'logo' => [
				'property' => 'logo',
				'type' => 'image',
				'label' => 'Logo',
				'description' => 'An image to display for the resource',
				'thumbWidth' => 200,
				'hideInLists' => true,
			],
			'featured' => [
				'property' => 'featured',
				'type' => 'checkbox',
				'label' => 'Featured?',
				'description' => 'Whether or not the resource is a featured resource',
				'default' => 0,
			],
			'generatePlacard' => [
				'property' => 'generatePlacard',
				'type' => 'checkbox',
				'label' => 'Generate Placard?',
				'description' => 'Whether or not the resource has an automatically generated placard which can be edited in Local Catalog Enrichment > Placards.',
				'default' => 0,
			],
			'teaser' => [
				'property' => 'teaser',
				'type' => 'markdown',
				'label' => 'Teaser',
				'description' => 'A short description of the resource to show in lists',
				'hideInLists' => true,
				'maxLength' => 512
			],
			'description' => [
				'property' => 'description',
				'type' => 'markdown',
				'label' => 'Description',
				'description' => 'A description of the resource',
				'hideInLists' => true,
			],
			'inLibraryUseOnly' => [
				'property' => 'inLibraryUseOnly',
				'type' => 'checkbox',
				'label' => 'In Library Use Only?',
				'description' => 'Whether or not the resource can only be used in the library',
				'default' => 0,
				'hideInLists' => true,
			],
			'requiresLibraryCard' => [
				'property' => 'requiresLibraryCard',
				'type' => 'checkbox',
				'label' => 'Requires Library Card?',
				'description' => 'Whether or not the resource requires a library card to use it',
				'default' => 0,
				'hideInLists' => true,
			],
			'requireLoginUnlessInLibrary' => [
				'property' => 'requireLoginUnlessInLibrary',
				'type' => 'checkbox',
				'label' => 'Requires being logged in to access, unless in library',
				'description' => 'Whether or not the resource requires patron to be logged in to use it unless they are in the library',
				'default' => 0,
				'hideInLists' => true,
				'onchange' => 'return AspenDiscovery.WebBuilder.updateWebResourcesFields();',
			],
			'allowAccessByLibrary' => [
				'property' => 'allowAccessByLibrary',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Allow Access to patrons of these home libraries',
				'description' => 'Define what libraries should have access to the web resource',
				'values' => $libraryList,
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

	/** @noinspection PhpUnused */
	public function getFormattedDescription() {
		require_once ROOT_DIR . '/sys/Parsedown/AspenParsedown.php';
		$parsedown = AspenParsedown::instance();
		require_once ROOT_DIR . '/sys/SystemVariables.php';
		$parsedown->setBreaksEnabled(true);
		return $parsedown->parse($this->description);
	}

	public function insert($context = '') {
		$this->lastUpdate = time();
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveAudiences();
			$this->saveCategories();
			$this->saveAllowableLibraries();
			if ($this->generatePlacard) {
				$this->generatePlacard();
			}
		}
		return $ret;
	}

	public function update($context = '') {
		$this->lastUpdate = time();
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveAudiences();
			$this->saveCategories();
			$this->saveAllowableLibraries();
			if ($this->generatePlacard) {
				$this->generatePlacard();
			}
		}
		return $ret;
	}

	public function __get($name) {
		if ($name == "libraries") {
			return $this->getLibraries();
		} elseif ($name == "audiences") {
			return $this->getAudiences();
		} elseif ($name == "categories") {
			return $this->getCategories();
		} elseif ($name == 'allowAccessByLibrary') {
			return $this->getAllowableLibraries();
		} else {
			return parent::__get($name);
		}
	}

	public function __set($name, $value) {
		if ($name == "libraries") {
			$this->_libraries = $value;
		} elseif ($name == "audiences") {
			$this->_audiences = $value;
		} elseif ($name == "categories") {
			$this->_categories = $value;
		} elseif ($name == 'allowAccessByLibrary') {
			$this->_allowAccessByLibrary = $value;
		} else {
			parent::__set($name, $value);
		}
	}

	public function delete($useWhere = false) : int {
		$ret = parent::delete($useWhere);
		if ($ret && !empty($this->id)) {
			$this->clearLibraries();
			$this->clearAudiences();
			$this->clearCategories();
			$this->clearAllowableLibraries();
		}
		return $ret;
	}

	public function getLibraries(): ?array {
		if (!isset($this->_libraries) && $this->id) {
			$this->_libraries = [];
			$libraryLink = new LibraryWebResource();
			$libraryLink->webResourceId = $this->id;
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
			$audienceLink = new WebResourceAudience();
			$audienceLink->webResourceId = $this->id;
			$audienceLink->find();
			while ($audienceLink->fetch()) {
				$audience = $audienceLink->getAudience();
				if ($audience != false) {
					$this->_audiences[$audienceLink->audienceId] = $audience;
				}
			}
			$sorter = function (WebBuilderAudience $a, WebBuilderAudience $b) {
				return strcasecmp($a->name, $b->name);
			};
			uasort($this->_audiences, $sorter);
		}
		return $this->_audiences;
	}

	/**
	 * @return WebBuilderCategory[];
	 */
	public function getCategories() {
		if (!isset($this->_categories) && $this->id) {
			$this->_categories = [];
			$categoryLink = new WebResourceCategory();
			$categoryLink->webResourceId = $this->id;
			$categoryLink->find();
			while ($categoryLink->fetch()) {
				$category = $categoryLink->getCategory();
				if ($category != false) {
					$this->_categories[$categoryLink->categoryId] = $category;
				}
			}
			$sorter = function (WebBuilderCategory $a, WebBuilderCategory $b) {
				return strcasecmp($a->name, $b->name);
			};
			uasort($this->_categories, $sorter);
		}
		return $this->_categories;
	}

	public function saveLibraries() {
		if (isset($this->_libraries) && is_array($this->_libraries)) {
			$this->clearLibraries();

			foreach ($this->_libraries as $libraryId) {
				$libraryLink = new LibraryWebResource();

				$libraryLink->webResourceId = $this->id;
				$libraryLink->libraryId = $libraryId;
				$libraryLink->insert();
			}
			unset($this->_libraries);
		}
	}

	public function saveAudiences() {
		if (isset($this->_audiences) && is_array($this->_audiences)) {
			$this->clearAudiences();

			foreach ($this->_audiences as $audienceId) {
				$link = new WebResourceAudience();

				$link->webResourceId = $this->id;
				$link->audienceId = $audienceId;
				$link->insert();
			}
			unset($this->_audiences);
		}
	}

	public function saveCategories() {
		if (isset($this->_categories) && is_array($this->_categories)) {
			$this->clearCategories();

			foreach ($this->_categories as $categoryId) {
				$link = new WebResourceCategory();

				$link->webResourceId = $this->id;
				$link->categoryId = $categoryId;
				$link->insert();
			}
			unset($this->_categories);
		}
	}

	private function clearLibraries() {
		//Delete links to the libraries
		$libraryLink = new LibraryWebResource();
		$libraryLink->webResourceId = $this->id;
		return $libraryLink->delete(true);
	}

	private function clearAudiences() {
		//Delete links to the libraries
		$link = new WebResourceAudience();
		$link->webResourceId = $this->id;
		return $link->delete(true);
	}

	private function clearCategories() {
		//Delete links to the libraries
		$link = new WebResourceCategory();
		$link->webResourceId = $this->id;
		return $link->delete(true);
	}

	public function getLinksForJSON(): array {
		$links = parent::getLinksForJSON();
		//Audiences
		$audiencesList = WebBuilderAudience::getAudiences();
		$audiences = $this->getAudiences();
		$links['audiences'] = [];
		foreach ($audiences as $audience => $audienceObject) {
			$links['audiences'][] = $audiencesList[$audience];
		}
		//Categories
		$categoriesList = WebBuilderCategory::getCategories();
		$categories = $this->getCategories();
		$links['categories'] = [];
		foreach ($categories as $category => $categoryObject) {
			$links['categories'][] = $categoriesList[$category];
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
		return $result;
	}

	public function loadCopyableSubObjects() {
		$this->getCategories();
		$index = -1;
		foreach ($this->_categories as $subObject) {
			$subObject->id = $index;
			$index--;
		}
		$this->getAudiences();
		$index = -1;
		foreach ($this->_audiences as $subObject) {
			$subObject->id = $index;
			$index--;
		}
	}

	public function canView(): bool {
		global $locationSingleton;
		$requireLoginUnlessInLibrary = $this->requireLoginUnlessInLibrary;
		$librariesWithAccess = $this->getAllowableLibraries();
		if($requireLoginUnlessInLibrary) {
			$activeLibrary = $locationSingleton->getActiveLocation();
			$user = UserAccount::getLoggedInUser();
			if($user) {
				if($activeLibrary != null) {
					$activeLibraryId = $activeLibrary->libraryId;
					if (in_array($activeLibraryId, $librariesWithAccess)) {
						return true;
					}
				}
				$userHomeLibrary = $user->getHomeLibrary();
				if($userHomeLibrary) {
					$userHomeLibraryId = $userHomeLibrary->libraryId;
					if (in_array($userHomeLibraryId, $librariesWithAccess)) {
						return true;
					}
				}
			} else {
				return false;
			}
		} else {
			return true;
		}
		return false;
	}

	public function getAllowableLibraries() {
		if (!isset($this->_allowAccessByLibrary) && $this->id) {
			$this->_allowAccessByLibrary = [];
			$libraryAccess = new WebResourceAccessLibrary();
			$libraryAccess->webResourceId = $this->id;
			$libraryAccess->find();
			while ($libraryAccess->fetch()) {
				$this->_allowAccessByLibrary[$libraryAccess->libraryId] = $libraryAccess->libraryId;
			}
		}
		return $this->_allowAccessByLibrary;
	}

	private function clearAllowableLibraries() {
		$link = new WebResourceAccessLibrary();
		$link->webResourceId = $this->id;
		return $link->delete(true);
	}


	public function saveAllowableLibraries() {
		if (isset($this->_allowAccessByLibrary) && is_array($this->_allowAccessByLibrary)) {
			$this->clearAllowableLibraries();

			foreach ($this->_allowAccessByLibrary as $libraryId) {
				$link = new WebResourceAccessLibrary();

				$link->webResourceId = $this->id;
				$link->libraryId = $libraryId;
				$link->insert();
			}
			unset($this->_allowAccessByLibrary);
		}
	}

	public function generatePlacard() {
		require_once ROOT_DIR . '/sys/LocalEnrichment/Placard.php';
		//check if placard already exists
		$placard = new Placard();
		$placard->sourceType = 'web_resource';
		$placard->sourceId = $this->id;
		if (!$placard->find(true)){//if placard exists don't update (user will be prompted separately)
			$fileType = substr($this->logo, -3);
			$fileType = match ($fileType) {
				'gif' => ".gif",
				'png' => ".png",
				'svg' => ".svg",
				default => ".jpg",
			};
			$placard->sourceType = 'web_resource';
			$placard->sourceId = $this->id;
			$placard->title = $this->name;
			$placard->image = "web_resource_image_".$this->id.$fileType;
			$placard->link = $this->url;
			$placard->body = empty($this->teaser) ? $this->description : $this->teaser;
			$placard->generatedFromSource = 'web_resource:' . $this->id;
			$placard->insert();
		}
	}
}
