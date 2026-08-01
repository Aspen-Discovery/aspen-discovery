<?php
/** @noinspection PhpMissingFieldTypeInspection */
require_once ROOT_DIR . '/sys/LibraryLocation/LibraryLinkAccess.php';
require_once ROOT_DIR . '/sys/LibraryLocation/LibraryLinkLanguage.php';

class LibraryLink extends DataObject {
	public $__table = 'library_links';
	public $__displayNameColumn = 'linkText';
	public $id;
	public $libraryId;
	public $category;
	/** @noinspection PhpUnused */
	public $iconName;
	public $linkText;
	public $url;
	public $weight;
	public /** @noinspection PhpUnused */
		$htmlContents;
	public $showToLoggedInUsersOnly;
	/** @noinspection PhpUnused */
	public $showInTopMenu;
	/** @noinspection PhpUnused */
	public $alwaysShowIconInTopMenu;
	public $showExpanded;
	public $published;
	public /** @noinspection PhpUnused */
		$openInNewTab;
	public $showLinkOn;

	private $_allowAccess;
	private $_languages;

	public function getNumericColumnNames(): array {
		return [
			'openInNewTab',
			'published',
			'showExpanded',
			'alwaysShowIconInTopMenu',
			'showInTopMenu',
			'showToLoggedInUsersOnly',
			'weight',
			'showLinkOn'
		];
	}

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context]) && self::$_objectStructure[$context] !== null) {
			return self::$_objectStructure[$context];
		}

			//Load Libraries for lookup values
		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));
		$languageList = Language::getLanguageList();

		$patronTypeList = PType::getPatronTypeList();
		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id of the hours within the database',
			],
			'libraryId' => [
				'property' => 'libraryId',
				'type' => 'enum',
				'values' => $libraryList,
				'label' => 'Library',
				'description' => 'A link to the library which the location belongs to',
			],
			'category' => [
				'property' => 'category',
				'type' => 'text',
				'label' => 'Category',
				'description' => 'The category of the link',
				'size' => '80',
				'maxLength' => 100,
			],
			'iconName' => [
				'property' => 'iconName',
				'type' => 'text',
				'label' => 'FontAwesome Icon Name <small><a href="https://fontawesome.com/v5/search?o=r&m=free&s=solid" target="_blank"><i class="fa fa-info-circle"></i></a></small>',
				'description' => 'Show a font awesome icon next to the menu name',
			],
			'linkText' => [
				'property' => 'linkText',
				'type' => 'text',
				'label' => 'Link Text',
				'description' => 'The text to display for the link ',
				'size' => '80',
				'maxLength' => 100,
			],
			'url' => [
				'property' => 'url',
				'type' => 'text',
				'label' => 'URL',
				'description' => 'The url to link to',
				'size' => '80',
				'maxLength' => 255,
			],
			//'htmlContents' => ['property'=>'htmlContents', 'type'=>'html', 'label'=>'HTML Contents', 'description'=>'Optional full HTML contents to show rather than showing a basic link within the sidebar.',],
			'showInTopMenu' => [
				'property' => 'showInTopMenu',
				'type' => 'checkbox',
				'label' => 'Show In Top Menu <span class="label label-default">Large Screens Only</span>',
				'description' => 'Show the link in the top menu for large screens',
				'default' => 0,
			],
			'alwaysShowIconInTopMenu' => [
				'property' => 'alwaysShowIconInTopMenu',
				'type' => 'checkbox',
				'label' => 'Show Icon In Top Menu <span class="label label-default">All Screen Sizes</span>',
				'description' => 'Always show the icon in the top menu at all screen sizes',
				'default' => 0,
			],
			'showExpanded' => [
				'property' => 'showExpanded',
				'type' => 'checkbox',
				'label' => 'Show Expanded',
				'description' => 'Expand the category by default',
			],
			'openInNewTab' => [
				'property' => 'openInNewTab',
				'type' => 'checkbox',
				'label' => 'Open In New Tab',
				'description' => 'Determine whether or not the link should be opened in a new tab',
				'default' => 1,
			],
			'published' => [
				'property' => 'published',
				'type' => 'checkbox',
				'label' => 'Published',
				'description' => 'The content is published and should be shown to all users',
				'default' => 1,
			],
			'weight' => [
				'property' => 'weight',
				'type' => 'numeric',
				'label' => 'Weight',
				'weight' => 'Defines how items are sorted.  Lower weights are displayed higher.',
				'required' => true,
			],
			'showToLoggedInUsersOnly' => [
				'property' => 'showToLoggedInUsersOnly',
				'type' => 'checkbox',
				'label' => 'Show to logged in users only',
				'description' => 'Show the link only to users that have logged in.',
				'onchange' => 'return AspenDiscovery.Admin.updateLibraryLinksFields();',
				'default' => 0,
			],
			'allowAccess' => [
				'property' => 'allowAccess',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Display only for',
				'description' => 'Define what patron types should see the menu link',
				'values' => $patronTypeList,
				'hideInLists' => true,
			],
			'showLinkOn' => [
				'property' => 'showLinkOn',
				'type' => 'enum',
				'label' => 'Show on',
				'description' => 'Define where this menu link should be shown',
				'values' => [
					0 => 'Aspen Discovery Only',
					1 => 'Aspen LiDA Only',
					2 => 'Aspen Discovery + Aspen LiDA',
				],
				'default' => 0,
			],
			'languages' => [
				'property' => 'languages',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Languages',
				'description' => 'Define languages that use this placard',
				'values' => $languageList,
				'hideInLists' => true,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	public function insert(string $context = '') : int|bool {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			if (empty($this->_allowAccess)) {
				$patronTypeList = PType::getPatronTypeList();
				foreach ($patronTypeList as $pTypeId => $pType) {
					$this->_allowAccess[$pTypeId] = $pTypeId;
				}
			}
			$this->saveAccess();
			//When inserting a library link, if nothing exists, apply to all languages
			if (empty($this->_languages)) {
				$languageList = Language::getLanguageList();
				foreach ($languageList as $languageId => $displayName) {
					$this->_languages[$languageId] = $languageId;
				}
			}
			$this->saveLanguages();
		}
		return $ret;
	}

	public function update(string $context = '') : int|bool {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveAccess();
			$this->saveLanguages();
		}
		return $ret;
	}

	public function __get($name) {
		if ($name == "allowAccess") {
			return $this->getAccess();
		} elseif ($name == 'languages') {
			$this->getLanguages();
			return $this->_languages;
		} else {
			return parent::__get($name);
		}
	}

	public function __set($name, $value) {
		if ($name == "allowAccess") {
			$this->_allowAccess = $value;
		} elseif ($name == 'languages') {
			$this->_languages = $value;
		} else {
			parent::__set($name, $value);
		}
	}

	public function delete(bool $useWhere = false, bool $hardDelete = false) : bool|int {
		$ret = parent::delete($useWhere, $hardDelete);
		if ($ret !== FALSE) {
			$this->clearAccess();

			$libraryLinkLocation = new LibraryLinkLanguage();
			$libraryLinkLocation->libraryLinkId = $this->id;
			$libraryLinkLocation->delete(true);
		}
		return $ret;
	}

	public function getAccess() : array {
		if (!isset($this->_allowAccess)) {
			$this->_allowAccess = [];
			if ($this->id) {
				$patronTypeLink = new LibraryLinkAccess();
				$patronTypeLink->libraryLinkId = $this->id;
				$patronTypeLink->find();
				while ($patronTypeLink->fetch()) {
					$this->_allowAccess[$patronTypeLink->patronTypeId] = $patronTypeLink->patronTypeId;
				}
			}
		}
		return $this->_allowAccess;
	}

	public function saveAccess() : void {
		if (isset($this->_allowAccess) && is_array($this->_allowAccess)) {
			$this->clearAccess();

			foreach ($this->_allowAccess as $patronTypeId) {
				$link = new LibraryLinkAccess();

				$link->libraryLinkId = $this->id;
				$link->patronTypeId = $patronTypeId;
				$link->insert();
			}
			unset($this->_allowAccess);
		}
	}

	private function clearAccess() : void {
		//Delete links to the patron types
		$link = new LibraryLinkAccess();
		$link->libraryLinkId = $this->id;
		$link->delete(true);
	}

	public function getLanguages() : array {
		if (!isset($this->_languages)) {
			$this->_languages = [];
			if ($this->id) {
				try {
					$language = new LibraryLinkLanguage();
					$language->libraryLinkId = $this->id;
					$language->find();
					while ($language->fetch()) {
						$this->_languages[$language->languageId] = $language->languageId;
					}
				} catch (Exception) {
					//This happens when the table is not setup yet
					$languageList = Language::getLanguageList();
					foreach ($languageList as $languageId => $displayName) {
						$this->_languages[$languageId] = $languageId;
					}
				}
			}
		}
		return $this->_languages;
	}

	public function saveLanguages() : void {
		if (isset ($this->_languages) && is_array($this->_languages)) {
			$languageList = Language::getLanguageList();
			foreach ($languageList as $languageId => $displayName) {
				$obj = new LibraryLinkLanguage();
				$obj->libraryLinkId = $this->id;
				$obj->languageId = $languageId;
				if (in_array($languageId, $this->_languages)) {
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

	public function isValidForDisplay() : bool {
		if ($this->showToLoggedInUsersOnly && !UserAccount::isLoggedIn()) {
			return false;
		}
		if (!$this->published && !UserAccount::userHasPermission('View Unpublished Content')) {
			return false;
		}
		if($this->showLinkOn == 1) {
			return false;
		}
		//Check to see if the library link is valid based on the language
		global $activeLanguage;
		if ($activeLanguage != null) {
			$validLanguages = $this->getLanguages();
			if (!in_array($activeLanguage->id, $validLanguages)) {
				return false;
			}
		}
		if ($this->showToLoggedInUsersOnly) {
			if (UserAccount::isLoggedIn()) {
				$user = UserAccount::getLoggedInUser();
				$userPatronType = $user->patronType;
				$userId = $user->id;
				require_once ROOT_DIR . '/sys/Account/PType.php';
				$patronType = new pType();
				$patronType->pType = $userPatronType;
				if ($patronType->find(true)) {
					$patronTypeId = $patronType->id;
					try {
						require_once ROOT_DIR . '/sys/LibraryLocation/LibraryLinkAccess.php';
						$patronTypeLink = new LibraryLinkAccess();
						$patronTypeLink->libraryLinkId = $this->id;
						$patronTypeLink->patronTypeId = $patronTypeId;
						if ((!$patronTypeLink->find(true)) && $userId != 1) {
							return false;
						} else {
							return true;
						}
					} catch (Exception) {
						//This happens before the table has been defined, ignore it
						return true;
					}
				} else {
					return false;
				}
			} else {
				return false;
			}
		} else {
			return true;
		}
	}

	public function isValidForDisplayForApp(User $user)  : bool {
		if($this->showLinkOn == 0) {
			return false;
		}
		//Check to see if the library link is valid based on the language
		global $activeLanguage;
		$validLanguages = $this->getLanguages();
		if (!in_array($activeLanguage->id, $validLanguages)) {
			return false;
		}

		if (!$this->published) {
			return false;
		}

		if ($this->showToLoggedInUsersOnly) {
			$userPatronType = $user->patronType;
			$userId = $user->id;
			require_once ROOT_DIR . '/sys/Account/PType.php';
			$patronType = new pType();
			$patronType->pType = $userPatronType;
			if ($patronType->find(true)) {
				$patronTypeId = $patronType->id;
				try {
					require_once ROOT_DIR . '/sys/LibraryLocation/LibraryLinkAccess.php';
					$patronTypeLink = new LibraryLinkAccess();
					$patronTypeLink->libraryLinkId = $this->id;
					$patronTypeLink->patronTypeId = $patronTypeId;
					if ((!$patronTypeLink->find(true)) && $userId != 1) {
						return false;
					} else {
						return true;
					}
				} catch (Exception) {
					//This happens before the table has been defined, ignore it
					return true;
				}
			} else {
				return false;
			}
		} else {
			return true;
		}
	}

	/** @noinspection PhpUnusedParameterInspection */
	public function getEditLink(string $context): string {
		return '/Admin/LibraryLinks?objectAction=edit&id=' . $this->id;
	}

	/** @noinspection PhpUnused */
	function getEscapedCategory() : string {
		return preg_replace('/\W/', '_', strtolower($this->category));
	}
}