<?php

class SearchObjectFactory
{

	/**
	 * initSearchObject
	 *
	 * This constructs a search object for the specified engine.
	 *
	 * @access  public
	 * @param string $engine The type of SearchObject to build.
	 * @return  SearchObject_BaseSearcher|false   The search object on success, false otherwise
	 */
	static function initSearchObject($engine = 'GroupedWork')
	{
		global $configArray;

		$path = "{$configArray['Site']['local']}/sys/SearchObject/{$engine}Searcher.php";
		if (is_readable($path)) {
			/** @noinspection PhpIncludeInspection */
			require_once $path;
			$class = 'SearchObject_' . $engine . 'Searcher';
			if (class_exists($class)) {
				/** @var SearchObject_BaseSearcher $searchObject */
				$searchObject = new $class();
				return $searchObject;
			}
		}

		return false;
	}

	/**
	 * initSearchObject
	 *
	 * This constructs a search object for the specified engine.
	 *
	 * @access  public
	 * @param string $searchSource
	 * @return  mixed               The search object on success, false otherwise
	 */
	static function initSearchObjectBySearchSource($searchSource = 'local')
	{
		// Figure out the engine type for the object we're about to construct:
		switch ($searchSource) {
			case 'open_archives' :
				$engine = 'OpenArchives';
				break;
			case 'lists' :
				$engine = 'Lists';
				break;
			case 'genealogy' :
				$engine = 'Genealogy';
				break;
			case 'websites' :
				$engine = 'Websites';
				break;
			case 'events' :
				$engine = 'Events';
				break;
			case 'ebsco_eds' :
				$engine = 'EbscoEds';
				break;
			default:
				$engine = 'GroupedWork';
				break;
		}

		$path = ROOT_DIR . "/sys/SearchObject/{$engine}Searcher.php";
		if (is_readable($path)) {
			/** @noinspection PhpIncludeInspection */
			require_once $path;
			$class = 'SearchObject_' . $engine . 'Searcher';
			if (class_exists($class)) {
				/** @var SearchObject_BaseSearcher $searchObject */
				$searchObject = new $class();
				return $searchObject;
			}
		}

		return false;
	}

	/**
	 * deminify
	 *
	 * Construct an appropriate Search Object from a MinSO object.
	 *
	 * @access  public
	 * @param object $minSO The MinSO object to use as the base.
	 * @return  mixed               The search object on success, false otherwise
	 */
	static function deminify($minSO)
	{
		// To avoid excessive constructor calls, we'll keep a static cache of
		// objects to use for the deminification process:
		/** @var SearchObject_BaseSearcher[] $objectCache */
		static $objectCache = array();

		// Figure out the engine type for the object we're about to construct:
		switch ($minSO->ss) {
			case 'open_archives' :
				$source = 'OpenArchives';
				break;
			case 'lists' :
				$source = 'Lists';
				break;
			case 'genealogy' :
				$source = 'Genealogy';
				break;
			case 'websites' :
				$source = 'Websites';
				break;
			case 'events' :
				$source = 'Events';
				break;
			case 'ebsco_eds' :
				$source = 'EbscoEds';
				break;
			default:
				$source = 'GroupedWork';
				break;
		}

		// Construct a new object if we don't already have one:
		if (!isset($objectCache[$source])) {
			$objectCache[$source] = self::initSearchObject($source);
		}

		// Populate and return the expanded object:
		$objectCache[$source]->deminify($minSO);
		//MDN 1/5/2015 return a clone of the search object since we may deminify several search objects in a single page load. 
		return clone $objectCache[$source];
	}
}