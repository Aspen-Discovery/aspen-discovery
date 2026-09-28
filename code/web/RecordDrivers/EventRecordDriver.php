<?php

require_once 'IndexRecordDriver.php';
require_once ROOT_DIR . '/sys/Utils/DateUtils.php';

abstract class EventRecordDriver extends IndexRecordDriver {
	public abstract function getBranch();
	public abstract function getStartDate();
	public abstract function getStartDateFromDB(string $id);
	public abstract function getTitleFromDB(string $id);

	public function getEventDateCoverData() : array {
		$startDate = $this->getStartDate();
		return [
			'title' => $this->getTitle(),
			'props' => [
				'eventDate' => $startDate,
				'isPastEvent' => DateUtils::isPastDate($startDate),
				'branch' => $this->getBranch(),
				'displayBranchOnThumbnail' => false,
			],
		];
	}

	public function getEventDateCoverDataFromDB(string $id) : array {
		$startDate = $this->getStartDateFromDB($id);
		return [
			'title' => $this->getTitleFromDB($id),
			'props' => [
				'eventDate' => $startDate,
				'isPastEvent' => DateUtils::isPastDate($startDate),
				'branch' => '',
				'displayBranchOnThumbnail' => false,
			],
		];
	}
}
