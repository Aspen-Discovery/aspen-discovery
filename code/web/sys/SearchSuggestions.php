<?php

class SearchSuggestions {
	function getAllSuggestions($searchTerm, $searchIndex, $searchSource) {
		$searcher = SearchObjectFactory::initSearchObjectBySearchSource($searchSource);
		$searchSuggestions = [];
		if ($searcher->supportsSuggestions()) {
			$searchSuggestions = $searcher->getSearchSuggestions($searchTerm, $searchIndex);
		}

		return $searchSuggestions;
	}
}