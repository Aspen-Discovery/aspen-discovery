<?php
/** @noinspection PhpMissingFieldTypeInspection */


class UserAppRequestLogEntry extends DataObject {
	public $__table = 'user_app_request_log';
	public $id;
	public $userId;
	public $action;
	public $method;
	public $queryString;
	public $time;
	public $version;

	static $_objectStructure = [];

	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context]) && self::$_objectStructure[$context] !== null) {
			return self::$_objectStructure[$context];
		}
		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'action' => [
				'property' => 'action',
				'type' => 'text',
				'label' => 'API',
				'description' => 'The API the request was made to',
				'readOnly' => true,
			],
			'method' => [
				'property' => 'method',
				'type' => 'text',
				'label' => 'Request Method',
				'description' => 'The method set to request',
				'readOnly' => true,
			],
			'queryString' => [
				'property' => 'queryString',
				'type' => 'textarea',
				'label' => 'Parameters',
				'description' => 'Parameters sent in the request',
				'hideInLists' => true,
				'readOnly' => true,
			],
			'version' => [
				'property' => 'version',
				'type' => 'text',
				'label' => 'LiDA Version',
				'description' => 'The version of LiDA that made the request',
				'hideInLists' => true,
				'readOnly' => true,
			],
			'time' => [
				'property' => 'time',
				'type' => 'timestamp',
				'label' => 'Request Time',
				'description' => 'When the request was made',
				'readOnly' => true,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	/**
	 * @param string $userId
	 * @param string $action
	 * @param string $method
	 * @param string $queryString
	 * @param string $version
	 */
	static function logRequest(string $userId, string $action, string $method, string $queryString, string $version): void {
		try {
			require_once ROOT_DIR . '/sys/SystemLogging/UserAppRequestLogEntry.php';
			$externalRequest = new UserAppRequestLogEntry();
			$externalRequest->userId = $userId;
			$externalRequest->action = $action;
			$externalRequest->method = $method;
			$externalRequest->queryString = UserAppRequestLogEntry::sanitize($queryString);
			$externalRequest->version = $version;
			$externalRequest->time = (new DateTime())->format('Y-m-d H:i:s');
			$externalRequest->insert();
		} catch (Exception $e) {
			global $logger;
			$logger->log("Error logging patron Aspen LiDA request " . $e->getMessage(), Logger::LOG_ERROR);
		}
	}

	private static function sanitize(string $data): string {
		$data = json_decode($data, true);
		// we could probably move these to dataToRemove just in case, but for now we'll just sanitize it
		$dataToSanitize = [
			'username',
			'password'
		];
		// these are automated parameters, and what we do need we log elsewhere
		$dataToRemove = [
			'method',
			'searchSource',
			'module',
			'action'
		];
		foreach ($data as $key => $value) {
			if (in_array($key, $dataToRemove)) {
				unset($data[$key]);
			} elseif (in_array($key, $dataToSanitize)) {
				$data[$key] = "********";
			}
		}
		$encodedData = json_encode($data);
		if (strlen($encodedData) > 65535) {
			$encodedData = substr($encodedData, 0, 65535);
		}
		return $encodedData;
	}
}