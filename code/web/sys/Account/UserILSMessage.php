<?php /** @noinspection PhpMissingFieldTypeInspection */

class UserILSMessage extends DataObject {
	public $__table = 'user_ils_messages';
	public $id;
	public $messageId; //For some ILSs this is the message ID retrieved from the underlying ILS. For others, where we generate our own message, it is the hold id or checkout id.
	public $userId;
	public $type;
	public $status;
	public $title;
	public $content;
	public $defaultContent;
	public $dateQueued;
	public $dateSent;
	public $isRead;

	public function getNumericColumnNames(): array {
		return [
			'messageId',
			'userId',
			'isRead'
		];
	}

	public function toArray($includeRuntimeProperties = true, $encryptFields = false): array {
		$return = parent::toArray($includeRuntimeProperties, $encryptFields);
		unset($return['userId']);
		return $return;
	}

	public function okToExport(array $selectedFilters): bool {
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

	public function getLinksForJSON(): array {
		$links = parent::getLinksForJSON();
		$user = new User();
		$user->id = $this->userId;
		if ($user->find(true)) {
			$links['user'] = $user->ils_barcode;
		}
		return $links;
	}

	public function loadEmbeddedLinksFromJSON($jsonData, $mappings, $overrideExisting = 'keepExisting') : void {
		parent::loadEmbeddedLinksFromJSON($jsonData, $mappings, $overrideExisting);
		if (isset($jsonData['user'])) {
			$username = $jsonData['user'];
			$user = new User();
			$user->ils_barcode = $username;
			if ($user->find(true)) {
				$this->userId = $user->id;
			}
		}
	}

}