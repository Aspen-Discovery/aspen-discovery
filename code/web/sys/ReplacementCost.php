<?php /** @noinspection PhpMissingFieldTypeInspection */

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class ReplacementCost extends DataObject {
	public $__table = 'replacement_costs';
	public $id;
	public $catalogFormat;
	public $replacementCost;

	/** @noinspection PhpUnusedParameterInspection */
	static function getObjectStructure($context = ''): array {
		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'catalogFormat' => [
				'property' => 'catalogFormat',
				'type' => 'text',
				'label' => 'Aspen Catalog Format',
				'description' => 'The format within the Aspen catalog.',
				'readOnly' => true
			],
			'replacementCost' => [
				'property' => 'replacementCost',
				'type' => 'currency',
				'displayFormat' => '%0.2f',
				'label' => 'Replacement Cost',
				'description' => 'The default replacement cost for the format.',
			],
		];
	}

	public static function loadActiveFormats() : void {
		//Automatically generate based on the data in the database.
		global $aspen_db;
		//Get the list of active formats from the database
		$loadDefaultFormatsStmt = "select trim(indexed_format.format) as format, MIN(permanent_id), count(grouped_work.id) as numWorks FROM grouped_work_record_items inner join grouped_work_records on groupedWorkRecordId = grouped_work_records.id join grouped_work_variation on grouped_work_variation.id = grouped_work_record_items.groupedWorkVariationId join indexed_format on grouped_work_variation.formatId = indexed_format.id join grouped_work on grouped_work_variation.groupedWorkId = grouped_work.id group by lower(trim(format));";
		$results = $aspen_db->query($loadDefaultFormatsStmt, PDO::FETCH_ASSOC);

		//Get the exising formats for the library
		$tmpReplacementCost = new ReplacementCost();
		//Load a list of active formats with the key being the format name and the value being the id
		$activeFormats = $tmpReplacementCost->fetchAll('catalogFormat', 'id');

		foreach ($results as $result) {
			//Check to see if we already have this format
			$formatExists = array_key_exists($result['format'], $activeFormats);

			if (!$formatExists) {
				$replacementCost = new ReplacementCost();
				$replacementCost->catalogFormat = $result['format'];
				$replacementCost->insert();
			}else{
				unset($activeFormats[$result['format']]);
			}
		}

		//Delete anything left over
		if (!empty($activeFormats)) {
			$replacementCost = new ReplacementCost();
			$replacementCost->whereAddIn('id', $activeFormats, false);
			$replacementCost->delete(true);
		}
	}

	private static $replacementCostsByFormat;
	/**
	 * Returns an array of replacement costs by lowercased format
	 *
	 * @return array
	 */
	static function getReplacementCostsByFormat() : array {
		if (ReplacementCost::$replacementCostsByFormat == null) {
			//Get the exising formats for the library
			$tmpReplacementCost = new ReplacementCost();
			//Load a list of active formats with the key being the lowercased format name and the value being the id
			$tmpReplacementCost->find();
			ReplacementCost::$replacementCostsByFormat = [];
			while ($tmpReplacementCost->fetch()) {
				ReplacementCost::$replacementCostsByFormat[strtolower($tmpReplacementCost->catalogFormat)] = $tmpReplacementCost->replacementCost;
			}
		}
		return ReplacementCost::$replacementCostsByFormat;
	}

	function getAdditionalListActions(): array {
		$objectActions = [];

		$objectActions[] = [
			'text' => 'Recalculate Historic Cost Savings',
			'onclick' => "return confirm('" . translate(['text'=>'Recalculating all costs savings will recalculate all savings so historic price changes will be lost. Proceed?', 'isAdminFacing' => true]). "')",
			'url' => '/Admin/ReplacementCosts?objectAction=recalculateHistoricCostSavings&format=' . urlencode($this->catalogFormat),
		];
		$objectActions[] = [
			'text' => 'Recalculate Zero Cost Savings',
			'onclick' => '',
			'url' => '/Admin/ReplacementCosts?objectAction=recalculateZeroCostSavings&format=' . urlencode($this->catalogFormat),
		];

		return $objectActions;
	}
}