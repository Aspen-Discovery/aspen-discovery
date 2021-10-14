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

	public static function getObjectStructure() : array
	{
		$structure = array(
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id'),
			'lu_eligible_ptypes' => array('property' => 'lu_eligible_ptypes', 'type' => 'text', 'label' => 'PTypes that can register for Rosen LevelUP', 'maxLength' => 50, 'description' => 'A list of P-Types that can register for Rosen LevelUP -- or * to allow all P-Types.'),
			'lu_ptypes_k' => array('property' => 'lu_ptypes_k', 'type' => 'text', 'label' => 'Kindergarten Patron Types REGEX', 'maxLength' => 50, 'description' => 'A regular expression for ptypes that should be registered in LevelUP as Kindergartener'),
			'lu_ptypes_1' => array('property' => 'lu_ptypes_1', 'type' => 'text', 'label' => 'First Grade Patron Types REGEX', 'maxLength' => 50, 'description' => 'A regular expression for ptypes that should be registered in LevelUP as First Graders'),
			'lu_ptypes_2' => array('property' => 'lu_ptypes_2', 'type' => 'text', 'label' => 'Second Grade Patron Types REGEX', 'maxLength' => 50, 'description' => 'A regular expression for ptypes that should be registered in LevelUP as Second Graders'),
			'lu_api_host' => array('property' => 'lu_api_host', 'type' => 'text', 'label' => 'LevelUP API Host', 'maxLength' => 50, 'description' => 'The domain of the LevelUP API server'),
			'lu_api_un' => array('property' => 'lu_api_un', 'type' => 'text', 'label' => 'LevelUP API Username', 'maxLength' => 50, 'description' => 'The username to connect to the LevelUP API Server'),
			'lu_api_pw' => array('property' => 'lu_api_pw', 'type' => 'storedPassword', 'label' => 'LevelUP API Password', 'maxLength' => 50, 'description' => 'The password to connect to the LevelUP API Server', 'hideInLists' => true),
			'lu_multi_district_name' => array('property' => 'lu_multi_district_name', 'type' => 'text', 'label' => 'LevelUP Multi-District Name', 'maxLength' => 50, 'description' => 'The label name of the multi-district organization.'),
			'lu_district_name' => array('property' => 'lu_district_name', 'type' => 'text', 'label' => 'LevelUP District Name', 'maxLength' => 50, 'description' => 'The label name of the school district.'),
			'lu_school_name' => array('property' => 'lu_school_name', 'type' => 'text', 'label' => 'LevelUP School Name', 'maxLength' => 50, 'description' => 'The domain of the school.'),
			'lu_location_code_prefix' => array('property' => 'lu_location_code_prefix', 'type' => 'text', 'label' => 'School Location Code Prefix', 'maxLength' => 50, 'description' => 'Rosen LevelUP requires school values be not just numbers. Coordinate with Rosen to add a text prefix, e.g., Amqui Elementary location code "105" becomes "Nashville 105"'),
		);
		return $structure;
	}
}