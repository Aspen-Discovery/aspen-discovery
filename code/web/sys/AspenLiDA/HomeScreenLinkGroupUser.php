<?php
/** @noinspection PhpMissingFieldTypeInspection */

require_once ROOT_DIR . '/sys/AspenLiDA/HomeScreenLinkGroup.php';

class HomeScreenLinkGroupUser extends DataObject {
	public $__table = 'aspen_lida_home_screen_link_group_users';
	public $id;
	public $homeScreenLinkGroupId;
	public $userId;

	function getUniquenessFields(): array {
		return [
			'homeScreenLinkGroupId',
			'userId',
		];
	}

	static $_objectStructure = [];

	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context]) && self::$_objectStructure[$context] !== null) {
			return self::$_objectStructure[$context];
		}
		//Get a list of users that have permissions to edit home screen link groups
		$groups = new HomeScreenLinkGroup();
		$groups->orderBy('name');
		$groups->find();
		$groupList = [];
		while ($groups->fetch()) {
			$groupList[$groups->id] = $groups->name;
		}

		$userIdList = [];
		//Get a list of all users who can administer selected home screen link groups
		$permission = new Permission();
		$permission->name = 'Administer Selected Aspen LiDA Home Screen Link Groups';
		if ($permission->find(true)) {
			$permissionId = $permission->id;
			require_once ROOT_DIR . '/sys/Administration/RolePermissions.php';
			$rolePermissions = new RolePermissions();
			$rolePermissions->permissionId = $permissionId;
			$roleIds = $rolePermissions->fetchAll('roleId');

			if (count($roleIds) > 0) {
				require_once ROOT_DIR . '/sys/Administration/UserRoles.php';
				$usersToRole = new UserRoles();
				$usersToRole->whereAddIn('roleId', $roleIds, false);
				$userIds = $usersToRole->fetchAll('userId');

				if (count($userIds) > 0) {
					$user = new User;
					$user->whereAddIn('id', $userIds, false);
					$user->find();
					while ($user->fetch()) {
						$userIdList[$user->id] = "$user->displayName (" . $user->getBarcode() . ")";
					}
				}
			}
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
				'description' => 'The group the user can edit',
			],
			'userId' => [
				'property' => 'userId',
				'type' => 'enum',
				'values' => $userIdList,
				'allValues' => $userIdList,
				'label' => 'User',
				'description' => 'The User who can edit the home screen link group ',
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	public function canActiveUserChangeSelection(): bool {
		return UserAccount::userHasPermission('Administer All Aspen LiDA Home Screen Links');
	}

	public function canActiveUserDelete(): bool {
		return UserAccount::userHasPermission('Administer All Aspen LiDA Home Screen Links');
	}

	public function canActiveUserEdit(): bool {
		return UserAccount::userHasPermission('Administer All Aspen LiDA Home Screen Links');
	}

	public function toArray($includeRuntimeProperties = true, $encryptFields = false): array {
		//Unset ids for group and home screen link since they will be set by links
		$return = parent::toArray($includeRuntimeProperties, $encryptFields);
		unset($return['homeScreenLinkGroupId']);
		unset($return['userId']);
		return $return;
	}

	private $_userDisplayName = null;

	/** @noinspection PhpUnused */
	public function getUserDisplayName(): string {
		if ($this->_userDisplayName == null) {
			$user = new User;
			$user->id = $this->userId;
			if ($user->find(true)) {
				$this->_userDisplayName = "$user->displayName (" . $user->getBarcode() . ")";
			} else {
				$this->_userDisplayName = 'Unknown';
			}
		}
		return $this->_userDisplayName;
	}
}