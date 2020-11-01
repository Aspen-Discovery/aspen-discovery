<?php

class Language extends DataObject
{
	public $__table = 'languages';
	public $id;
	public $weight;
	public $code;
	public $displayName;
	public $displayNameEnglish;
	public $locale;
	public $facetValue;
	public $displayToTranslatorsOnly;

	static function getObjectStructure(){
		return [
			'id' => array('property'=>'id', 'type'=>'hidden', 'label'=>'Id', 'description'=>'The unique id'),
			'weight' => array('property'=>'weight', 'type'=>'integer', 'label'=>'Weight', 'description'=>'The sort order', 'default' => 0),
			'code' => array('property'=>'code', 'type'=>'text', 'label'=>'Code', 'description'=>'The code for the language see https://www.w3schools.com/tags/ref_language_codes.asp', 'size'=>'3'),
			'displayName' => array('property'=>'displayName', 'type'=>'text', 'label'=>'Display name - native', 'description'=>'Display Name for the language in the language itself', 'size'=>'50'),
			'displayNameEnglish' => array('property'=>'displayNameEnglish', 'type'=>'text', 'label'=>'Display name - English', 'description'=>'The url of the open archives site', 'size'=>'50'),
			'locale' => array('property' => 'locale', 'type' => 'text', 'label' => 'Locale (i.e. en-US, en-CA, es-US, fr-CA)', 'description'=>'The locale to use when formatting numbers', 'default' => 'en-US'),
			'facetValue' => array('property'=>'facetValue', 'type'=>'text', 'label'=>'Facet Value', 'description'=>'The facet value for filtering results and applying preferences', 'size'=>'100'),
			'displayToTranslatorsOnly' => array('property'=>'displayToTranslatorsOnly', 'type'=>'checkbox', 'label'=>'Display To Translators Only', 'description'=>'Whether or not only translators should see the translation (good practice before the translation is completed)', 'default' => 0),
		];
	}

	public function getNumericColumnNames()
	{
		return ['id', 'weight'];
	}
}