<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';
class LoanRule extends DataObject
{
	public $__table = 'loan_rules';   // table name
	public $id;
	public $loanRuleId;
	public $name;
	public $code;
	public $normalLoanPeriod;
	public $holdable;
	public $bookable;
	public $homePickup;
	public $shippable;

	function keys() {
		return array('iId');
	}

    static function getObjectStructure(){
		$structure = array(
			'id' => array('property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id of the p-type within the database', 'hideInLists' => true),
			'loanRuleId' => array('property'=>'loanRuleId', 'type'=>'integer', 'label'=>'Loan Rule Id', 'description'=>'The id of the loan rule', 'hideInLists' => false),
			'name' => array('property'=>'name', 'type'=>'text', 'label'=>'Name', 'description'=>'A name for the loan rule'),
			'code' => array('property'=>'code', 'type'=>'text', 'label'=>'Code', 'description'=>'The code for the loan rule'),
			'normalLoanPeriod' => array('property'=>'normalLoanPeriod', 'type'=>'integer', 'label'=>'Normal Loan Period', 'description'=>'The normal loan period for the loan rule'),
			'holdable' => array('property'=>'holdable', 'type'=>'checkbox', 'label'=>'Holdable', 'description'=>'Whether or not items are holdable'),
			'bookable' => array('property'=>'bookable', 'type'=>'checkbox', 'label'=>'Bookable', 'description'=>'Whether or not items are bookable'),
			'homePickup' => array('property'=>'homePickup', 'type'=>'checkbox', 'label'=>'Home Pickup', 'description'=>'Whether or not items are available for Home Pickup', 'hideInLists' => true),
			'shippable' => array('property'=>'shippable', 'type'=>'checkbox', 'label'=>'Shippable', 'description'=>'Whether or not items are shippable', 'hideInLists' => true),
		);
		return $structure;
	}

	function insert(){
		parent::insert();
		global $memCache;
		global $instanceName;
		$memCache->delete($instanceName . '_loan_rules');
	}

	function update(){
		parent::update();
		global $memCache;
		global $instanceName;
		$memCache->delete($instanceName . '_loan_rules');
	}
}