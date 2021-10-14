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

	static function getObjectStructure() : array {
		return [
			'id' => array('property'=>'id', 'type'=>'hidden', 'label'=>'Id', 'description'=>'The unique id'),
			'weight' => array('property'=>'weight', 'type'=>'integer', 'label'=>'Weight', 'description'=>'The sort order', 'default' => 0),
			'code' => array('property'=>'code', 'type'=>'text', 'label'=>'Code', 'description'=>'The code for the language see https://www.w3schools.com/tags/ref_language_codes.asp', 'size'=>'3', 'required' => true),
			'displayName' => array('property'=>'displayName', 'type'=>'text', 'label'=>'Display name - native', 'description'=>'Display Name for the language in the language itself', 'size'=>'50', 'required' => true),
			'displayNameEnglish' => array('property'=>'displayNameEnglish', 'type'=>'text', 'label'=>'Display name - English', 'description'=>'The display name of the language in English', 'size'=>'50', 'required' => true),
			'locale' => array('property' => 'locale', 'type' => 'text', 'label' => 'Locale (i.e. en-US, en-CA, es-US, fr-CA)', 'description'=>'The locale to use when formatting numbers', 'default' => 'en-US', 'required' => true),
			'facetValue' => array('property'=>'facetValue', 'type'=>'text', 'label'=>'Facet Value', 'description'=>'The facet value for filtering results and applying preferences', 'size'=>'100', 'required' => true),
			'displayToTranslatorsOnly' => array('property'=>'displayToTranslatorsOnly', 'type'=>'checkbox', 'label'=>'Display To Translators Only', 'description'=>'Whether or not only translators should see the translation (good practice before the translation is completed)', 'default' => 0),
		];
	}

	public static function getLanguageList()
	{
		$language = new Language();
		$language->orderBy('displayName');
		$language->find();
		$languageList = [];
		while ($language->fetch()) {
			$languageList[$language->id] = $language->displayName;
		}
		return $languageList;
	}

	public function getNumericColumnNames() : array
	{
		return ['id', 'weight'];
	}
}