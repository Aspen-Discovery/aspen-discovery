<?php


class UserEventsUsage extends DataObject
{
	public $__table = 'user_events_usage';
	public $id;
	public $userId;
	public $type;
	public $source;
	public $year;
	public $month;
	public $usageCount;

	public function getUniquenessFields(): array
	{
		return ['type', 'source','userId','year', 'month'];
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
			if ($user->homeLocationId == 0 || in_array($user->homeLocationId, $selectedFilters['locations'])) {
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
			$links['user'] = $user->cat_username;
		}
		return $links;
	}

	public function loadEmbeddedLinksFromJSON($jsonData, $mappings, $overrideExisting = 'keepExisting')
	{
		parent::loadEmbeddedLinksFromJSON($jsonData, $mappings, $overrideExisting);
		if (isset($jsonData['user'])){
			$username = $jsonData['user'];
			$user = new User();
			$user->cat_username = $username;
			if ($user->find(true)){
				$this->userId = $user->id;
			}
		}
	}
}