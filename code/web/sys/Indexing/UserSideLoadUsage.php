<?php


class UserSideLoadUsage extends DataObject
{
	public $__table = 'user_sideload_usage';
	public $id;
	public $instance;
	public $userId;
	public $sideLoadId;
	public $year;
	public $month;
	public $usageCount; //Number of clicks

	public function getUniquenessFields(): array
	{
		return ['instance','userId', 'sideLoadId','year', 'month'];
	}

	public function toArray($includeRuntimeProperties = true, $encryptFields = false): array
	{
		$return =  parent::toArray($includeRuntimeProperties, $encryptFields);
		unset($return['userId']);
		return $return;
	}

	public function okToExport(array $selectedFilters): bool
	{
		$okToExport = parent::okToExport($selectedFilters);
		if (in_array($this->instance, $selectedFilters['instances'])){
			$okToExport = true;
		}
		$user = new User();
		$user->id = $this->userId;
		if ($user->find(true)) {
			if ($user->homeLocationId == 0 || array_key_exists($user->homeLocationId, $selectedFilters['locations'])) {
				$okToExport = true;
			}
		}
		return $okToExport;
	}

	public function getLinksForJSON(): array
	{
		$links =  parent::getLinksForJSON();
		$user = new User();
		$user->id = $this->userId;
		if ($user->find(true)){
			$links['user'] = $user->username;
		}
		return $links;
	}

	public function loadEmbeddedLinksFromJSON($jsonData, $mappings, $overrideExisting = 'keepExisting')
	{
		parent::loadEmbeddedLinksFromJSON($jsonData, $mappings, $overrideExisting);
		if (isset($jsonData['user'])){
			$username = $jsonData['user'];
			if (array_key_exists($username, $mappings['users'])){
				$username = $mappings['users'][$username];
			}
			$user = new User();
			$user->username = $username;
			if ($user->find(true)){
				$this->userId = $user->id;
			}
		}
	}
}