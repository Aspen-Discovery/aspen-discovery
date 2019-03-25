<?php

class SearchObjectFactory {

	/**
	 * initSearchObject
	 *
	 * This constructs a search object for the specified engine.
	 *
	 * @access  public
	 * @param   string  $engine     The type of SearchObject to build.
	 * @return  mixed               The search object on success, false otherwise
	 */
	static function initSearchObject($engine = 'GroupedWork')
	{
		global $configArray;

		$path = "{$configArray['Site']['local']}/sys/SearchObject/{$engine}Searcher.php";
		if (is_readable($path)) {
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
	 * @param   object  $minSO      The MinSO object to use as the base.
	 * @return  mixed               The search object on success, false otherwise
	 */
	static function deminify($minSO)
	{
		// To avoid excessive constructor calls, we'll keep a static cache of
		// objects to use for the deminification process:
		/** @var SearchObject_BaseSearcher[] $objectCache */
		static $objectCache = array();

		// Figure out the engine type for the object we're about to construct:
		switch($minSO->ty) {
			case 'islandora' :
				$type = 'Islandora';
				break;
            case 'open_archives' :
                $type = 'OpenArchives';
                break;
            case 'genealogy' :
				$type = 'Genealogy';
				break;
			default:
				$type = 'GroupedWork';
				break;
		}

		// Construct a new object if we don't already have one:
		if (!isset($objectCache[$type])) {
			$objectCache[$type] = self::initSearchObject($type);
		}

		// Populate and return the deminified object:
		$objectCache[$type]->deminify($minSO);
		//MDN 1/5/2015 return a clone of the search object since we may deminify several search objects in a single page load. 
		return clone $objectCache[$type];
	}
}