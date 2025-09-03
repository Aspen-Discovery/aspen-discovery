<?php /** @noinspection PhpMissingFieldTypeInspection */

class Axis360Title extends DataObject {
	public $__table = 'axis360_title';

	public $id;
	public $axis360Id;
	public $isbn;
	public $title;
	public $subtitle;
	public $primaryAuthor;
	/** @noinspection PhpUnused */
	public $formatType;
	/** @noinspection PhpUnused */
	public $rawChecksum;
	public $rawResponse;
	/** @noinspection PhpUnused */
	public $lastChange;
	/** @noinspection PhpUnused */
	public $dateFirstDetected;
	public $deleted;

	/** @var Axis360Title[] */
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
		$axis360Products = new Axis360Title();
		$axis360Products->whereAddIn('axis360Id', $identifiers, true);
		$allAxis360Products = $axis360Products->fetchAll();
		foreach ($allAxis360Products as $axis360Product) {
			self::$_preloadedTitles[$axis360Product->axis360Id] = $axis360Product;
		}
	}

	/**
	 * @param string $identifier
	 * @return ?Axis360Title
	 */
	static function getAxis360TitleForId(string $identifier) : ?Axis360Title {
		if (isset(self::$_preloadedTitles[$identifier])) {
			return self::$_preloadedTitles[$identifier];
		}else{
			$axis360Product = new Axis360Title();
			$axis360Product->axis360Id = $identifier;
			if ($axis360Product->fetch()) {
				return $axis360Product;
			}else{
				return null;
			}
		}
	}
}