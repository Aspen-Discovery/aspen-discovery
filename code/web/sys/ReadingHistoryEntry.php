<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';
class ReadingHistoryEntry extends DataObject
{
	public $__table = 'user_reading_history_work';   // table name
	public $id;
	public $userId;
	public $groupedWorkPermanentId;
	public $source;
	public $sourceId;
	public $title;
	public $author;
	public $format;
	public $checkOutDate;
	public $checkInDate;
	public $deleted;

	public function getUniquenessFields(): array
	{
		return ['userId', 'groupedWorkPermanentId', 'source', 'sourceId'];
	}

	public function okToExport(array $selectedFilters): bool
	{
		$okToExport = parent::okToExport($selectedFilters);
		$user = new User();
		$user->id = $this->userId;
		if ($user->find(true)) {
			if ($user->homeLocationId == 0 || in_array($user->homeLocationId, $selectedFilters['locations'])) {
				$okToExport = true;
			}
		}
		return $okToExport;
	}

	public function toArray($includeRuntimeProperties = true, $encryptFields = false): array
	{
		$return =  parent::toArray($includeRuntimeProperties, $encryptFields);
		unset($return['userId']);
		return $return;
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

	public function loadFromJSON($jsonData, $mappings, $overrideExisting = 'keepExisting'): bool
	{
		if (array_key_exists($jsonData['sourceId'], $mappings['bibs'])){
			$jsonData['sourceId'] = $mappings['bibs'][$this->sourceId];
		}
		return parent::loadFromJSON($jsonData, $mappings, $overrideExisting);
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
