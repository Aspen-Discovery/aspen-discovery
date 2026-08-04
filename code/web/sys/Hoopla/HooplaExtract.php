<?php /** @noinspection PhpMissingFieldTypeInspection */

class HooplaExtract extends DataObject {
	public $id;
	public $hooplaId;
	public $title;
	public $format;
	public $pa;  //Parental Advisory
	/** @noinspection PhpUnused */
	public $demo;
	/** @noinspection PhpUnused */
	public $profanity;
	public $rating; // eg TV parental guidance rating
	/** @noinspection PhpUnused */
	public $abridged;
	/** @noinspection PhpUnused */
	public $ppuPrice;
	/** @noinspection PhpUnused */
	public $rawChecksum;
	public $rawResponse;
	public $dateFirstDetected;

	// Legacy Hoopla v1 columns
	public $active;
	public $kind;
	public $price;
	public $hooplaType;

	public $__table = 'hoopla_export';

	public function getCompressedColumnNames(): array {
		return ['rawResponse'];
	}

	/** @var HooplaExtract[] */
	private static $_preloadedTitles = [];
	/**
	 * Preloads products for a list of identifiers using minimal database queries
	 *
	 * @param array $identifiers
	 * @return void
	 */
	static function preloadTitles(array $identifiers) : void {
		foreach ($identifiers as $identifier) {
			if (!isset(self::$_preloadedTitles[$identifier])) {
				self::$_preloadedTitles[$identifier] = null;
			}
		}
		$hooplaProducts = new HooplaExtract();
		$hooplaProducts->whereAddIn('hooplaId', $identifiers, true);
		$allHooplaProducts = $hooplaProducts->fetchAll();
		foreach ($allHooplaProducts as $hooplaProduct) {
			self::$_preloadedTitles[$hooplaProduct->hooplaId] = $hooplaProduct;
		}
	}

	/**
	 * @param string $identifier
	 * @return ?HooplaExtract
	 */
	static function getHooplaTitleForId(string $identifier) : ?HooplaExtract {
		if (isset(self::$_preloadedTitles[$identifier])) {
			return self::$_preloadedTitles[$identifier];
		}

		$hooplaProduct = new HooplaExtract();
		$hooplaProduct->hooplaId = $identifier;
		if ($hooplaProduct->find(true)) {
			return $hooplaProduct;
		}else{
			return null;
		}
	}

	// Override the find method to only select the columns that exist
	// TODO: Remove this block once v1 is retired
	public function find($fetchFirst = false, $requireOneMatchToReturn = false, $hasPredefinedSelects = false): bool {
		if (!$hasPredefinedSelects) {
			$actualColumns = $this->table();

			$selectParts = [];
			foreach ($actualColumns as $columnName => $columnInfo) {
				if ($columnName === 'rawResponse') {
					$selectParts[] = 'UNCOMPRESS(' . $this->__table . '.rawResponse) as rawResponse';
				} else {
					$selectParts[] = $this->__table . '.' . $columnName;
				}
			}

			$this->selectAdd();
			$this->selectAdd(implode(', ', $selectParts));
		}

		return parent::find($fetchFirst, $requireOneMatchToReturn);
	}
}