<?php

require_once ROOT_DIR . '/sys/Account/UserNotificationToken.php';

class UserNotification extends DataObject {
	public $__table = 'user_notifications';

	public $id;
	public $userId;
	public $notificationType;
	public $notificationDate;
	public $pushToken;
	public $receiptId;
	public $completed;
	public $error;
	public $message;

	public static function getObjectStructure($context) {
		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id within the database',
			],
			'notificationDate' => [
				'property' => 'notificationDate',
				'type' => 'timestamp',
				'label' => 'Notification Date',
				'description' => 'The date the notification was sent',
				'readOnly' => true,
			],
			'notificationType' => [
				'property' => 'notificationType',
				'type' => 'text',
				'label' => 'Notification Type',
				'description' => 'The kind of notification this was',
				'readOnly' => true,
			],
			'user' => [
				'property' => 'user',
				'type' => 'text',
				'label' => 'User',
				'description' => 'The user who the notification was sent to',
				'readOnly' => true,
			],
			'library' => [
				'property' => 'library',
				'type' => 'text',
				'label' => 'Library',
				'description' => 'The user\'s home library',
				'readOnly' => true,
			],
			'device' => [
				'property' => 'device',
				'type' => 'text',
				'label' => 'Device',
				'description' => 'The device that the notification was sent to',
				'readOnly' => true,
			],
			'receiptId' => [
				'property' => 'receiptId',
				'type' => 'text',
				'label' => 'Receipt ID',
				'description' => 'The ID of the notification within the notification API',
				'readOnly' => true,
			],
			'completed' => [
				'property' => 'completed',
				'type' => 'checkbox',
				'label' => 'Completed?',
				'description' => 'Whether or not the notification has been received by the device',
				'readOnly' => true,
			],
			'error' => [
				'property' => 'error',
				'type' => 'checkbox',
				'label' => 'Error?',
				'description' => 'Whether or not an error occurred during processing of the notification',
				'readOnly' => true,
			],
			'message' => [
				'property' => 'message',
				'type' => 'text',
				'label' => 'Message',
				'description' => 'A message returned by the notification API',
				'readOnly' => true,
			],
		];
	}

	public function getNumericColumnNames(): array {
		return [
			'completed',
			'error',
		];
	}

	/** @var User[] */
	private static $usersById = [];

	function __get($name) {
		if ($name == 'user') {
			if (empty($this->userId)) {
				return translate([
					'text' => 'Unknown',
					'isPublicFacing' => true,
				]);
			}
			if (empty($this->_data['user'])) {
				if (!array_key_exists($this->userId, UserNotification::$usersById)) {
					$user = new User();
					$user->id = $this->userId;
					if ($user->find(true)) {
						UserNotification::$usersById[$this->userId] = $user;
					}
				}
				if (array_key_exists($this->userId, UserNotification::$usersById)) {
					$user = UserNotification::$usersById[$this->userId];
					if (!empty($user->displayName)) {
						$this->_data['user'] = $user->displayName . ' (' . $user->getBarcode() . ')';
					} else {
						$this->_data['user'] = $user->firstname . ' ' . $user->lastname . ' (' . $user->getBarcode() . ')';
					}
				} else {
					$this->_data['user'] = translate([
						'text' => 'Unknown',
						'isPublicFacing' => true,
					]);
				}

			}
		} elseif ($name == 'library') {
			if (empty($this->_data['library'])) {
				if (array_key_exists($this->userId, UserNotification::$usersById)) {
					$this->_data['library'] = UserNotification::$usersById[$this->userId]->getHomeLibrary()->displayName;
				} else {
					$this->_data['library'] = translate([
						'text' => 'Unknown',
						'isPublicFacing' => true,
					]);
				}
			}
		} elseif ($name == 'device') {
			if (empty($this->_data['device'])) {
				if (array_key_exists($this->userId, UserNotification::$usersById)) {
					$token = new UserNotificationToken();
					$token->pushToken = $this->pushToken;
					if ($token->find(true)) {
						$this->_data['device'] = $token->deviceModel;
					}
				} else {
					$this->_data['device'] = translate([
						'text' => 'Unknown',
						'isPublicFacing' => true,
					]);
				}
			}
		}
		return $this->_data[$name];
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