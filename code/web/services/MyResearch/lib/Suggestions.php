<?php
require_once ROOT_DIR . '/sys/LocalEnrichment/NotInterested.php';

class Suggestions{
	/*
	 * Get suggestions for titles that a user might like based on their rating history
	 * and related titles from Novelist.
	 */
	static function getSuggestions($userId = -1, $numberOfSuggestionsToGet = null){
		global $configArray;
		global $timer;

		//Configuration for suggestions
		$doNovelistRecommendations = true;
		$numTitlesToLoadNovelistRecommendationsFor = 10;
		$doMetadataRecommendations = true;
		$doSimilarlyRatedRecommendations = false;
		$maxRecommendations = empty($numberOfSuggestionsToGet) ? 30 : $numberOfSuggestionsToGet;
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
		$ratings->find();
		while ($ratings->fetch()){
			$allRatedTitles[$ratings->groupedRecordPermanentId] = $ratings->groupedRecordPermanentId;
			if ($ratings->rating >= 4){
				$allLikedRatedTitles[] = $ratings->groupedRecordPermanentId;
			}
		}
		$timer->logTime("Loaded titles the patron has rated");

		// Setup Search Engine Connection
		$class = $configArray['Index']['engine'];
		$url = $configArray['Index']['url'];
		$db = new $class($url);

		$suggestions = array();
		if ($doNovelistRecommendations){
			//Get a list of all titles the user has rated (3 star and above)
			$ratings = new UserWorkReview();
			$ratings->whereAdd("userId = $userId", 'AND');
			$ratings->whereAdd('rating >= 3', 'AND');
			$ratings->orderBy('rating DESC, dateRated DESC, id DESC');
			//Use just recent ratings to make real-time recommendations faster
			$ratings->limit(0, $numTitlesToLoadNovelistRecommendationsFor);

			$ratings->find();
			//echo("User has rated {$ratings->N} titles<br/>");
			require_once ROOT_DIR . '/services/API/WorkAPI.php';
			$workApi = new WorkAPI();
			if ($ratings->N > 0){
				while($ratings->fetch()){
					$groupedWorkId = $ratings->groupedRecordPermanentId;
					//echo("Found resource for $resourceId - {$resource->title}<br/>");
					$ratedTitles[$ratings->groupedRecordPermanentId] = clone $ratings;
					$timer->logTime("Cloned Rating data");
					$isbns = $workApi->getIsbnsForWork($groupedWorkId);
					$timer->logTime("Loaded ISBNs for work");
					if (count($isbns) > 0){
						Suggestions::getNovelistRecommendations($ratings, $groupedWorkId, $isbns, $allRatedTitles, $suggestions, $notInterestedTitles);
						$timer->logTime("Got recommendations from Novelist for $groupedWorkId");
					}
					/*if (count($suggestions) >= $maxRecommendations){
						break;
					}*/
				}
			}
			$timer->logTime("Loaded novelist recommendations");
		}

		if ($doSimilarlyRatedRecommendations && count($suggestions) < $maxRecommendations){
			//Get a list of all titles the user has rated (3 star and above)
			$ratings = new UserWorkReview();
			$ratings->whereAdd("userId = $userId", 'AND');
			$ratings->whereAdd('rating >= 3', 'AND');
			$ratings->orderBy('rating DESC, dateRated DESC, id DESC');
			//Use just recent ratings to make real-time recommendations faster
			$ratings->limit(0, $numTitlesToLoadNovelistRecommendationsFor);

			$ratings->find();
			//echo("User has rated {$ratings->N} titles<br/>");
			require_once ROOT_DIR . '/services/API/WorkAPI.php';
			$workApi = new WorkAPI();
			if ($ratings->N > 0){
				while($ratings->fetch()){
					Suggestions::getSimilarlyRatedTitles($workApi, $db, $ratings, $userId, $allRatedTitles, $suggestions, $notInterestedTitles);
				}
			}
			$timer->logTime("Loaded recommendations based on similarly rated titles");
		}

		//Get metadata recommendations if enabled, we have ratings, and we don't have enough suggestions yet
		if ($doMetadataRecommendations && count($allLikedRatedTitles) > 0 && count($suggestions) < $maxRecommendations){
			//Get recommendations based on everything I've rated using more like this functionality
			$class = $configArray['Index']['engine'];
			$url = $configArray['Index']['url'];
			/** @var Solr $db */
			$db = new $class($url);
			//$db->debug = true;
			$moreLikeTheseSuggestions = $db->getMoreLikeThese($allLikedRatedTitles, $notInterestedTitles);
			if (isset($moreLikeTheseSuggestions['response']['docs'])) {
				foreach ($moreLikeTheseSuggestions['response']['docs'] as $suggestion) {
					if (!array_key_exists($suggestion['id'], $allRatedTitles) && !array_key_exists($suggestion['id'], $notInterestedTitles)) {
						$suggestions[$suggestion['id']] = array(
							'rating' => $suggestion['rating'] - 2.5,
							'titleInfo' => $suggestion,
							'basedOn' => 'MetaData for all titles rated',
						);
					}
					if (count($suggestions) == $maxRecommendations) {
						break;
					}
				}
			} else {
				if (isset($moreLikeTheseSuggestions['error'])) {
					global $logger;
					$logger->log('Error looking for Suggested Titles : '.$moreLikeTheseSuggestions['error']['msg'], PEAR_LOG_ERR);
				}
			}
			$timer->logTime("Loaded recommendations based on ratings");
		}


		//sort suggestions based on score from ascending to descending
		uasort($suggestions, 'Suggestions::compareSuggestions');
		//Only return up to $maxRecommendations suggestions to make the page size reasonable
		$suggestions = array_slice($suggestions, 0, $maxRecommendations, true);
		$timer->logTime("Sorted and filterd suggestions");
		//Return suggestions for use in the user interface.
		return $suggestions;
	}


