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

	public abstract function getEventDateCoverType() : string;

	public function getBookcoverUrl($size = 'small', $absolutePath = false) : string {
		global $configArray;

		$bookCoverUrl = $absolutePath ? $configArray['Site']['url'] : '';
		$type = $this->getEventDateCoverType();

		return $bookCoverUrl . "/bookcover.php?id={$this->getEventCoverId()}&size={$size}&type={$type}&fingerprint={$this->getEventDateCoverFingerprint()}";
	}

	protected function getEventCoverId() {
		return $this->getUniqueID();
	}

	public function getEventDateCoverFingerprint() : string {
		return self::buildEventDateCoverFingerprint($this->getEventDateCoverData());
	}

	public static function buildEventDateCoverFingerprint(array $eventDateCoverData) : string {
		$props = $eventDateCoverData['props'];
		$eventDate = $props['eventDate'] instanceof DateTime ? $props['eventDate']->getTimestamp() : '';

		return substr(md5(implode('|', [
			$eventDateCoverData['title'],
			$eventDate,
			$props['branch'],
			$props['displayBranchOnThumbnail'],
			$props['isPastEvent'],
		])), 0, 8);
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
