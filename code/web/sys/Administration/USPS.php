<?php /** @noinspection PhpMissingFieldTypeInspection */

class USPS extends DataObject {
	public $__table = 'usps_settings';
	public $id;
	public $clientId;
	public $clientSecret;

	/**
	 * @return string[]
	 */
	function getEncryptedFieldNames(): array {
		return ['clientSecret'];
	}

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context])) {
			return self::$_objectStructure[$context];
		}
		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'clientId' => [
				'property' => 'clientId',
				'type' => 'text',
				'label' => 'Client ID',
				'maxLength' => 255,
			],
			'clientSecret' => [
				'property' => 'clientSecret',
				'type' => 'storedPassword',
				'label' => 'Client Secret',
				'maxLength' => 255,
				'hideInLists' => true,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	/** @var null|USPS */
	protected static $_USPS = null;

	/**
	 * @return USPS|false
	 */
	public static function getUSPSInfo() : USPS|false {
		if (USPS::$_USPS == null) {
			USPS::$_USPS = new USPS();
			if (!USPS::$_USPS->find(true)) {
				USPS::$_USPS = false;
			}
		}
		return USPS::$_USPS;
	}
}