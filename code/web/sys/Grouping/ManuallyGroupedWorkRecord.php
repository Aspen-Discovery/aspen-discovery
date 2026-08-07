<?php
/** @noinspection PhpMissingFieldTypeInspection */

class ManuallyGroupedWorkRecord extends DataObject {
	public $__table = 'manually_grouped_work_records';
	public $id;
	public $manually_grouped_work_id;
	public $type;
	public $identifier;
	public $user_provided_identifier;
	public $identifier_type;
	public $date_added;

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context]) && self::$_objectStructure[$context] !== null) {
			return self::$_objectStructure[$context];
		}
		global $indexingProfiles;
		global $sideLoadSettings;
		global $enabledModules;

		// Get available record sources
		$availableSources = [];
		foreach ($indexingProfiles as $profile) {
			$displayName = $profile->name;
			$associatedAccountProfile = $profile->getAccountProfile();
			if ($associatedAccountProfile && !empty($associatedAccountProfile->ils) && $associatedAccountProfile->ils !== 'na') {
				$displayName .= ' (' . ucfirst($associatedAccountProfile->ils) . ')';
			}
			$availableSources[$profile->name] = $displayName;
		}
		foreach ($sideLoadSettings as $profile) {
			$availableSources[$profile->name] = $profile->name;
		}

		// Only include eContent options if their modules are enabled
		if (array_key_exists('Axis 360', $enabledModules)) {
			$availableSources['axis360'] = 'Boundless';
		}
		if (array_key_exists('Cloud Library', $enabledModules)) {
			$availableSources['cloud_library'] = 'Cloud Library';
		}
		if (array_key_exists('Hoopla', $enabledModules)) {
			$availableSources['hoopla'] = 'Hoopla';
		}
		if (array_key_exists('OverDrive', $enabledModules)) {
			$availableSources['overdrive'] = 'Overdrive';
		}
		if (array_key_exists('Palace Project', $enabledModules)) {
			$availableSources['palace_project'] = 'Palace Project';
		}

		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'manually_grouped_work_id' => [
				'property' => 'manually_grouped_work_id',
				'type' => 'hidden',
				'label' => 'Manually Grouped Work Id',
				'description' => 'The ID of the manually grouped work to which this record belongs.',
			],
			'date_added' => [
				'property' => 'date_added',
				'type' => 'timestamp',
				'label' => 'Date Added',
				'description' => 'The date this record was added to the group.',
				'readOnly' => true,
				'hideInLists' => true,
			],
			'type' => [
				'property' => 'type',
				'type' => 'enum',
				'values' => $availableSources,
				'label' => 'Source',
				'description' => 'The source of the record.',
				'required' => true,
			],
			'identifier_type' => [
				'property' => 'identifier_type',
				'type' => 'enum',
				'values' => [
					'record_id' => 'Record ID',
					'isbn' => 'ISBN',
					'barcode' => 'Item Barcode',
				],
				'label' => 'Identifier Type',
				'description' => 'The type of identifier being used.',
				'default' => 'record_id',
				'required' => true,
			],
			'user_provided_identifier' => [
				'property' => 'user_provided_identifier',
				'type' => 'text',
				'maxLength' => 255,
				'label' => 'Identifier Value',
				'description' => 'Enter the Record ID, ISBN, or Barcode here.',
				'required' => true,
			],
			'identifier' => [
				'property' => 'identifier',
				'type' => 'text',
				'label' => 'Resolved Record ID',
				'description' => 'The actual system identifier for the record, resolved from user input.',
				'readOnly' => true,
				'readOnlyWhenNew' => true,
				'placeholder' => 'Record identifier will populate here...',
			],
			'indexed' => [
				'property' => 'indexed',
				'type' => 'checkbox',
				'label' => 'Indexed?',
				'description' => 'Whether this record has been indexed into the manually created grouped work.',
				'readOnly' => true,
				'readOnlyWhenNew' => true,
				'hideInLists' => false,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	/**
	 * Find a record's primary identifier by item barcode.
	 *
	 * @param string $barcode The item barcode to look up.
	 * @param string $source The source system (e.g., "ils").
	 * @return array|null Array with 'identifier' and 'type' if found, null if not found.
	 */
	private function getPrimaryIdentifierByBarcode(string $barcode, string $source): ?array {
		require_once ROOT_DIR . '/sys/SearchObject/SearchObjectFactory.php';
		/** @var SearchObject_AbstractGroupedWorkSearcher $searchObject */
		$searchObject = SearchObjectFactory::initSearchObject();
		$searchObject->init();
		$recordData = $searchObject->getRecordByBarcode($barcode);

		if ($recordData && isset($recordData['id'])) {
			require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
			require_once ROOT_DIR . '/sys/Grouping/GroupedWorkPrimaryIdentifier.php';
			$permanentId = $recordData['id'];
			$groupedWork = new GroupedWork();
			$groupedWork->selectAdd();
			$groupedWork->selectAdd('id');
			$groupedWork->permanent_id = $permanentId;
			if ($groupedWork->find(true)) {
				$primaryIdentifier = new GroupedWorkPrimaryIdentifier();
				$primaryIdentifier->selectAdd();
				$primaryIdentifier->selectAdd('identifier');
				$primaryIdentifier->grouped_work_id = $groupedWork->id;
				$primaryIdentifier->type = $source;
				if ($primaryIdentifier->find(true)) {
					return [
						'identifier' => $primaryIdentifier->identifier,
						'type' => $source
					];
				}
			}
		}

		return null;
	}

	/**
	 * Find a record's primary identifier by ISBN.
	 *
	 * @param string $isbn The ISBN to look up (ISBN-10 or ISBN-13).
	 * @param string $source The source system.
	 * @return array|null Array with 'identifier' and 'type' if found, null if not found.
	 */
	private function getPrimaryIdentifierByISBN(string $isbn, string $source): ?array {
		require_once ROOT_DIR . '/sys/ISBN.php';
		$isbnObj = new ISBN($isbn);
		$searchIsbns = [];
		if ($isbn13 = $isbnObj->get13()) {
			$searchIsbns[] = $isbn13;
		}
		if ($isbn10 = $isbnObj->get10()) {
			$searchIsbns[] = $isbn10;
		}

		if (empty($searchIsbns)) {
			global $logger;
			$logger->log("Invalid ISBN provided: $isbn", Logger::LOG_ERROR);
			return null;
		}

		require_once ROOT_DIR . '/sys/SearchObject/SearchObjectFactory.php';
		/** @var SearchObject_AbstractGroupedWorkSearcher $searchObject */
		$searchObject = SearchObjectFactory::initSearchObject();
		$searchObject->init();
		$recordData = $searchObject->getRecordByIsbn($searchIsbns);

		global $logger;
		if ($recordData && isset($recordData['id'])) {
			$logger->log("Record data: " . print_r($recordData, true), Logger::LOG_ERROR);
			require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
			require_once ROOT_DIR . '/sys/Grouping/GroupedWorkPrimaryIdentifier.php';
			$permanentId = $recordData['id'];
			$groupedWork = new GroupedWork();
			$groupedWork->selectAdd();
			$groupedWork->selectAdd('id');
			$groupedWork->permanent_id = $permanentId;
			if ($groupedWork->find(true)) {
				$primaryIdentifier = new GroupedWorkPrimaryIdentifier();
				$primaryIdentifier->selectAdd();
				$primaryIdentifier->selectAdd('identifier');
				$primaryIdentifier->grouped_work_id = $groupedWork->id;
				$primaryIdentifier->type = $source;
				if ($primaryIdentifier->find(true)) {
					return [
						'identifier' => $primaryIdentifier->identifier,
						'type' => $source
					];
				}
			}
		} else {
			$logger->log("No record found for ISBN: $isbn", Logger::LOG_ERROR);
		}

		return null;
	}

	/**
	 * Resolve this record's identifier if it's a barcode or ISBN to the primary record ID.
	 * Updates $this->identifier to the primary record identifier.
	 *
	 * @return array|bool True if the identifier is resolved or no resolution needed;
	 *                    Array with 'success' => false and 'message' => error message if resolution fails.
	 */
	public function resolvePrimaryIdentifier(): bool|array {
		if ($this->identifier_type === 'barcode' || $this->identifier_type === 'isbn') {
			if ($this->identifier_type === 'barcode') {
				$result = $this->getPrimaryIdentifierByBarcode($this->user_provided_identifier, $this->type);
			} else {
				$result = $this->getPrimaryIdentifierByISBN($this->user_provided_identifier, $this->type);
			}
			if ($result !== null && isset($result['identifier'])) {
				$this->identifier = $result['identifier'];
				return true;
			}

			if ($this->identifier_type === 'barcode') {
				$errorMessage = "No item found with barcode '{$this->user_provided_identifier}' in source '{$this->type}'.";
			} else {
				$errorMessage = "No record found with ISBN '{$this->user_provided_identifier}' in source '{$this->type}'.";
			}
			return [
				'success' => false,
				'message' => $errorMessage
			];
		}
		return true;
	}

	public function __get($name) {
		if ($name === 'indexed') {
			return $this->isIndexedIntoManualGroupedWork();
		}
		return parent::__get($name);
	}

	/**
	 * Check if this record has been indexed into the manually created grouped work.
	 *
	 * @return bool
	 */
	private function isIndexedIntoManualGroupedWork(): bool {
		if (empty($this->identifier) || empty($this->type) || empty($this->manually_grouped_work_id)) {
			return false;
		}

		require_once ROOT_DIR . '/sys/Grouping/ManualGroupedWork.php';
		$manualGroupedWork = new ManualGroupedWork();
		$manualGroupedWork->selectAdd();
		$manualGroupedWork->selectAdd('grouped_work_permanent_id');
		$manualGroupedWork->id = $this->manually_grouped_work_id;
		if (!$manualGroupedWork->find(true) || empty($manualGroupedWork->grouped_work_permanent_id)) {
			return false;
		}

		require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
		$groupedWork = new GroupedWork();
		$groupedWork->selectAdd();
		$groupedWork->selectAdd('id');
		$groupedWork->permanent_id = $manualGroupedWork->grouped_work_permanent_id;
		if (!$groupedWork->find(true)) {
			return false;
		}

		require_once ROOT_DIR . '/sys/Grouping/GroupedWorkPrimaryIdentifier.php';
		$primaryIdentifier = new GroupedWorkPrimaryIdentifier();
		$primaryIdentifier->selectAdd();
		$primaryIdentifier->selectAdd('id');
		$primaryIdentifier->grouped_work_id = $groupedWork->id;
		$primaryIdentifier->type = $this->type;
		$primaryIdentifier->identifier = $this->identifier;
		return $primaryIdentifier->find(true) !== false;
	}
}