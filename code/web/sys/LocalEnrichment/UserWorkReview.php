<?php
/**
 * Table Definition for User Ratings
 */
require_once ROOT_DIR . '/sys/DB/DataObject.php';
class UserWorkReview extends DataObject
{
  public $__table = 'user_work_review';    // table name
  public $id;                       //int(11)
	public $groupedRecordPermanentId; //varchar(36)
  public $userId;                   //int(11)
  public $rating;                   //int(5)
	public $review;                  //MEDIUM TEXT
	public $dateRated;
}