	/**
	 * Load titles that have been rated by other users which are similar to this.
	 *
	 * @param WorkAPI $workApi
	 * @param SearchObject_Solr|SearchObject_Base $db
	 * @param UserWorkReview $ratedTitle
	 * @param integer $userId
	 * @param array $ratedTitles
	 * @param array $suggestions
	 * @param integer[] $notInterestedTitles
	 * @return int The number of suggestions for this title
	 */
	static function getSimilarlyRatedTitles($workApi, $db, $ratedTitle, $userId, $ratedTitles, &$suggestions, $notInterestedTitles){
		$numRecommendations = 0;
		//If there is no ISBN, can we come up with an alternative algorithm?
		//Possibly using common ratings with other patrons?
		//Get a list of other patrons that have rated this title and that like it as much or more than the active user..
		$otherRaters = new UserWorkReview();
		//Query the database to get items that other users who rated this liked.
		$sqlStatement = ("SELECT groupedRecordPermanentId, " .
                    " sum(case rating when 5 then 10 when 4 then 6 end) as rating " . //Scale the ratings similar to the above.
                    " FROM `user_work_review` WHERE userId in " .
                    " (select userId from user_work_review where groupedRecordPermanentId = " . $ratedTitle->groupedRecordPermanentId . //Get other users that have rated this title.
                    " and rating >= 4 " . //Make sure that other users liked the book.
                    " and userid != " . $userId . ") " . //Make sure that we don't include this user in the results.
                    " and rating >= 4 " . //Only include ratings that are 4 or 5 star so we don't get books the other user didn't like.
                    " and groupedRecordPermanentId != " . $ratedTitle->groupedRecordPermanentId . //Make sure we don't get back this title as a recommendation.
                    " and deleted = 0 " . //Ignore deleted resources
                    " group by resourceid order by rating desc limit 10"); //Sort so the highest titles are on top and limit to 10 suggestions.
		$otherRaters->query($sqlStatement);
		if ($otherRaters->N > 0){
			//Other users have also rated this title.
			while ($otherRaters->fetch()){
				//Process the title
				disableErrorHandler();

				if (!($ownedRecord = $db->getRecord($otherRaters->groupedRecordPermanentId))) {
					//Old record which has been removed? Ignore for purposes of suggestions.
					continue;
				}
				enableErrorHandler();
				//get the title from the Solr Index
				if (isset($ownedRecord['isbn'])){
					if (strpos($ownedRecord['isbn'][0], ' ') > 0){
						$isbnInfo = explode(' ', $ownedRecord['isbn'][0]);
						$isbn = $isbnInfo[0];
					}else{
						$isbn = $ownedRecord['isbn'][0];
					}
					$isbn13 = strlen($isbn) == 13 ? $isbn : ISBNConverter::convertISBN10to13($isbn);
					$isbn10 = strlen($isbn) == 10 ? $isbn : ISBNConverter::convertISBN13to10($isbn);
				}else{
					$isbn13 = '';
					$isbn10 = '';
				}
				//See if we can get the series title from the record
				if (isset($ownedRecord['series'])){
					$series = $ownedRecord['series'][0];
				}else{
					$series = '';
				}
				$similarTitle = array(
						'title' => $ownedRecord['title'],
						'title_short' => $ownedRecord['title_short'],
						'author' => isset($ownedRecord['author']) ? $ownedRecord['author'] : '',
						'publicationDate' => $ownedRecord['publishDate'],
						'isbn' => $isbn13,
						'isbn10' => $isbn10,
						'upc' => isset($ownedRecord['upc']) ? $ownedRecord['upc'][0] : '',
						'recordId' => $ownedRecord['id'],
						'id' => $ownedRecord['id'], //This allows the record to be displayed in various locations.
						'libraryOwned' => true,
						'isCurrent' => false,
						'shortId' => substr($ownedRecord['id'], 1),
						'format_category' => isset($ownedRecord['format_category']) ? $ownedRecord['format_category'] : '',
						'format' => $ownedRecord['format'],
						'recordtype' => $ownedRecord['recordtype'],
						'series' => $series,
				);
				$numRecommendations++;
				Suggestions::addTitleToSuggestions($ratedTitle, $similarTitle['title'], $similarTitle['recordId'], $similarTitle, $ratedTitles, $suggestions, $notInterestedTitles);
			}
		}
		return $numRecommendations;
	}

