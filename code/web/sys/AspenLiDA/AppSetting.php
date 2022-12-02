<?php

class AppSetting extends DataObject {
	public $__table = 'aspen_lida_general_settings';
	public $id;
	public $name;
	public $enableAccess;
	public $releaseChannel;

	private $_locations;

	static function getObjectStructure(): array {
		$releaseChannels = [
			0 => 'Beta (Testing)',
			1 => 'Production (Public)',
		];
		$locationList = Location::getLocationList(false);

		$structure = [
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
				'description' => 'The name for these settings',
				'maxLength' => 50,
				'required' => true,
			],
			'enableAccess' => [
				'property' => 'enableAccess',
				'type' => 'checkbox',
				'label' => 'Display location(s) in Aspen LiDA',
				'description' => 'Whether or not the selected locations are available in Aspen LiDA.',
				'default' => true,
			],
			'releaseChannel' => [
				'property' => 'releaseChannel',
				'type' => 'enum',
				'values' => $releaseChannels,
				'label' => 'Release Channel',
				'description' => 'Is the location available in the production or beta/testing app',
			],
			'locations' => [
				'property' => 'locations',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Locations',
				'description' => 'Define locations that use these settings',
				'values' => $locationList,
			],

		];

		return $structure;
	}

	public function __get($name) {
		if ($name == "locations") {
			if (!isset($this->_locations) && $this->id) {
				$this->_locations = [];
				$obj = new Location();
				$obj->lidaGeneralSettingId = $this->id;
				$obj->find();
				while ($obj->fetch()) {
					$this->_locations[$obj->locationId] = $obj->locationId;
				}
			}
			return $this->_locations;
		} else {
			return $this->_data[$name];
		}
	}

	public function __set($name, $value) {
		if ($name == "locations") {
			$this->_locations = $value;
		} else {
			$this->_data[$name] = $value;
		}
	}

	public function update() {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveLocations();
		}
		return true;
	}

	public function insert() {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLocations();
		}
		return $ret;
	}

	public function saveLocations() {
		if (isset ($this->_locations) && is_array($this->_locations)) {
			$locationList = Location::getLocationList(UserAccount::userHasPermission('Administer Aspen LiDA Settings'));
			foreach ($locationList as $locationId => $displayName) {
				$location = new Location();
				$location->locationId = $locationId;
				$location->find(true);
				if (in_array($locationId, $this->_locations)) {
					//We want to apply the scope to this library
					if ($location->lidaGeneralSettingId != $this->id) {
						$location->lidaGeneralSettingId = $this->id;
						$location->update();
					}
				} else {
					//It should not be applied to this scope. Only change if it was applied to the scope
					if ($location->lidaGeneralSettingId == $this->id) {
						$location->lidaGeneralSettingId = -1;
						$location->update();
					}
				}
			}
			unset($this->_locations);
		}
	}

	function getEditLink($context): string {
		return '/AspenLiDA/AppSettings?objectAction=edit&id=' . $this->id;
	}

	static function getDeepLinks(): array {
		return [
			'home' => 'Home Screen',
			'search' => 'Search: Specific term',
			'search/grouped_work' => 'Search: Grouped Work',
			'search/browse_category' => 'Search: Browse Category',
			'search/author' => 'Search: Specific Author',
			'search/list' => 'Search: Public List',
			'user' => 'User: Profile',
			'user/preferences' => 'User: Preferences',
			'user/linked_accounts' => 'User: Linked Accounts',
			'user/library_card' => 'User: Library Card',
			'user/holds' => 'User: Holds',
			'user/checkouts' => 'User: Checkouts',
			'user/saved_searches' => 'User: Saved Searches',
			'user_lists' => 'User: Lists',
		];
	}

	static function getDeepLinkByName($name, $id = null) {
		$scheme = 'aspen-lida';
		$title = 'Unknown';
		$fullPath = '';

		if (strpos($name, 'grouped_work')) {
			require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
			$groupedWork = new GroupedWork();
			$groupedWork->permanent_id = $id;
			if ($groupedWork->find(true)) {
				$title = $groupedWork->full_title;
			}
		} elseif (strpos($name, 'browse_category')) {
			require_once ROOT_DIR . '/sys/Browse/BrowseCategory.php';
			$browseCategory = new BrowseCategory();
			$browseCategory->textId = $id;
			if ($browseCategory->find(true)) {
				$title = $browseCategory->label;
			}
		} elseif (strpos($name, 'list')) {
			require_once ROOT_DIR . '/sys/UserLists/UserList.php';
			$list = new UserList();
			$list->id = $id;
			if ($list->find(true)) {
				$title = $list->title;
			}
		}

		switch ($name) {
			case "search":
			case "search/author":
				$fullPath = $scheme . '://' . $name . '?term=' . $id;
				break;
			case "search/grouped_work":
				$fullPath = $scheme . '://' . $name . '?id=' . $id;
				break;
			case "search/browse_category":
			case "search/list":
				$fullPath = $scheme . '://' . $name . '?id=' . $id . '&title=' . $title;
				break;
			default:
				$fullPath = $scheme . '://' . $name;
		}

		return $fullPath;
	}
}