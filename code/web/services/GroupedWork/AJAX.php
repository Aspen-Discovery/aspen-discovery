<?php
require_once ROOT_DIR . '/JSON_Action.php';

class GroupedWork_AJAX extends JSON_Action {
	/**
	 * Alias of deleteUserReview()
	 *
	 * @return array
	 */
	/** @noinspection PhpUnused */
	function clearUserRating() : array {
		return $this->deleteUserReview();
	}

	/** @noinspection PhpUnused */
	function deleteUserReview() : array {
		$id = $_REQUEST['id'];
		$result = ['result' => false];
		if (!UserAccount::isLoggedIn()) {
			$result['message'] = 'You must be logged in to delete ratings.';
		} else {
			require_once ROOT_DIR . '/sys/LocalEnrichment/UserWorkReview.php';
			$userWorkReview = new UserWorkReview();
			$userWorkReview->groupedRecordPermanentId = $id;
			$userWorkReview->userId = UserAccount::getActiveUserId();
			if ($userWorkReview->find(true)) {
				$userWorkReview->delete();
				$result = [
					'result' => true,
					'message' => 'We successfully deleted the rating for you.',
				];
			} else {
				$result['message'] = 'Sorry, we could not find that review in the system.';
			}
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	function forceReindex() : array {
		require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';

		$id = $_REQUEST['id'];
		$groupedWork = new GroupedWork();
		$groupedWork->permanent_id = $id;
		if ($groupedWork->find(true)) {
			$groupedWork->forceReindex(true);

			return [
				'success' => true,
				'title' => 'Success',
				'message' => 'This title will be indexed again shortly.',
			];
		} else {
			return [
				'success' => false,
				'title' => 'Error',
				'message' => 'Unable to mark the title for indexing. Could not find the title.',
			];
		}
	}

	/** @noinspection PhpUnused */
	function viewDebugging() : array {
		require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';

		$id = $_REQUEST['id'];
		$groupedWork = new GroupedWork();
		$groupedWork->permanent_id = $id;
		if ($groupedWork->find(true)) {
			$debuggingInfo = $groupedWork->getDebuggingInfo();
			global $interface;
			$interface->assign('debuggingInfo', $debuggingInfo);
			$modalBody = $interface->fetch('GroupedWork/debugging.tpl');

			if ($debuggingInfo->processed) {
				$buttons = "<button onclick=\"return AspenDiscovery.GroupedWork.resetDebugging('$groupedWork->permanent_id');\" class=\"modal-buttons btn btn-default\" style='float: left'>" . translate([
						'text' => "Reset Info",
						'isAdminFacing' => true,
					]) . "</button></a>";
			}else{
				$buttons = '';
			}
			return [
				'success' => true,
				'title' => 'Diagnostic Information',
				'message' => $modalBody,
				'modalButtons' => $buttons,
			];
		} else {
			return [
				'success' => false,
				'title' => 'Diagnostic Information',
				'message' => 'Unable to mark the title for diagnostics. Could not find the title.',
			];
		}
	}

	/** @noinspection PhpUnused */
	function resetDebugging() : array {
		require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';

		$id = $_REQUEST['id'];
		$groupedWork = new GroupedWork();
		$groupedWork->permanent_id = $id;
		if ($groupedWork->find(true)) {
			$groupedWork->resetDebugging();

			return [
				'success' => true,
				'title' => 'Diagnostic Information',
				'message' => 'Reset Diagnostic Information reset.',
			];
		} else {
			return [
				'success' => false,
				'title' => 'Diagnostic Information',
				'message' => 'Unable to reset diagnostics. Could not find the title.',
				'modalButtons' => '',
			];
		}
	}

	/** @noinspection PhpUnused */
	function getDescription() : array {
		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		$result = [
			'success' => false,
		];
		$id = $_REQUEST['id'];

		$recordDriver = new GroupedWorkDriver($id);
		if ($recordDriver->isValid()) {
			$description = $recordDriver->getDescription();
			if (strlen($description) == 0) {
				$description = translate([
					'text' => 'Description not provided',
					'isPublicFacing' => true,
				]);
			}
			$description = strip_tags($description, '<a><b><p><i><em><strong><ul><li><ol>');
			$result['success'] = true;
			$result['description'] = $description;
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	function getEnrichmentInfo() : array {
		global $interface;
		global $memoryWatcher;

		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		$id = $_REQUEST['id'];

		if (isset($_REQUEST['reload'])) {
			require_once ROOT_DIR . '/sys/Enrichment/NovelistData.php';
			$novelistData = new NovelistData();
			$novelistData->groupedRecordPermanentId = $id;
			if ($novelistData->find(true)) {
				$novelistData->delete();
			}
		}
		$recordDriver = new GroupedWorkDriver($id);
		$interface->assign('recordDriver', $recordDriver);

		$enrichmentResult = [];
		$enrichmentData = $recordDriver->loadEnrichment();
		$memoryWatcher->logMemory('Loaded Enrichment information from NoveList');

		//Process series data
		$titles = [];
		/** @var NovelistData $novelistData */
		if (isset($enrichmentData['novelist'])) {
			$novelistData = $enrichmentData['novelist'];
			if ($novelistData->getSeriesCount() == 0) {
				$enrichmentResult['seriesInfo'] = [
					'titles' => $titles,
					'currentIndex' => 0,
				];
			} else {
				foreach ($novelistData->getSeriesTitles() as $key => $record) {
					$titles[] = $this->getScrollerTitle($record, $key, 'Series');
				}

				$seriesInfo = [
					'titles' => $titles,
					'currentIndex' => $novelistData->getSeriesDefaultIndex(),
				];
				$enrichmentResult['seriesInfo'] = $seriesInfo;
			}
			$memoryWatcher->logMemory('Loaded Series information');

			//Process other data from novelist
			if ($novelistData->getSimilarTitleCount() > 0) {
				$interface->assign('similarTitles', $novelistData->getSimilarTitles());
				$enrichmentResult['similarTitlesNovelist'] = $interface->fetch('GroupedWork/similarTitlesNovelist.tpl');
			}
			$memoryWatcher->logMemory('Loaded Similar titles from NoveList');

			if ($novelistData->getAuthorCount()) {
				$interface->assign('similarAuthors', $novelistData->getAuthors());
				$enrichmentResult['similarAuthorsNovelist'] = $interface->fetch('GroupedWork/similarAuthorsNovelist.tpl');
			}
			$memoryWatcher->logMemory('Loaded Similar authors from NoveList');

			if ($novelistData->getSimilarSeriesCount()) {
				$interface->assign('similarSeries', $novelistData->getSimilarSeries());
				$enrichmentResult['similarSeriesNovelist'] = $interface->fetch('GroupedWork/similarSeriesNovelist.tpl');
			}
			$memoryWatcher->logMemory('Loaded Similar series from NoveList');
		}

		//Load go deeper options
		//TODO: Additional go deeper options
		global $library;
		if ($library->showGoDeeper == 0) {
			$enrichmentResult['showGoDeeper'] = false;
		} else {
			require_once(ROOT_DIR . '/Drivers/marmot_inc/GoDeeperData.php');
			$goDeeperOptions = GoDeeperData::getGoDeeperOptions($recordDriver->getCleanISBN(), $recordDriver->getCleanUPC());
			if (count($goDeeperOptions['options']) == 0) {
				$enrichmentResult['showGoDeeper'] = false;
			} else {
				$enrichmentResult['showGoDeeper'] = true;
				$enrichmentResult['goDeeperOptions'] = $goDeeperOptions['options'];
			}
		}
		$memoryWatcher->logMemory('Loaded additional go deeper data');

		//Load Series Summary
		$indexedSeries = $recordDriver->getIndexedSeries();
		$series = $recordDriver->getSeries();
		if (!empty($indexedSeries) || !empty($series)) {
			global $library;
			foreach ($library->getGroupedWorkDisplaySettings()->showInMainDetails as $detailOption) {
				$interface->assign($detailOption, true);
			}
			$interface->assign('indexedSeries', $indexedSeries);
			$interface->assign('series', $series);
			$enrichmentResult['seriesSummary'] = $interface->fetch('GroupedWork/series-summary.tpl');
		}

		return $enrichmentResult;
	}

	/** @noinspection PhpUnused */
	function getMoreLikeThis() : array {
		global $configArray;
		global $memoryWatcher;

		$id = $_REQUEST['id'];

		$enrichmentResult = [
			'similarTitles' => [
				'titles' => [],
			],
		];

		//Make sure that the title exists
		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		$recordDriver = new GroupedWorkDriver($id);
		if ($recordDriver->isValid()) {
			//Load Similar titles (from Solr)
			$url = $configArray['Index']['url'];
			$systemVariables = SystemVariables::getSystemVariables();
			if ($systemVariables->searchVersion == 1) {
				require_once ROOT_DIR . '/sys/SolrConnector/GroupedWorksSolrConnector.php';
				$db = new GroupedWorksSolrConnector($url);
			} else {
				require_once ROOT_DIR . '/sys/SolrConnector/GroupedWorksSolrConnector2.php';
				$db = new GroupedWorksSolrConnector2($url);
			}

			$db->disableScoping();
			$similar = $db->getMoreLikeThis($id);
			$memoryWatcher->logMemory('Loaded More Like This data from Solr');
			// Send the similar items to the template; if there is only one, we need
			// to force it to be an array or things will not display correctly.
			if (isset($similar) && !empty($similar['response']['docs'])) {
				$similarTitles = [];
				foreach ($similar['response']['docs'] as $key => $similarTitle) {
					$similarTitleDriver = new GroupedWorkDriver($similarTitle);
					$similarTitles[] = $similarTitleDriver->getScrollerTitle($key, 'MoreLikeThis');
				}
				$similarTitlesInfo = [
					'titles' => $similarTitles,
					'currentIndex' => 0,
				];
				$enrichmentResult['similarTitles'] = $similarTitlesInfo;
			}
			$memoryWatcher->logMemory('Loaded More Like This scroller data');
		}

		return $enrichmentResult;
	}

	/** @noinspection PhpUnused */
	function getWhileYouWait() : array {
		global $interface;

		$id = $_REQUEST['id'];

		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		$groupedWorkDriver = new GroupedWorkDriver($id);
		if ($groupedWorkDriver->isValid()) {
			$whileYouWaitTitles = $groupedWorkDriver->getWhileYouWait();

			$interface->assign('whileYouWaitTitles', $whileYouWaitTitles);

			return [
				'success' => true,
				'title' => translate([
					'text' => 'While You Wait',
					'isPublicFacing' => 'true',
				]),
				'body' => $interface->fetch('GroupedWork/whileYouWait.tpl'),
			];
		} else {
			return [
				'success' => false,
				'message' => translate([
					'text' => 'That title could not be found',
					'isPublicFacing' => 'true',
				]),
			];
		}
	}

	/** @noinspection PhpUnused */
	function getYouMightAlsoLike() : array {
		global $interface;
		global $memoryWatcher;

		$id = $_REQUEST['id'];
		$format = $_REQUEST['activeFormat'];
		$interface->assign('activeFormat', $format);

		global $library;
		if (!$library->showYouMightAlsoLike) {
			$interface->assign('numTitles', 0);
		} else {
			//Get all the titles to ignore, everything that has been rated, in reading history, or that the user is not interested in

			//Load Similar titles (from Solr)
			require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
			require_once ROOT_DIR . '/sys/SolrConnector/GroupedWorksSolrConnector.php';
			/** @var SearchObject_AbstractGroupedWorkSearcher $db */
			$searchObject = SearchObjectFactory::initSearchObject();
			if ($library->showYouMightAlsoLike == 1) {
				$searchObject->init();
				$searchObject->disableScoping();
				$interface->assign('activeSearchSource', 'global');
			} else {
				$searchObject->init('local');
				$interface->assign('activeSearchSource', 'local');
			}
			UserAccount::getActiveUserObj();
			if ($library->showYouMightAlsoLike == 3 && !empty($_REQUEST['activeFormat'])) {
				$format = $_REQUEST['activeFormat'];
				$interface->assign('activeFormat', $format);
				$similar = $searchObject->getMoreLikeThis($id, false, true, 3, $format);
			} else{
				$similar = $searchObject->getMoreLikeThis($id, false, false, 3);
			}
			$memoryWatcher->logMemory('Loaded More Like This data from Solr');
			// Send the similar items to the template; if there is only one, we need
			// to force it to be an array or things will not display correctly.
			if (isset($similar) && count($similar['response']['docs']) > 0) {
				$youMightAlsoLikeTitles = [];
				foreach ($similar['response']['docs'] as $similarTitle) {
					$similarTitleDriver = new GroupedWorkDriver($similarTitle);
					$youMightAlsoLikeTitles[] = $similarTitleDriver;
				}
				$interface->assign('numTitles', count($similar['response']['docs']));
				$interface->assign('youMightAlsoLikeTitles', $youMightAlsoLikeTitles);
			} else {
				$interface->assign('numTitles', 0);
			}
			$memoryWatcher->logMemory('Loaded More Like This scroller data');
		}

		return [
			'success' => true,
			'title' => translate([
				'text' => 'You Might Also Like',
				'isPublicFacing' => true,
			]),
			'body' => $interface->fetch('GroupedWork/youMightAlsoLike.tpl'),
		];
	}

	function getScrollerTitle($record, $index, $scrollerName) : array {
		$cover = $record['mediumCover'];
		$title = preg_replace("~\\s*([/:])\\s*$~", "", $record['title']);
		$series = '';
		if (isset($record['series']) && $record['series'] != null) {
			if (is_array($record['series'])) {
				foreach ($record['series'] as $series) {
					if (strcasecmp($series, 'none') !== 0) {
						break;
					} else {
						$series = '';
					}
				}
			} else {
				$series = $record['series'];
			}
			if (isset($series)) {
				$title .= ' (' . $series;
				if (isset($record['volume'])) {
					$title .= ' Volume ' . $record['volume'];
				}
				$title .= ')';
			}
		}

		if (isset($record['id'])) {
			global $interface;
			$interface->assign('index', $index);
			$interface->assign('scrollerName', $scrollerName);
			$interface->assign('id', $record['id']);
			$interface->assign('title', $title);
			$interface->assign('linkUrl', $record['fullRecordLink']);
			$interface->assign('bookCoverUrl', $record['mediumCover']);
			$interface->assign('bookCoverUrlMedium', $record['mediumCover']);
			$formattedTitle = $interface->fetch('RecordDrivers/GroupedWork/scroller-title.tpl');
		} else {
			$originalId = $_REQUEST['id'];
			$formattedTitle = "<div id=\"scrollerTitle$scrollerName$index\" class=\"scrollerTitle\" onclick=\"return AspenDiscovery.showElementInPopup('$title', '#noResults$index')\">" . "<img src=\"$cover\" class=\"scrollerTitleCover\" alt=\"$title Cover\"/>" . "</div>";
			$formattedTitle .= "<div id=\"noResults$index\" style=\"display:none\">
					<div class=\"row\">
						<div class=\"result-label col-md-3\">Author: </div>
						<div class=\"col-md-9 result-value notranslate\">
							<a href='/Author/Home?author=\"{$record['author']}\"'>{$record['author']}</a>
						</div>
					</div>
					<div class=\"series row\">
						<div class=\"result-label col-md-3\">Series: </div>
						<div class=\"col-md-9 result-value\">
							<a href=\"/GroupedWork/$originalId/Series\">$series</a>
						</div>
					</div>
					<div class=\"row related-manifestation\">
						<div class=\"col-sm-12\">
							" . translate([
					'text' => "The library does not own any copies of this title.",
					'isPublicFacing' => true,
				]) . "
						</div>
					</div>
				</div>";
		}

		return [
			'id' => $record['id'] ?? '',
			'image' => $cover,
			'title' => $title,
			'author' => $record['author'] ?? '',
			'formattedTitle' => $formattedTitle,
		];
	}

	/** @noinspection PhpUnused */
	function getGoDeeperData() : array {
		require_once(ROOT_DIR . '/Drivers/marmot_inc/GoDeeperData.php');
		$dataType = strip_tags($_REQUEST['dataType']);

		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		$id = !empty($_REQUEST['id']) ? $_REQUEST['id'] : $_GET['id'];
		// TODO: request id is not always being set by index page.
		$recordDriver = new GroupedWorkDriver($id);
		$upc = $recordDriver->getCleanUPC();
		$isbn = $recordDriver->getCleanISBN();

		$formattedData = GoDeeperData::getHtmlData($dataType, 'GroupedWork', $isbn, $upc);
		return [
			'formattedData' => $formattedData,
		];

	}

	/** @noinspection PhpUnused */
	function getWorkInfo() : array {
		global $interface;

		//Indicate we are showing search results, so we don't get hold buttons
		$interface->assign('displayingSearchResults', true);

		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		$id = $_REQUEST['id'];
		$_SESSION['returnToModule'] = 'GroupedWork';
		$_SESSION['returnToAction'] = $id;

		$recordDriver = new GroupedWorkDriver($id);

		if (!empty($_REQUEST['browseCategoryId'])) { // TODO need to check for $_REQUEST['subCategory'] ??
			// Changed from $_REQUEST['browseCategoryId'] to $_REQUEST['browseCategory'] to be consistent with Browse Category code.
			// TODO Need to see when this comes into action and verify it works as expected. plb 8-19-2015
			require_once ROOT_DIR . '/sys/Browse/BrowseCategory.php';
			$browseCategory = new BrowseCategory();
			$browseCategory->textId = $_REQUEST['browseCategoryId'];
			if ($browseCategory->find(true)) {
				$browseCategory->numTitlesClickedOn++;
				$browseCategory->update_stats_only();
			}
		}
		$interface->assign('recordDriver', $recordDriver);

		// if the grouped work consists of only 1 related item, return the record url, otherwise return the grouped-work url
		$relatedRecords = $recordDriver->getRelatedRecords();

		// short version
		if (count($relatedRecords) == 1) {
			$firstRecord = reset($relatedRecords);
			$url = $firstRecord->getUrl();
		} else {
			$url = $recordDriver->getLinkUrl();
		}

		$escapedId = htmlentities($recordDriver->getPermanentId()); // escape for HTML
		$buttonLabel = translate([
			'text' => 'Add to List',
			'isPublicFacing' => true,
		]);

		// button template
		$interface->assign('workId', $recordDriver->getPermanentId());
		$interface->assign('escapeId', $escapedId);
		$interface->assign('buttonLabel', $buttonLabel);
		$interface->assign('url', $url);

		$modalBody = $interface->fetch('GroupedWork/work-details.tpl');
		$showFavorites = $interface->getVariable('showFavorites');
		$buttons = "<button onclick=\"return AspenDiscovery.Account.showSaveToListForm(this, 'GroupedWork', '$escapedId');\" class=\"modal-buttons btn btn-primary addToListBtn\" style='float: left'>$buttonLabel</button>" . "<a href='$url'><button class='modal-buttons btn btn-primary'>" . translate([
				'text' => "More Info",
				'isPublicFacing' => true,
			]) . "</button></a>";
        global $offlineMode;
        global $loginAllowedWhileOffline;
		if(!$showFavorites || ($offlineMode && !$loginAllowedWhileOffline)) {
			$buttons = "<a href='$url'><button class='modal-buttons btn btn-primary'>" . translate([
					'text' => 'More Info',
					'isPublicFacing' => true,
				]) . '</button></a>';
		}
		return [
			'title' => "<a href='$url'>{$recordDriver->getTitle()}</a>",
			'modalBody' => $modalBody,
			'modalButtons' => $buttons,
		];
	}

	/** @noinspection PhpUnused */
	function rateTitle() : array {
		require_once(ROOT_DIR . '/sys/LocalEnrichment/UserWorkReview.php');
		if (!UserAccount::isLoggedIn()) {
			return [
				'error' => translate([
					'text' => 'Please login to rate this title.',
					'isPublicFacing' => true,
				]),
			];
		}
		if (empty($_REQUEST['id'])) {
			return [
				'error' => translate([
					'text' => 'ID for the item to rate is required.',
					'isPublicFacing' => true,
				]),
			];
		}
		if (empty($_REQUEST['rating']) || !ctype_digit($_REQUEST['rating'])) {
			return [
				'error' => translate([
					'text' => 'Invalid value for rating.',
					'isPublicFacing' => true,
				]),
			];
		}
		$rating = $_REQUEST['rating'];
		//Save the rating
		$workReview = new UserWorkReview();
		$workReview->groupedRecordPermanentId = $_REQUEST['id'];
		$workReview->userId = UserAccount::getActiveUserId();
		if ($workReview->find(true)) {
			if ($rating != $workReview->rating) { // update gives an error if the rating value is the same as stored.
				$workReview->rating = $rating;
				$success = $workReview->update();
			} else {
				// pretend success since rating is already set to the same value.
				$success = true;
			}
		} else {
			$workReview->rating = $rating;
			$workReview->review = '';  // default value required for insert statements
			$workReview->dateRated = time(); // moved to be consistent with the add review behavior
			$success = $workReview->insert();
		}

		if ($success) {
			require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
			$groupedWork = new GroupedWork();
			$groupedWork->permanent_id = $_REQUEST['id'];
			if ($groupedWork->find(true)) {
				$groupedWork->forceReindex();
			}

			// Reset any cached suggestion browse category for the user
			$this->clearMySuggestionsBrowseCategoryCache();

			return ['rating' => $rating];
		} else {
			return [
				'error' => translate([
					'text' => 'Unable to save your rating.',
					'isPublicFacing' => 'true',
				]),
			];
		}
	}

	private function clearMySuggestionsBrowseCategoryCache() : void {
		// Reset any cached suggestion browse category for the user
		global $memCache;
		global $solrScope;
		foreach ([
					 '0',
					 '1',
				 ] as $browseMode) { // (Browse modes are set in class Browse_AJAX)
			$key = 'browse_category_system_recommended_for_you_' . UserAccount::getActiveUserId() . '_' . $solrScope . '_' . $browseMode;
			$memCache->delete($key);
		}

	}

	/** @noinspection PhpUnused */
	function getReviewInfo() : array {
		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		$id = $_REQUEST['id'];
		$recordDriver = new GroupedWorkDriver($id);
		$isbn = $recordDriver->getCleanISBN();

		//Load external (syndicated reviews)
		require_once ROOT_DIR . '/sys/Reviews.php';
		$externalReviews = new ExternalReviews($isbn);
		$reviews = $externalReviews->fetch();
		global $interface;
		$interface->assign('id', $id);
		$numSyndicatedReviews = 0;
		foreach ($reviews as $providerReviews) {
			$numSyndicatedReviews += count($providerReviews);
		}
		$interface->assign('syndicatedReviews', $reviews);

		$userReviews = $recordDriver->getUserReviews();
		foreach ($userReviews as $key => $review) {
			if (empty($review->review)) {
				unset($userReviews[$key]);
			}
		}
		$interface->assign('userReviews', $userReviews);

		return [
			'numSyndicatedReviews' => $numSyndicatedReviews,
			'syndicatedReviewsHtml' => $interface->fetch('GroupedWork/view-syndicated-reviews.tpl'),
			'numCustomerReviews' => count($userReviews),
			'customerReviewsHtml' => $interface->fetch('GroupedWork/view-user-reviews.tpl'),
		];
	}

	/** @noinspection PhpUnused */
	function getPromptForReviewForm() : array {
		$user = UserAccount::getActiveUserObj();
		if ($user) {
			if (!$user->noPromptForUserReviews) {
				global $interface;
				$id = $_REQUEST['id'];
				if (!empty($id)) {
					$results = [
						'prompt' => true,
						'title' => translate([
							'text' => 'Add a Review',
							'isPublicFacing' => true,
						]),
						'modalBody' => $interface->fetch("GroupedWork/prompt-for-review-form.tpl"),
						'modalButtons' => "<button class='tool btn btn-primary' onclick='AspenDiscovery.GroupedWork.showReviewForm(this, \"$id\");'>" . translate([
								'text' => "Submit A Review",
								'isPublicFacing' => true,
							]) . "</button>",
					];
				} else {
					$results = [
						'error' => true,
						'message' => 'Invalid ID.',
					];
				}
			} else {
				// Option already set to not prompt, don't prompt.
				$results = [
					'prompt' => false,
				];
			}
		} else {
			$results = [
				'error' => true,
				'message' => 'You are not logged in.',
			];
		}
		return $results;
	}

	/** @noinspection PhpUnused */
	function setNoMoreReviews() : array {
		$user = UserAccount::getActiveUserObj();
		if ($user) {
			$user->noPromptForUserReviews = 1;
			$success = $user->update();
			return ['success' => $success];
		} else {
			return ['success' => false];
		}
	}

	/** @noinspection PhpUnused */
	function getReviewForm() : array {
		global $interface;
		$id = $_REQUEST['id'];
		if (!empty($id)) {
			$interface->assign('id', $id);

			// check if rating/review exists for user and work
			require_once ROOT_DIR . '/sys/LocalEnrichment/UserWorkReview.php';
			$groupedWorkReview = new UserWorkReview();
			$groupedWorkReview->userId = UserAccount::getActiveUserId();
			$groupedWorkReview->groupedRecordPermanentId = $id;
			if ($groupedWorkReview->find(true)) {
				$interface->assign('userRating', $groupedWorkReview->rating);
				$interface->assign('userReview', $groupedWorkReview->review);
			}

			$results = [
				'title' => translate([
					'text' => 'Review',
					'isPublicFacing' => true,
				]),
				'modalBody' => $interface->fetch("GroupedWork/review-form-body.tpl"),
				'modalButtons' => "<button class='tool btn btn-primary' onclick='AspenDiscovery.GroupedWork.saveReview(\"$id\");'>" . translate([
						'text' => "Submit Review",
						'isPublicFacing' => true,
					]) . "</button>",
			];
		} else {
			$results = [
				'error' => true,
				'message' => translate([
					'text' => 'Invalid ID.',
					'isPublicFacing' => true,
				]),
			];
		}
		return $results;
	}

	/** @noinspection PhpUnused */
	function saveReview() : array {
		$result = [];

		if (!UserAccount::isLoggedIn()) {
			$result['success'] = false;
			$result['message'] = translate([
				'text' => 'Please login before adding a review.',
				'isPublicFacing' => true,
			]);
		} elseif (empty($_REQUEST['id'])) {
			$result['success'] = false;
			$result['message'] = translate([
				'text' => 'ID for the item to review is required.',
				'isPublicFacing' => true,
			]);
		} else {
			require_once ROOT_DIR . '/sys/LocalEnrichment/UserWorkReview.php';
			$id = $_REQUEST['id'];
			$rating = $_REQUEST['rating'] ?? '';
			$HadReview = isset($_REQUEST['comment']); // did form have the review field turned on? (maybe only ratings instead)
			$comment = $HadReview ? trim($_REQUEST['comment']) : ''; //avoids undefined index notice when doing only ratings.

			$groupedWorkReview = new UserWorkReview();
			$groupedWorkReview->userId = UserAccount::getActiveUserId();
			$groupedWorkReview->groupedRecordPermanentId = $id;
			$newReview = true;
			if ($groupedWorkReview->find(true)) { // check for existing rating by user
				$newReview = false;
			}
			// set the user's rating and/or review
			if (!empty($rating) && is_numeric($rating)) {
				$groupedWorkReview->rating = $rating;
			}
			if ($newReview) {
				$groupedWorkReview->review = $HadReview ? $comment : ''; // set an empty review when the user was doing only ratings. (per library settings) //TODO there is no default value in the database.
				$groupedWorkReview->dateRated = time();
				$success = $groupedWorkReview->insert();
			} else {
				if ((!empty($rating) && $rating != $groupedWorkReview->rating) || ($HadReview && $comment != $groupedWorkReview->review)) { // update gives an error if the updated values are the same as stored values.
					if ($HadReview) {
						$groupedWorkReview->review = $comment;
					} // only update the review if the review input was in the form.
					$success = $groupedWorkReview->update();
				} else {
					$success = true;
				} // pretend success since values are already set to same values.
			}
			if (!$success) { // if SQL save didn't work, let user know.
				$result['success'] = false;
				$result['message'] = translate([
					'text' => 'Failed to save rating or review.',
					'isPublicFacing' => true,
				]);
			} else { // successfully saved
				$result['success'] = true;
				$result['newReview'] = $newReview;
				$result['reviewId'] = $groupedWorkReview->id;
				global $interface;
				$interface->assign('review', $groupedWorkReview);
				$result['reviewHtml'] = $interface->fetch('GroupedWork/view-user-review.tpl');
			}
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	function getEmailForm() : array {
		global $interface;
		require_once ROOT_DIR . '/sys/Email/Mailer.php';

		$id = $_REQUEST['id'];
		$interface->assign('id', $id);

		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		$recordDriver = new GroupedWorkDriver($id);

		$relatedRecords = $recordDriver->getRelatedRecords();
		$interface->assign('relatedRecords', $relatedRecords);
		return [
			'title' => translate([
				'text' => 'Share via Email',
				'isPublicFacing' => true,
			]),
			'modalBody' => $interface->fetch("GroupedWork/email-form-body.tpl"),
			'modalButtons' => "<button class='tool btn btn-primary' onclick='AspenDiscovery.GroupedWork.sendEmail(\"$id\"); return false;'>" . translate([
					'text' => 'Send Email',
					'isPublicFacing' => true,
				]) . "</button>",
		];
	}

	/** @noinspection PhpUnused */
	function sendEmail() : array {
		global $interface;

		$to = strip_tags($_REQUEST['to']);
		$from = isset($_REQUEST['from']) ? strip_tags($_REQUEST['from']) : '';
		$message = $_REQUEST['message'];

		$id = $_REQUEST['id'];
		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		$recordDriver = new GroupedWorkDriver($id);
		$interface->assign('recordDriver', $recordDriver);
		$interface->assign('url', $recordDriver->getLinkUrl(true));

		if (isset($_REQUEST['related_record'])) {
			$relatedRecord = $_REQUEST['related_record'];
			require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
			$recordDriver = new GroupedWorkDriver($id);

			$relatedRecords = $recordDriver->getRelatedRecords();

			foreach ($relatedRecords as $curRecord) {
				if ($curRecord->id == $relatedRecord) {
					if (isset($curRecord->callNumber)) {
						$interface->assign('callnumber', $curRecord->callNumber);
					}
					if (isset($curRecord->shelfLocation)) {
						$interface->assign('shelfLocation', strip_tags($curRecord->shelfLocation));
					}
					$interface->assign('url', $curRecord->getDriver()->getAbsoluteUrl());
					break;
				}
			}
		}

		$subject = translate([
				'text' => "Library Catalog Record",
				'isPublicFacing' => true,
				'inAttribute' => true,
			]) . ": " . $recordDriver->getTitle();
		$interface->assign('from', $from);
		$interface->assign('emailDetails', $recordDriver->getEmail());
		$interface->assign('recordID', $recordDriver->getUniqueID());
		/** @noinspection PhpStrFunctionsInspection */
		if (strpos($message, 'http') === false && strpos($message, 'mailto') === false && $message == strip_tags($message)) {
			$interface->assign('message', $message);
			$body = $interface->fetch('Emails/grouped-work-email.tpl');

			require_once ROOT_DIR . '/sys/Email/Mailer.php';
			$mail = new Mailer();
			$emailResult = $mail->send($to, $subject, $body);

			if ($emailResult === true) {
				$result = [
					'result' => true,
					'message' => translate([
						'text' => 'Your email was sent successfully.',
						'isPublicFacing' => 'true',
					]),
				];
			} else {
				$result = [
					'result' => false,
					'message' => translate([
						'text' => 'Your email message could not be sent due to an unknown error.',
						'isPublicFacing' => 'true',
					]),
				];
			}
		} else {
			$result = [
				'result' => false,
				'message' => translate([
					'text' => 'Sorry, we can&apos;t send emails with html or other data in it.',
					'isPublicFacing' => 'true',
				]),
			];
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function markNotInterested() : array {
		$result = [
			'result' => false,
			'message' => "Unknown error.",
		];
		if (UserAccount::isLoggedIn()) {
			$id = $_REQUEST['id'];
			require_once ROOT_DIR . '/sys/LocalEnrichment/NotInterested.php';
			$notInterested = new NotInterested();
			$notInterested->userId = UserAccount::getActiveUserId();
			$notInterested->groupedRecordPermanentId = $id;

			if (!$notInterested->find(true)) {
				$notInterested->dateMarked = time();
				if ($notInterested->insert()) {

					// Reset any cached suggestion browse category for the user
					$this->clearMySuggestionsBrowseCategoryCache();

					$result = [
						'result' => true,
						'message' => translate([
							'text' => "You won't be shown this title in the future. It may take a few minutes before the title is removed from your recommendations.",
							'isPublicFacing' => 'true',
						]),
					];
				}
			} else {
				$result = [
					'result' => false,
					'message' => translate([
						'text' => "This record was already marked as something you aren't interested in.",
						'isPublicFacing' => 'true',
					]),
				];
			}
		} else {
			$result = [
				'result' => false,
				'message' => translate([
					'text' => "Please log in.",
					'isPublicFacing' => 'true',
				]),
			];
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function clearNotInterested() : array {
		$idToClear = $_REQUEST['id'];
		require_once ROOT_DIR . '/sys/LocalEnrichment/NotInterested.php';
		$notInterested = new NotInterested();
		$notInterested->userId = UserAccount::getActiveUserId();
		$notInterested->id = $idToClear;
		$result = ['result' => false];
		if ($notInterested->find(true)) {
			$notInterested->delete();
			$result = ['result' => true];
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function getInnReachInfo() : array {
		require_once ROOT_DIR . '/sys/InterLibraryLoan/InnReach.php';
		global $interface;
		$id = $_REQUEST['id'];
		$interface->assign('id', $id);

		/** @var SearchObject_AbstractGroupedWorkSearcher $searchObject */
		$searchObject = SearchObjectFactory::initSearchObject();
		$searchObject->init();

		// Retrieve Full record from Solr
		if (!($record = $searchObject->getRecord($id))) {
			AspenError::raiseError(new AspenError('Record Does Not Exist'));
		}

		$innReach = new InnReach();

		$searchTerms = [
			[
				'lookfor' => $record['title_short'],
				'index' => 'Title',
			],
		];
		if (isset($record['author'])) {
			$searchTerms[] = [
				'lookfor' => $record['author'],
				'index' => 'Author',
			];
		}

		$innReachResults = $innReach->getTopSearchResults($searchTerms, 10);
		$interface->assign('innReachResults', $innReachResults['records']);

		return [
			'numTitles' => count($innReachResults),
			'formattedData' => $interface->fetch('GroupedWork/ajax-innreach.tpl'),
		];
	}

	/** @noinspection PhpUnused */
	function getShareItInfo() : array {
		require_once ROOT_DIR . '/sys/InterLibraryLoan/ShareIt.php';
		global $interface;
		$id = $_REQUEST['id'];
		$interface->assign('id', $id);

		/** @var SearchObject_AbstractGroupedWorkSearcher $searchObject */
		$searchObject = SearchObjectFactory::initSearchObject();
		$searchObject->init();

		// Retrieve Full record from Solr
		if (!($record = $searchObject->getRecord($id))) {
			AspenError::raiseError(new AspenError('Record Does Not Exist'));
		}

		$shareIt = new ShareIt();

		$searchTerms = [
			[
				'lookfor' => $record['title_short'],
				'index' => 'Title',
			],
		];
		if (isset($record['author_display'])) {
			$searchTerms[] = [
				'lookfor' => $record['author_display'],
				'index' => 'Author',
			];
		}

		$shareItResults = $shareIt->getTopSearchResults($searchTerms, 10);
		$interface->assign('shareItResults', $shareItResults['records']);

		return [
			'numTitles' => count($shareItResults),
			'formattedData' => $interface->fetch('GroupedWork/ajax-shareit.tpl'),
		];
	}

	/** @noinspection PhpUnused */
	function getSeriesSummary() : array {
		global $interface;
		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		$id = $_REQUEST['id'];
		$recordDriver = new GroupedWorkDriver($id);
		$interface->assign('recordDriver', $recordDriver);
		$indexedSeries = $recordDriver->getIndexedSeries();
		$series = $recordDriver->getSeries();
		$result = [
			'result' => false,
			'message' => translate([
				'text' => 'No series exist for this record',
				'isPublicFacing' => 'true',
			]),
		];
		if (!empty($indexedSeries) || !empty($series)) {
			global $library;
			foreach ($library->getGroupedWorkDisplaySettings()->showInSearchResultsMainDetails as $detailOption) {
				$interface->assign($detailOption, true);
			}
			$interface->assign('indexedSeries', $indexedSeries);
			$interface->assign('series', $series);
			$result = [
				'result' => true,
				'seriesSummary' => $interface->fetch('GroupedWork/series-summary.tpl'),
			];
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function reloadCover() : array {
		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		$id = $_REQUEST['id'];
		$recordDriver = new GroupedWorkDriver($id);

		require_once ROOT_DIR . '/sys/Covers/BookCoverInfo.php';
		$bookCoverInfo = new BookCoverInfo();
		$bookCoverInfo->__set('recordType', 'grouped_work');
		$bookCoverInfo->__set('recordId',  $id);
		if ($bookCoverInfo->find(true)) {
			//Force the image source to be determined again unless the user uploaded a cover.
			if ($bookCoverInfo->getImageSource() != "upload") {
				$bookCoverInfo->__set('imageSource',  '');
			}
			$bookCoverInfo->__set('thumbnailLoaded',  0);
			$bookCoverInfo->__set('mediumLoaded',  0);
			$bookCoverInfo->__set('largeLoaded',  0);
			$bookCoverInfo->update();
		}

		$relatedRecords = $recordDriver->getRelatedRecords(true);
		foreach ($relatedRecords as $record) {
			require_once ROOT_DIR . '/sys/Covers/BookCoverInfo.php';
			$bookCoverInfo = new BookCoverInfo();
			if (strpos($record->id, ':') > 0) {
				[
					$source,
					$recordId,
				] = explode(':', $record->id);
				$bookCoverInfo->__set('recordType', $source);
				$bookCoverInfo->__set('recordId', $recordId);
			} else {
				$bookCoverInfo->__set('recordType', $record->source);
				$bookCoverInfo->__set('recordId', $record->id);
			}

			if ($bookCoverInfo->find(true)) {
				//Force the image source to be determined again unless the user uploaded a cover.
				if ($bookCoverInfo->getImageSource() != "upload") {
					$bookCoverInfo->__set('imageSource', '');
				}
				$bookCoverInfo->__set('thumbnailLoaded', 0);
				$bookCoverInfo->__set('mediumLoaded', 0);
				$bookCoverInfo->__set('largeLoaded', 0);
				$bookCoverInfo->update();
			}
		}

		return [
			'success' => true,
			'message' => translate([
				'text' => 'Covers have been reloaded.  You may need to refresh the page to clear your local cache.',
				'isPublicFacing' => true,
			]),
		];
	}

	/** @noinspection PhpUnused */
	function getUploadCoverForm() : array {
		global $interface;

		$groupedWorkId = $_REQUEST['id'];
		$recordType = $_REQUEST['recordType'] ?? 'grouped_work';
		$recordId = $_REQUEST['recordId'] ?? $groupedWorkId;
		$interface->assign('groupedWorkId', $groupedWorkId);
		$interface->assign('recordType', $recordType);
		$interface->assign('recordId', $recordId);

		return [
			'title' => translate([
				'text' => 'Upload a New Cover',
				'isPublicFacing' => 'true',
			]),
			'modalBody' => $interface->fetch("GroupedWork/upload-cover-form.tpl"),
			'modalButtons' => "<button class='tool btn btn-primary' onclick='$(\"#uploadCoverForm\").submit()'>" . translate([
					'text' => 'Upload Cover',
					'isPublicFacing' => 'true',
				]) . "</button>",
		];
	}

	/** @noinspection PhpUnused */
	function uploadCover() : array {
		$result = [
			'success' => false,
			'title' => translate([
				'text' => 'Uploading custom cover',
				'isPublicFacing' => true,
			]),
			'message' => translate([
				'text' => 'Sorry your cover could not be uploaded',
				'isAdminFacing' => true,
			]),
		];
		if (UserAccount::isLoggedIn() && (UserAccount::userHasPermission('Upload Covers'))) {
			if (isset($_FILES['coverFile'])) {
				$uploadedFile = $_FILES['coverFile'];
				if (isset($uploadedFile["error"]) && $uploadedFile["error"] == 4) {
					$result['message'] = translate([
						'text' => "No Cover file was uploaded",
						'isAdminFacing' => true,
					]);
				} elseif (isset($uploadedFile["error"]) && $uploadedFile["error"] > 0) {
					$result['message'] = translate([
						'text' => "Error in file upload for cover %1%",
						1 => $uploadedFile["error"],
						"isAdminFacing" => true,
					]);
				} else {
					$id = $_REQUEST['id'];
					$recordType = $_REQUEST['recordType'] ?? 'grouped_work';
					$recordId = $_REQUEST['recordId'] ?? $id;
					$uploadOption = $_REQUEST['uploadOption'];

					if($recordType !== 'grouped_work') {
						$id = $recordId;
					}

					global $configArray;
					$destFullPath = $configArray['Site']['coverPath'] . '/original/' . $id . '.png';
					require_once ROOT_DIR . '/sys/Covers/CoverImageUtils.php';

					/** @noinspection SpellCheckingInspection */
					if ($uploadOption == 'andgrouped') { //update image for grouped work and individual bib record
						$res = formatImageUpload($uploadedFile, $destFullPath, $id, $recordType);
						/** @noinspection PhpIfWithCommonPartsInspection */
						if ($res) {
							$id = $_REQUEST['id'];
							$recordType = 'grouped_work';
							$destFullPath = $configArray['Site']['coverPath'] . '/original/' . $id . '.png';
							$res = formatImageUpload($uploadedFile, $destFullPath, $id, $recordType);
							$result = $res;
						}else{
							$result = $res;
						}
						$result['message'] = translate([
							'text' => 'Your cover has been uploaded successfully',
							'isAdminFacing' => true,
						]);
					} /** @noinspection SpellCheckingInspection */
					elseif ($uploadOption == 'alldefault') {//update image for all records in grouped work with default covers
						$res = formatImageUpload($uploadedFile, $destFullPath, $id, $recordType);
						if ($res) {
							require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
							require_once ROOT_DIR . '/sys/Covers/BookCoverInfo.php';
							$id = $_REQUEST['id'];
							$recordDriver = new GroupedWorkDriver($id);
							$relatedRecords = $recordDriver->getRelatedRecords(true);

							foreach ($relatedRecords as $record) {
								require_once ROOT_DIR . '/sys/Covers/BookCoverInfo.php';
								$hasDefaultCover = new BookCoverInfo();
								$hasDefaultCover->__set('id',$record->id);
								$hasDefaultCover->__set('imageSource', 'default');
								if ($hasDefaultCover->find(true)){
									$id = substr(strstr($record->id, ':'), 1);
									$recordType = $record->source;
									$destFullPath = $configArray['Site']['coverPath'] . '/original/' . $id . '.png';
									$res = formatImageUpload($uploadedFile, $destFullPath, $id, $recordType);
									if ($res){
										continue;
									}else{
										$result = $res;
									}
								}
							}
							$result['success'] = true;
							$result['message'] = translate([
								'text' => 'Your cover has been uploaded successfully',
								'isAdminFacing' => true,
							]);
						} else {
							$result = $res;
							$result['message'] = translate([
								'text' => 'Your cover could not be uploaded',
								'isAdminFacing' => true,
							]);
						}
					}else{ //only updating grouped work or individual bib cover
						if($recordType == 'grouped_work') {
							if (formatImageUpload($uploadedFile, $destFullPath, $id, $recordType)) {
								require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
								$recordDriver = new GroupedWorkDriver($id);
								$relatedRecords = $recordDriver->getRelatedRecords(true);
								if (sizeof($relatedRecords) == 1) {
									$id = substr(strstr($relatedRecords[0]->id, ':'), 1);
									$recordType = $relatedRecords[0]->source;
									$destFullPath = $configArray['Site']['coverPath'] . '/original/' . $id . '.png';
									$result = formatImageUpload($uploadedFile, $destFullPath, $id, $recordType);
									if ($result['success'] = true){
										$result['message'] = translate([
											'text' => 'Your cover has been uploaded successfully',
											'isAdminFacing' => true,
										]);
									}
								}else{
									$result['success'] = true;
									$result['message'] = translate([
										'text' => 'Your cover has been uploaded successfully',
										'isAdminFacing' => true,
									]);
								}
							}
						} else {
							$result = formatImageUpload($uploadedFile, $destFullPath, $id, $recordType);
							if ($result['success'] = true){
								$result['message'] = translate([
									'text' => 'Your cover has been uploaded successfully',
									'isAdminFacing' => true,
								]);
							}
						}
					}
				}
			} else {
				$result['message'] = translate([
					'text' => 'No cover was uploaded, please try again.',
					'isAdminFacing' => true,
				]);
			}
		}
		if ($result['success']) {
			require_once ROOT_DIR . '/sys/Covers/BookCoverInfo.php';
			$bookCoverInfo = new BookCoverInfo();
			$bookCoverInfo->__set('recordType', $_REQUEST['recordType'] ?? 'grouped_work');
			$bookCoverInfo->__set('recordId', $_REQUEST['recordId'] ?? $_REQUEST['id']);
			if($bookCoverInfo->find(true)) {
				$bookCoverInfo->__set('imageSource', "upload");
				$bookCoverInfo->__set('thumbnailLoaded', 0);
				$bookCoverInfo->__set('mediumLoaded', 0);
				$bookCoverInfo->__set('largeLoaded', 0);
				if($bookCoverInfo->update()) {
					$result['message'] = translate([
						'text' => 'Your cover has been uploaded successfully',
						'isAdminFacing' => true,
					]);
				}
			}
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function getUploadCoverFormByURL() : array {
		global $interface;

		$groupedWorkId = $_REQUEST['id'];
		$recordType = $_REQUEST['recordType'] ?? 'grouped_work';
		$recordId = $_REQUEST['recordId'] ?? $groupedWorkId;
		$interface->assign('groupedWorkId', $groupedWorkId);
		$interface->assign('recordType', $recordType);
		$interface->assign('recordId', $recordId);

		return [
			'title' => translate([
				'text' => 'Upload a New Cover by URL',
				'isAdminFacing' => true,
			]),
			'modalBody' => $interface->fetch("GroupedWork/upload-cover-form-url.tpl"),
			'modalButtons' => "<button class='tool btn btn-primary' onclick='$(\"#uploadCoverFormByURL\").submit()'>" . translate([
					'text' => "Upload Cover",
					'isAdminFacing' => true,
				]) . "</button>",
		];
	}

	/** @noinspection PhpUnused */
	function uploadCoverByURL() : array {
		$result = [
			'success' => false,
			'title' => translate([
				'text' => 'Uploading custom cover',
				'isAdminFacing' => true,
			]),
			'message' => translate([
				'text' => 'Sorry your cover could not be uploaded',
				'isAdminFacing' => true,
			]),
		];
		if (isset($_POST['coverFileURL'])) {
			$url = $_POST['coverFileURL'];
			$filename = basename($url);
			$uploadedFile = @file_get_contents($url);

			if ($uploadedFile === false) {
				$result['message'] = translate([
					'text' => "Unable to load image from URL provided",
					'isAdminFacing' => true,
				]);
			}else{
				$id = $_REQUEST['id'];
				$recordType = $_REQUEST['recordType'] ?? 'grouped_work';
				$recordId = $_REQUEST['recordId'] ?? $id;
				$uploadOption = $_REQUEST['uploadOption'];

				if($recordType !== 'grouped_work') {
					$id = $recordId;
				}
				global $configArray;
				$destFullPath = $configArray['Site']['coverPath'] . '/original/' . $id . '.png';
				$ext = pathinfo($filename, PATHINFO_EXTENSION);
				if ($ext == "jpg" or $ext == "png" or $ext == "gif" or $ext == "jpeg") {
					$upload = file_put_contents($destFullPath, $uploadedFile);
					/** @noinspection SpellCheckingInspection */
					if ($uploadOption == 'andgrouped') { //update image for grouped work and individual bib record
						if ($upload) {
							$id = $_REQUEST['id'];
							$recordType = 'grouped_work';
							$destFullPath = $configArray['Site']['coverPath'] . '/original/' . $id . '.png';
							$upload = file_put_contents($destFullPath, $uploadedFile);
							if ($upload){
								require_once ROOT_DIR . '/sys/Covers/BookCoverInfo.php';
								$bookCoverInfo = new BookCoverInfo();
								$bookCoverInfo->__set('recordType', $recordType);
								$bookCoverInfo->__set('recordId',  $id);
								if ($bookCoverInfo->find(true)) {
									$bookCoverInfo->__set('imageSource',  'upload');
									$bookCoverInfo->__set('thumbnailLoaded',  0);
									$bookCoverInfo->__set('mediumLoaded',  0);
									$bookCoverInfo->__set('largeLoaded',  0);
									if ($bookCoverInfo->update()) {
										$result['message'] = translate([
											'text' => 'Your cover has been uploaded successfully',
											'isAdminFacing' => true,
										]);
									}
								}
								$result['success'] = true;
							}
						}
					} /** @noinspection SpellCheckingInspection */
					elseif ($uploadOption == 'alldefault') {//update image for all records in grouped work with default covers
						if ($upload) {
							require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
							$id = $_REQUEST['id'];
							$recordDriver = new GroupedWorkDriver($id);
							$relatedRecords = $recordDriver->getRelatedRecords(true);

							//set the result as success for initial upload
							$result['success'] = true;
							$result['message'] = translate([
								'text' => 'Your cover has been uploaded successfully',
								'isAdminFacing' => true,
							]);

							foreach ($relatedRecords as $record) {
								require_once ROOT_DIR . '/sys/Covers/BookCoverInfo.php';
								$hasDefaultCover = new BookCoverInfo();
								$hasDefaultCover->__set('id', $record->id);
								$hasDefaultCover->__set('imageSource', 'default');
								if ($hasDefaultCover->find(true)){
									$id = substr(strstr($record->id, ':'), 1);
									$recordType = $record->source;
									$destFullPath = $configArray['Site']['coverPath'] . '/original/' . $id . '.png';
									$upload = file_put_contents($destFullPath, $uploadedFile);
									if ($upload){
										require_once ROOT_DIR . '/sys/Covers/BookCoverInfo.php';
										$bookCoverInfo = new BookCoverInfo();
										$bookCoverInfo->__set('recordType', $recordType);
										$bookCoverInfo->__set('recordId', $id);
										if ($bookCoverInfo->find(true)) {
											$bookCoverInfo->__set('imageSource', 'upload');
											$bookCoverInfo->__set('thumbnailLoaded', 0);
											$bookCoverInfo->__set('mediumLoaded', 0);
											$bookCoverInfo->__set('largeLoaded', 0);
											if ($bookCoverInfo->update()) {
												$result['message'] = translate([
													'text' => 'Your cover has been uploaded successfully',
													'isAdminFacing' => true,
												]);
											}
										}
									}
								}
							}
						}
					}else{ //only updating grouped work or individual bib cover

						if($recordType == 'grouped_work') {
							if ($upload){
								//set the result as success for initial upload
								$result['success'] = true;
								$result['message'] = translate([
									'text' => 'Your cover has been uploaded successfully',
									'isAdminFacing' => true,
								]);
								require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
								$recordDriver = new GroupedWorkDriver($id);
								$relatedRecords = $recordDriver->getRelatedRecords(true);
								if (sizeof($relatedRecords) == 1){
									$id = substr(strstr($relatedRecords[0]->id, ':'), 1);
									$recordType = $relatedRecords[0]->source;
									$destFullPath = $configArray['Site']['coverPath'] . '/original/' . $id . '.png';
									$upload = file_put_contents($destFullPath, $uploadedFile);
									if ($upload){
										$result['success'] = true;

										require_once ROOT_DIR . '/sys/Covers/BookCoverInfo.php';
										$bookCoverInfo = new BookCoverInfo();
										$bookCoverInfo->__set('recordType', $recordType);
										$bookCoverInfo->__set('recordId', $id);
										if ($bookCoverInfo->find(true)) {
											$bookCoverInfo->__set('imageSource', 'upload');
											$bookCoverInfo->__set('thumbnailLoaded', 0);
											$bookCoverInfo->__set('mediumLoaded', 0);
											$bookCoverInfo->__set('largeLoaded', 0);
											if ($bookCoverInfo->update()) {
												$result['message'] = translate([
													'text' => 'Your cover has been uploaded successfully',
													'isAdminFacing' => true,
												]);
											}
										}
									}else{
										$result['message'] = translate([
											'text' => 'Incorrect image type.  Please upload a PNG, GIF, or JPEG',
											'isAdminFacing' => true,
										]);
									}
								}
							}else {
								$result['message'] = translate([
									'text' => 'Incorrect image type.  Please upload a PNG, GIF, or JPEG',
									'isAdminFacing' => true,
								]);
							}
						}else{
							if ($upload) {
								$result['success'] = true;
							} else {
								$result['message'] = translate([
									'text' => 'Incorrect image type.  Please upload a PNG, GIF, or JPEG',
									'isAdminFacing' => true,
								]);
							}
						}
					}
				} else {
					$result['message'] = translate([
						'text' => 'Incorrect image type.  Please upload a PNG, GIF, or JPEG',
						'isAdminFacing' => true,
					]);
				}
			}
		} else {
			$result['message'] = translate([
				'text' => 'No cover was uploaded, please try again.',
				'isAdminFacing' => true,
			]);
		}
		if ($result['success']) {
			require_once ROOT_DIR . '/sys/Covers/BookCoverInfo.php';
			$bookCoverInfo = new BookCoverInfo();
			$bookCoverInfo->__set('recordType', $_REQUEST['recordType'] ?? 'grouped_work');
			$bookCoverInfo->__set('recordId', $_REQUEST['recordId'] ?? $_REQUEST['id']);
			if ($bookCoverInfo->find(true)) {
				$bookCoverInfo->__set('imageSource', 'upload');
				$bookCoverInfo->__set('thumbnailLoaded', 0);
				$bookCoverInfo->__set('mediumLoaded', 0);
				$bookCoverInfo->__set('largeLoaded', 0);
				if ($bookCoverInfo->update()) {
					$result['message'] = translate([
						'text' => 'Your cover has been uploaded successfully',
						'isAdminFacing' => true,
					]);
				}
			}
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function getCopyDetails() : array {
		global $interface;

		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		$id = $_REQUEST['id'];
		$recordDriver = new GroupedWorkDriver($id);
		$interface->assign('recordDriver', $recordDriver);

		$recordId = $_REQUEST['recordId'];
		$selectedFormat = urldecode($_REQUEST['format']);

		$relatedManifestation = null;
		foreach ($recordDriver->getRelatedManifestations() as $relatedManifestation) {
			if ($relatedManifestation->format == $selectedFormat) {
				break;
			}
		}

		if ($relatedManifestation == null) {
			return [
				'title' => translate([
					'text' => "Error",
					'isPublicFacing' => true,
				]),
				'modalBody' => translate(['text' => 'Unable to find copy information.', 'isPublicFacing' => true,]),
			];
		}

		$interface->assign('itemSummaryId', $id);
		$interface->assign('relatedManifestation', $relatedManifestation);
		$interface->assign('isEContent', $relatedManifestation->isEContent());

		if ($recordId != $id) {
			$record = $recordDriver->getRelatedRecord($recordId);
			$summary = null;
			if ($record != null) {
				foreach ($relatedManifestation->getVariations() as $variation) {
					foreach ($variation->getRecords() as $recordWithVariation) {
						if ($recordWithVariation->id == $recordId) {
							$summary = $recordWithVariation->getItemSummary();
							break;
						}
					}
					if (!empty($summary)) {
						break;
					}
				}
			} else {
				foreach ($relatedManifestation->getVariations() as $variation) {
					if ($recordId == $id . '_' . $variation->label) {
						$summary = $variation->getItemSummary();
						break;
					}
				}
			}
		} else {
			$summary = $relatedManifestation->getItemSummary();
		}
		$interface->assign('showEContentHoldCounts', true);

		$interface->assign('summary', $summary);

		$modalBody = $interface->fetch('GroupedWork/copyDetails.tpl');
		return [
			'title' => translate([
				'text' => "where_is_it_title",
				'defaultText' => 'Where is it?',
				'isPublicFacing' => true,
			]),
			'modalBody' => $modalBody,
		];
	}

	/** @noinspection PhpUnused */
	function getGroupWithForm() : array {
		$results = [
			'success' => false,
			'message' => translate([
				'text' => 'Unknown Error',
				'isPublicFacing' => true,
			]),
		];

		if (UserAccount::isLoggedIn() && (UserAccount::userHasPermission('Manually Group and Ungroup Works'))) {
			require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
			$groupedWork = new GroupedWork();
			$id = $_REQUEST['id'];
			$groupedWork->permanent_id = $id;
			if ($groupedWork->find(true)) {
				global $interface;
				$interface->assign('id', $id);
				$interface->assign('groupedWork', $groupedWork);
				$results = [
					'success' => true,
					'title' => translate([
						'text' => "Group this with another work",
						'isAdminFacing' => true,
					]),
					'modalBody' => $interface->fetch("GroupedWork/groupWithForm.tpl"),
					'modalButtons' => "<button class='tool btn btn-primary' onclick='AspenDiscovery.GroupedWork.processGroupWithForm()'>" . translate([
							'text' => "Group",
							'isAdminFacing' => true,
						]) . "</button>",
				];
			} else {
				$results['message'] = translate([
					'text' => "Could not find a work with that id",
					'isAdminFacing' => true,
				]);
			}
		} else {
			$results['message'] = translate([
				'text' => "You do not have the correct permissions for this operation",
				'isAdminFacing' => true,
			]);
		}
		return $results;
	}

	/** @noinspection PhpUnused */
	function getGroupWithInfo() : array {
		/** @noinspection PhpArrayIndexImmediatelyRewrittenInspection */
		$results = [
			'success' => false,
			'message' => translate([
				'text' => 'Unknown Error',
				'isAdminFacing' => true,
			]),
		];
		if (UserAccount::isLoggedIn() && (UserAccount::userHasPermission('Manually Group and Ungroup Works'))) {
			require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
			$groupedWork = new GroupedWork();
			$id = $_REQUEST['id'];
			$groupedWork->permanent_id = $id;
			if ($groupedWork->find(true)) {
				$results['success'] = true;
				$results['message'] = "<div class='row'><div class='col-tn-3'>" . translate([
						'text' => 'Title',
						'isAdminFacing' => true,
					]) . "</div><div class='col-tn-9'><strong>$groupedWork->full_title</strong></div></div>";
				$results['message'] .= "<div class='row'><div class='col-tn-3'>" . translate([
						'text' => 'Author',
						'isAdminFacing' => true,
					]) . "</div><div class='col-tn-9'><strong>$groupedWork->author</strong></div></div>";
			} else {
				$results['message'] = translate([
					'text' => "Could not find a work with that id",
					'isAdminFacing' => true,
				]);
			}
		} else {
			$results['message'] = translate([
				'text' => "You do not have the correct permissions for this operation",
				'isAdminFacing' => true,
			]);
		}
		return $results;
	}

	/** @noinspection PhpUnused */
	function processGroupWithForm() : array {
		/** @noinspection PhpArrayIndexImmediatelyRewrittenInspection */
		$results = [
			'success' => false,
			'message' => translate([
				'text' => 'Unknown Error',
				'isPublicFacing' => true,
			]),
		];
		if (UserAccount::isLoggedIn() && (UserAccount::userHasPermission('Manually Group and Ungroup Works'))) {
			require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';

			$id = $_REQUEST['id'];
			$originalGroupedWork = new GroupedWork();
			$originalGroupedWork->permanent_id = $id;
			if (!empty($id) && $originalGroupedWork->find(true)) {
				$workToGroupWithId = $_REQUEST['groupWithId'];
				$workToGroupWith = new GroupedWork();
				$workToGroupWith->permanent_id = $workToGroupWithId;
				if (!empty($workToGroupWithId) && $workToGroupWith->find(true)) {
					$okToGroup = false;
					if ($originalGroupedWork->grouping_category == $workToGroupWith->grouping_category) {
						$okToGroup = true;
					}elseif (($originalGroupedWork->grouping_category == 'comic') && ($workToGroupWith->grouping_category == 'book')) {
						//Comics can go onto books
						$okToGroup = true;
					}elseif (($workToGroupWith->grouping_category == 'other')) {
						$okToGroup = true;
					}elseif (($originalGroupedWork->grouping_category == 'other')) {
						//Other can go onto anything else
						$tmpWork = $originalGroupedWork;
						$originalGroupedWork = $workToGroupWith;
						$workToGroupWith = $tmpWork;
						$okToGroup = true;
					}elseif (($originalGroupedWork->grouping_category == 'book') && ($workToGroupWith->grouping_category == 'comic')) {
						//These are ok to group, but we want the comic to go on the book.
						$tmpWork = $originalGroupedWork;
						$originalGroupedWork = $workToGroupWith;
						$workToGroupWith = $tmpWork;
						$okToGroup = true;
					}
					if (!$okToGroup) {
						$results['message'] = translate([
							'text' => "These are different categories of works, cannot group.",
							'isAdminFacing' => true,
						]);
					} else {
						require_once ROOT_DIR . '/sys/Grouping/GroupedWorkAlternateTitle.php';
						$groupedWorkAlternateTitle = new GroupedWorkAlternateTitle();
						$groupedWorkAlternateTitle->permanent_id = $workToGroupWith->permanent_id;
						//Check to see if the author has an authority
						require_once ROOT_DIR . '/sys/Grouping/AuthorAuthority.php';
						require_once ROOT_DIR . '/sys/Grouping/AuthorAuthorityAlternative.php';
						$authorAuthorityAlternative = new AuthorAuthorityAlternative();
						$authorAuthorityAlternative->normalized = $originalGroupedWork->author;
						$useOriginalAuthor = true;
						if ($authorAuthorityAlternative->find(true)) {
							$authorAuthority = new AuthorAuthority();
							$authorAuthority->id = $authorAuthorityAlternative->authorId;
							if ($authorAuthority->find(true)) {
								$useOriginalAuthor = false;
								$groupedWorkAlternateTitle->alternateAuthor = $authorAuthority->normalized;
							}
						}
						if ($useOriginalAuthor) {
							$groupedWorkAlternateTitle->alternateAuthor = $originalGroupedWork->author;
						}
						$groupedWorkAlternateTitle->alternateTitle = $originalGroupedWork->full_title;
						$groupedWorkAlternateTitle->alternateGroupingCategory = $originalGroupedWork->grouping_category;
						if (!$groupedWorkAlternateTitle->find(true)) {
							$groupedWorkAlternateTitle->addedBy = UserAccount::getActiveUserId();
							$groupedWorkAlternateTitle->dateAdded = time();
							$groupedWorkAlternateTitle->insert();
							$originalGroupedWork->forceReindex(true);
							$workToGroupWith->forceReindex(true);
							$results['success'] = true;
							$results['message'] = translate([
								'text' => "Your works have been grouped successfully, the index will update shortly.",
								'isAdminFacing' => true,
							]);
						}else{
							$results['message'] = translate([
								'text' => "This grouping already exists.",
								'isAdminFacing' => true,
							]);
						}
					}
				} else {
					$results['message'] = translate([
						'text' => "Could not find work to group with",
						'isAdminFacing' => true,
					]);
				}
			} else {
				$results['message'] = translate([
					'text' => "Could not find work for original id",
					'isAdminFacing' => true,
				]);
			}

		} else {
			$results['message'] = translate([
				'text' => "You do not have the correct permissions for this operation",
				'isAdminFacing' => true,
			]);
		}
		return $results;
	}

	/** @noinspection PhpUnused */
	function getGroupWithSearchForm() : array {
		$results = [
			'success' => false,
			'message' => translate([
				'text' => 'Unknown Error',
				'isAdminFacing' => true,
			]),
		];

		if (UserAccount::isLoggedIn() && (UserAccount::userHasPermission('Manually Group and Ungroup Works'))) {
			require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
			$groupedWork = new GroupedWork();
			$id = $_REQUEST['id'];
			$groupedWork->permanent_id = $id;
			if ($groupedWork->find(true)) {
				global $interface;
				$interface->assign('id', $id);
				$interface->assign('groupedWork', $groupedWork);

				$searchId = $_REQUEST['searchId'];
				/** @var SearchObject_AbstractGroupedWorkSearcher $searchObject */
				$searchObject = SearchObjectFactory::initSearchObject();
				$searchObject->init();
				$searchObject = $searchObject->restoreSavedSearch($searchId, false);

				if (!empty($_REQUEST['page'])) {
					$searchObject->setPage($_REQUEST['page']);
				}

				$searchResults = $searchObject->processSearch(false, false);
				$availableRecords = [];
				$availableRecords[-1] = translate([
					'text' => "Select the primary work",
					'isAdminFacing' => true,
				]);
				$recordIndex = ($searchObject->getPage() - 1) * $searchObject->getLimit();
				foreach ($searchResults['response']['docs'] as $doc) {
					$recordIndex++;
					if ($doc['id'] != $id) {
						$primaryWork = new GroupedWork();
						$primaryWork->permanent_id = $doc['id'];
						if ($primaryWork->find(true)) {
							$isValidForGrouping = false;
							if ($primaryWork->grouping_category == $groupedWork->grouping_category) {
								$isValidForGrouping = true;
							}elseif (($groupedWork->grouping_category == 'comic' && $primaryWork->grouping_category == 'book') || ($groupedWork->grouping_category == 'book' && $primaryWork->grouping_category == 'comic')){
								$isValidForGrouping = true;
							}elseif ($groupedWork->grouping_category == 'other' || $primaryWork->grouping_category == 'other') {
								$isValidForGrouping = true;
							}
							if ($isValidForGrouping) {
								$availableRecords[$doc['id']] = "$recordIndex) $primaryWork->full_title $primaryWork->author";
							}
						}
					}
				}
				$interface->assign('availableRecords', $availableRecords);

				$results = [
					'success' => true,
					'title' => translate([
						'text' => "Group this with another work",
						'isAdminFacing' => true,
					]),
					'modalBody' => $interface->fetch("GroupedWork/groupWithSearchForm.tpl"),
					'modalButtons' => "<button class='tool btn btn-primary' onclick='AspenDiscovery.GroupedWork.processGroupWithForm()'>" . translate([
							'text' => "Group",
							'isAdminFacing' => true,
						]) . "</button>",
				];
			} else {
				$results['message'] = translate([
					'text' => "Could not find a work with that id",
					'isAdminFacing' => true,
				]);
			}
		} else {
			$results['message'] = translate([
				'text' => "You do not have the correct permissions for this operation",
				'isAdminFacing' => true,
			]);
		}
		return $results;
	}

	function getStaffView() : array {
		$id = $_REQUEST['id'];
		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		$recordDriver = new GroupedWorkDriver($id);
		global $interface;
		$interface->assign('recordDriver', $recordDriver);
		return [
			'success' => true,
			'staffView' => $interface->fetch($recordDriver->getStaffView()),
		];
	}

	/** @noinspection PhpUnused */
	function deleteAlternateTitle() : array {
		$result = [
			'success' => false,
			'message' => translate([
				'text' => 'Unknown error deleting alternate title',
				'isAdminFacing' => true,
			]),
		];
		if (UserAccount::isLoggedIn() && (UserAccount::userHasPermission('Manually Group and Ungroup Works'))) {
			$id = $_REQUEST['id'];
			require_once ROOT_DIR . '/sys/Grouping/GroupedWorkAlternateTitle.php';
			$alternateTitle = new GroupedWorkAlternateTitle();
			$alternateTitle->id = $id;
			if ($alternateTitle->find(true)) {
				$alternateTitle->delete();
				$result = [
					'success' => true,
					'message' => translate([
						'text' => "Successfully deleted the alternate title",
						'isAdminFacing' => true,
					]),
				];
			} else {
				$result['message'] = translate([
					'text' => "Could not find the alternate title to delete",
					'isAdminFacing' => true,
				]);
			}
		} else {
			$result['message'] = translate([
				'text' => "You do not have the correct permissions for this operation",
				'isAdminFacing' => true,
			]);
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function deleteUngrouping() : array {
		$result = [
			'success' => false,
			'message' => translate([
				'text' => 'Unknown error deleting ungrouping',
				'isAdminFacing' => true,
			]),
		];
		if (UserAccount::isLoggedIn() && (UserAccount::userHasPermission('Manually Group and Ungroup Works'))) {
			$id = $_REQUEST['ungroupingId'];
			require_once ROOT_DIR . '/sys/Grouping/NonGroupedRecord.php';
			$nonGroupedRecord = new NonGroupedRecord();
			$nonGroupedRecord->id = $id;
			if ($nonGroupedRecord->find(true)) {
				$nonGroupedRecord->delete();
				require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
				$groupedRecord = new GroupedWork();
				$groupedRecord->permanent_id = $_REQUEST['id'];
				if ($groupedRecord->find(true)) {
					$groupedRecord->forceReindex(true);
				}
				$result = [
					'success' => true,
					'message' => translate([
						'text' => "This title can group with other records again",
						'isAdminFacing' => true,
					]),
				];
			} else {
				$result['message'] = translate([
					'text' => "Could not find the ungrouping entry to delete",
					'isAdminFacing' => true,
				]);
			}
		} else {
			$result['message'] = translate([
				'text' => "You do not have the correct permissions for this operation",
				'isAdminFacing' => true,
			]);
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function getDisplayInfoForm() : array {
		$results = [
			'success' => false,
			'message' => translate([
				'text' => 'Unknown Error',
				'isAdminFacing' => true,
			]),
		];
		if (UserAccount::isLoggedIn() && (UserAccount::userHasPermission('Set Grouped Work Display Information'))) {
			require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
			$groupedWork = new GroupedWork();
			$id = $_REQUEST['id'];
			$groupedWork->permanent_id = $id;
			if ($groupedWork->find(true)) {
				global $interface;
				$interface->assign('id', $id);
				$interface->assign('groupedWork', $groupedWork);

				require_once ROOT_DIR . '/sys/Grouping/GroupedWorkDisplayInfo.php';
				$existingDisplayInfo = new GroupedWorkDisplayInfo();
				$existingDisplayInfo->permanent_id = $id;
				if ($existingDisplayInfo->find(true)) {
					$interface->assign('title', $existingDisplayInfo->title);
					$interface->assign('author', $existingDisplayInfo->author);
					$interface->assign('seriesName', $existingDisplayInfo->seriesName);
					$interface->assign('seriesDisplayOrder', ($existingDisplayInfo->seriesDisplayOrder == 0) ? '' : $existingDisplayInfo->seriesDisplayOrder);
				} else {
					require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
					$recordDriver = new GroupedWorkDriver($id);
					$interface->assign('title', $recordDriver->getTitle());
					$interface->assign('author', $recordDriver->getPrimaryAuthor());
					$series = $recordDriver->getSeries();
					if (!empty($series)) {
						$interface->assign('seriesName', $series['seriesTitle']);
						$interface->assign('seriesDisplayOrder', $series['volume']);
					} else {
						$interface->assign('seriesName', '');
						$interface->assign('seriesDisplayOrder', '');
					}
				}

				$results = [
					'success' => true,
					'title' => translate([
						'text' => "Set display information",
						'isAdminFacing' => true,
					]),
					'modalBody' => $interface->fetch("GroupedWork/groupedWorkDisplayInfoForm.tpl"),
					'modalButtons' => "<button class='tool btn btn-primary' onclick='AspenDiscovery.GroupedWork.processGroupedWorkDisplayInfoForm(\"$id\")'>" . translate([
							'text' => "Set Display Info",
							'isAdminFacing' => true,
						]) . "</button>",
				];
			} else {
				$results['message'] = translate([
					'text' => "Could not find a work with that id",
					'isAdminFacing' => true,
				]);
			}
		} else {
			$results['message'] = translate([
				'text' => "You do not have the correct permissions for this operation",
				'isAdminFacing' => true,
			]);
		}
		return $results;
	}

	/** @noinspection PhpUnused */
	function processDisplayInfoForm() : array {
		$results = [
			'success' => false,
			'message' => translate([
				'text' => 'Unknown Error',
				'isAdminFacing' => true,
			]),
		];
		if (UserAccount::isLoggedIn() && (UserAccount::userHasPermission('Set Grouped Work Display Information'))) {
			require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
			$groupedWork = new GroupedWork();
			$id = $_REQUEST['id'];
			$groupedWork->permanent_id = $id;
			if ($groupedWork->find(true)) {
				$title = $_REQUEST['title'];
				$author = $_REQUEST['author'];
				$seriesName = $_REQUEST['seriesName'];
				$seriesDisplayOrder = $_REQUEST['seriesDisplayOrder'];
				if (!is_numeric($seriesDisplayOrder)) {
					$seriesDisplayOrder = '0';
				}
				if (empty($title) && empty($author) && empty($seriesName) && empty($seriesDisplayOrder)) {
					$results['message'] = translate([
						'text' => "Please specify at least one piece of information",
						'isAdminFacing' => true,
					]);
				} else {
					require_once ROOT_DIR . '/sys/Grouping/GroupedWorkDisplayInfo.php';
					$existingDisplayInfo = new GroupedWorkDisplayInfo();
					$existingDisplayInfo->permanent_id = $id;
					$isNew = true;
					if ($existingDisplayInfo->find(true)) {
						$isNew = false;
					}
					$existingDisplayInfo->title = $title;
					$existingDisplayInfo->author = $author;
					$existingDisplayInfo->seriesName = $seriesName;
					$existingDisplayInfo->seriesDisplayOrder = $seriesDisplayOrder;
					if ($isNew) {
						$existingDisplayInfo->addedBy = UserAccount::getActiveUserId();
						$existingDisplayInfo->dateAdded = time();
					}
					$existingDisplayInfo->update();

					$groupedWork->forceReindex();

					$results = [
						'success' => true,
						'message' => translate([
							'text' => 'The display information has been set and the index will update shortly.',
							'isAdminFacing' => true,
						]),
					];
				}
			} else {
				$results['message'] = translate([
					'text' => "Could not find a work with that id",
					'isAdminFacing' => true,
				]);
			}
		} else {
			$results['message'] = translate([
				'text' => "You do not have the correct permissions for this operation",
				'isAdminFacing' => true,
			]);
		}
		return $results;
	}

	/** @noinspection PhpUnused */
	function deleteDisplayInfo() : array {
		$result = [
			'success' => false,
			'title' => translate([
				'text' => 'Deleting display information',
				'isAdminFacing' => true,
			]),
			'message' => translate([
				'text' => 'Unknown error deleting display info',
				'isAdminFacing' => true,
			]),
		];
		if (UserAccount::isLoggedIn() && (UserAccount::userHasPermission('Set Grouped Work Display Information'))) {
			$id = $_REQUEST['id'];
			require_once ROOT_DIR . '/sys/Grouping/GroupedWorkDisplayInfo.php';
			$existingDisplayInfo = new GroupedWorkDisplayInfo();
			$existingDisplayInfo->permanent_id = $id;
			if ($existingDisplayInfo->find(true)) {
				$existingDisplayInfo->delete();
				require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
				$groupedWork = new GroupedWork();
				$groupedWork->permanent_id = $id;
				if ($groupedWork->find(true)) {
					$groupedWork->forceReindex();
				}
				$result = [
					'success' => true,
					'message' => translate([
						'text' => "Successfully deleted the display info, the index will update shortly.",
						'isAdminFacing' => true,
					]),
				];
			} else {
				$result['message'] = translate([
					'text' => "Could not find the display info to delete, it's likely been deleted already",
					'isAdminFacing' => true,
				]);
			}
		} else {
			$result['message'] = translate([
				'text' => "You do not have the correct permissions for this operation",
				'isAdminFacing' => true,
			]);
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function showSelectDownloadForm() : array {
		global $interface;

		$id = $_REQUEST['id'];
		$fileType = $_REQUEST['type'];
		$interface->assign('fileType', $fileType);
		require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
		require_once ROOT_DIR . '/sys/Grouping/GroupedWorkPrimaryIdentifier.php';
		$groupedWork = new GroupedWork();
		$groupedWork->permanent_id = $id;
		if ($groupedWork->find(true)) {
			$interface->assign('id', $id);

			$groupedWorkPrimaryIdentifier = new GroupedWorkPrimaryIdentifier();
			$groupedWorkPrimaryIdentifier->grouped_work_id = $groupedWork->id;
			$groupedWorkPrimaryIdentifier->find();
			$validFiles = [];
			while ($groupedWorkPrimaryIdentifier->fetch()) {
				require_once ROOT_DIR . '/sys/ILS/RecordFile.php';
				require_once ROOT_DIR . '/sys/File/FileUpload.php';
				$recordFile = new RecordFile();
				$recordFile->type = $groupedWorkPrimaryIdentifier->type;
				$recordFile->identifier = $groupedWorkPrimaryIdentifier->identifier;
				$recordFile->find();
				while ($recordFile->fetch()) {
					$fileUpload = new FileUpload();
					$fileUpload->id = $recordFile->fileId;
					$fileUpload->type = $fileType;
					if ($fileUpload->find(true)) {
						$validFiles[$recordFile->fileId] = $fileUpload->title;
					}
				}
			}
			asort($validFiles);
			$interface->assign('validFiles', $validFiles);

			if ($fileType == 'RecordPDF') {
				$buttonTitle = translate([
					'text' => 'Download PDF',
					'isAdminFacing' => true,
				]);
			} else {
				$buttonTitle = translate([
					'text' => 'Download Supplemental File',
					'isAdminFacing' => true,
				]);
			}
			return [
				'title' => translate([
					'text' => 'Select File to download',
					'isAdminFacing' => true,
				]),
				'modalBody' => $interface->fetch("GroupedWork/select-download-file-form.tpl"),
				'modalButtons' => "<button class='tool btn btn-primary' onclick='$(\"#downloadFile\").submit()'>$buttonTitle</button>",
			];
		} else {
			return [
				'title' => translate([
					'text' => 'Error',
					'isAdminFacing' => true,
				]),
				'modalBody' => "<div class='alert alert-danger'>" . translate([
						'text' => "Could not find that record",
						'isAdminFacing' => true,
					]) . "</div>",
				'modalButtons' => "",
			];
		}
	}

	/** @noinspection PhpUnused */
	function showSelectFileToViewForm() : array {
		global $interface;

		$id = $_REQUEST['id'];
		$fileType = $_REQUEST['type'];
		$interface->assign('fileType', $fileType);
		require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
		require_once ROOT_DIR . '/sys/Grouping/GroupedWorkPrimaryIdentifier.php';
		$groupedWork = new GroupedWork();
		$groupedWork->permanent_id = $id;
		if ($groupedWork->find(true)) {
			$interface->assign('id', $id);

			$groupedWorkPrimaryIdentifier = new GroupedWorkPrimaryIdentifier();
			$groupedWorkPrimaryIdentifier->grouped_work_id = $groupedWork->id;
			$groupedWorkPrimaryIdentifier->find();
			$validFiles = [];
			while ($groupedWorkPrimaryIdentifier->fetch()) {
				require_once ROOT_DIR . '/sys/ILS/RecordFile.php';
				require_once ROOT_DIR . '/sys/File/FileUpload.php';
				$recordFile = new RecordFile();
				$recordFile->type = $groupedWorkPrimaryIdentifier->type;
				$recordFile->identifier = $groupedWorkPrimaryIdentifier->identifier;
				$recordFile->find();
				while ($recordFile->fetch()) {
					$fileUpload = new FileUpload();
					$fileUpload->id = $recordFile->fileId;
					$fileUpload->type = $fileType;
					if ($fileUpload->find(true)) {
						$validFiles[$recordFile->fileId] = $fileUpload->title;
					}
				}
			}
			asort($validFiles);
			$interface->assign('validFiles', $validFiles);

			$buttonTitle = translate([
				'text' => 'View PDF',
				'isAdminFacing' => true,
			]);
			return [
				'title' => translate([
					'text' => 'Select File to View',
					'isAdminFacing' => true,
				]),
				'modalBody' => $interface->fetch("GroupedWork/select-view-file-form.tpl"),
				'modalButtons' => "<button class='tool btn btn-primary' onclick='$(\"#viewFile\").submit()'>$buttonTitle</button>",
			];
		} else {
			return [
				'title' => translate([
					'text' => 'Error',
					'isAdminFacing' => true,
				]),
				'modalBody' => "<div class='alert alert-danger'>" . translate([
						'text' => "Could not find that record",
						'isAdminFacing' => true,
					]) . "</div>",
				'modalButtons' => "",
			];
		}
	}

	/** @noinspection PhpUnused */
	function getPreviewRelatedCover() : array {
		global $interface;

		$groupedWorkId = $_REQUEST['id'];
		$recordId = $_REQUEST['recordId'];
		$recordType = $_REQUEST['recordType'];
		$interface->assign('groupedWorkId', $groupedWorkId);
		$interface->assign('recordId', $recordId);
		$interface->assign('recordType', $recordType);

		return [
			'title' => translate([
				'text' => 'Previewing Related Cover',
				'isAdminFacing' => true,
			]),
			'modalBody' => $interface->fetch("GroupedWork/previewRelatedCover.tpl"),
			'modalButtons' => "<button class='tool btn btn-primary' onclick='AspenDiscovery.GroupedWork.setRelatedCover(\"$recordId\",\"$groupedWorkId\",\"$recordType\")'>" . translate([
					'text' => "Use Cover",
					'isAdminFacing' => true,
				]) . "</button>",
		];
	}

	/** @noinspection PhpUnused */
	function setRelatedCover() : array {
		if (UserAccount::isLoggedIn() && (UserAccount::userHasPermission('Upload Covers'))) {
			require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
			$groupedWork = new GroupedWork();
			$groupedWorkId = $_REQUEST['id'];
			$recordId = $_REQUEST['recordId'];
			$recordType = $_REQUEST['recordType'];
			$groupedWork->permanent_id = $groupedWorkId;

			if ($groupedWork->find(true)) {
				$this->clearUploadedCover();
				$groupedWork->referenceCover = $recordType . ':' . $recordId;
				$groupedWork->update();
				return [
					'success' => true,
					'message' => translate([
						'text' => 'Your cover has been set successfully',
						'isAdminFacing' => true,
					]),
					'title' => translate([
						'text' => 'Previewing cover from related work',
						'isAdminFacing' => true,
					]),
				];
			} else {
				return [
					'success' => false,
					'message' => translate([
						'text' => 'Could not find the grouped work to update.',
						'isAdminFacing' => true,
					]),
					'title' => translate([
						'text' => 'Previewing cover from related work',
						'isAdminFacing' => true,
					]),
				];
			}
		}  else {
			return [
				'success' => false,
				'message' => translate([
					'text' => 'Error updating cover.',
					'isAdminFacing' => true,
				]),
				'title' => translate([
					'text' => 'Sorry, your are not logged in or do not have permissions to set this cover.',
					'isAdminFacing' => true,
				]),
			];
		}
	}

	/** @noinspection PhpUnused */
	function clearRelatedCover() : array {
		require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';

		$id = $_REQUEST['id'];
		$groupedWork = new GroupedWork();
		$groupedWork->permanent_id = $id;
		if ($groupedWork->find(true)) {
			$groupedWork->referenceCover = '';
			$groupedWork->update();
			$this->reloadCover();
			return [
				'success' => true,
				'message' => translate([
					'text' => 'The cover has been reset',
					'isAdminFacing' => true,
				]),
			];
		} else {
			return [
				'success' => false,
				'message' => translate([
					'text' => 'Unable to reset the cover',
					'isAdminFacing' => true,
				]),
			];
		}
	}

	/** @noinspection PhpUnused */
	function thirdPartyCoverToggle() : array {
		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		$id = $_REQUEST['id'];
		$recordDriver = new GroupedWorkDriver($id);

		require_once ROOT_DIR . '/sys/Covers/BookCoverInfo.php';
		$bookcoverInfo = new BookCoverInfo();
		$bookcoverInfo->__set('recordType', 'grouped_work');
		$bookcoverInfo->__set('recordId', $id);

		if ($bookcoverInfo->find(true)) {
			if ($bookcoverInfo->getDisallowThirdPartyCover() == 0){
				$bookcoverInfo->__set('disallowThirdPartyCover', 1);
				$result['message'] = translate([
					'text' => 'Covers for this work will no longer be pulled from a third party.',
					'isAdminFacing' => true,
				]);
			}else{
				$bookcoverInfo->__set('disallowThirdPartyCover',  0);
				$result['message'] = translate([
					'text' => 'Covers for this work will be pulled from a third party if one is available.',
					'isAdminFacing' => true,
				]);
			}
			$bookcoverInfo->__set('thumbnailLoaded',  0);
			$bookcoverInfo->__set('mediumLoaded',  0);
			$bookcoverInfo->__set('largeLoaded',  0);
			$bookcoverInfo->__set('imageSource',  '');
			$bookcoverInfo->update();
			$result['success'] = true;

			$relatedRecords = $recordDriver->getRelatedRecords(true);
			foreach ($relatedRecords as $record) {
				require_once ROOT_DIR . '/sys/Covers/BookCoverInfo.php';
				$bookCoverInfo = new BookCoverInfo();
				if (strpos($record->id, ':') > 0) {
					[
						$source,
						$recordId,
					] = explode(':', $record->id);
					$bookCoverInfo->__set('recordType', $source);
					$bookCoverInfo->__set('recordId', $recordId);
				} else {
					$bookCoverInfo->__set('recordType', $record->source);
					$bookCoverInfo->__set('recordId', $record->id);
				}

				if ($bookCoverInfo->find(true)) {
					//Force the image source to be determined again unless the user uploaded a cover.
					if ($bookCoverInfo->getDisallowThirdPartyCover() == 0){
						$bookCoverInfo->__set('disallowThirdPartyCover', 1);
					}else{
						$bookCoverInfo->__set('disallowThirdPartyCover', 0);
					}
					if ($bookCoverInfo->getImageSource() != "upload") {
						$bookCoverInfo->__set('imageSource', '');
					}
					$bookCoverInfo->__set('thumbnailLoaded', 0);
					$bookCoverInfo->__set('mediumLoaded', 0);
					$bookCoverInfo->__set('largeLoaded', 0);
					$bookCoverInfo->update();
				}
			}
		}else{
			return [
				'success' => false,
				'message' => translate([
					'text' => 'Unable to find book cover information for this record.',
					'isAdminFacing' => true,
				]),
			];
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function clearUploadedCover() : array {
		require_once ROOT_DIR . '/sys/Covers/BookCoverInfo.php';
		$bookcoverInfo = new BookCoverInfo();

		$id = $_REQUEST['id'];
		$recordType = $_REQUEST['recordType'] ?? 'grouped_work';
		$recordId = $_REQUEST['recordId'] ?? $id;

		if($recordType !== 'grouped_work') {
			$id = $recordId;
		}
		$bookcoverInfo->__set('recordId', $id);
		if ($bookcoverInfo->find(true)) {
			global $configArray;
			$bookCoverPath = $configArray['Site']['coverPath'];
			$permanentId = $bookcoverInfo->getRecordId();

			$originalUploadedImage = $bookCoverPath . '/original/' . $permanentId . '.png';
			if (file_exists($originalUploadedImage)) {
				unlink($originalUploadedImage);
			}

			$smallUploadedImage = $bookCoverPath . '/small/' . $permanentId . '.png';
			if (file_exists($smallUploadedImage)) {
				unlink($smallUploadedImage);
			}

			$mediumUploadedImage = $bookCoverPath . '/medium/' . $permanentId . '.png';
			if (file_exists($mediumUploadedImage)) {
				unlink($mediumUploadedImage);
			}

			$largeUploadedImage = $bookCoverPath . '/large/' . $permanentId . '.png';
			if (file_exists($largeUploadedImage)) {
				unlink($largeUploadedImage);
			}

			$bookcoverInfo->__set('imageSource', '');
			$bookcoverInfo->update();
			return [
				'success' => true,
				'message' => translate([
					'text' => 'The cover has been reset',
					'isAdminFacing' => true,
				]),
			];
		} else {
			return [
				'success' => false,
				'message' => translate([
					'text' => 'Unable to reset the cover',
					'isAdminFacing' => true,
				]),
			];
		}
	}

	/** @noinspection PhpUnused */
	function getLargeCover() : array {
		global $interface;

		$groupedWorkId = $_REQUEST['id'];
		$interface->assign('groupedWorkId', $groupedWorkId);

		return [
			'title' => translate([
				'text' => 'Cover Image',
				'isPublicFacing' => true,
			]),
			'modalBody' => $interface->fetch("GroupedWork/largeCover.tpl"),
			'modalButtons' => "",
		];
	}

	/** @noinspection PhpUnused */
	function getRelatedManifestations() : array {
		global $interface;
		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		$id = $_REQUEST['id'];
		$selectedFormat = $_REQUEST['format'];
		$variationId = $_REQUEST['variationId'];

		$result = [
			'success' => false,
			'message' => translate([
				'text' => 'No related manifestations exist for this record',
				'isPublicFacing' => 'true',
			]),
		];

		$groupedWorkDriver = new GroupedWorkDriver($id);
		$relatedManifestation = null;
		$foundManifestation = false;
		$relatedManifestations = $groupedWorkDriver->getRelatedManifestations();
		foreach ($relatedManifestations as $relatedManifestation) {
			if (preg_replace('/[^a-zA-Z0-9_-]/', '_', $relatedManifestation->format) == $selectedFormat) {
				$foundManifestation = true;
				break;
			}
		}

		if ($foundManifestation) {
			$variation = null;
			foreach ($relatedManifestation->getVariations() as $variation) {
				if ($variation->databaseId == $variationId) {
					break;
				}
			}

			if ($variation != null) {
				$interface->assign('relatedRecords', $variation->getRelatedRecords());
				$interface->assign('relatedManifestation', $relatedManifestation);
				$interface->assign('variationId', $variation->databaseId);
				$interface->assign('workId', $id);

				$result = [
					'success' => true,
					'body' => $interface->fetch('GroupedWork/relatedRecords.tpl'),
				];
			}
		}

		return $result;
	}

}
