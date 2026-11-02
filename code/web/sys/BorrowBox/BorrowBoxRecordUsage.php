<?php /** @noinspection PhpMissingFieldTypeInspection */


require_once ROOT_DIR . '/sys/AbstractUsage.php';

/**
 * @property int|null $recordsUsed
 * @property int|null $recordsWithUsage
 * @property int|null $totalCheckouts
 * @property int|null $totalHolds
 */
class BorrowBoxRecordUsage extends AbstractUsage {
	public $__table = 'borrowbox_record_usage';
	public $id;
	public $instance;
	public $borrowboxId;
	public $year;
	public $month;
	public $day;
	public $timesHeld;
	public $timesCheckedOut;

	public function getUniquenessFields(): array {
		return [
			'instance',
			'borrowboxId',
			'year',
			'month',
			'day',
		];
	}

	public function okToExport(array $selectedFilters): bool {
		$okToExport = parent::okToExport($selectedFilters);
		if (in_array($this->instance, $selectedFilters['instances'])) {
			$okToExport = true;
		}
		return $okToExport;
	}
}
