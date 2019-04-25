<?php

require_once ROOT_DIR . '/Drivers/marmot_inc/SpellingWord.php';
require_once(ROOT_DIR . '/Drivers/marmot_inc/SearchStatNew.php');

class SearchSuggestions{
	function getAllSuggestions($searchTerm, $searchIndex, $searchSource){
		/** @var SearchObject_BaseSearcher $searcher */
		$searcher = SearchObjectFactory::initSearchObjectBySearchSource($searchSource);
        $searchSuggestions = [];
		if ($searcher->supportsSuggestions()){
            $searchSuggestions = $searcher->getSearchSuggestions($searchTerm, $searchIndex);
        }

		return $searchSuggestions;
	}
}