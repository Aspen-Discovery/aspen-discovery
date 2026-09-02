<?php /** @noinspection PhpMissingFieldTypeInspection */


class OMDBSetting extends DataObject {
	public $__table = 'omdb_settings';    // table name
	public $id;
	public $apiKey;
	public $fetchCoversWithoutDates;

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
			'apiKey' => [
				'property' => 'apiKey',
				'type' => 'storedPassword',
				'label' => 'API Key',
				'description' => 'The Key for the API',
				'maxLength' => '10',
				'hideInLists' => true,
			],
			'fetchCoversWithoutDates' => [
				'property' => 'fetchCoversWithoutDates',
				'type' => 'checkbox',
				'label' => 'Fetch Covers Without Dates',
				'description' => 'If Unchecked, covers must match the date and title of the cover for the cover to be shown.  This can cause fewer covers to be shown',
				'default' => 1,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	public function update(string $context = '') : int|bool {
		$result = parent::update();
		if (in_array('fetchCoversWithoutDates', $this->_changedFields)) {
			require_once ROOT_DIR . '/sys/Covers/BookCoverInfo.php';
			$covers = new BookCoverInfo();
			$covers->reloadOMDBCovers();
		}
		return $result;
	}
}