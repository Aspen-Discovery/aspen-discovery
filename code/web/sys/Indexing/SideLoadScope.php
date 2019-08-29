<?php

require_once ROOT_DIR . '/sys/Indexing/SideLoad.php';
class SideLoadScope extends DataObject
{
	public $__table = 'sideload_scopes';
	public $id;
	public $name;
	public $sideLoadId;
	public $restrictToChildrensMaterial;

	public static function getObjectStructure()
	{
		$validSideLoads = [];
		$sideLoad = new SideLoad();
		$sideLoad->orderBy('name');
		$sideLoad->find();
		while ($sideLoad->fetch()){
			$validSideLoads[$sideLoad->id] = $sideLoad->name;
		}
		$structure = array(
			'id' => array('property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id'),
			'name' => array('property'=>'name', 'type'=>'text', 'label'=>'Name', 'description'=>'The Name of the scope', 'maxLength' => 50),
			'sideLoadId' => array('property' => 'sideLoadId', 'type' => 'enum', 'values'=>$validSideLoads, 'label' => 'Side Load Settings', 'description' =>'The Side Load to apply the scope to'),
			'restrictToChildrensMaterial' => array('property'=>'restrictToChildrensMaterial', 'type'=>'checkbox', 'label'=>'Include Children\'s Materials Only', 'description'=>'If checked only includes titles identified as children by RBdigital', 'default'=>0),
		);
		return $structure;
	}
}
