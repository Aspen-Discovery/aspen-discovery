<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';
class Role extends DataObject
{
	public $__table = 'roles';// table name
    public $__primaryKey = 'roleId';
	public $roleId;                        //int(11)
	public $name;                     //varchar(50)
	public $description;              //varchar(100)

	function keys() {
		return array('roleId');
	}

    static function getObjectStructure(){
		$structure = array(
          'roleId' => array('property'=>'roleId', 'type'=>'label', 'label'=>'Role Id', 'description'=>'The unique id of the role within the database'),
          'name' => array('property'=>'name', 'type'=>'text', 'label'=>'Name', 'maxLength'=>50, 'description'=>'The full name of the role.'),
          'description' => array('property'=>'name', 'type'=>'text', 'label'=>'Name', 'maxLength'=>100, 'description'=>'The full name of the role.'),
		);
		return $structure;
	}

	static function getLookup(){
		$role = new Role();
		$role->orderBy('name');
		$role->find();
		$roleList = array();
		while ($role->fetch()){
			$roleList[$role->roleId] = $role->name . ' - ' . $role->description;
		}
		return $roleList;
	}
}