<?php
require_once(ROOT_DIR . '/Drivers/marmot_inc/ISBNConverter.php');
require_once ROOT_DIR . '/sys/Enrichment/NovelistData.php';
require_once ROOT_DIR . '/sys/CurlWrapper.php';

class Novelist3 {

	function doesGroupedWorkHaveCachedSeries($groupedRecordId) : bool {
		$novelistData = new NovelistData();
		if ($groupedRecordId != null && $groupedRecordId != '') {
			$novelistData->groupedRecordPermanentId = $groupedRecordId;
			if ($novelistData->find(true)) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	/**
	 * @param $groupedRecordId
	 * @param $isbns
	 * @param bool $allowReload
	 * @return NovelistData
	 */
	function loadBasicEnrichment($groupedRecordId, $isbns, $allowReload = true) {
		$novelistData = $this->getRawNovelistData($groupedRecordId, $isbns, $allowReload);
		if (!empty($novelistData)) {
			if (empty($novelistData->seriesTitle) || isset($_REQUEST['reload'])) {
				$data = $novelistData->getJsonData();
				if (isset($data->FeatureContent) && $data->FeatureCount > 0) {
					//Series Information
					if (isset($data->FeatureContent->SeriesInfo)) {
						$this->loadSeriesInfoFast($data, $novelistData);
					}
				}
			}
		}
		return $novelistData;
	}

	/**
	 * Loads NoveList data from NoveList for a grouped record
	 *
	 * @param String $groupedRecordId The permanent id of the grouped record
	 * @param String[] $isbns a list of ISBNs for the record
	 * @return NovelistData
	 */
	function loadEnrichment($groupedRecordId, $isbns) {
		$novelistData = $this->getRawNovelistData($groupedRecordId, $isbns);
		if (!empty($novelistData)) {
			$data = $novelistData->getJsonData();
			if (isset($data->FeatureContent) && $data->FeatureCount > 0) {
				//Series Information
				if (isset($data->FeatureContent->SeriesInfo)) {
					$this->loadSeriesInfo($groupedRecordId, $data->FeatureContent->SeriesInfo, $novelistData);
				}

				//Similar Titles
				if (isset($data->FeatureContent->SimilarTitles)) {
					$this->loadSimilarTitleInfo($groupedRecordId, $data->FeatureContent->SimilarTitles, $novelistData);
				}

				//Similar Authors
				if (isset($data->FeatureContent->SimilarAuthors)) {
					$this->loadSimilarAuthorInfo($data->FeatureContent->SimilarAuthors, $novelistData);
				}

				//Similar Series
				if (isset($data->FeatureContent->SimilarSeries)) {
					$this->loadSimilarSeries($data->FeatureContent->SimilarSeries, $novelistData);
				}

				//Related Content
				if (isset($data->FeatureContent->RelatedContent)) {
					$this->loadRelatedContent($data->FeatureContent->RelatedContent, $novelistData);
				}

				//GoodReads Ratings
				if (isset($data->FeatureContent->GoodReads)) {
					$this->loadGoodReads($data->FeatureContent->GoodReads, $novelistData);
				}
			}
		}
		return $novelistData;
	}

	/**
	 * Loads NoveList data from NoveList for a grouped record
	 *
	 * @param String $groupedRecordId The permanent id of the grouped record
	 * @param String[] $isbns a list of ISBNs for the record
	 * @return NovelistData
	 */
	function getSimilarTitles($groupedRecordId, $isbns) {
		$novelistData = $this->getRawNovelistData($groupedRecordId, $isbns);
		if (!empty($novelistData)) {
			$data = $novelistData->getJsonData();
			if (isset($data->FeatureContent) && $data->FeatureCount > 0) {
				//Similar Titles
				if (isset($data->FeatureContent->SimilarTitles)) {
					$this->loadSimilarTitleInfo($groupedRecordId, $data->FeatureContent->SimilarTitles, $novelistData);
				}
			}
		}

		return $novelistData;
	}

	function getRawNovelistDataISBN($isbn, $infoSetting) {
		global $timer;
		$novelistSettings = $this->getNovelistSettings();
		if ($novelistSettings == null) {
			return null;
		}

		$requestUrl = "https://novselect.ebscohost.com/Data/ContentByQuery?profile={$novelistSettings->profile}&password={$novelistSettings->pwd}&ClientIdentifier=test&ISBN={$isbn}&version=2.6&tmpstmp=" . time();

		//echo($requestUrl);
		try {
			//Get the JSON from the service
			disableErrorHandler();
			$req = new CurlWrapper();
			$req->setConnectTimeout(5);
			$req->setTimeout(20);

			$response = $req->curlGetPage($requestUrl);
			ExternalRequestLogEntry::logRequest('novelist.contentByQuery', 'GET', $requestUrl, [], '', $req->getResponseCode(), $response, ['password' => $novelistSettings->pwd]);
			$timer->logTime("Made call to NoveList for enrichment information $isbn");


			//Parse the JSON
			$decodedData = json_decode($response);
			//Get the ISBN
			if (!empty($decodedData->TitleInfo) && !empty($decodedData->TitleInfo->primary_isbn)) {
				if ($infoSetting == "off") {
					$isbn = $decodedData->TitleInfo->primary_isbn;
					$series_title = "None";
					if (!empty($decodedData->FeatureContent->SeriesInfo->series_titles)) {
						foreach ($decodedData->FeatureContent->SeriesInfo->series_titles as $seriesTitle) {
							if ($seriesTitle->primary_isbn == $isbn) {
								$series_title = $seriesTitle;
								break;
							} else {
								$series_title = null;
							}
						}
					}
					if (empty($decodedData->FeatureContent->SeriesInfo)){
						$isbnNovelistData = $decodedData;
					}
					else{
						$decodedData->FeatureContent->SeriesInfo->series_titles = null;
						$decodedData->TitleInfo->manifestations = null;

						$isbnNovelistData = array(
							"Title Info" => $decodedData->TitleInfo,
							"Series Info" => $series_title,
							"Feature Content" =>$decodedData->FeatureContent,
						);
					}
				}else {
					$isbnNovelistData = $decodedData;
				}
			}
			else{
				return "No data available";
			}
		} catch (Exception $e) {
			global $logger;
			$logger->log("Error fetching data from NoveList $e", Logger::LOG_ERROR);
			if (isset($response)) {
				$logger->log($response, Logger::LOG_DEBUG);
			}
			$isbnNovelistData = null;
		}

		return $isbnNovelistData;

	}
	/**
	 * @param string $groupedRecordId
	 * @param string[] $isbns
	 * @param bool $allowReload
	 * @return NovelistData|null
	 */
	function getRawNovelistData($groupedRecordId, $isbns, $allowReload = true) {
		global $timer;
		$novelistSettings = $this->getNovelistSettings();

		//First make sure that NoveList is enabled
		if ($novelistSettings == null) {
			return null;
		}

		if ($groupedRecordId == null || $groupedRecordId == '') {
			return null;
		}

		//Now check the database
		$novelistData = new NovelistData();
		$novelistData->groupedRecordPermanentId = $groupedRecordId;
		$doUpdate = true;
		if ($novelistData->find(true)) {
			$now = time();
			if ($novelistData->lastUpdate < $now - (7 * 24 * 60 * 60)) {
				$doUpdate = true;
			} else {
				$doUpdate = false;
			}
		}

		$data = null;
		if (($allowReload && $doUpdate) || isset($_REQUEST['reload'])) {
			$novelistData->groupedRecordHasISBN = count($isbns) > 0;

			if ($doUpdate && $novelistData->primaryISBN != null && strlen($novelistData->primaryISBN) > 0) {
				//Just check the primary ISBN since we know that was good.
				$isbns = [$novelistData->primaryISBN];
			}

			//Update the last update time to optimize caching
			$novelistData->lastUpdate = time();

			if (count($isbns) == 0) {
				//Whoops, no ISBNs, can't get enrichment for this
				$novelistData->hasNovelistData = 0;
			} else {
				$novelistData->hasNovelistData = 0;

				//Check up to 50 ISBNs for enrichment data, NoveList now accepts these all at once and we should generally just get back response
				if (count($isbns) > 50) {
					$isbns = array_slice($isbns, 0, 50);
				}
				$isbnParam = '';
				foreach ($isbns as $isbn) {
					$isbnParam .= "&ISBN={$isbn}";
				}
				$requestUrl = "https://novselect.ebscohost.com/Data/ContentByQuery?profile={$novelistSettings->profile}&password={$novelistSettings->pwd}&ClientIdentifier=test{$isbnParam}&version=2.6&tmpstmp=" . time();

				//echo($requestUrl);
				try {
					//Get the JSON from the service
					disableErrorHandler();
					$req = new CurlWrapper();
					$req->setConnectTimeout(5);
					$req->setTimeout(20);

					$response = $req->curlGetPage($requestUrl);
					ExternalRequestLogEntry::logRequest('novelist.contentByQuery', 'GET', $requestUrl, [], '', $req->getResponseCode(), $response, ['password' => $novelistSettings->pwd]);
					$timer->logTime("Made call to NoveList for enrichment information $isbnParam");


					//Parse the JSON
					$decodedData = json_decode($response);
					$bestResponse = '';
					$primaryISBN = '';
					$numManifestationsForBest = -1;
					$numISBNMatchesForBest = -1;
					//Get the ISBN
					if (!empty($decodedData->titles)) {
						foreach ($decodedData->titles as $title) {
							if (!is_null($title->TitleInfo)) {
								$numManifestations = count($title->TitleInfo->manifestations);
								$numISBNMatches = 0;
								foreach ($title->TitleInfo->manifestations as $manifestation) {
									if (in_array($manifestation->ISBN, $isbns)) {
										$numISBNMatches = $numISBNMatches + 1;
									}
								}
								if (($numManifestations > 0 && $numISBNMatches > $numISBNMatchesForBest)) {
									$novelistData->hasNovelistData = 1;

									$bestResponse = json_encode($title);
									if (!empty($title->TitleInfo->primary_isbn)) {
										$primaryISBN = $title->TitleInfo->primary_isbn;
									}
									$numManifestationsForBest = $numManifestations;
									$numISBNMatchesForBest = $numISBNMatches;
								}
							}
						}
					} else {
						if (!empty($decodedData->TitleInfo) && !empty($decodedData->TitleInfo->primary_isbn)) {
							$bestResponse = json_encode($decodedData);
							$primaryISBN = $decodedData->TitleInfo->primary_isbn;
						}
					}
					if (!empty($bestResponse)) {
						$novelistData->jsonResponse = $bestResponse;
						$novelistData->primaryISBN = $primaryISBN;
					}
				} catch (Exception $e) {
					global $logger;
					$logger->log("Error fetching data from NoveList $e", Logger::LOG_ERROR);
					if (isset($response)) {
						$logger->log($response, Logger::LOG_DEBUG);
					}
					$data = null;
				}
			}

			$novelistData->update();
		}

		return $novelistData;
	}

	/**
	 * Loads NoveList data from NoveList for a grouped record
	 *
	 * @param String $groupedRecordId The permanent id of the grouped record
	 * @param String[] $isbns a list of ISBNs for the record
	 * @return NovelistData
	 */
	function getSimilarAuthors($groupedRecordId, $isbns) {
		$novelistData = $this->getRawNovelistData($groupedRecordId, $isbns);
		if (!empty($novelistData)) {
			$data = $novelistData->getJsonData();

			if (isset($data->FeatureContent) && $data->FeatureCount > 0) {
				//Similar Authors
				if (isset($data->FeatureContent->SimilarAuthors)) {
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
	private function loadSimilarAuthorInfo($feature, &$enrichment) {
		$authors = [];
		$items = $feature->authors;
		foreach ($items as $item) {
			$authors[] = [
				'name' => $item->full_name,
				'reason' => $item->reason,
				'link' => '/Author/Home/?author="' . urlencode($item->main_name) . '"',
			];
		}
		$enrichment->setAuthors($authors);
	}

	/**
	 * Loads NoveList data from NoveList for a grouped record
	 *
	 * @param String $groupedRecordId The permanent id of the grouped record
	 * @param String[] $isbns a list of ISBNs for the record
	 * @return NovelistData
	 */
	function getSeriesTitles($groupedRecordId, $isbns) {
		$novelistData = $this->getRawNovelistData($groupedRecordId, $isbns);
		if (!empty($novelistData)) {
			$data = $novelistData->getJsonData();

			if (isset($data->FeatureContent) && $data->FeatureCount > 0) {
				//Series Information
				if (isset($data->FeatureContent->SeriesInfo) && count($data->FeatureContent->SeriesInfo->series_titles) > 0) {
					$this->loadSeriesInfo($groupedRecordId, $data->FeatureContent->SeriesInfo, $novelistData);

				}
			}
		}

		return $novelistData;
	}

	/**
	 * @param stdClass $data decoded json data
	 * @param NovelistData $novelistData
	 */
	private function loadSeriesInfoFast($data, &$novelistData) {
		$seriesData = $data->FeatureContent->SeriesInfo;
		$seriesName = $seriesData->full_title;
		$items = $seriesData->series_titles;

		//If we don't get additional series titles, don't mark this as being good novelist data
		if (count($items) == 0) {
			$novelistData->seriesTitle = null;
			$novelistData->volume = null;
			$novelistData->seriesNote = null;
		} else {
			foreach ($items as $item) {
				$isbns = $this->getIsbnsForNovelistTitle($item);
				if (in_array($novelistData->primaryISBN, $isbns)) {
					$novelistData->volume = $this->normalizeSeriesVolume($item->volume);
					break;
				} elseif ($item->main_title == $data->TitleInfo->main_title) {
					$novelistData->volume = $this->normalizeSeriesVolume($item->volume);
					break;
				}
			}
			$novelistData->seriesTitle = $seriesName;
			$novelistData->setSeriesNote($seriesData->series_note);
		}
		$novelistData->update();
	}

	/**
	 * @param $currentId
	 * @param $seriesData
	 * @param NovelistData $novelistData
	 */
	private function loadSeriesInfo($currentId, $seriesData, &$novelistData) {
		$seriesName = $seriesData->full_title;
		$seriesTitles = [];
		$items = $seriesData->series_titles;
		$titlesOwned = 0;
		$this->loadNoveListTitles($currentId, $items, $seriesTitles, $titlesOwned, $seriesName);
		foreach ($seriesTitles as $curTitle) {
			if ($curTitle['isCurrent'] && isset($curTitle['volume']) && strlen($curTitle['volume']) > 0) {
				$enrichment['volumeLabel'] = (isset($curTitle['volume']) ? ('volume ' . $curTitle['volume']) : '');
				$novelistData->volume = $this->normalizeSeriesVolume($curTitle['volume']);
				$novelistData->update();
			} elseif ($curTitle['libraryOwned']) {
				$novelistDataForTitle = new NovelistData();
				$novelistDataForTitle->groupedRecordPermanentId = $curTitle['id'];
				if (!$novelistDataForTitle->find(true)) {
					$novelistDataForTitle->hasNovelistData = 1;
					$novelistDataForTitle->primaryISBN = $curTitle['isbn'];
					$novelistDataForTitle->groupedRecordHasISBN = count($curTitle['allIsbns']) > 0;
					$novelistDataForTitle->seriesTitle = $curTitle['series'];
					$novelistDataForTitle->volume = $this->normalizeSeriesVolume($curTitle['volume']);
					$novelistDataForTitle->setSeriesNote($seriesData->series_note);

					$novelistDataForTitle->insert();
				} elseif (empty($novelistDataForTitle->seriesTitle) || empty($novelistDataForTitle->volume)) {
					$novelistDataForTitle->seriesTitle = $curTitle['series'];
					$novelistDataForTitle->volume = $this->normalizeSeriesVolume($curTitle['volume']);
					$novelistDataForTitle->update();
				}
			}
		}
		$novelistData->setSeriesTitles($seriesTitles);
		$novelistData->seriesTitle = $seriesName;
		$novelistData->setSeriesNote($seriesData->series_note);

		$novelistData->setSeriesCount(count($items));
		$novelistData->setSeriesCountOwned($titlesOwned);
		$novelistData->setSeriesDefaultIndex(1);
		$curIndex = 0;
		foreach ($seriesTitles as $title) {

			if ($title['isCurrent']) {
				$novelistData->setSeriesDefaultIndex($curIndex);
			}
			$curIndex++;
		}
	}

	/**
	 * @param $similarSeriesData
	 * @param NovelistData $enrichment
	 */
	private function loadSimilarSeries($similarSeriesData, &$enrichment) {
		$similarSeries = [];
		foreach ($similarSeriesData->series as $similarSeriesInfo) {
			$similarSeries[] = [
				'title' => $similarSeriesInfo->full_name,
				'author' => $similarSeriesInfo->author,
				'reason' => $similarSeriesInfo->reason,
				'link' => 'Union/Search/?lookfor=' . $similarSeriesInfo->full_name . " AND " . $similarSeriesInfo->author,
			];
		}
		$enrichment->setSimilarSeries($similarSeries);
	}

	/**
	 * @param $currentId
	 * @param $similarTitles
	 * @param NovelistData $enrichment
	 */
	private function loadSimilarTitleInfo($currentId, $similarTitles, &$enrichment) {
		$items = $similarTitles->titles;
		$titlesOwned = 0;
		$similarTitlesReturn = [];
		$this->loadNoveListTitles($currentId, $items, $similarTitlesReturn, $titlesOwned);
		$enrichment->setSimilarTitles($similarTitlesReturn);
		$enrichment->setSimilarTitleCountOwned($titlesOwned);
	}

	private function loadNoveListTitles($currentId, $items, &$titleList, &$titlesOwned, $seriesName = '') {
		global $timer;
		$timer->logTime("Start loadNoveListTitle");

		$titleList = [];
		foreach ($items as $index => $item) {
			$titleList[$index] = null;

			//Do various lookups to figure out what to link to

			//check novelist cache by series
			if ($titleList[$index] == null && !empty($seriesName) && !empty($item->volume)) {
				//Check to see if we can get a grouped work id based on the volume and series name
				require_once ROOT_DIR . '/sys/Enrichment/NovelistData.php';
				$novelistData = new NovelistData();
				$novelistData->seriesTitle = $seriesName;
				if (isset($item->volume)) {
					$novelistData->volume = $this->normalizeSeriesVolume($item->volume);
				}
				if ($novelistData->find(true)) {
					$groupedWorkDriver = new GroupedWorkDriver($novelistData->groupedRecordPermanentId);
					if ($groupedWorkDriver->isValid()) {
						$titlesOwned++;
						$titleInfo = $this->setupTitleInfoForWork($groupedWorkDriver);
						$tempArray = [$titleInfo];
						$titleList = $this->addTitleToTitleList($currentId, $titleList, $seriesName, $titleInfo, $tempArray, 0, $item, $index);
					}
				}
			}

			//Load based on ISBN
			$isbns = $this->getIsbnsForNovelistTitle($item);
			if ($titleList[$index] == null && count($isbns) > 0) {
				$allIsbns = implode(' OR ', $isbns);

				//First check novelist cache by ISBN
				$novelistCache = new NovelistData();
				$allIsbnsQuoted = '';
				foreach ($isbns as $isbn) {
					if (strlen($allIsbnsQuoted) > 0) {
						$allIsbnsQuoted .= ', ';
					}
					$allIsbnsQuoted .= $novelistCache->escape($isbn);
				}
				$novelistCache->whereAdd();
				$novelistCache->whereAdd("primaryISBN IN ($allIsbnsQuoted)");
				if ($novelistCache->find(true)) {
					$groupedWorkDriver = new GroupedWorkDriver($novelistCache->groupedRecordPermanentId);
					if ($groupedWorkDriver->isValid()) {
						$titlesOwned++;
						$titleInfo = $this->setupTitleInfoForWork($groupedWorkDriver);
						$tempArray = [$titleInfo];
						$titleList = $this->addTitleToTitleList($currentId, $titleList, $seriesName, $titleInfo, $tempArray, 0, $item, $index);
					}
				}

				//Finally check solr by isbn
				if ($titleList[$index] == null) {
					//Now check solr
					/** @var SearchObject_AbstractGroupedWorkSearcher $searchObject */
					$searchObject = SearchObjectFactory::initSearchObject();
					$searchObject->clearFacets();
					$searchObject->disableSpelling();
					$searchObject->disableLogging();
					$searchObject->setLimit(1);
					$searchObject->setBasicQuery($allIsbns, "isbn");

					$response = $searchObject->processSearch(true, false, false);
					if ($response && isset($response['response'])) {
						//Get information about each project
						if ($searchObject->getResultTotal() > 0) {
							foreach ($response['response']['docs'] as $fields) {
								$recordDriver = new GroupedWorkDriver($fields);
								$timer->logTime("Create driver");

								if ($recordDriver->isValid) {
									$curTitle = $this->setupTitleInfoForWork($recordDriver);
									$timer->logTime("Load title information");
									$titlesOwned++;
									$titlesFromCatalog[] = $curTitle;
									$titleList = $this->addTitleToTitleList($currentId, $titleList, $seriesName, $curTitle, $titlesFromCatalog, 0, $item, $index);
								}

							}
						}
					}
				}
			}

			//If we got this far, we don't own the title
			if ($titleList[$index] == null) {

				$isbn = reset($isbns);
				$isbn13 = strlen($isbn) == 13 ? $isbn : ISBNConverter::convertISBN10to13($isbn);
				$isbn10 = strlen($isbn) == 10 ? $isbn : ISBNConverter::convertISBN13to10($isbn);
				$curTitle = [
					'title' => $item->full_title,
					'author' => $item->author,
					'isbn' => $isbn13,
					'isbn10' => $isbn10,
					'recordId' => -1,
					'libraryOwned' => false,
					'smallCover' => "/bookcover.php?size=small&isn=" . $isbn13,
					'mediumCover' => "/bookcover.php?size=medium&isn=" . $isbn13,
				];

				$curTitle['isCurrent'] = $currentId == $curTitle['recordId'];
				$curTitle['series'] = isset($seriesName) ? $seriesName : '';;
				$curTitle['volume'] = isset($item->volume) ? $this->normalizeSeriesVolume($item->volume) : '';
				$curTitle['reason'] = isset($item->reason) ? $item->reason : '';

				$titleList[$index] = $curTitle;

			}
		}
	}

	private function loadRelatedContent($relatedContent, &$enrichment) {
		$relatedContentReturn = [];
		foreach ($relatedContent->doc_types as $contentSection) {
			$section = [
				'title' => $contentSection->doc_type,
				'content' => [],
			];
			foreach ($contentSection->content as $content) {
				//print_r($content);
				$contentUrl = $content->links[0]->url;
				$section['content'][] = [
					'author' => $content->feature_author,
					'title' => $content->title,
					'contentUrl' => $contentUrl,
				];
			}
			$relatedContentReturn[] = $section;
		}
		$enrichment->relatedContent = $relatedContentReturn;
	}

	private function loadGoodReads($goodReads, &$enrichment) {
		$goodReadsInfo = [
			'inGoodReads' => $goodReads->is_in_goodreads,
			'averageRating' => $goodReads->average_rating,
			'numRatings' => $goodReads->ratings_count,
			'numReviews' => $goodReads->reviews_count,
			'sampleReviewsUrl' => $goodReads->links[0]->url,
		];
		$enrichment->goodReads = $goodReadsInfo;
	}

	/**
	 * @param string $currentId - Record Id of the current record we are looking at
	 * @param array $titleList - A list of all titles we are getting data for
	 * @param string $seriesName
	 * @param array $titleFromCatalog
	 * @param array $titlesFromCatalog
	 * @param int $titleIndex - The index of the title we are loading data for in titleList
	 * @param stdClass $item
	 * @param int $index
	 * @return array titleList
	 */
	private function addTitleToTitleList($currentId, &$titleList, $seriesName, $titleFromCatalog, &$titlesFromCatalog, $titleIndex, $item, $index) {

		$curTitle = $titleFromCatalog;
		//Only use each title once if possible
		unset($titlesFromCatalog[$titleIndex]);

		$curTitle['isCurrent'] = $currentId == $curTitle['recordId'];
		$curTitle['series'] = isset($seriesName) ? $seriesName : '';;
		$curTitle['volume'] = isset($item->volume) ? $this->normalizeSeriesVolume($item->volume) : '';
		$curTitle['reason'] = isset($item->reason) ? $item->reason : '';

		$titleList[$index] = $curTitle;
		return $titleList;
	}

	private function normalizeSeriesVolume($volume) {
		$volume = preg_replace('/^0+/', '', $volume);
		$volume = preg_replace('/\.$/', '', $volume);
		return trim($volume);
	}

	/**
	 * @param GroupedWorkDriver $recordDriver
	 * @return array
	 */
	private function setupTitleInfoForWork(GroupedWorkDriver $recordDriver): array {
		global $timer;
		//Load data about the record
		$ratingData = $recordDriver->getRatingData();
		$timer->logTime("Get Rating data");
		$fullRecordLink = $recordDriver->getLinkUrl();

		//See if we can get the series title from the record
		return [
			'title' => $recordDriver->getTitle(),
			'title_short' => $recordDriver->getTitle(),
			'author' => $recordDriver->getPrimaryAuthor(),
			'isbn' => $recordDriver->getCleanISBN(),
			'allIsbns' => $recordDriver->getISBNs(),
			'isbn10' => $recordDriver->getCleanISBN(),
			'upc' => $recordDriver->getCleanUPC(),
			'recordId' => $recordDriver->getPermanentId(),
			'recordtype' => 'grouped_work',
			'id' => $recordDriver->getPermanentId(),
			//This allows the record to be displayed in various locations.
			'libraryOwned' => true,
			'shortId' => $recordDriver->getPermanentId(),
			'format_category' => $recordDriver->getFormatCategory(),
			'ratingData' => $ratingData,
			'fullRecordLink' => $fullRecordLink,
			'recordDriver' => $recordDriver,
			'smallCover' => $recordDriver->getBookcoverUrl('small'),
			'mediumCover' => $recordDriver->getBookcoverUrl('medium'),
		];
	}

	/**
	 * @return NovelistSetting | null
	 */
	public function getNovelistSettings() {
		global $library;
		if ($library->novelistSettingId != -1) {
			require_once ROOT_DIR . '/sys/Enrichment/NovelistSetting.php';
			$novelistSettings = new NovelistSetting();
			$novelistSettings->id = $library->novelistSettingId;
			$novelistSettings->find(true);
			if ($novelistSettings->getNumResults() == 0) {
				return null;
			} else {
				return $novelistSettings;
			}
		}else{
			return null;
		}
	}

	private function getIsbnsForNovelistTitle($item) {
		$isbns = [];
		if (!empty($item->isbns)) {
			$isbns = $item->isbns;
		} else {
			if (!empty($item->manifestations)) {
				foreach ($item->manifestations as $manifestation) {
					if (!empty($manifestation->ISBN)) {
						$isbns[$manifestation->ISBN] = $manifestation->ISBN;
					}
				}
			}
		}
		return $isbns;
	}
}