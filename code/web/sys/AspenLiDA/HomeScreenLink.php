<?php /** @noinspection PhpMissingFieldTypeInspection */

require_once ROOT_DIR . '/sys/AspenLiDA/HomeScreenLinkGroup.php';
require_once ROOT_DIR . '/sys/AspenLiDA/LocationSetting.php';

class HomeScreenLink extends DataObject {
	public $__table = 'aspen_lida_home_screen_link';
	public $id;
	public $title;
	public $textId;

	public $userId;
	public $sharing;
	public $libraryId;

	public $typeOfIcon;
	public $materialIcon;
	public $uploadIcon;
	public $linkType;
	public $deepLinkPath;
	public $deepLinkId;
	public $linkUrl;

	function getNumericColumnNames(): array {
		return [
			'id',
			'userId',
		];
	}

	function getUniquenessFields(): array {
		return ['textId'];
	}

	static $_objectStructure = [];

	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context]) && self::$_objectStructure[$context] !== null) {
			return self::$_objectStructure[$context];
		}

		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Aspen LiDA Home Screen Links'));
		$libraryList[-1] = 'No Library Selected';

		$deepLinks = LocationSetting::getDeepLinks();
		unset($deepLinks['home']);

		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'title' => [
				'property' => 'title',
				'type' => 'text',
				'label' => 'Title',
				'description' => 'The title to display for the home screen link',
				'maxLength' => 100,
				'required' => true,
			],
			'textId' => [
				'property' => 'textId',
				'type' => 'text',
				'label' => 'textId',
				'description' => 'A textual id to identify the home screen link',
				'serverValidation' => 'validateTextId',
				'maxLength' => 150,
			],
			'userId' => [
				'property' => 'userId',
				'type' => 'label',
				'label' => 'userId',
				'description' => 'The User Id who created this home screen link',
				'default' => UserAccount::getActiveUserId(),
			],
			'sharing' => [
				'property' => 'sharing',
				'type' => 'enum',
				'values' => [
					'library' => 'Selected Library',
					'everyone' => 'Everyone',
				],
				'label' => 'Share With',
				'description' => 'Who the category should be shared with',
				'default' => 'library',
				'onchange' => 'return AspenDiscovery.Admin.updateBrowseCategoryFields();',
			],
			'libraryId' => [
				'property' => 'libraryId',
				'type' => 'enum',
				'values' => $libraryList,
				'label' => 'Library',
				'description' => 'A link to the library which the location belongs to',
			],
			'typeOfIcon' => [
				'property' => 'typeOfIcon',
				'type' => 'enum',
				'values' => [
					'uploadIcon' => 'Image Upload',
					'materialIcon' => 'Material Icon',
				],
				'label' => 'Type of Icon',
				'description' => 'The type of icon to represent the link',
				'default' => 'none',
				'onchange' => 'return AspenDiscovery.Admin.toggleHomeScreenIconTypeFields();',
			],
			'materialIcon' => [
				'property' => 'materialIcon',
				'type' => 'text',
				'label' => 'Google Material Icon Name <small><a href="https://fonts.google.com/icons?icon.set=Material+Icons&icon.style=Filled&icon.size=24&icon.color=%23cdd6f4" target="_blank"><i class="fa fa-info-circle"></i></a></small>',
				'description' => 'A Google Material Icon to represent the link',
				'note' => 'Use the "icon name", all lowercase. No spaces allowed, use underscores for multi-word icons (ex: "account_circle")',
				'hideInLists' => true,
			],
			'uploadIcon' => [
				'property' => 'uploadIcon',
				'type' => 'image',
				'label' => 'Upload an Icon',
				'description' => 'An uploaded icon to represent the link',
				'note' => 'Recommended size is 52x52 pixels',
				'maxWidth' => 52,
				'maxHeight' => 52,
				'thumbWidth' => 52,
				'hideInLists' => true,
			],
			'linkType' => [
				'property' => 'linkType',
				'type' => 'enum',
				'label' => 'On tap, send user to',
				'values' => [
					"deepLink" => 'A specific screen in the app',
					"externalLink" => 'An external website',
				],
				'default' => 0,
				'onchange' => 'return AspenDiscovery.Admin.getUrlOptions();',
				'hideInLists' => true,
				'canSort' => false,
			],
			'deepLinkPath' => [
				'property' => 'deepLinkPath',
				'type' => 'enum',
				'label' => 'Aspen LiDA Screen',
				'values' => $deepLinks,
				'default' => 'home',
				'onchange' => 'return AspenDiscovery.Admin.getDeepLinkFullPath();',
				'hideInLists' => true,
				'canSort' => false,
			],
			'deepLinkId' => [
				'property' => 'deepLinkId',
				'type' => 'text',
				'label' => 'Id for Object',
				'hideInLists' => true,
				'canSort' => false,
			],
			'linkUrl' => [
				'property' => 'linkUrl',
				'type' => 'url',
				'label' => 'External URL',
				'description' => 'A URL for users to be redirected to when opening the home screen link',
				'hideInLists' => true,
				'canSort' => false,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	/** @noinspection PhpUnusedParameterInspection */
	public function getEditLink(string $context): string {
		return '/AspenLiDA/HomeScreenLinks?objectAction=edit&id=' . $this->id;
	}

	function validateTextId(): array {
		$validationResults = [
			'validatedOk' => true,
			'errors' => [],
		];

		if (!$this->textId || strlen($this->textId) == 0) {
			$this->textId = $this->title . ' ' . $this->sharing;
			if ($this->sharing == 'private') {
				$this->textId .= '_' . $this->userId;
			} elseif ($this->sharing == 'location') {
				$location = Location::getUserHomeLocation();
				$this->textId .= '_' . $location->code;
			} elseif ($this->sharing == 'library') {
				if (Library::getPatronHomeLibrary()) {
					$this->textId .= '_' . Library::getPatronHomeLibrary()->subdomain;
				} else {
					global $library;
					$this->textId .= '_' . $library->subdomain;
				}
			}
		}

		$this->textId = strtolower($this->textId);
		// Convert any non-word characters to an underscore.
		$this->textId = preg_replace('/\W/', '_', $this->textId);
		// Ensure the length is 150 or fewer characters.
		if (strlen($this->textId) > 150) {
			$this->textId = substr($this->textId, 0, 150);
		}

		return $validationResults;
	}

	public function delete(bool $useWhere = false, bool $hardDelete = false): bool|int {
		$ret = parent::delete($useWhere, $hardDelete);
		if ($ret && !empty($this->textId)) {
			//Remove from any groups that use it.
			require_once ROOT_DIR . '/sys/AspenLiDA/HomeScreenLinkGroupEntry.php';
			$homeScreenLinkGroup = new HomeScreenLinkGroupEntry();
			$homeScreenLinkGroup->homeScreenLinkId = $this->id;
			$homeScreenLinkGroup->delete(true);
		}
		return $ret;
	}

	public function canActiveUserEdit(): bool {
		if ($this->sharing == 'everyone') {
			return UserAccount::userHasPermission('Administer All Aspen LiDA Home Screen Links') || ($this->userId == UserAccount::getActiveUserId());
		}
		if (UserAccount::userHasPermission('Administer Selected Aspen LiDA Home Screen Link Groups')) {
			//only allow editing the ones the user created
			return $this->userId == UserAccount::getActiveUserId();
		} else {
			//Don't need to limit for the library since the user will need Administer Library Aspen LiDA Home Screen Links to even view them.
			return true;
		}
	}

	public function toArray($includeRuntimeProperties = true, $encryptFields = false): array {
		$return = parent::toArray($includeRuntimeProperties, $encryptFields);
		unset ($return['libraryId']);
		unset ($return['userId']);

		return $return;
	}

	public function getLinksForJSON(): array {
		$links = parent::getLinksForJSON();
		//library
		$allLibraries = Library::getLibraryListAsObjects(false);
		if (array_key_exists($this->libraryId, $allLibraries)) {
			$library = $allLibraries[$this->libraryId];
			$links['library'] = empty($library->subdomain) ? $library->ilsCode : $library->subdomain;
		}
		//user
		$user = new User();
		$user->id = $this->userId;
		if ($user->find(true)) {
			$links['user'] = $user->ils_barcode;
		}

		return $links;
	}

	public function loadEmbeddedLinksFromJSON($jsonData, $mappings, string $overrideExisting = 'keepExisting'): void {
		parent::loadEmbeddedLinksFromJSON($jsonData, $mappings, $overrideExisting);

		if (isset($jsonData['library'])) {
			$allLibraries = Library::getLibraryListAsObjects(false);
			$subdomain = $jsonData['library'];
			if (array_key_exists($subdomain, $mappings['libraries'])) {
				$subdomain = $mappings['libraries'][$subdomain];
			}
			foreach ($allLibraries as $tmpLibrary) {
				if ($tmpLibrary->subdomain == $subdomain || $tmpLibrary->ilsCode == $subdomain) {
					$this->libraryId = $tmpLibrary->libraryId;
					break;
				}
			}
		}
		if (isset($jsonData['user'])) {
			$username = $jsonData['user'];
			$user = new User();
			$user->ils_barcode = $username;
			if ($user->find(true)) {
				$this->userId = $user->id;
			}
		}
	}

	public function loadRelatedLinksFromJSON($jsonData, $mappings, string $overrideExisting = 'keepExisting'): bool {
		$result = parent::loadRelatedLinksFromJSON($jsonData, $mappings, $overrideExisting);
		return $result;
	}

}
