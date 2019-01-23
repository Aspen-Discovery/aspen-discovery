<?php
/**
 * Table Definition for library
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';

class PType extends DB_DataObject
{
	public $__table = 'ptype';   // table name
	public $id;
	public $pType;				//varchar(45)
	public $maxHolds;			//int(11)
	public $masquerade;   //varchar(45)

	static $masqueradeLevels = array(
		'none'     => 'No Masquerade',
		'location' => 'Masquerade as Patrons of home branch',
	  'library'  => 'Masquerade as Patrons of home library',
		'any'      => 'Masquerade as any user'
	);

	function keys() {
		return array('id');
	}

	function getObjectStructure(){
		$structure        = array(
			'id'         => array('property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id of the p-type within the database', 'hideInLists' => false),
			'pType'      => array('property'=>'pType', 'type'=>'text', 'label'=>'P-Type', 'description'=>'The P-Type for the patron'),
			'maxHolds'   => array('property'=>'maxHolds', 'type'=>'integer', 'label'=>'Max Holds', 'description'=>'The maximum holds that a patron can have.', 'default' => 300),
			'masquerade' => array('property' => 'masquerade', 'type'=> 'enum', 'values' => self::$masqueradeLevels, 'label'=>'Masquerade Level', 'description'=>'The level at which this ptype can masquerade at', 'default'=>'none')
		);
		return $structure;
	}
}