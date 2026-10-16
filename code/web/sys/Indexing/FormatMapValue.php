<?php /** @noinspection PhpMissingFieldTypeInspection */

class FormatMapValue extends DataObject {
	public $__table = 'format_map_values';    // table name
	public $id;
	public $indexingProfileId;
	public $value;
	public $format;
	public $formatCategory;
	public $formatBoost;
	public $appliesToMatType;
	public $appliesToBibLevel;
	public $appliesToItemShelvingLocation;
	public $appliesToItemSublocation;
	public $appliesToItemCollection;
	public $appliesToItemType;
	public $appliesToItemFormat;
	public $appliesToFallbackFormat;
	public $displaySierraCheckoutGrid;

	public $suppress;
	public /** @noinspection PhpUnused */
		$inLibraryUseOnly;
	public $holdType;
	public $pickupAt;

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context])) {
			return self::$_objectStructure[$context];
		}

		$formatCategories = [
			'Audio Books' => 'Audio Books',
			'Books' => 'Books',
			'eBook' => 'eBook',
			'Movies' => 'Movies',
			'Music' => 'Music',
			'Other' => 'Other',
		];
		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id within the database',
			],
			'indexingProfileId' => [
				'property' => 'indexingProfileId',
				'type' => 'foreignKey',
				'label' => 'Indexing Profile Id',
				'description' => 'The Profile this is associated with',
			],
			'value' => [
				'property' => 'value',
				'type' => 'text',
				'label' => 'Value',
				'description' => 'The value to be translated',
				'maxLength' => '50',
				'required' => true,
				'forcesReindex' => true,
			],
			'format' => [
				'property' => 'format',
				'type' => 'text',
				'label' => 'Format',
				'description' => 'The detailed format',
				'maxLength' => '255',
				'required' => false,
				'forcesReindex' => true,
				'forcesRegroup' => true,
				'onchange' => 'return AspenDiscovery.Admin.calculateGroupingCategories(this);',
			],
			'formatCategory' => [
				'property' => 'formatCategory',
				'type' => 'enum',
				'label' => 'Format Category',
				'description' => 'The Format Category',
				'values' => $formatCategories,
				'required' => true,
				'forcesReindex' => true,
				'forcesRegroup' => true,
				'onchange' => 'return AspenDiscovery.Admin.calculateGroupingCategories(this);',
			],
			'groupingCategory' => [
				'property' => 'groupingCategory',
				'type' => 'dynamic_label',
				'label' => 'Grouping Category',
				'description' => 'The Grouping Category for the format',
				'readOnly' => true,
			],
			'formatBoost' => [
				'property' => 'formatBoost',
				'type' => 'enum',
				'values' => [
					1 => 'None',
					'3' => 'Low',
					6 => 'Medium',
					9 => 'High',
					'12' => 'Very High',
				],
				'label' => 'Format Boost',
				'description' => 'The Format Boost to apply during indexing',
				'default' => 1,
				'required' => true,
				'forcesReindex' => true,
			],
			'appliesToMatType' => [
				'property' => 'appliesToMatType',
				'type' => 'checkbox',
				'label' => 'Applies to Mat Type?',
				'description' => 'Use the format during Sierra Mat Type format determination',
				'default' => 1,
				'forcesReindex' => true,
			],
			'appliesToItemShelvingLocation' => [
				'property' => 'appliesToItemShelvingLocation',
				'type' => 'checkbox',
				'label' => 'Applies to Item Shelving Location?',
				'description' => 'Use the format during item level shelving location format determination',
				'default' => 1,
				'forcesReindex' => true,
			],
			'appliesToItemSublocation' => [
				'property' => 'appliesToItemSublocation',
				'type' => 'checkbox',
				'label' => 'Applies to Item Sublocation?',
				'description' => 'Use the format during item level sublocation format determination',
				'default' => 1,
				'forcesReindex' => true,
			],
			'appliesToItemCollection' => [
				'property' => 'appliesToItemCollection',
				'type' => 'checkbox',
				'label' => 'Applies to Item Collection?',
				'description' => 'Use the format during item level collection format determination',
				'default' => 1,
				'forcesReindex' => true,
			],
			'appliesToItemType' => [
				'property' => 'appliesToItemType',
				'type' => 'checkbox',
				'label' => 'Applies to Item Type?',
				'description' => 'Use the format during item level item type format determination',
				'default' => 1,
				'forcesReindex' => true,
			],
			'appliesToItemFormat' => [
				'property' => 'appliesToItemFormat',
				'type' => 'checkbox',
				'label' => 'Applies to Item Format?',
				'description' => 'Use the format during item level format field determination',
				'default' => 1,
				'forcesReindex' => true,
			],
			'appliesToBibLevel' => [
				'property' => 'appliesToBibLevel',
				'type' => 'checkbox',
				'label' => 'Applies to Bib Level?',
				'description' => 'Use the format during bib level format determination',
				'default' => 1,
				'forcesReindex' => true,
			],
			'appliesToFallbackFormat' => [
				'property' => 'appliesToFallbackFormat',
				'type' => 'checkbox',
				'label' => 'Applies to Fallback Format?',
				'description' => 'Use the format during fallback format field determination',
				'default' => 1,
				'forcesReindex' => true,
			],
			'holdType' => [
				'property' => 'holdType',
				'type' => 'enum',
				'values' => [
					'bib' => 'Bib Only',
					'item' => 'Item Only',
					'either' => 'Either Bib or Item',
					'none' => 'No Holds Allowed',
				],
				'label' => 'Hold Type',
				'description' => 'Types of Holds to allow',
				'default' => 'bib',
				'required' => true,
				'forcesReindex' => true,
			],
			'suppress' => [
				'property' => 'suppress',
				'type' => 'checkbox',
				'label' => 'Suppress?',
				'description' => 'Suppress from the catalog',
				'default' => 0,
				'required' => true,
				'forcesReindex' => true,
			],
			'pickupAt' => [
				'property' => 'pickupAt',
				'type' => 'enum',
				'values' => [
					0 => 'Any valid location',
					2 => 'Holding Library',
					1 => 'Holding Location',
				],
				'label' => 'Pickup at',
				'description' => 'When placing holds, only branches where the item is can be used as pickup locations.',
				'default' => 0,
				'required' => true,
				'forcesReindex' => false,
			],
			'displaySierraCheckoutGrid' => [
				'property' => 'displaySierraCheckoutGrid',
				'type' => 'checkbox',
				'label' => 'Display Sierra Checkout Grid?',
				'description' => 'Whether or not checkout grids are shown for this type of item',
				'default' => 0,
				'required' => false,
				'forcesReindex' => false,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	public function __get($name) {
		if ($name == 'groupingCategory') {
			$formatLower = strtolower($this->format);
			if (preg_match('/graphicnovel|graphic novel|comic|ecomic|manga/',$formatLower)) {
				return 'comic';
			}
			if ($this->formatCategory == "Movies") {
				return 'movie';
			} elseif ($this->formatCategory == "Music") {
				return 'music';
			} elseif ($this->formatCategory == "Other") {
				return 'other';
			} else {
				return 'book';
			}
		}
		return parent::__get($name);
	}

	/**
	 * Override delete to ensure format map deletions trigger regrouping because
	 * the forcesRegroup flag exists on properties inside FormatMapValue objects,
	 * not on the parent relationship in IndexingProfile, which the standard deletion process cannot detect.
	 */
	public function delete(bool $useWhere = false, bool $hardDelete = false) : bool|int {
		// Check if we're deleting a specific record (i.e., not a bulk delete).
		if (!$useWhere && !empty($this->id)) {
			// Trigger regrouping if a format value is being deleted.
			$objectStructure = self::getObjectStructure();
			if (isset($objectStructure['format'])) {
				$this->handlePropertyChangeEffects('format', $this->format, null, $objectStructure['format'], 'deleted');
			}
		}

		// Call parent delete to perform the actual deletion.
		return parent::delete($useWhere, $hardDelete);
	}
}