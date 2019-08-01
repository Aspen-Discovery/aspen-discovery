<?php

class FormatMapValue extends DataObject{
	public $__table = 'format_map_values';    // table name
	public $id;
	public $indexingProfileId;
	public $value;
	public $format;
	public $formatCategory;
	public $formatBoost;
	public $suppress;

    static function getObjectStructure(){
	    $formatCategories = [
	    	'Audio Books' => 'Audio Books',
	    	'Books' => 'Books',
	    	'eBook' => 'eBook',
		    'Movies' => 'Movies',
		    'Music' => 'Music',
			'Other' => 'Other',
	    ];
		$structure = array(
			'id' => array('property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id within the database'),
			'indexingProfileId' => array('property' => 'indexingProfileId', 'type' => 'foreignKey', 'label' => 'Indexing Profile Id', 'description' => 'The Profile this is associated with'),
			'value' => array('property'=>'value', 'type'=>'text', 'label'=>'Value', 'description'=>'The value to be translated', 'maxLength' => '50', 'required' => true),
			'format' => array('property'=>'format', 'type'=>'text', 'label'=>'Format', 'description'=>'The detailed format', 'maxLength' => '255', 'required' => true),
			'formatCategory' => array('property'=>'formatCategory', 'type'=>'enum', 'label'=>'Format Category', 'description'=>'The Format Category', 'values' => $formatCategories, 'required' => true),
			'formatBoost' => array('property'=>'formatBoost', 'type'=>'integer', 'label'=>'Format Boost', 'description'=>'The Format Boost to apply during indexing', 'default' => 1, 'required' => true),
			'suppress' => array('property'=>'suppress', 'type'=>'checkbox', 'label'=>'Suppress?', 'description'=>'Suppress from the catalog', 'default' => 0, 'required' => true),
		);
		return $structure;
	}
}