<?php
require_once(ROOT_DIR . '/Drivers/marmot_inc/ISBNConverter.php');
require_once ROOT_DIR . '/sys/Novelist/NovelistData.php';
require_once ROOT_DIR . '/sys/HTTP/HTTP_Request.php';
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

    /**
     * @param $groupedRecordId
     * @param $isbns
     * @param bool $allowReload
     * @return NovelistData
     */
	function loadBasicEnrichment($groupedRecordId, $isbns, $allowReload = true){
		$novelistData = $this->getRawNovelistData($groupedRecordId, $isbns, $allowReload);
		if (!empty($novelistData)){
			$data = $novelistData->getJsonData();
			if (isset($data->FeatureContent) && $data->FeatureCount > 0){
				$novelistData->hasNovelistData = true;
				//We got data!
				$novelistData->primaryISBN = $data->TitleInfo->primary_isbn;

				//Series Information
				if (isset($data->FeatureContent->SeriesInfo)){
					$this->loadSeriesInfoFast($data->FeatureContent->SeriesInfo, $novelistData);
				}
			}
		}
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
		$novelistData = $this->getRawNovelistData($groupedRecordId, $isbns);
		if (!empty($novelistData)){
			$data = $novelistData->getJsonData();
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
			}
		}
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
		$novelistData = $this->getRawNovelistData($groupedRecordId, $isbns);
		if (!empty($novelistData)){
			$data = $novelistData->getJsonData();
			if (isset($data->FeatureContent) && $data->FeatureCount > 0){
				$novelistData->hasNovelistData = true;
				//We got data!
				$novelistData->primaryISBN = $data->TitleInfo->primary_isbn;

				//Similar Titles
				if (isset($data->FeatureContent->SimilarTitles)){
					$this->loadSimilarTitleInfo($groupedRecordId, $data->FeatureContent->SimilarTitles, $novelistData);
				}
			}
		}

		return $novelistData;
	}

	/**
	 * @param string $groupedRecordId
	 * @param string[] $isbns
	 * @return NovelistData|null
	 */
	function getRawNovelistData($groupedRecordId, $isbns, $allowReload = true){
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

		//Now check the database
		$novelistData = new NovelistData();
		$novelistData->groupedRecordPermanentId = $groupedRecordId;
		$doUpdate = true;
		if ($novelistData->find(true)){
			$now = time();
			if ($novelistData->lastUpdate < $now - (7 * 24 * 60 * 60)){
				$doUpdate = true;
			}else{
				$doUpdate = false;
			}
		}

		$data = null;
		if ($allowReload && ($doUpdate || isset($_REQUEST['reload']))){
			$novelistData->groupedRecordHasISBN = count($isbns) > 0;

			if ($doUpdate && $novelistData->primaryISBN != null && strlen($novelistData->primaryISBN) > 0){
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

				$bestIsbn = '';
				$bestRawJson = '';
				//Check each ISBN for enrichment data
				foreach ($isbns as $isbn){
					$requestUrl = "http://novselect.ebscohost.com/Data/ContentByQuery?profile=$profile&password=$pwd&ClientIdentifier={$isbn}&isbn={$isbn}&version=2.1&tmpstmp=" . time();
					//echo($requestUrl);
					try{
						//Get the JSON from the service
						disableErrorHandler();
						$req = new HTTP_Request($requestUrl);
						//$result = file_get_contents($req);
						if ($req->sendRequest() instanceof AspenError) {
							enableErrorHandler();
							//No enrichment for this isbn, go to the next one
							continue;
						}
						enableErrorHandler();

						$response = $req->getResponseBody();
						$timer->logTime("Made call to Novelist for enrichment information");

						if (strlen($response) > strlen($bestRawJson)){
							$bestRawJson = $response;
							$bestIsbn = $isbn;
						}

						//Parse the JSON
						$decodedData = json_decode($response);
						if (count($decodedData->FeatureCount) > 0){
							break;
						}
					}catch (Exception $e) {
						global $logger;
						$logger->log("Error fetching data from NoveList $e", Logger::LOG_ERROR);
						if (isset($response)){
							$logger->log($response, Logger::LOG_DEBUG);
						}
						$data = null;
					}
				}
				$novelistData->jsonResponse = $bestRawJson;
				$novelistData->primaryISBN = $bestIsbn;
			}

			$novelistData->update();
		}

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
		$novelistData = $this->getRawNovelistData($groupedRecordId, $isbns);
		if (!empty($novelistData)){
			$data = $novelistData->getJsonData();

			if (isset($data->FeatureContent) && $data->FeatureCount > 0){
				$novelistData->hasNovelistData = true;
				//We got data!
				$novelistData->primaryISBN = $data->TitleInfo->primary_isbn;

				//Similar Authors
				if (isset($data->FeatureContent->SimilarAuthors)){
					$this->loadSimilarAuthorInfo($data->FeatureContent->SimilarAuthors, $novelistData);
				}
			}
		}

		return $novelistData;
	}

    /**
     * @param $feature
     * @param NovelistData $enrichment
     */
	private function loadSimilarAuthorInfo($feature, &$enrichment){
		$authors = array();
		$items = $feature->authors;
		foreach ($items as $item){
			$authors[] = array(
				'name' => $item->full_name,
				'reason' => $item->reason,
				'link' => '/Author/Home/?author="'. urlencode($item->main_name) . '"',
			);
		}
		$enrichment->setAuthors($authors);
	}

	/**
	 * Loads Novelist data from Novelist for a grouped record
	 *
	 * @param String    $groupedRecordId  The permanent id of the grouped record
	 * @param String[]  $isbns            a list of ISBNs for the record
	 * @return NovelistData
	 */
	function getSeriesTitles($groupedRecordId, $isbns){
		$novelistData = $this->getRawNovelistData($groupedRecordId, $isbns);
		if (!empty($novelistData)){
			$data = $novelistData->getJsonData();

			if (isset($data->FeatureContent) && $data->FeatureCount > 0){
				$novelistData->hasNovelistData = true;
				//We got data!
				$novelistData->primaryISBN = $data->TitleInfo->primary_isbn;

				//Series Information
				if (isset($data->FeatureContent->SeriesInfo) && count($data->FeatureContent->SeriesInfo->series_titles) > 0){
					$this->loadSeriesInfo($groupedRecordId, $data->FeatureContent->SeriesInfo, $novelistData);

				}
			}
		}

		return $novelistData;
	}

	/**
	 * @param SimpleXMLElement $seriesData
	 * @param NovelistData $novelistData
	 */
	private function loadSeriesInfoFast($seriesData, &$novelistData){
		/** @noinspection PhpUndefinedFieldInspection */
		$seriesName = $seriesData->full_title;
		/** @noinspection PhpUndefinedFieldInspection */
		$items = $seriesData->series_titles;
		foreach ($items as $item){
			/** @noinspection PhpUndefinedFieldInspection */
			if ($item->primary_isbn == $novelistData->primaryISBN){
				/** @noinspection PhpUndefinedFieldInspection */
				$volume = $item->volume;
				$volume = preg_replace('/^0+/', '', $volume);
				$novelistData->volume = $volume;
			}
		}
		$novelistData->seriesTitle = $seriesName;
		/** @noinspection PhpUndefinedFieldInspection */
		$novelistData->seriesNote = $seriesData->series_note;
		if (strlen($novelistData->seriesNote) > 255){
			require_once ROOT_DIR . '/sys/Utils/StringUtils.php';
			$novelistData->seriesNote = StringUtils::truncate($novelistData->seriesNote, 255);
		}
	}

    /**
     * @param $currentId
     * @param $seriesData
     * @param NovelistData $novelistData
     */
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
				$novelistData->update();
			}elseif ($curTitle['libraryOwned']){
				$novelistDataForTitle = new NovelistData();
				$novelistDataForTitle->groupedRecordPermanentId = $curTitle['id'];
				if (!$novelistDataForTitle->find()){
					$novelistDataForTitle->hasNovelistData = 1;
					$novelistDataForTitle->primaryISBN = $curTitle['isbn'];
					$novelistDataForTitle->groupedRecordHasISBN = count($curTitle['allIsbns']) > 0;
					$novelistDataForTitle->seriesTitle = $curTitle['series'];
					$novelistDataForTitle->volume = $curTitle['volume'];
					$novelistDataForTitle->seriesNote = $seriesData->series_note;
					$novelistDataForTitle->update();
				}
			}
		}
		$novelistData->setSeriesTitles($seriesTitles);
		$novelistData->seriesTitle = $seriesName;
		$novelistData->seriesNote = $seriesData->series_note;
		if (strlen($novelistData->seriesNote) > 255){
			require_once ROOT_DIR . '/sys/Utils/StringUtils.php';
			$novelistData->seriesNote = StringUtils::truncate($novelistData->seriesNote, 255);
		}

		$novelistData->setSeriesCount(count($items));
		$novelistData->setSeriesCountOwned($titlesOwned);
		$novelistData->setSeriesDefaultIndex(1);
		$curIndex = 0;
		foreach ($seriesTitles as $title){

			if ($title['isCurrent']){
				$novelistData->setSeriesDefaultIndex($curIndex);
			}
			$curIndex++;
		}
	}

    /**
     * @param $similarSeriesData
     * @param NovelistData $enrichment
     */
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
		$enrichment->setSimilarSeries($similarSeries);
	}

    /**
     * @param $currentId
     * @param $similarTitles
     * @param NovelistData $enrichment
     */
	private function loadSimilarTitleInfo($currentId, $similarTitles, &$enrichment){
		$items = $similarTitles->titles;
		$titlesOwned = 0;
		$similarTitlesReturn = array();
		$this->loadNoveListTitles($currentId, $items, $similarTitlesReturn, $titlesOwned);
		$enrichment->setSimilarTitles($similarTitlesReturn);
		$enrichment->setSimilarTitleCountOwned($titlesOwned);
	}

	private function loadNoveListTitles($currentId, $items, &$titleList, &$titlesOwned, $seriesName = ''){
		global $timer;
		$timer->logTime("Start loadNoveListTitle");

		/** @var SearchObject_GroupedWorkSearcher $searchObject */
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
				$titleList = $this->addTitleToTitleList($currentId, $titleList, $seriesName, $titleFromCatalog, $titlesFromCatalog, $titleIndex, $item, $index);
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
					$titleList = $this->addTitleToTitleList($currentId, $titleList, $seriesName, $titleFromCatalog, $titlesFromCatalog, $titleIndex, $item, $index);
					unset($titlesFromCatalog[$titleIndex]);
				}else{
					$isbn = reset($item->isbns);
					$isbn13 = strlen($isbn) == 13 ? $isbn : ISBNConverter::convertISBN10to13($isbn);
					$isbn10 = strlen($isbn) == 10 ? $isbn : ISBNConverter::convertISBN13to10($isbn);
					$curTitle = array(
							'title' => $item->full_title,
							'author' => $item->author,
							'isbn' => $isbn13,
							'isbn10' => $isbn10,
							'recordId' => -1,
							'libraryOwned' => false,
							'smallCover' => "/bookcover.php?size=small&isn=" . $isbn13,
							'mediumCover' => "/bookcover.php?size=medium&isn=" . $isbn13,
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
	private function addTitleToTitleList($currentId, &$titleList, $seriesName, $titleFromCatalog, &$titlesFromCatalog, $titleIndex, $item, $index)
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