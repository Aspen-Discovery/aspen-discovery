<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class HideSeries extends DataObject {
	public $__table = 'hide_series';
	public $id;
	public $seriesTerm;
	public $seriesNormalized;
	public $dateAdded;

	static function getObjectStructure($context = ''): array {
		return [
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
	}

	public function insert($context = '') {
		$this->seriesNormalized = $this->normalizeSeries($this->seriesTerm);
		return parent::insert();
	}

	public function update($context = '') {
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
        $seriesTerm = rtrim($seriesTerm, '- .,;');
        return $seriesTerm;
	}
}