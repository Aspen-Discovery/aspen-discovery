<?php
require_once ROOT_DIR . '/sys/LocalEnrichment/NotInterested.php';

class Suggestions{
	/*
	 * Get suggestions for titles that a user might like based on their rating history
	 * and related titles from Novelist.
	 */
	static function getSuggestions($userId = -1, $page = 1, $limit = 25){
		global $configArray;
		global $timer;

		//Configuration for suggestions
		if ($userId == -1){
			$userId = UserAccount::getActiveUserId();
		}

		//Load all titles the user is not interested in
		$notInterestedTitles = array();
		$notInterested = new NotInterested();
		$notInterested->userId = $userId;
		$notInterested->find();
		while ($notInterested->fetch()){
			$notInterestedTitles[$notInterested->groupedRecordPermanentId] = $notInterested->groupedRecordPermanentId;
		}
		$timer->logTime("Loaded titles the patron is not interested in");

		//Load all titles the user has rated.  Need to load all so we don't recommend things they already rated
		$allRatedTitles = array();
		$allLikedRatedTitles = array();
		require_once ROOT_DIR . '/sys/LocalEnrichment/UserWorkReview.php';
		$ratings = new UserWorkReview();
		$ratings->userId = $userId;
		$ratings->orderBy('dateRated DESC');
		$ratings->find();
		while ($ratings->fetch()){
			$allRatedTitles[$ratings->groupedRecordPermanentId] = $ratings->groupedRecordPermanentId;
			if ($ratings->rating >= 3){
				$allLikedRatedTitles[] = [
					'workId' => $ratings->groupedRecordPermanentId,
					'rating' => $ratings->rating,
				];
			}
		}
		$timer->logTime("Loaded titles the patron has rated");

		// Setup Search Engine Connection
		$suggestions = [];

		//Get metadata recommendations if enabled, we have ratings, and we don't have enough suggestions yet
		if (count($allLikedRatedTitles) > 0){
			//Get recommendations based on everything I've rated using more like this functionality

			/** @var SearchObject_GroupedWorkSearcher $searchObject */
			$searchObject = SearchObjectFactory::initSearchObject();

			if ($page == 1){
				//Get 1 recommendation for each of the last 5 titles that were rated
				for ($i = 0; $i < min(5, count($allLikedRatedTitles)); $i++){
					if ($allLikedRatedTitles[$i]['rating'] == 3){
						$titleLimit = 1;
					}elseif ($allLikedRatedTitles[$i]['rating'] == 4){
						$titleLimit = 2;
					}else{
						$titleLimit = 3;
					}
					$suggestions = self::getMoreLikeTheseSuggestions(1, $titleLimit, $searchObject, [$allLikedRatedTitles[$i]], $notInterestedTitles, $allRatedTitles, $suggestions);
				}
			}

			//$db->debug = true;
			$suggestions = self::getMoreLikeTheseSuggestions($page, $limit - count($suggestions), $searchObject, $allLikedRatedTitles, $notInterestedTitles, $allRatedTitles, $suggestions);
			$timer->logTime("Loaded recommendations based on metadata");
		}

		//Return suggestions for use in the user interface.
		return $suggestions;
	}

	/**
	 * @param $page
	 * @param $limit
	 * @param SearchObject_GroupedWorkSearcher $searchObject
	 * @param array $titlesToBaseRecommendationsOn
	 * @param array $notInterestedTitles
	 * @param array $allRatedTitles
	 * @param array $suggestions
	 * @return array
	 */
	private static function getMoreLikeTheseSuggestions($page, $limit, SearchObject_GroupedWorkSearcher $searchObject, array $titlesToBaseRecommendationsOn, array $notInterestedTitles, array $allRatedTitles, array $suggestions): array
	{
		$moreLikeTheseSuggestions = $searchObject->getMoreLikeThese($titlesToBaseRecommendationsOn, $notInterestedTitles, $page, $limit);
		if (isset($moreLikeTheseSuggestions['response']['docs'])) {
			foreach ($moreLikeTheseSuggestions['response']['docs'] as $suggestion) {
				if (!array_key_exists($suggestion['id'], $allRatedTitles) && !array_key_exists($suggestion['id'], $notInterestedTitles)) {
					$suggestions[$suggestion['id']] = array(
						'titleInfo' => $suggestion,
						'basedOn' => 'MetaData for all titles rated',
					);
				}
			}
		} else {
			if (isset($moreLikeTheseSuggestions['error'])) {
				global $logger;
				$logger->log('Error looking for Suggested Titles : ' . $moreLikeTheseSuggestions['error']['msg'], Logger::LOG_ERROR);
			}
		}
		return $suggestions;
	}
}
