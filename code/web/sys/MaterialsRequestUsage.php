<?php


class MaterialsRequestUsage extends DataObject {
	public $__table = 'materials_request_usage';
	public $id;
	public $locationId;
	public $year;
	public $month;
	public $statusId;
	public $numUsed;

	public function getUniquenessFields(): array {
		return [
			'locationId',
			'year',
			'month',
			'statusId',
		];
	}

	static function incrementStat($status, $homeLocation) {
		try {
			$materialsRequestUsage = new MaterialsRequestUsage();
			$materialsRequestUsage->year = date('Y');
			$materialsRequestUsage->month = date('n');
			$materialsRequestUsage->locationId = $homeLocation;
			$materialsRequestUsage->statusId = $status;
			if ($materialsRequestUsage->find(true)) {
				$materialsRequestUsage->numUsed++;
				$materialsRequestUsage->update();
			} else {
				$materialsRequestUsage->numUsed = 1;
				$materialsRequestUsage->insert();
			}
		} catch (PDOException $e) {
			//This happens if the table has not been created, ignore it
		}
	}
}