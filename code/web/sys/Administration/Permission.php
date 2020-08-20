<?php


class Permission extends DataObject
{
	public $__table = 'permissions';// table name
	public $id;
	public $name;
	public $sectionName;
	public $description;

	static function getObjectStructure()
	{
		return array(
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id of the permission within the database'),
			'name' => array('property' => 'name', 'type' => 'text', 'label' => 'Name', 'maxLength' => 50, 'description' => 'The full name of the permission.'),
			'description' => array('property' => 'name', 'type' => 'text', 'label' => 'Name', 'maxLength' => 100, 'description' => 'A description of the permission.'),
		);
	}
}