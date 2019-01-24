<?php
/**
 * Table Definition for bad words
 */
require_once ROOT_DIR . '/sys/DB/DataObject.php';
class SearchStat extends DataObject
{
	public $__table = 'search_stats';    // table name
	public $id;                      //int(11)
	public $phrase;                    //varchar(500)
	public $type;             //varchar(50)
	public $numResults;       //int(16)
	public $lastSearch;       //timestamp
	public $numSearches;      //int(16)
	public $locationId;      //int(16)
	public $libraryId;      //int(16)

	function keys() {
		return array('id', 'phrase', 'type');
	}

	function getSearchSuggestions($phrase, $type){
		//global $logger;
		//$logger->log("Loading search suggestions", PEAR_LOG_DEBUG);
		$phrase = strtolower($phrase);
		$activeLibrary = Library::getActiveLibrary();
		$libraryId = -1;
		if ($activeLibrary != null && $activeLibrary->useScope){
			$libraryId = $activeLibrary->libraryId;
		}

		/** @var $locationSingleton Location */
		global $locationSingleton;
		$locationId = -1;
		$activeLocation = $locationSingleton->getActiveLocation();
		if ($activeLocation != null && $activeLocation->useScope){
			$locationId = $activeLocation->locationId;
		}
		if (!isset($type)){
			$type = 'Keyword';
		}

		$searchStat = new SearchStat();
		//If we are scoped, limit suggestions to searches that have been done in the
		//same scope so we get a correct number of hits
		//$searchStat->selectAdd("sum(numResults) as totalSearches");
		$searchStat->libraryId = $libraryId;
		$searchStat->locationId = $locationId;

		//If we are searching a specific type, limit results to that type so the results
		//are better.
		if ($type == 'ISN' || $type == 'Tag' || $type == 'Author'){
			$searchStat->type = $type;
		}else{
			$searchStat->whereAdd("type = '' OR type ='{$type}'");
		}

		//Don't suggest things to users that will result in them not getting any results
		$searchStat->whereAdd("numResults > 0");
		$splitPhrase = explode(' ', $phrase);
		$rebuiltPhrase = implode(' %', $splitPhrase);
		if ($rebuiltPhrase)
		$searchStat->whereAdd("(phrase like '" . mysql_escape_string($rebuiltPhrase) ."%' or phrase sounds like '" . mysql_escape_string($phrase) ."')");
		//$searchStat->groupBy('phrase');
		$searchStat->orderBy("numSearches DESC");
		$searchStat->limit(0, 20);
		$searchStat->find();
		$results = array();
		if ($searchStat->N > 0){
			while($searchStat->fetch()){
				$searchStat->phrase = trim(str_replace('"', '', $searchStat->phrase));
				if ($this->phrase != $phrase && !array_key_exists($searchStat->phrase, $results)){
					//Check the levenshtein distance to make sure that the terms are actually close.
					//$logger->log("Testing {$searchStat->phrase}", PEAR_LOG_DEBUG);
					$levenshteinDistance = levenshtein($phrase, $searchStat->phrase);
					//$logger->log("  Levenshtein Distance is $levenshteinDistance", PEAR_LOG_DEBUG);
					similar_text($phrase, $searchStat->phrase, $percent);
					//$logger->log("  Similarity is $percent", PEAR_LOG_DEBUG);
					$allPartsContained = true;
					foreach ($splitPhrase as $phrasePart){
						$stringPosition = strpos($searchStat->phrase, $phrasePart);
						if ($stringPosition === false){
							$allPartsContained = false;
						}
					}

					//$logger->log("  String Position is $stringPosition, $stringPosition2", PEAR_LOG_DEBUG);
					if ($levenshteinDistance == 1 || $percent >= 75 || $allPartsContained){
						$results[$searchStat->phrase] = array('phrase'=>$searchStat->phrase, 'numSearches'=>$searchStat->numSearches, 'numResults'=>$searchStat->numResults);
					}
				}
			}
		}
		return array_values($results);
	}

	function saveSearch($phrase, $type, $numResults){
		if (!isset($numResults)){
			//This only happens if there is an error parsing the query.
			return;
		}
		$activeLibrary = Library::getActiveLibrary();
		$libraryId = -1;
		if ($activeLibrary != null && $activeLibrary->useScope){
			$libraryId = $activeLibrary->libraryId;
		}

		/** @var $locationSingleton Location */
		global $locationSingleton;
		$locationId = -1;
		$activeLocation = $locationSingleton->getActiveLocation();
		if ($activeLocation != null && $activeLocation->useScope){
			$locationId = $activeLocation->locationId;
		}

		$searchStat = new SearchStat();
		$searchStat->phrase = strtolower($phrase);
		$searchStat->type = $type;
		$searchStat->libraryId = $libraryId;
		$searchStat->locationId = $locationId;
		$searchStat->find();
		$isNew = true;
		if ($searchStat->N > 0){
			$searchStat->fetch();
			$searchStat->numSearches++;
			$isNew = false;
		}else{
			$searchStat->numSearches = 1;
		}
		$searchStat->numResults = $numResults;
		$searchStat->lastSearch = time();
		if ($isNew){
			$searchStat->insert();
		}else{
			$searchStat->update();
		}
	}

}