<?php
require_once(ROOT_DIR . '/Drivers/marmot_inc/ISBNConverter.php');
require_once ROOT_DIR . '/sys/Novelist/NovelistData.php';
class Novelist3{

	function doesGroupedWorkHaveCachedSeries($groupedRecordId){
		$novelistData = new NovelistData();
		if ($groupedRecordId != null && $groupedRecordId != ''){
			$novelistData->groupedRecordPermanentId = $groupedRecordId;
			if ($novelistData->find(true)){
				return true;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}
	function loadBasicEnrichment($groupedRecordId, $isbns, $allowReload = true){
		global $timer;
		global $configArray;

		//First make sure that Novelist is enabled
		if (isset($configArray['Novelist']) && isset($configArray['Novelist']['profile']) && strlen($configArray['Novelist']['profile']) > 0){
			$profile = $configArray['Novelist']['profile'];
			$pwd = $configArray['Novelist']['pwd'];
		}else{
			return null;
		}

		if ($groupedRecordId == null || $groupedRecordId == ''){
			return null;
		}

		//Check to see if we have cached data, first check MemCache.
		/** @var Memcache $memCache */
		global $memCache;
		$novelistData = $memCache->get("novelist_enrichment_basic_$groupedRecordId");
		if ($novelistData != false && !isset($_REQUEST['reload'])){
			return $novelistData;
		}

		$timer->logTime("Starting to load data from novelist for $groupedRecordId");
		//Now check the database
		$novelistData = new NovelistData();
		$novelistData->groupedRecordPermanentId = $groupedRecordId;
		$doUpdate = true;
		$recordExists = false;
		if ($novelistData->find(true)){
			$recordExists = true;
			$doUpdate = false;
			//We already have data loaded, make sure the data is still "fresh"

			//First check to see if the record had isbns before we update
			if ($novelistData->groupedRecordHasISBN || count($isbns) > 0){
				//We do have at least one ISBN
				//If it's been more than 30 days since we updated, update 20% of the time
				//We do it randomly to spread out the updates.
				if ($allowReload){
					$now = time();
					if ($novelistData->lastUpdate < $now - (30 * 24 * 60 * 60)){
						$random = rand(1, 100);
						if ($random <= 20){
							$doUpdate = true;
						}
					}
				}
			}//else, no ISBNs, don't update

		}

		$novelistData->groupedRecordHasISBN = count($isbns) > 0;

		//Check to see if a reload is being forced
		if (isset($_REQUEST['reload'])){
			$doUpdate = true;
		}

		//Check to see if we need to do an update
		if (!$recordExists || $doUpdate){
			if ($recordExists && $novelistData->primaryISBN != null && strlen($novelistData->primaryISBN) > 0 && !isset($_REQUEST['reload'])){
				//Just check the primary ISBN since we know that was good.
				$isbns = array($novelistData->primaryISBN);
			}

			//Update the last update time to optimize caching
			$novelistData->lastUpdate = time();

			if (count($isbns) == 0){
				//Whoops, no ISBNs, can't get enrichment for this
				$novelistData->hasNovelistData = false;
			}else{
				$novelistData->hasNovelistData = false;

				//Check each ISBN for enrichment data
				foreach ($isbns as $isbn){
					$requestUrl = "http://novselect.ebscohost.com/Data/ContentByQuery?profile=$profile&password=$pwd&ClientIdentifier={$isbn}&isbn={$isbn}&version=2.1&tmpstmp=" . time();
					//echo($requestUrl);
					try{
						//Get the JSON from the service
						disableErrorHandler();
						$req = new Proxy_Request($requestUrl);
						//$result = file_get_contents($req);
						if (PEAR_Singleton::isError($req->sendRequest())) {
							enableErrorHandler();
							//No enrichment for this isbn, go to the next one
							continue;
						}
						enableErrorHandler();

						$response = $req->getResponseBody();
						$timer->logTime("Made call to Novelist to get basic enrichment info $isbn");

						//Parse the JSON
						$data = json_decode($response);
						//print_r($data);

						//Related ISBNs

						if (isset($data->FeatureContent) && $data->FeatureCount > 0){
							$novelistData->hasNovelistData = true;
							//We got data!
							$novelistData->primaryISBN = $data->TitleInfo->primary_isbn;

							//Series Information
							if (isset($data->FeatureContent->SeriesInfo)){
								$this->loadSeriesInfoFast($data->FeatureContent->SeriesInfo, $novelistData);
								$timer->logTime("loaded series data");

								if (count($data->FeatureContent->SeriesInfo->series_titles) > 0){
									//We got good data, quit looking at ISBNs
									break;
								}
							}
						}
					}catch (Exception $e) {
						global $logger;
						$logger->log("Error fetching data from NoveList $e", PEAR_LOG_ERR);
						if (isset($response)){
							$logger->log($response, PEAR_LOG_DEBUG);
						}
						$enrichment = null;
					}
				}//Loop on each ISBN
			}//Check for number of ISBNs
		}//Don't need to do an update

		if ($recordExists){
			if ($doUpdate){
				$novelistData->update();
			}
		}else{
			$novelistData->insert();
		}

		$memCache->set("novelist_enrichment_basic_$groupedRecordId", $novelistData, 0, $configArray['Caching']['novelist_enrichment']);
		return $novelistData;
	}

	/**
	 * Loads Novelist data from Novelist for a grouped record
	 *
	 * @param String    $groupedRecordId  The permanent id of the grouped record
	 * @param String[]  $isbns            a list of ISBNs for the record
	 * @return NovelistData
	 */
	function loadEnrichment($groupedRecordId, $isbns){
		global $timer;
		global $memoryWatcher;
		global $configArray;

		//First make sure that Novelist is enabled
		if (isset($configArray['Novelist']) && isset($configArray['Novelist']['profile']) && strlen($configArray['Novelist']['profile']) > 0){
			$profile = $configArray['Novelist']['profile'];
			$pwd = $configArray['Novelist']['pwd'];
		}else{
			return null;
		}

		if ($groupedRecordId == null || $groupedRecordId == ''){
			return null;
		}

		//Check to see if we have cached data, first check MemCache.
		/** @var Memcache $memCache */
		global $memCache;
		$novelistData = $memCache->get("novelist_enrichment_$groupedRecordId");
		if ($novelistData != false && !isset($_REQUEST['reload'])){
			$memoryWatcher->logMemory('Got novelist data from memcache');
			return $novelistData;
		}

		//Now check the database
		$novelistData = new NovelistData();
		$novelistData->groupedRecordPermanentId = $groupedRecordId;
		$recordExists = false;
		$doFullUpdate = true;
		if ($novelistData->find(true)){
			$recordExists = true;
			//We already have data loaded, make sure the data is still "fresh"

			//First check to see if the record had isbns before we update
			if ($novelistData->groupedRecordHasISBN || count($isbns) > 0){
				//We do have at least one ISBN
				//If it's been more than 30 days since we updated, update 20% of the time
				//We do it randomly to spread out the updates.
				$now = time();
				if ($novelistData->lastUpdate < $now - (30 * 24 * 60 * 60)){
					$random = rand(1, 100);
					if ($random <= 80  && !isset($_REQUEST['reload'])){
						//MDN 4/27/2015
						//Can't return data here because we haven't actually loaded the enrichment.
						//We are just checking if the data should be reloaded.
						//return $novelistData;
						$doFullUpdate = false;
					}
				}
			}//else, no ISBNs, don't update

		}

		$novelistData->groupedRecordHasISBN = count($isbns) > 0;

		//When loading full data, we always need to load the data since we can't cache due to terms of service
		if ($recordExists && $novelistData->primaryISBN != null && strlen($novelistData->primaryISBN) > 0 && !isset($_REQUEST['reload']) && !$doFullUpdate){
			//Just check the primary ISBN since we know that was good.
			$isbns = array($novelistData->primaryISBN);
		}

		//Update the last update time to optimize caching
		$novelistData->lastUpdate = time();

		if (count($isbns) == 0){
			//Whoops, no ISBNs, can't get enrichment for this
			$novelistData->hasNovelistData = false;
		}else{
			$novelistData->hasNovelistData = false;

			//Check each ISBN for enrichment data
			foreach ($isbns as $isbn){
				$requestUrl = "http://novselect.ebscohost.com/Data/ContentByQuery?profile=$profile&password=$pwd&ClientIdentifier={$isbn}&isbn={$isbn}&version=2.1&tmpstmp=" . time();
				//echo($requestUrl);
				try{
					//Get the JSON from the service
					disableErrorHandler();
					$req = new Proxy_Request($requestUrl);
					//$result = file_get_contents($req);
					if (PEAR_Singleton::isError($req->sendRequest())) {
						enableErrorHandler();
						//No enrichment for this isbn, go to the next one
						continue;
					}
					enableErrorHandler();

					$response = $req->getResponseBody();
					$timer->logTime("Made call to Novelist for enrichment information");

					//Parse the JSON
					$data = json_decode($response);
					//print_r($data);

					//Related ISBNs

					if (isset($data->FeatureContent) && $data->FeatureCount > 0){
						$novelistData->hasNovelistData = true;
						//We got data!
						$novelistData->primaryISBN = $data->TitleInfo->primary_isbn;

						//Series Information
						if (isset($data->FeatureContent->SeriesInfo)){
							$this->loadSeriesInfo($groupedRecordId, $data->FeatureContent->SeriesInfo, $novelistData);
						}

						//Similar Titles
						if (isset($data->FeatureContent->SimilarTitles)){
							$this->loadSimilarTitleInfo($groupedRecordId, $data->FeatureContent->SimilarTitles, $novelistData);
						}

						//Similar Authors
						if (isset($data->FeatureContent->SimilarAuthors)){
							$this->loadSimilarAuthorInfo($data->FeatureContent->SimilarAuthors, $novelistData);
						}

						//Similar Series
						if (isset($data->FeatureContent->SimilarSeries)){
							$this->loadSimilarSeries($data->FeatureContent->SimilarSeries, $novelistData);
						}

						//Related Content
						if (isset($data->FeatureContent->RelatedContent)){
							$this->loadRelatedContent($data->FeatureContent->RelatedContent, $novelistData);
						}

						//GoodReads Ratings
						if (isset($data->FeatureContent->GoodReads)){
							$this->loadGoodReads($data->FeatureContent->GoodReads, $novelistData);
						}

						//print_r($data);
						//We got good data, quit looking at ISBNs
						//If we get series data, stop.
						//Sometimes we get data for an audiobook that is less complete.
						if (isset($data->FeatureContent->SeriesInfo) && count($data->FeatureContent->SeriesInfo->series_titles) > 0) {
							break;
						}
					}
				}catch (Exception $e) {
					global $logger;
					$logger->log("Error fetching data from NoveList $e", PEAR_LOG_ERR);
					if (isset($response)){
						$logger->log($response, PEAR_LOG_DEBUG);
					}
					$enrichment = null;
				}
			}//Loop on each ISBN
		}//Check for number of ISBNs

		if ($recordExists){
			$novelistData->update();
		}else{
			$novelistData->insert();
		}

		//Ignore warnings if the object is too large for the cache
		@$memCache->set("novelist_enrichment_$groupedRecordId", $novelistData, 0, $configArray['Caching']['novelist_enrichment']);
		return $novelistData;
	}

	/**
	 * Loads Novelist data from Novelist for a grouped record
	 *
	 * @param String    $groupedRecordId  The permanent id of the grouped record
	 * @param String[]  $isbns            a list of ISBNs for the record
	 * @return NovelistData
	 */
	function getSimilarTitles($groupedRecordId, $isbns){
		global $timer;
		global $configArray;

		//First make sure that Novelist is enabled
		if (isset($configArray['Novelist']) && isset($configArray['Novelist']['profile']) && strlen($configArray['Novelist']['profile']) > 0){
			$profile = $configArray['Novelist']['profile'];
			$pwd = $configArray['Novelist']['pwd'];
		}else{
			return null;
		}

		if ($groupedRecordId == null || $groupedRecordId == ''){
			return null;
		}

		//Check to see if we have cached data, first check MemCache.
		/** @var Memcache $memCache */
		global $memCache;
		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		$novelistData = $memCache->get("novelist_similar_titles_$groupedRecordId");
		if ($novelistData != false && !isset($_REQUEST['reload'])){
			return $novelistData;
		}

		//Now check the database
		$novelistData = new NovelistData();
		$novelistData->groupedRecordPermanentId = $groupedRecordId;
		$recordExists = false;
		if ($novelistData->find(true)){
			$recordExists = true;
			//We already have data loaded, make sure the data is still "fresh"

			//First check to see if the record had isbns before we update
			if ($novelistData->groupedRecordHasISBN || count($isbns) > 0){
				//We do have at least one ISBN
				//If it's been more than 30 days since we updated, update 20% of the time
				//We do it randomly to spread out the updates.
				$now = time();
				if ($novelistData->lastUpdate < $now - (30 * 24 * 60 * 60)){
					$random = rand(1, 100);
					if ($random <= 20){
						$doUpdate = true;
					}
				}
			}//else, no ISBNs, don't update

		}

		$novelistData->groupedRecordHasISBN = count($isbns) > 0;

		//When loading full data, we aways need to load the data since we can't cache due to terms of sevice
		if ($recordExists && $novelistData->primaryISBN != null && strlen($novelistData->primaryISBN) > 0){
			//Just check the primary ISBN since we know that was good.
			$isbns = array($novelistData->primaryISBN);
		}

		//Update the last update time to optimize caching
		$novelistData->lastUpdate = time();

		if (count($isbns) == 0){
			//Whoops, no ISBNs, can't get enrichment for this
			$novelistData->hasNovelistData = false;
		}else{
			$novelistData->hasNovelistData = false;

			//Check each ISBN for enrichment data
			foreach ($isbns as $isbn){
				$requestUrl = "http://novselect.ebscohost.com/Data/ContentByQuery?profile=$profile&password=$pwd&ClientIdentifier={$isbn}&isbn={$isbn}&version=2.1&tmpstmp=" . time();
				//echo($requestUrl);
				try{
					//Get the JSON from the service
					disableErrorHandler();
					$req = new Proxy_Request($requestUrl);
					//$result = file_get_contents($req);
					if (PEAR_Singleton::isError($req->sendRequest())) {
						enableErrorHandler();
						//No enrichment for this isbn, go to the next one
						continue;
					}
					enableErrorHandler();

					$response = $req->getResponseBody();
					$timer->logTime("Made call to Novelist for enrichment information");

					//Parse the JSON
					$data = json_decode($response);
					//print_r($data);

					//Related ISBNs

					if (isset($data->FeatureContent) && $data->FeatureCount > 0){
						$novelistData->hasNovelistData = true;
						//We got data!
						$novelistData->primaryISBN = $data->TitleInfo->primary_isbn;

						//Similar Titles
						if (isset($data->FeatureContent->SimilarTitles)){
							$this->loadSimilarTitleInfo($groupedRecordId, $data->FeatureContent->SimilarTitles, $novelistData);
						}

						//We got good data, quit looking at ISBNs
						break;
					}
				}catch (Exception $e) {
					global $logger;
					$logger->log("Error fetching data from NoveList $e", PEAR_LOG_ERR);
					if (isset($response)){
						$logger->log($response, PEAR_LOG_DEBUG);
					}
					$enrichment = null;
				}
			}//Loop on each ISBN
		}//Check for number of ISBNs

		if ($recordExists){
			$ret = $novelistData->update();
		}else{
			$ret = $novelistData->insert();
		}

		$memCache->set("novelist_similar_titles_$groupedRecordId", $novelistData, 0, $configArray['Caching']['novelist_enrichment']);
		return $novelistData;
	}

	/**
	 * Loads Novelist data from Novelist for a grouped record
	 *
	 * @param String    $groupedRecordId  The permanent id of the grouped record
	 * @param String[]  $isbns            a list of ISBNs for the record
	 * @return NovelistData
	 */
	function getSimilarAuthors($groupedRecordId, $isbns){
		global $timer;
		global $configArray;

		//First make sure that Novelist is enabled
		if (isset($configArray['Novelist']) && isset($configArray['Novelist']['profile']) && strlen($configArray['Novelist']['profile']) > 0){
			$profile = $configArray['Novelist']['profile'];
			$pwd = $configArray['Novelist']['pwd'];
		}else{
			return null;
		}

		if ($groupedRecordId == null || $groupedRecordId == ''){
			return null;
		}

		//Check to see if we have cached data, first check MemCache.
		/** @var Memcache $memCache */
		global $memCache;
		$novelistData = $memCache->get("novelist_similar_authors_$groupedRecordId");
		if ($novelistData != false && !isset($_REQUEST['reload'])){
			return $novelistData;
		}

		//Now check the database
		$novelistData = new NovelistData();
		$novelistData->groupedRecordPermanentId = $groupedRecordId;
		$recordExists = false;
		if ($novelistData->find(true)){
			$recordExists = true;
		}

		$novelistData->groupedRecordHasISBN = count($isbns) > 0;

		//When loading full data, we aways need to load the data since we can't cache due to terms of sevice
		if ($recordExists && $novelistData->primaryISBN != null && strlen($novelistData->primaryISBN) > 0){
			//Just check the primary ISBN since we know that was good.
			$isbns = array($novelistData->primaryISBN);
		}

		//Update the last update time to optimize caching
		$novelistData->lastUpdate = time();

		if (count($isbns) == 0){
			//Whoops, no ISBNs, can't get enrichment for this
			$novelistData->hasNovelistData = false;
		}else{
			$novelistData->hasNovelistData = false;

			//Check each ISBN for enrichment data
			foreach ($isbns as $isbn){
				$requestUrl = "http://novselect.ebscohost.com/Data/ContentByQuery?profile=$profile&password=$pwd&ClientIdentifier={$isbn}&isbn={$isbn}&version=2.1&tmpstmp=" . time();
				//echo($requestUrl);
				try{
					//Get the JSON from the service
					disableErrorHandler();
					$req = new Proxy_Request($requestUrl);
					//$result = file_get_contents($req);
					if (PEAR_Singleton::isError($req->sendRequest())) {
						enableErrorHandler();
						//No enrichment for this isbn, go to the next one
						continue;
					}
					enableErrorHandler();

					$response = $req->getResponseBody();
					$timer->logTime("Made call to Novelist for enrichment information");

					//Parse the JSON
					$data = json_decode($response);
					//print_r($data);

					//Related ISBNs

					if (isset($data->FeatureContent) && $data->FeatureCount > 0){
						$novelistData->hasNovelistData = true;
						//We got data!
						$novelistData->primaryISBN = $data->TitleInfo->primary_isbn;

						//Similar Authors
						if (isset($data->FeatureContent->SimilarAuthors)){
							$this->loadSimilarAuthorInfo($data->FeatureContent->SimilarAuthors, $novelistData);
						}

						//We got good data, quit looking at ISBNs
						break;
					}
				}catch (Exception $e) {
					global $logger;
					$logger->log("Error fetching data from NoveList $e", PEAR_LOG_ERR);
					if (isset($response)){
						$logger->log($response, PEAR_LOG_DEBUG);
					}
					$enrichment = null;
				}
			}//Loop on each ISBN
		}//Check for number of ISBNs

		$memCache->set("novelist_similar_authors_$groupedRecordId", $novelistData, 0, $configArray['Caching']['novelist_enrichment']);
		return $novelistData;
	}

	private function loadSimilarAuthorInfo($feature, &$enrichment){
		$authors = array();
		$items = $feature->authors;
		foreach ($items as $item){
			$authors[] = array(
				'name' => $item->full_name,
				'reason' => $item->reason,
				'link' => '/Author/Home/?author="'. urlencode($item->full_name) . '"',
			);
		}
		$enrichment->authors = $authors;
		$enrichment->similarAuthorCount = count($authors);
	}

	/**
	 * Loads Novelist data from Novelist for a grouped record
	 *
	 * @param String    $groupedRecordId  The permanent id of the grouped record
	 * @param String[]  $isbns            a list of ISBNs for the record
	 * @return NovelistData
	 */
	function getSeriesTitles($groupedRecordId, $isbns){
		global $timer;
		global $configArray;

		//First make sure that Novelist is enabled
		if (isset($configArray['Novelist']) && isset($configArray['Novelist']['profile']) && strlen($configArray['Novelist']['profile']) > 0){
			$profile = $configArray['Novelist']['profile'];
			$pwd = $configArray['Novelist']['pwd'];
		}else{
			return null;
		}

		if ($groupedRecordId == null || $groupedRecordId == ''){
			return null;
		}

		//Check to see if we have cached data, first check MemCache.
		/** @var Memcache $memCache */
		global $memCache;
		global $solrScope;
		$novelistData = $memCache->get("novelist_series_{$groupedRecordId}_{$solrScope}");
		if ($novelistData != false && !isset($_REQUEST['reload'])){
			return $novelistData;
		}

		//Now check the database
		$novelistData = new NovelistData();
		$novelistData->groupedRecordPermanentId = $groupedRecordId;
		$recordExists = false;
		if ($novelistData->find(true)){
			$recordExists = true;
		}

		$novelistData->groupedRecordHasISBN = count($isbns) > 0;

		//When loading full data, we aways need to load the data since we can't cache due to terms of sevice
		if ($recordExists && $novelistData->primaryISBN != null && strlen($novelistData->primaryISBN) > 0){
			//Just check the primary ISBN since we know that was good.
			$isbns = array($novelistData->primaryISBN);
		}

		//Update the last update time to optimize caching
		$novelistData->lastUpdate = time();

		if (count($isbns) == 0){
			//Whoops, no ISBNs, can't get enrichment for this
			$novelistData->hasNovelistData = false;
		}else{
			$novelistData->hasNovelistData = false;

			//Check each ISBN for enrichment data
			foreach ($isbns as $isbn){
				$requestUrl = "http://novselect.ebscohost.com/Data/ContentByQuery?profile=$profile&password=$pwd&ClientIdentifier={$isbn}&isbn={$isbn}&version=2.1&tmpstmp=" . time();
				//echo($requestUrl);
				try{
					//Get the JSON from the service
					disableErrorHandler();
					$req = new Proxy_Request($requestUrl);
					//$result = file_get_contents($req);
					if (PEAR_Singleton::isError($req->sendRequest())) {
						enableErrorHandler();
						//No enrichment for this isbn, go to the next one
						continue;
					}
					enableErrorHandler();

					$response = $req->getResponseBody();
					$timer->logTime("Made call to Novelist for enrichment information");

					//Parse the JSON
					$data = json_decode($response);
					//print_r($data);

					//Related ISBNs

					if (isset($data->FeatureContent) && $data->FeatureCount > 0){
						$novelistData->hasNovelistData = true;
						//We got data!
						$novelistData->primaryISBN = $data->TitleInfo->primary_isbn;

						//Series Information
						if (isset($data->FeatureContent->SeriesInfo) && count($data->FeatureContent->SeriesInfo->series_titles) > 0){
							$this->loadSeriesInfo($groupedRecordId, $data->FeatureContent->SeriesInfo, $novelistData);

							break;
						}
					}
				}catch (Exception $e) {
					global $logger;
					$logger->log("Error fetching data from NoveList $e", PEAR_LOG_ERR);
					if (isset($response)){
						$logger->log($response, PEAR_LOG_DEBUG);
					}
					$enrichment = null;
				}
			}//Loop on each ISBN
		}//Check for number of ISBNs

		$memCache->set("novelist_series_{$groupedRecordId}_{$solrScope}", $novelistData, 0, $configArray['Caching']['novelist_enrichment']);
		return $novelistData;
	}

	/**
	 * @param SimpleXMLElement $seriesData
	 * @param NovelistData $novelistData
	 */
	private function loadSeriesInfoFast($seriesData, &$novelistData){
		$seriesName = $seriesData->full_title;
		$items = $seriesData->series_titles;
		foreach ($items as $item){
			if ($item->primary_isbn == $novelistData->primaryISBN){
				$volume = $item->volume;
				$volume = preg_replace('/^0+/', '', $volume);
				$novelistData->volume = $volume;
			}
		}
		$novelistData->seriesTitle = $seriesName;
		$novelistData->seriesNote = $seriesData->series_note;
	}

	private function loadSeriesInfo($currentId, $seriesData, &$novelistData){
		$seriesName = $seriesData->full_title;
		$seriesTitles = array();
		$items = $seriesData->series_titles;
		$titlesOwned = 0;
		$this->loadNoveListTitles($currentId, $items, $seriesTitles, $titlesOwned, $seriesName);
		foreach ($seriesTitles as $curTitle){
			if ($curTitle['isCurrent'] && isset($curTitle['volume']) && strlen($curTitle['volume']) > 0){
				$enrichment['volumeLabel'] = (isset($curTitle['volume']) ? ('volume ' . $curTitle['volume']) : '');
				$novelistData->volume = $curTitle['volume'];
			}
		}
		$novelistData->seriesTitles = $seriesTitles;
		$novelistData->seriesTitle = $seriesName;
		$novelistData->seriesNote = $seriesData->series_note;

		$novelistData->seriesCount = count($items);
		$novelistData->seriesCountOwned = $titlesOwned;
		$novelistData->seriesDefaultIndex = 1;
		$curIndex = 0;
		foreach ($seriesTitles as $title){

			if ($title['isCurrent']){
				$novelistData->seriesDefaultIndex = $curIndex;
			}
			$curIndex++;
		}
	}

	private function loadSimilarSeries($similarSeriesData, &$enrichment){
		$similarSeries = array();
		foreach ($similarSeriesData->series as $similarSeriesInfo){
			$similarSeries[] = array(
				'title' => $similarSeriesInfo->full_name,
				'author' => $similarSeriesInfo->author,
				'reason' => $similarSeriesInfo->reason,
				'link' => 'Union/Search/?lookfor='. $similarSeriesInfo->full_name . " AND " . $similarSeriesInfo->author,
			);
		}
		$enrichment->similarSeries = $similarSeries;
		$enrichment->similarSeriesCount = count($similarSeries);
	}

	private function loadSimilarTitleInfo($currentId, $similarTitles, &$enrichment){
		$items = $similarTitles->titles;
		$titlesOwned = 0;
		$similarTitlesReturn = array();
		$this->loadNoveListTitles($currentId, $items, $similarTitlesReturn, $titlesOwned);
		$enrichment->similarTitles = $similarTitlesReturn;
		$enrichment->similarTitleCount = count($items);
		$enrichment->similarTitleCountOwned = $titlesOwned;
	}

	private function loadNoveListTitles($currentId, $items, &$titleList, &$titlesOwned, $seriesName = ''){
		global $timer;
		global $configArray;
		$timer->logTime("Start loadNoveListTitle");

		/** @var SearchObject_Solr $searchObject */
		$searchObject = SearchObjectFactory::initSearchObject();
		//$searchObject->disableScoping();
		if (function_exists('disableErrorHandler')){
			disableErrorHandler();
		}

		//Get all of the records that could match based on ISBN
		$allIsbns = "";
		foreach ($items as $item){
			if (count($item->isbns) > 0){
				if (strlen($allIsbns) > 0){
					$allIsbns .= ' OR ';
				}
				$allIsbns .= implode(' OR ', $item->isbns);
			}
		}
		$searchObject->setBasicQuery($allIsbns, "isbn");
		$searchObject->clearFacets();
		$searchObject->disableSpelling();
		$searchObject->disableLogging();
		$searchObject->setLimit(count($items));
		$response = $searchObject->processSearch(true, false, false);

		//Get all the titles from the catalog
		$titlesFromCatalog = array();
		if ($response && isset($response['response'])) {
			//Get information about each project
			if ($searchObject->getResultTotal() > 0) {
				foreach ($response['response']['docs'] as $fields) {
					$recordDriver = new GroupedWorkDriver($fields);
					$timer->logTime("Create driver");

					if ($recordDriver->isValid){
						//Load data about the record
						$ratingData = $recordDriver->getRatingData();
						$timer->logTime("Get Rating data");
						$fullRecordLink = $recordDriver->getLinkUrl();

						//See if we can get the series title from the record
						$curTitle = array(
								'title' => $recordDriver->getTitle(),
								'title_short' => $recordDriver->getTitle(),
								'author' => $recordDriver->getPrimaryAuthor(),
							//'publicationDate' => (string)$item->PublicationDate,
								'isbn' => $recordDriver->getCleanISBN(),
								'allIsbns' => $recordDriver->getISBNs(),
								'isbn10' => $recordDriver->getCleanISBN(),
								'upc' => $recordDriver->getCleanUPC(),
								'recordId' => $recordDriver->getPermanentId(),
								'recordtype' => 'grouped_work',
								'id' => $recordDriver->getPermanentId(), //This allows the record to be displayed in various locations.
								'libraryOwned' => true,
								'shortId' => $recordDriver->getPermanentId(),
								'format_category' => $recordDriver->getFormatCategory(),
								'ratingData' => $ratingData,
								'fullRecordLink' => $fullRecordLink,
								'recordDriver' => $recordDriver,
								'smallCover' => $recordDriver->getBookcoverUrl('small'),
								'mediumCover' => $recordDriver->getBookcoverUrl('medium'),
						);
						$timer->logTime("Load title information");
						$titlesOwned++;
						$titlesFromCatalog[] = $curTitle;
					}

				}
			}
		}

		//Loop through items an match to records we found in the catalog.
		$titleList = array();
		foreach ($items as $index => $item){
			$titleList[$index] = null;
		}
		//Do 2 passes, one to check based on primary_isbn only and one to check based on all isbns
		foreach ($items as $index => $item){
			$isInCatalog = false;
			$titleFromCatalog = null;
			foreach ($titlesFromCatalog as $titleIndex => $titleFromCatalog){
				if (in_array($item->primary_isbn, $titleFromCatalog['allIsbns'])){
					$isInCatalog = true;
				}

				if ($isInCatalog) break;
			}
			if ($isInCatalog){
				$titleList = $this->addTitleToTitleList($currentId, $titleList, $seriesName, $titleFromCatalog, $titlesFromCatalog, $titleIndex, $item, $configArray, $index);
			}
		}

		foreach ($titleList as $index => $title){
			if ($titleList[$index] == null){
				$isInCatalog = false;
				$item = $items[$index];
				foreach ($titlesFromCatalog as $titleIndex => $titleFromCatalog) {
					foreach ($item->isbns as $isbn) {
						if (in_array($isbn, $titleFromCatalog['allIsbns'])) {
							$isInCatalog = true;
							break;
						}
					}
					if ($isInCatalog){
						break;
					}
				}

				if ($isInCatalog) {
					$titleList = $this->addTitleToTitleList($currentId, $titleList, $seriesName, $titleFromCatalog, $titlesFromCatalog, $titleIndex, $item, $configArray, $index);
					unset($titlesFromCatalog[$titleIndex]);
				}else{
					$isbn = reset($item->isbns);
					$isbn13 = strlen($isbn) == 13 ? $isbn : ISBNConverter::convertISBN10to13($isbn);
					$isbn10 = strlen($isbn) == 10 ? $isbn : ISBNConverter::convertISBN13to10($isbn);
					$curTitle = array(
							'title' => $item->full_title,
							'author' => $item->author,
						//'publicationDate' => (string)$item->PublicationDate,
							'isbn' => $isbn13,
							'isbn10' => $isbn10,
							'recordId' => -1,
							'libraryOwned' => false,
							'smallCover' => $cover = $configArray['Site']['coverUrl'] . "/bookcover.php?size=small&isn=" . $isbn13,
							'mediumCover' => $cover = $configArray['Site']['coverUrl'] . "/bookcover.php?size=medium&isn=" . $isbn13,
					);

					$curTitle['isCurrent'] = $currentId == $curTitle['recordId'];
					$curTitle['series'] = isset($seriesName) ? $seriesName : '';;
					$curTitle['volume'] = isset($item->volume) ? $item->volume : '';
					$curTitle['reason'] = isset($item->reason) ? $item->reason : '';

					$titleList[$index] = $curTitle;
				}

			}

		}

	}

	private function loadRelatedContent($relatedContent, &$enrichment) {
		$relatedContentReturn = array();
		foreach ($relatedContent->doc_types as $contentSection){
			$section = array(
				'title' => $contentSection->doc_type,
				'content' => array(),
			);
			foreach ($contentSection->content as $content){
				//print_r($content);
				$contentUrl = $content->links[0]->url;
				$section['content'][] = array(
					'author' => $content->feature_author,
					'title' => $content->title,
					'contentUrl' => $contentUrl,
				);
			}
			$relatedContentReturn[] = $section;
		}
		$enrichment->relatedContent = $relatedContentReturn;
	}

	private function loadGoodReads($goodReads, &$enrichment) {
		$goodReadsInfo = array(
			'inGoodReads' => $goodReads->is_in_goodreads,
			'averageRating' => $goodReads->average_rating,
			'numRatings' => $goodReads->ratings_count,
			'numReviews' => $goodReads->reviews_count,
			'sampleReviewsUrl' => $goodReads->links[0]->url,
		);
		$enrichment->goodReads = $goodReadsInfo;
	}

	/**
	 * @param $currentId
	 * @param $titleList
	 * @param $seriesName
	 * @param $isInCatalog
	 * @param $titleFromCatalog
	 * @param $titlesFromCatalog
	 * @param $titleIndex
	 * @param $item
	 * @param $configArray
	 * @param $index
	 * @return array titleList
	 */
	private function addTitleToTitleList($currentId, &$titleList, $seriesName, $titleFromCatalog, &$titlesFromCatalog, $titleIndex, $item, $configArray, $index)
	{

		$curTitle = $titleFromCatalog;
		//Only use each title once if possible
		unset($titlesFromCatalog[$titleIndex]);

		$curTitle['isCurrent'] = $currentId == $curTitle['recordId'];
		$curTitle['series'] = isset($seriesName) ? $seriesName : '';;
		$curTitle['volume'] = isset($item->volume) ? $item->volume : '';
		$curTitle['reason'] = isset($item->reason) ? $item->reason : '';

		$titleList[$index] = $curTitle;
		return $titleList;
	}
}