	static function getNovelistRecommendations($userRating, $groupedWorkId, $isbn, $allRatedTitles, &$suggestions, $notInterestedTitles){
		//We now have the title, we can get the related titles from Novelist
		$novelist = NovelistFactory::getNovelist();;
		//Use loadEnrichmentInfo even though there is more data than we need since it uses caching.
		$enrichmentInfo = $novelist->getSimilarTitles($groupedWorkId, $isbn);
		$numRecommendations = 0;

		if (isset($enrichmentInfo->similarTitleCountOwned) && $enrichmentInfo->similarTitleCountOwned > 0){
			//For each related title
			foreach ($enrichmentInfo->similarTitles as $similarTitle){
				if ($similarTitle['libraryOwned']){
					Suggestions::addTitleToSuggestions($userRating, $groupedWorkId, $groupedWorkId, $similarTitle, $allRatedTitles, $suggestions, $notInterestedTitles);
					$numRecommendations++;
				}
			}
		}
		return $numRecommendations;
	}

	static function addTitleToSuggestions($userRating, $sourceTitle, $sourceId, $similarTitle, $allRatedTitles, &$suggestions, $notInterestedTitles){
		//Don't suggest titles that have already been rated
		if (array_key_exists($similarTitle['id'], $allRatedTitles)){
			return;
		}
		//Don't suggest titles the user is not interested in.
		if (array_key_exists($similarTitle['id'], $notInterestedTitles)){
			return;
		}

		$rating = 0;
		$suggestedBasedOn = array();
		//Get the existing rating if any
		if (array_key_exists($similarTitle['id'], $suggestions)){
			$rating = $suggestions[$similarTitle['id']]['rating'];
			$suggestedBasedOn = $suggestions[$similarTitle['id']]['basedOn'];
		}
		//Update the suggestion score.
		//Using the scale:
		//  10 pts - 5 star rating
		//  6 pts -  4 star rating
		//  2 pts -  3 star rating
		if ($userRating->rating == 5){
			$rating += 10;
		}elseif ($userRating->rating == 4){
			$rating += 6;
		}else{
			$rating += 2;
		}
		if (count($suggestedBasedOn) < 3){
			$suggestedBasedOn[] = array('title'=>$sourceTitle,'id'=>$sourceId);
		}
		$suggestions[$similarTitle['id']] = array(
            'rating'=>$rating,
            'titleInfo'=>$similarTitle,
            'basedOn'=>$suggestedBasedOn,
		);
	}

	static function compareSuggestions($a, $b){
		if ($a['rating'] == $b['rating']){
			return 0;
		}
		return ($a['rating'] <= $b['rating']) ? 1 : -1;
	}
}
