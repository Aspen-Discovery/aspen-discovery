<?php

class UserLink extends DataObject{
	public $id;
	public $primaryAccountId;
	public $linkedAccountId;
	public $linkingDisabled;

	public $__table = 'user_link';    // table name

	public function getUniquenessFields(): array
	{
		return ['primaryAccountId', 'linkedAccountId'];
	}

	public function okToExport(array $selectedFilters): bool
	{
		$okToExport = parent::okToExport($selectedFilters);

		$primaryAccountOkToExport = false;
		$user = new User();
		$user->id = $this->primaryAccountId;
		if ($user->find(true)) {
			if ($user->homeLocationId == 0 || array_key_exists($user->homeLocationId, $selectedFilters['locations'])) {
				$primaryAccountOkToExport = true;
			}
		}

		$linkedAccountOkToExport = false;
		$user = new User();
		$user->id = $this->linkedAccountId;
		if ($user->find(true)) {
			if ($user->homeLocationId == 0 || array_key_exists($user->homeLocationId, $selectedFilters['locations'])) {
				$linkedAccountOkToExport = true;
			}
		}

		if ($linkedAccountOkToExport && $primaryAccountOkToExport){
			$okToExport = true;
		}

		return $okToExport;
	}

	public function toArray($includeRuntimeProperties = true, $encryptFields = false): array
	{
		$return =  parent::toArray($includeRuntimeProperties, $encryptFields);
		unset($return['primaryAccountId']);
		unset($return['linkedAccountId']);
		return $return;
	}

	public function getLinksForJSON(): array
	{
		$links = parent::getLinksForJSON();
		$user = new User();
		$user->id = $this->primaryAccountId;
		if ($user->find(true)) {
			$links['primaryAccount'] = $user->cat_username;
		}
		$user = new User();
		$user->id = $this->linkedAccountId;
		if ($user->find(true)) {
			$links['linkedAccount'] = $user->cat_username;
		}
		return $links;
	}

	public function loadEmbeddedLinksFromJSON($jsonData, $mappings, $overrideExisting = 'keepExisting')
	{
		parent::loadEmbeddedLinksFromJSON($jsonData, $mappings, $overrideExisting);
		if (isset($jsonData['primaryAccount'])){
			$username = $jsonData['primaryAccount'];
			$user = new User();
			$user->cat_username = $username;
			if ($user->find(true)){
				$this->primaryAccountId = $user->id;
			}

			$username = $jsonData['linkedAccount'];
			$user = new User();
			$user->cat_username = $username;
			if ($user->find(true)){
				$this->linkedAccountId = $user->id;
			}
		}
	}
}