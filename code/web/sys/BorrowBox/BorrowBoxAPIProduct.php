<?php /** @noinspection PhpMissingFieldTypeInspection */

class BorrowBoxAPIProduct extends DataObject {
	public $__table = 'borrowbox_api_products';

	public $id;
	public $borrowboxId;
	public $isbn13;
	public $mediaType;
	public $title;
	public $subtitle;
	public $series;
	/** @noinspection PhpUnused */
	public $seriesNumber;
	public $primaryCreatorName;
	public $cover;
	public $dateAdded;
	public $dateUpdated;
	/** @noinspection PhpUnused */
	public $lastMetadataCheck;
	/** @noinspection PhpUnused */
	public $lastMetadataChange;
	public $deleted;
	/** @noinspection PhpUnused */
	public $dateDeleted;
	/** @noinspection PhpUnused */
	public $lastSeen;

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
		$borrowBoxProducts = new BorrowBoxAPIProduct();
		$borrowBoxProducts->whereAddIn('borrowboxId', $identifiers, true);
		$allBorrowBoxProducts = $borrowBoxProducts->fetchAll();
		foreach ($allBorrowBoxProducts as $borrowBoxProduct) {
			self::$_preloadedProducts[$borrowBoxProduct->borrowboxId] = $borrowBoxProduct;
		}
	}

	/**
	 * @param string $borrowboxId
	 * @return ?BorrowBoxAPIProduct
	 */
	static function getBorrowBoxProductForId(string $borrowboxId) : ?BorrowBoxAPIProduct {
		if (isset(self::$_preloadedProducts[$borrowboxId])) {
			return self::$_preloadedProducts[$borrowboxId];
		}
		$borrowBoxProduct = new BorrowBoxAPIProduct();
		$borrowBoxProduct->borrowboxId = $borrowboxId;
		if (!$borrowBoxProduct->find(true)) {
			return null;
		}
		return $borrowBoxProduct;
	}
}
