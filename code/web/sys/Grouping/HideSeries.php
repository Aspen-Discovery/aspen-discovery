<?php /** @noinspection PhpMissingFieldTypeInspection */


class HideSeries extends DataObject {
	public $__table = 'hide_series';
	public $id;
	public $seriesTerm;
	public $seriesNormalized;
	public $dateAdded;

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
			'seriesTerm' => [
				'property' => 'seriesTerm',
				'type' => 'text',
				'label' => 'Hide Series Term',
				'description' => 'Series term to hide',
				'autocomplete' => 'off',
				'forcesReindex' => true,
			],
			'seriesNormalized' => [
				'property' => 'seriesNormalized',
				'type' => 'text',
				'label' => 'Hide Series Term, normalized',
				'description' => 'Series term to hide, normalized',
				'readOnly' => true,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	public function insert(string $context = '') : int|bool {
		$this->seriesNormalized = $this->normalizeSeries($this->seriesTerm);
		return parent::insert();
	}

	public function update(string $context = '') : int|bool {
		$this->__set("seriesNormalized", $this->normalizeSeries($this->seriesTerm));
		return parent::update();
	}

	public function normalizeSeries($seriesTerm): string {
		$seriesTerm = rtrim($seriesTerm, '- .,;');
		$seriesTerm = preg_replace('/[#|]\s*\d+$/','',$seriesTerm);
		$seriesTerm = preg_replace('/ & /', ' and ', $seriesTerm);
		$seriesTerm = preg_replace('/--/',' ',$seriesTerm);
		$seriesTerm = preg_replace('/,\s+(the|an)$/','',$seriesTerm);
		$seriesTerm = preg_replace('/[:,]\s/','',$seriesTerm);
		$seriesTerm = preg_replace('/(?i)\s+series$/','',$seriesTerm);
		return rtrim($seriesTerm, '- .,;');
	}
}