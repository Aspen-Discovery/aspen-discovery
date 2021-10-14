<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class UserWorkReview extends DataObject
{
	public $__table = 'user_work_review';
	public $id;
	public $groupedRecordPermanentId;
	public $userId;
	public $rating;
	public $review;
	public $dateRated;
	public $importedFrom;

	private $_displayName;

	/**
	 * @return mixed
	 */
	public function getDisplayName()
	{
		return $this->_displayName;
	}

	/**
	 * @param mixed $displayName
	 */
	public function setDisplayName($displayName): void
	{
		$this->_displayName = $displayName;
	}
}