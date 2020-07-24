<?php

class RosenLevelUPSetting extends DataObject
{
	public $__table = 'rosen_levelup_settings';
	public $id;
	public $lu_api_host;
    public $lu_api_pw;
    public $lu_api_un;
    public $lu_district_name;
    public $lu_eligible_ptypes;
    public $lu_multi_district_name;
    public $lu_school_name;

	public static function getObjectStructure()
	{
		$structure = array(
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id'),
			'lu_eligible_ptypes' => array('property' => 'lu_eligible_ptypes', 'type' => 'text', 'label' => 'PTypes that can register for Rosen LevelUP', 'description' => 'A list of P-Types that can register for Rosen LevelUP -- or * to allow all P-Types.'),
			'lu_api_host' => array('property' => 'lu_api_host', 'type' => 'text', 'label' => 'LevelUP API Host', 'description' => 'The domain of the LevelUP API server'),
			'lu_api_un' => array('property' => 'lu_api_un', 'type' => 'text', 'label' => 'LevelUP API Username', 'description' => 'The username to connect to the LevelUP API Server'),
			'lu_api_pw' => array('property' => 'lu_api_pw', 'type' => 'storedPassword', 'label' => 'LevelUP API Password', 'description' => 'The password to connect to the LevelUP API Server', 'hideInLists' => true),
            'lu_multi_district_name' => array('property' => 'lu_multi_district_name', 'type' => 'text', 'label' => 'LevelUP Multi-District Name', 'description' => 'The label name of the multi-district organization.'),
            'lu_district_name' => array('property' => 'lu_district_name', 'type' => 'text', 'label' => 'LevelUP District Name', 'description' => 'The label name of the school district.'),
            'lu_school_name' => array('property' => 'lu_school_name', 'type' => 'text', 'label' => 'LevelUP School Name', 'description' => 'The domain of the school.'),
		);
		return $structure;
	}
}