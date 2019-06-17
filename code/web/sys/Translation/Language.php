<?php

class Language extends DataObject
{
	public $__table = 'languages';
	public $id;
	public $weight;
	public $code;
	public $displayName;
	public $displayNameEnglish;
	public $facetValue;

	static function getObjectStructure(){
		return [
			'id' => array('property'=>'id', 'type'=>'hidden', 'label'=>'Id', 'description'=>'The unique id'),
			'weight' => array('property'=>'weight', 'type'=>'integer', 'label'=>'Weight', 'description'=>'The sort order', 'default' => 0),
			'code' => array('property'=>'code', 'type'=>'text', 'label'=>'Code', 'description'=>'The code for the language see https://www.w3schools.com/tags/ref_language_codes.asp', 'size'=>'3'),
			'displayName' => array('property'=>'displayName', 'type'=>'text', 'label'=>'Display name - native', 'description'=>'Display Name for the language in the language itself', 'size'=>'50'),
			'displayNameEnglish' => array('property'=>'displayNameEnglish', 'type'=>'text', 'label'=>'Display name - English', 'description'=>'The url of the open archives site', 'size'=>'50'),
			'facetValue' => array('property'=>'facetValue', 'type'=>'text', 'label'=>'Facet Value', 'description'=>'The facet value for filtering results and applying preferences', 'size'=>'100'),
		];
	}

	public function getNumericColumnNames()
	{
		return ['id', 'weight'];
	}
}