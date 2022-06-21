<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class UserEbscoEdsUsage extends DataObject
{
	public $__table = 'user_ebsco_eds_usage';
	public $id;
	public $instance;
	public $userId;
	public $year;
	public $month;
	public $usageCount;

	public function getUniquenessFields(): array
	{
		return ['instance','userId','year', 'month'];
	}

	public function toArray($includeRuntimeProperties = true, $encryptFields = false): array
	{
		$return = parent::toArray($includeRuntimeProperties, $encryptFields);
		unset($return['userId']);
		return $return;
	}

	public function okToExport(array $selectedFilters): bool
	{
		$okToExport = parent::okToExport($selectedFilters);
		if (in_array($this->instance, $selectedFilters['instances'])) {
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
		$links = parent::getLinksForJSON();
		$user = new User();
		$user->id = $this->userId;
		if ($user->find(true)) {
			$links['user'] = $user->cat_username;
		}
		return $links;
	}

	public function loadEmbeddedLinksFromJSON($jsonData, $mappings, $overrideExisting = 'keepExisting')
	{
		parent::loadEmbeddedLinksFromJSON($jsonData, $mappings, $overrideExisting);
		if (isset($jsonData['user'])) {
			$username = $jsonData['user'];
			$user = new User();
			$user->cat_username = $username;
			if ($user->find(true)) {
				$this->userId = $user->id;
			}
		}
	}
}