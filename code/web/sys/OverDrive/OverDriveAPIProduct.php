<?php /** @noinspection PhpMissingFieldTypeInspection */

class OverDriveAPIProduct extends DataObject {
	public $__table = 'overdrive_api_products';   // table name

	public $id;
	public $overdriveId;
	public $crossRefId;
	/** @noinspection PhpUnused */
	public $mediaType;
	public $title;
	public $subtitle;
	public $series;
	/** @noinspection PhpUnused */
	public $primaryCreatorRole;
	public $primaryCreatorName;
	public $cover;
	public $dateAdded;
	public $dateUpdated;
	/** @noinspection PhpUnused */
	public $lastMetadataCheck;
	/** @noinspection PhpUnused */
	public $lastMetadataChange;
	/** @noinspection PhpUnused */
	public $lastAvailabilityCheck;
	/** @noinspection PhpUnused */
	public $lastAvailabilityChange;
	public $deleted;
	/** @noinspection PhpUnused */
	public $dateDeleted;

	private static $_preloadedProducts = [];

	/**
	 * Preloads products for a list of identifiers using minimal database queries
	 *
	 * @param array $identifiers
	 * @return void
	 */
	static function preloadProducts(array $identifiers) : void {
		foreach ($identifiers as $identifier) {
			if (!isset(self::$_preloadedProducts[$identifier])) {
				self::$_preloadedProducts[$identifier] = null;
			}
		}
		$overDriveProducts = new OverDriveAPIProduct();
		$overDriveProducts->whereAddIn('overdriveId', $identifiers, true);
		$allOverDriveProducts = $overDriveProducts->fetchAll();
		foreach ($allOverDriveProducts as $overDriveProduct) {
			self::$_preloadedProducts[$overDriveProduct->overdriveId] = $overDriveProduct;
		}
	}

	/**
	 * @param string $identifier
	 * @return ?OverDriveAPIProduct
	 */
	static function getOverDriveProductForId(string $identifier) : ?OverDriveAPIProduct {
		if (isset(self::$_preloadedProducts[$identifier])) {
			return self::$_preloadedProducts[$identifier];
		}else{
			$overDriveProduct = new OverDriveAPIProduct();
			$overDriveProduct->overdriveId = $identifier;
			if ($overDriveProduct->fetch()) {
				return $overDriveProduct;
			}else{
				return null;
			}
		}
	}
}