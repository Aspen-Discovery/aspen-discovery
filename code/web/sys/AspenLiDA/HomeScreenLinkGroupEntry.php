<?php /** @noinspection PhpMissingFieldTypeInspection */

require_once ROOT_DIR . '/sys/AspenLiDA/HomeScreenLinkGroup.php';

class HomeScreenLinkGroupEntry extends DataObject {
	public $__table = 'aspen_lida_home_screen_link_group_entry';
	public $id;
	public $weight;
	public $homeScreenLinkGroupId;
	public $homeScreenLinkId;

	function getUniquenessFields(): array {
		return [
			'homeScreenLinkGroupId',
			'homeScreenLinkId',
		];
	}

	static $_objectStructure = [];

	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context]) && self::$_objectStructure[$context] !== null) {
			return self::$_objectStructure[$context];
		}
		//Load Groups for lookup values
		$groups = new HomeScreenLinkGroup();
		$groups->orderBy('name');
		$groups->find();
		$groupList = [];
		while ($groups->fetch()) {
			$groupList[$groups->id] = $groups->name;
		}
		require_once ROOT_DIR . '/sys/AspenLiDA/HomeScreenLink.php';
		$homeScreenLinks = new HomeScreenLink();
		$homeScreenLinks->orderBy('title');
		$homeScreenLinksList = [];
		if (!UserAccount::userHasPermission('Administer All Aspen LiDA Home Screen Links')) {
			$validLibraries = Library::getLibraryList(true);
			$libraryIds = empty($validLibraries) ? [-1] : array_keys($validLibraries);
			$homeScreenLinks->whereAdd("sharing = 'everyone'");
			$homeScreenLinks->whereAdd("sharing = 'library' AND libraryId IN (" . implode(',' ,$libraryIds) . ')', 'OR');
			$homeScreenLinks->find();
			while ($homeScreenLinks->fetch()) {
				$homeScreenLinksList[$homeScreenLinks->id] = $homeScreenLinks->title . " ($homeScreenLinks->textId)" . " - $homeScreenLinks->id";
			}
		} elseif (UserAccount::userHasPermission('Administer All Aspen LiDA Home Screen Links')) {
			$homeScreenLinks->find();
			while ($homeScreenLinks->fetch()) {
				$homeScreenLinksList[$homeScreenLinks->id] = $homeScreenLinks->title . " ($homeScreenLinks->textId)" . " - $homeScreenLinks->id";
			}
		}
		$homeScreenLinks = new HomeScreenLink();
		$homeScreenLinks->orderBy('title');
		$homeScreenLinks->find();
		$allHomeScreenLinksList = [];
		while ($homeScreenLinks->fetch()) {
			$allHomeScreenLinksList[$homeScreenLinks->id] = $homeScreenLinks->title . " ($homeScreenLinks->textId)" . " - $homeScreenLinks->id";
		}
		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id of the hours within the database',
			],
			'homeScreenLinkGroupId' => [
				'property' => 'homeScreenLinkGroupId',
				'type' => 'enum',
				'values' => $groupList,
				'label' => 'Group',
				'description' => 'The group the home screen link should be added in',
			],
			'homeScreenLinkId' => [
				'property' => 'homeScreenLinkId',
				'type' => 'enum',
				'values' => $homeScreenLinksList,
				'allValues' => $allHomeScreenLinksList,
				'label' => 'Home Screen Link',
				'description' => 'The home screen link to display ',
			],
			'weight' => [
				'property' => 'weight',
				'type' => 'numeric',
				'label' => 'Weight',
				'weight' => 'Defines how lists are sorted within the group.  Lower weights are displayed to the left of the screen.',
				'required' => true,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	protected $_homeScreenLink = null;

	function getHomeScreenLink(): HomeScreenLink|false {
		if ($this->_homeScreenLink == null) {
			require_once ROOT_DIR . '/sys/AspenLiDA/HomeScreenLink.php';
			$this->_homeScreenLink = new HomeScreenLink();
			$this->_homeScreenLink->id = $this->homeScreenLinkId;
			if (!$this->_homeScreenLink->find(true)) {
				$this->_homeScreenLink = false;
			}
		}

		return $this->_homeScreenLink;
	}

	/** @noinspection PhpUnusedParameterInspection */
	public function getEditLink(string $context): string {
		return '/AspenLiDA/HomeScreenLink?objectAction=edit&id=' . $this->homeScreenLinkId;
	}

	public function canActiveUserChangeSelection(): bool {
		$validLibraries = Library::getLibraryList(true);
		$libraryIds = empty($validLibraries) ? [-1] : array_keys($validLibraries);

		$linkId = $this->getHomeScreenLink()->libraryId;
		if (($this->getHomeScreenLink()->sharing == 'everyone') || (UserAccount::userHasPermission('Administer All Aspen LiDA Home Screen Links'))) {
			return true;
		} else if (in_array($linkId, $libraryIds)) {
			return UserAccount::userHasPermission('Administer Library Aspen LiDA Home Screen Links');
		}
		return false;
	}

	public function canActiveUserDelete(): bool {
		return UserAccount::userHasPermission('Administer All Aspen LiDA Home Screen Links') || UserAccount::userHasPermission('Administer Library Aspen LiDA Home Screen Links');
	}

	public function canActiveUserEdit(): bool {
		$validLibraries = Library::getLibraryList(true);
		$libraryIds = empty($validLibraries) ? [-1] : array_keys($validLibraries);
		$linkId = $this->getHomeScreenLink()->libraryId;
		if (($this->getHomeScreenLink()->sharing == 'everyone') || (UserAccount::userHasPermission('Administer All Aspen LiDA Home Screen Links'))) {
			return true;
		} elseif (in_array($linkId, $libraryIds)) {
			return UserAccount::userHasPermission('Administer Library Aspen LiDA Home Screen Links');
		}
		return false;
	}

	public function toArray($includeRuntimeProperties = true, $encryptFields = false): array {
		$return = parent::toArray($includeRuntimeProperties, $encryptFields);
		unset($return['homeScreenLinkGroupId']);
		unset($return['homeScreenLinkId']);
		return $return;
	}

	public function getLinksForJSON(): array {
		$links = parent::getLinksForJSON();
		$link = $this->getHomeScreenLink();
		$linksArray = $link->toArray();
		$linksArray['links'] = $link->getLinksForJSON();
		$links['homeScreenLinks'] = $linksArray;
		return $links;
	}

	public function loadEmbeddedLinksFromJSON($jsonData, $mappings, string $overrideExisting = 'keepExisting'): void {
		parent::loadRelatedLinksFromJSON($jsonData, $mappings, $overrideExisting);
		if (array_key_exists('homeScreenLinks', $jsonData)) {
			require_once ROOT_DIR . '/sys/AspenLiDA/HomeScreenLink.php';
			$homeScreenLink = new HomeScreenLink();
			$homeScreenLink->loadFromJSON($jsonData['homeScreenLinks'], $mappings, $overrideExisting);
			$this->homeScreenLinkId = $homeScreenLink->id;
		}
	}
}