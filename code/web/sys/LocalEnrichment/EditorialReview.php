<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';
class EditorialReview extends DataObject {
	public $__table = 'editorial_reviews';    // table name
    public $__primaryKey = 'editorialReviewId';
	public $editorialReviewId;
	public $recordId;
	public $title;

	public $review;
	public $teaser;
	public $source;
	public $pubDate;

	public $tabName;

	function keys() {
		return array('editorialReviewId', 'source');
	}

	function formattedPubDate() {

		$publicationDate = getDate($this->pubDate);
		$pDate = $publicationDate["mon"]."/".$publicationDate["mday"]."/".$publicationDate["year"];
		return $pDate;
	}

	static function getObjectStructure(){
		$structure = array(
		array(
			'property'=>'editorialReviewId',
			'type'=>'hidden',
			'label'=>'Id',
			'description'=>'The unique id of the editorial review in the database',
			'storeDb' => true,
			'primaryKey' => true,
		),
		array(
			'property'=>'title',
			'type'=>'text',
			'size' => 100,
			'maxLength'=>100,
			'label'=>'Title',
			'description'=>'The title of the review is required.',
			'storeDb' => true,
			'required' => true,
		),
		array(
			'property'=>'teaser',
			'type'=>'textarea',
			'rows'=>3,
			'cols'=>80,
			'size' => 512,
			'label'=>'Teaser (can be omitted to use the first part of the review)',
			'description'=>'Teaser for the review.',
			'storeDb' => true,
		),
		array(
			'property'=>'review',
			'type'=>'html',
			'allowableTags' => '<p><a><b><em><ul><ol><em><li><strong><i><br><iframe><div>',
			'rows'=>6,
			'cols'=>80,
			'label'=>'Review',
			'description'=>'Review.',
			'storeDb' => true,
		),
		array(
			'property'=>'source',
			'type'=>'text',
			'size' => 25,
			'maxLength'=>25,
			'label'=>'Source',
			'description'=>'Source.',
			'storeDb' => true,
		),
		array(
			'property'=>'tabName',
			'type'=>'text',
			'size' => 25,
			'maxLength'=>25,
			'label'=>'Tab Name',
			'description'=>'The Tab to display the review on',
			'default' => 'Reviews',
			'storeDb' => true,
		),
		'recordId' => array(
			'property'=>'recordId',
			'type'=>'text',
			'size' => 36,
			'maxLength'=>36,
			'label'=>'Record Id',
			'description'=>'Record Id.',
			'storeDb' => true,
		),
		'pubDate' => array(
			'property'=>'pubDate',
			'type'=>'hidden',
			'label'=>'pubDate',
			'description'=>'pubDate',
			'storeDb' => true,
		),
		);
		return $structure;
	}

	function insert(){
		//Update publication date if it hasn't been set already.
		if (!isset($this->pubDate)){
			$this->pubDate = time();
		}

		$ret = parent::insert();

		return $ret;
	}

	function update(){
		$ret =  parent::update();

		return $ret;
	}

	function delete($useWhere = false){
		$ret =  parent::delete($useWhere);

		return $ret;
	}
}