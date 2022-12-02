<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class UserWorkReview extends DataObject {
	public $__table = 'user_work_review';
	public $id;
	public $groupedRecordPermanentId;
	public $userId;
	public $rating;
	public $review;
	public $dateRated;
	public $importedFrom;

	private $_displayName;

	/**
	 * @return mixed
	 */
	public function getDisplayName() {
		return $this->_displayName;
	}

	/**
	 * @param mixed $displayName
	 */
	public function setDisplayName($displayName): void {
		$this->_displayName = $displayName;
	}

	public function getUniquenessFields(): array {
		return [
			'userId',
			'groupedRecordPermanentId',
		];
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

	public function toArray($includeRuntimeProperties = true, $encryptFields = false): array {
		$return = parent::toArray($includeRuntimeProperties, $encryptFields);
		unset($return['userId']);
		return $return;
	}

	public function getLinksForJSON(): array {
		$links = parent::getLinksForJSON();
		$user = new User();
		$user->id = $this->userId;
		if ($user->find(true)) {
			$links['user'] = $user->cat_username;
		}
		return $links;
	}

	public function loadEmbeddedLinksFromJSON($jsonData, $mappings, $overrideExisting = 'keepExisting') {
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