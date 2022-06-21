<?php


class SystemMessageDismissal extends DataObject
{
	public $__table = 'system_message_dismissal';
	public $id;
	public $systemMessageId;
	public $userId;

	public function getUniquenessFields(): array
	{
		return ['userId', 'systemMessageId'];
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
		$systemMessage = new SystemMessage();
		$systemMessage->id = $this->systemMessageId;
		if ($user->find(true)) {
			$links['systemMessage'] = $systemMessage->title;
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
		if (isset($jsonData['systemMessage'])){
			$systemMessage = new SystemMessage();
			$systemMessage->title = $jsonData['systemMessage'];
			if ($systemMessage->find(true)) {
				$this->systemMessageId = $systemMessage->id;
			}
		}
	}
}