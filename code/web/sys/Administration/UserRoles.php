<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';
class UserRoles extends DataObject
{

	public $__table = 'user_roles';// table name
    public $id;
	public $userId; // int(11)
	public $roleId; // int(11)

	public function getUniquenessFields(): array
	{
		return ['userId', 'roleId'];
	}

	public function okToExport(array $selectedFilters) : bool{
		return true;
	}

	public function getNumericColumnNames(): array
	{
		return ['userId', 'roleId'];
	}

	public function toArray($includeRuntimeProperties = true, $encryptFields = false): array
	{
		return [];
	}

	public function getLinksForJSON(): array
	{
		$links = parent::getLinksForJSON();
		$user = new User();
		$user->id = $this->userId;
		if ($user->find(true)){
			$links['user'] = $user->cat_username;
		}
		$role = new Role();
		$role->roleId = $this->roleId;
		if ($role->find(true)){
			$links['role'] = $role->name;
		}

		return $links;
	}

	public function loadEmbeddedLinksFromJSON($jsonData, $mappings, $overrideExisting = 'keepExisting')
	{
		parent::loadEmbeddedLinksFromJSON($jsonData, $mappings, $overrideExisting);
		if (array_key_exists('user', $jsonData)){
			$username = $jsonData['user'];
			$user = new User();
			$user->cat_username = $username;
			if ($user->find(true)){
				$this->userId = $user->id;
			}
		}
		if (array_key_exists('role', $jsonData)){
			$role = new Role();
			$role->name = $jsonData['role'];
			if ($role->find(true)){
				$this->roleId = $role->roleId;
			}
		}
	}
}