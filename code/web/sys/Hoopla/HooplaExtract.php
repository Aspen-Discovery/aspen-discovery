<?php /** @noinspection PhpMissingFieldTypeInspection */

class HooplaExtract extends DataObject {
	public $id;
	public $hooplaId;
	public $active;
	public $title;
	public $kind;
	public $pa;  //Parental Advisory
	/** @noinspection PhpUnused */
	public $demo;
	/** @noinspection PhpUnused */
	public $profanity;
	public $rating; // eg TV parental guidance rating
	/** @noinspection PhpUnused */
	public $abridged;
	/** @noinspection PhpUnused */
	public $price;
	/** @noinspection PhpUnused */
	public $rawChecksum;
	public $rawResponse;
	public $dateFirstDetected;
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
		}else{
			$hooplaProduct = new HooplaExtract();
			$hooplaProduct->hooplaId = $identifier;
			if ($hooplaProduct->fetch()) {
				return $hooplaProduct;
			}else{
				return null;
			}
		}
	}
}