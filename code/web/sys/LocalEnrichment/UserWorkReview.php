<?php
/**
 * Table Definition for User Ratings
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';

class UserWorkReview extends DB_DataObject
{
  public $__table = 'user_work_review';    // table name
  public $id;                       //int(11)
	public $groupedRecordPermanentId; //varchar(36)
  public $userId;                   //int(11)
  public $rating;                   //int(5)
	public $review;                  //MEDIUM TEXT
	public $dateRated;
}