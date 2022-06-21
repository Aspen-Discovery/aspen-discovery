<?php


class PlacardDismissal extends DataObject
{
	public $__table = 'placard_dismissal';
	public $id;
	public $placardId;
	public $userId;

	public function toArray($includeRuntimeProperties = true, $encryptFields = false): array
	{
		$return =  parent::toArray($includeRuntimeProperties, $encryptFields);
		unset($return['userId']);
		unset($return['placardId']);
		return $return;
	}

	public function okToExport(array $selectedFilters) : bool{
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

	public function getLinksForJSON(): array
	{
		$links =  parent::getLinksForJSON();
		$user = new User();
		$user->id = $this->userId;
		if ($user->find(true)){
			$links['user'] = $user->cat_username;
		}
		require_once ROOT_DIR . '/sys/LocalEnrichment/Placard.php';
		$placard = new Placard();
		$placard->id = $this->placardId;
		if ($placard->find(true)){
			$links['placard'] = $placard->title;
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
		if (isset($jsonData['placard'])){
			$placard = new Placard();
			$placard->title = $jsonData['placard'];
			if ($placard->find(true)){
				$this->placardId = $placard->id;
			}
		}
	}
}