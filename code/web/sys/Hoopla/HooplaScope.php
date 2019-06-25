<?php


class HooplaScope extends DataObject
{
	public $__table = 'hoopla_scopes';
	public $id;
	public $name;
	public $includeEBooks;
	public $maxCostPerCheckoutEBooks;
	public $includeEComics;
	public $maxCostPerCheckoutEComics;
	public $includeEAudiobook;
	public $maxCostPerCheckoutEAudiobook;
	public $includeMovies;
	public $maxCostPerCheckoutMovies;
	public $includeMusic;
	public $maxCostPerCheckoutMusic;
	public $includeTelevision;
	public $maxCostPerCheckoutTelevision;
	public $restrictToChildrensMaterial;
	public $ratingsToExclude;
	public $excludeAbridged;
	public $excludeParentalAdvisory;
	public $excludeProfanity;

	public static function getObjectStructure()
	{
		$structure = array(
			'id' => array('property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id'),
			'name' => array('property'=>'name', 'type'=>'text', 'label'=>'Name', 'description'=>'The Name of the scope', 'maxLength' => 50),
			'includeEAudiobook' => array('property'=>'includeEAudiobook', 'type'=>'checkbox', 'label'=>'Include eAudio books', 'description'=>'Whether or not EAudiobook are included', 'default'=>1),
			'maxCostPerCheckoutEAudiobook' => array('property'=>'maxCostPerCheckoutEAudiobook', 'type'=>'currency', 'displayFormat'=>'%0.2f', 'label'=>'Max Cost Per Checkout for eAudio books', 'description'=>'The maximum per checkout cost to include', 'default'=>5),
			'includeEBooks' => array('property'=>'includeEBooks', 'type'=>'checkbox', 'label'=>'Include eBooks', 'description'=>'Whether or not EBooks are included', 'default'=>1),
			'maxCostPerCheckoutEBooks' => array('property'=>'maxCostPerCheckoutEBooks', 'type'=>'currency', 'displayFormat'=>'%0.2f', 'label'=>'Max Cost Per Checkout for eBooks', 'description'=>'The maximum per checkout cost to include', 'default'=>5),
			'includeEComics' => array('property'=>'includeEComics', 'type'=>'checkbox', 'label'=>'Include eComics', 'description'=>'Whether or not EComics are included', 'default'=>1),
			'maxCostPerCheckoutEComics' => array('property'=>'maxCostPerCheckoutEComics', 'type'=>'currency', 'displayFormat'=>'%0.2f', 'label'=>'Max Cost Per Checkout for eComics', 'description'=>'The maximum per checkout cost to include', 'default'=>5),
			'includeMovies' => array('property'=>'includeMovies', 'type'=>'checkbox', 'label'=>'Include Movies', 'description'=>'Whether or not Movies are included', 'default'=>1),
			'maxCostPerCheckoutMovies' => array('property'=>'maxCostPerCheckoutMovies', 'type'=>'currency', 'displayFormat'=>'%0.2f', 'label'=>'Max Cost Per Checkout for Movies', 'description'=>'The maximum per checkout cost to include', 'default'=>5),
			'includeMusic' => array('property'=>'includeMusic', 'type'=>'checkbox', 'label'=>'Include Music', 'description'=>'Whether or not Music is included', 'default'=>1),
			'maxCostPerCheckoutMusic' => array('property'=>'maxCostPerCheckoutMusic', 'type'=>'currency', 'displayFormat'=>'%0.2f', 'label'=>'Max Cost Per Checkout for Music', 'description'=>'The maximum per checkout cost to include', 'default'=>5),
			'includeTelevision' => array('property'=>'includeTelevision', 'type'=>'checkbox', 'label'=>'Include Television', 'description'=>'Whether or not Television is included', 'default'=>1),
			'maxCostPerCheckoutTelevision' => array('property'=>'maxCostPerCheckoutTelevision', 'type'=>'currency', 'displayFormat'=>'%0.2f', 'label'=>'Max Cost Per Checkout for Television', 'description'=>'The maximum per checkout cost to include', 'default'=>5),
			'restrictToChildrensMaterial' => array('property'=>'restrictToChildrensMaterial', 'type'=>'checkbox', 'label'=>'Include Children\'s Materials Only', 'description'=>'If checked only includes titles identified as children by Hoopla', 'default'=>0),
			'ratingsToExclude' => array('property'=>'ratingsToExclude', 'type'=>'text', 'label'=>'Ratings to Exclude (separate with pipes)', 'description'=>'A pipe separated list of ratings that should not be included in the index'),
			'excludeAbridged' => array('property' => 'excludeAbridged', 'type' => 'checkbox', 'label' => 'Exclude Abridged Records', 'description'=>'Whether or not records marked as abridged should be included', 'default'=>0),
			'excludeParentalAdvisory' => array('property' => 'excludeParentalAdvisory', 'type' => 'checkbox', 'label' => 'Exclude Parental Advisory Records', 'description'=>'Whether or not records marked with a parental advisory indicator should be included', 'default'=>0),
			'excludeProfanity' => array('property' => 'excludeProfanity', 'type' => 'checkbox', 'label' => 'Exclude Records With Profanity', 'description'=>'Whether or not records marked with a profanity waning should be included', 'default'=>0),
		);
		return $structure;
	}
}