<?php
require_once ROOT_DIR . '/JSON_Action.php';

class GroupedWork_AJAX extends JSON_Action {
	/**
	 * Alias of deleteUserReview()
	 *
	 * @return array
	 */
	/** @noinspection PhpUnused */
	function clearUserRating() {
		return $this->deleteUserReview();
	}

	function deleteUserReview() {
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
	function forceReindex() {
		require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';

		$id = $_REQUEST['id'];
		$groupedWork = new GroupedWork();
		$groupedWork->permanent_id = $id;
		if ($groupedWork->find(true)) {
			$groupedWork->forceReindex(true);

			return [
				'success' => true,
				'message' => 'This title will be indexed again shortly.',
			];
		} else {
			return [
				'success' => false,
				'message' => 'Unable to mark the title for indexing. Could not find the title.',
			];
		}
	}

	function getDescription() {
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
	function getEnrichmentInfo() {
		global $interface;
		global $memoryWatcher;

		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		$id = $_REQUEST['id'];
		$recordDriver = new GroupedWorkDriver($id);
		$interface->assign('recordDriver', $recordDriver);

		$enrichmentResult = [];
		$enrichmentData = $recordDriver->loadEnrichment();
		$memoryWatcher->logMemory('Loaded Enrichment information from Novelist');

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
			$memoryWatcher->logMemory('Loaded Similar titles from Novelist');

			if ($novelistData->getAuthorCount()) {
				$interface->assign('similarAuthors', $novelistData->getAuthors());
				$enrichmentResult['similarAuthorsNovelist'] = $interface->fetch('GroupedWork/similarAuthorsNovelist.tpl');
			}
			$memoryWatcher->logMemory('Loaded Similar authors from Novelist');

			if ($novelistData->getSimilarSeriesCount()) {
				$interface->assign('similarSeries', $novelistData->getSimilarSeries());
				$enrichmentResult['similarSeriesNovelist'] = $interface->fetch('GroupedWork/similarSeriesNovelist.tpl');
			}
			$memoryWatcher->logMemory('Loaded Similar series from Novelist');
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
	function getMoreLikeThis() {
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
			if (isset($similar) && count($similar['response']['docs']) > 0) {
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
	function getWhileYouWait() {
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
	function getYouMightAlsoLike() {
		global $interface;
		global $memoryWatcher;

		$id = $_REQUEST['id'];

		global $library;
		if (!$library->showWhileYouWait) {
			$interface->assign('numTitles', 0);
		} else {
			//Get all the titles to ignore, everything that has been rated, in reading history, or that the user is not interested in

			//Load Similar titles (from Solr)
			require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
			require_once ROOT_DIR . '/sys/SolrConnector/GroupedWorksSolrConnector.php';
			/** @var SearchObject_AbstractGroupedWorkSearcher $db */
			$searchObject = SearchObjectFactory::initSearchObject();
			$searchObject->init();
			$searchObject->disableScoping();
			UserAccount::getActiveUserObj();
			$similar = $searchObject->getMoreLikeThis($id, false, false, 3);
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

	function getScrollerTitle($record, $index, $scrollerName) {
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
			$formattedTitle = "<div id=\"scrollerTitle{$scrollerName}{$index}\" class=\"scrollerTitle\" onclick=\"return AspenDiscovery.showElementInPopup('$title', '#noResults{$index}')\">" . "<img src=\"{$cover}\" class=\"scrollerTitleCover\" alt=\"{$title} Cover\"/>" . "</div>";
			$formattedTitle .= "<div id=\"noResults{$index}\" style=\"display:none\">
					<div class=\"row\">
						<div class=\"result-label col-md-3\">Author: </div>
						<div class=\"col-md-9 result-value notranslate\">
							<a href='/Author/Home?author=\"{$record['author']}\"'>{$record['author']}</a>
						</div>
					</div>
					<div class=\"series row\">
						<div class=\"result-label col-md-3\">Series: </div>
						<div class=\"col-md-9 result-value\">
							<a href=\"/GroupedWork/{$originalId}/Series\">{$series}</a>
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
			'id' => isset($record['id']) ? $record['id'] : '',
			'image' => $cover,
			'title' => $title,
			'author' => isset($record['author']) ? $record['author'] : '',
			'formattedTitle' => $formattedTitle,
		];
	}

	/** @noinspection PhpUnused */
	function getGoDeeperData() {
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
	function getWorkInfo() {
		global $interface;

		//Indicate we are showing search results so we don't get hold buttons
		$interface->assign('displayingSearchResults', true);

		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		$id = $_REQUEST['id'];
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

		$escapedId = htmlentities($recordDriver->getPermanentId()); // escape for html
		$buttonLabel = translate([
			'text' => 'Add to list',
			'isPublicFacing' => true,
		]);

		// button template
		$interface->assign('escapeId', $escapedId);
		$interface->assign('buttonLabel', $buttonLabel);
		$interface->assign('url', $url);

		$modalBody = $interface->fetch('GroupedWork/work-details.tpl');
		return [
			'title' => "<a href='$url'>{$recordDriver->getTitle()}</a>",
			'modalBody' => $modalBody,
			'modalButtons' => "<button onclick=\"return AspenDiscovery.Account.showSaveToListForm(this, 'GroupedWork', '$escapedId');\" class=\"modal-buttons btn btn-primary\" style='float: left'>$buttonLabel</button>" . "<a href='$url'><button class='modal-buttons btn btn-primary addToListBtn'>" . translate([
					'text' => "More Info",
					'isPublicFacing' => true,
				]) . "</button></a>",
		];
	}

	/** @noinspection PhpUnused */
	function rateTitle() {
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
				// pretend success since rating is already set to same value.
				$success = true;
			}
		} else {
			$workReview->rating = $rating;
			$workReview->review = '';  // default value required for insert statements //TODO alter table structure, null should be default value.
			$workReview->dateRated = time(); // moved to be consistent with add review behaviour
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

	private function clearMySuggestionsBrowseCategoryCache() {
		// Reset any cached suggestion browse category for the user /** @var Memcache $memCache */ global $memCache;
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
	function getReviewInfo() {
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
	function getPromptForReviewForm() {
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
						'modalButtons' => "<button class='tool btn btn-primary' onclick='AspenDiscovery.GroupedWork.showReviewForm(this, \"{$id}\");'>" . translate([
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
				// Option already set to don't prompt, so let's don't prompt already.
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
	function setNoMoreReviews() {
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
	function getReviewForm() {
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
				'modalButtons' => "<button class='tool btn btn-primary' onclick='AspenDiscovery.GroupedWork.saveReview(\"{$id}\");'>" . translate([
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
	function saveReview() {
		$result = [];

		if (UserAccount::isLoggedIn() == false) {
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
			$rating = isset($_REQUEST['rating']) ? $_REQUEST['rating'] : '';
			$HadReview = isset($_REQUEST['comment']); // did form have the review field turned on? (may be only ratings instead)
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
			if (!$success) { // if sql save didn't work, let user know.
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
	function getEmailForm() {
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
			'modalButtons' => "<button class='tool btn btn-primary' onclick='AspenDiscovery.GroupedWork.sendEmail(\"{$id}\"); return false;'>" . translate([
					'text' => 'Send Email',
					'isPublicFacing' => true,
				]) . "</button>",
		];
	}

	/** @noinspection PhpUnused */
	function sendEmail() {
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
			} elseif (($emailResult instanceof AspenError)) {
				$result = [
					'result' => false,
					'message' => translate([
						'text' => "Your email message could not be sent: %1%.",
						1 => $emailResult->getMessage(),
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
	function markNotInterested() {
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
	function clearNotInterested() {
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
	function getProspectorInfo() {
		require_once ROOT_DIR . '/Drivers/marmot_inc/Prospector.php';
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

		$prospector = new Prospector();

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

		$prospectorResults = $prospector->getTopSearchResults($searchTerms, 10);
		$interface->assign('prospectorResults', $prospectorResults['records']);

		return [
			'numTitles' => count($prospectorResults),
			'formattedData' => $interface->fetch('GroupedWork/ajax-innreach.tpl'),
		];
	}

	/** @noinspection PhpUnused */
	function getSeriesSummary() {
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
	function reloadCover() {
		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		$id = $_REQUEST['id'];
		$recordDriver = new GroupedWorkDriver($id);

		require_once ROOT_DIR . '/sys/Covers/BookCoverInfo.php';
		$bookCoverInfo = new BookCoverInfo();
		$bookCoverInfo->recordType = 'grouped_work';
		$bookCoverInfo->recordId = $id;
		if ($bookCoverInfo->find(true)) {
			$bookCoverInfo->imageSource = '';
			$bookCoverInfo->thumbnailLoaded = 0;
			$bookCoverInfo->mediumLoaded = 0;
			$bookCoverInfo->largeLoaded = 0;
			$bookCoverInfo->update();
		}

		$relatedRecords = $recordDriver->getRelatedRecords(true);
		foreach ($relatedRecords as $record) {
			$bookCoverInfo = new BookCoverInfo();
			if (strpos($record->id, ':') > 0) {
				[
					$source,
					$recordId,
				] = explode(':', $record->id);
				$bookCoverInfo->recordType = $source;
				$bookCoverInfo->recordId = $recordId;
			} else {
				$bookCoverInfo->recordType = $record->source;
				$bookCoverInfo->recordId = $record->id;
			}

			if ($bookCoverInfo->find(true)) {
				$bookCoverInfo->imageSource = '';
				$bookCoverInfo->thumbnailLoaded = 0;
				$bookCoverInfo->mediumLoaded = 0;
				$bookCoverInfo->largeLoaded = 0;
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
	function getUploadCoverForm() {
		global $interface;

		$id = $_REQUEST['id'];
		$interface->assign('id', $id);

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
	function uploadCover() {
		$result = [
			'success' => false,
			'title' => translate([
				'text' => 'Uploading custom cover',
				'isPublicFacing' => 'true',
			]),
			'message' => translate([
				'text' => 'Sorry your cover could not be uploaded',
				'isAdminFacing=true',
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
					global $configArray;
					$destFullPath = $configArray['Site']['coverPath'] . '/original/' . $id . '.png';
					$fileType = $uploadedFile["type"];
					if ($fileType == 'image/png') {
						if (copy($uploadedFile["tmp_name"], $destFullPath)) {
							$result['success'] = true;
						}
					} elseif ($fileType == 'image/gif') {
						$imageResource = @imagecreatefromgif($uploadedFile["tmp_name"]);
						if (!$imageResource) {
							$result['message'] = translate([
								'text' => 'Unable to process this image, please try processing in an image editor and reloading',
								'isAdminFacing' => true,
							]);
						} elseif (@imagepng($imageResource, $destFullPath, 9)) {
							$result['success'] = true;
						}
					} elseif ($fileType == 'image/jpg' || $fileType == 'image/jpeg') {
						$imageResource = @imagecreatefromjpeg($uploadedFile["tmp_name"]);
						if (!$imageResource) {
							$result['message'] = translate([
								'text' => 'Unable to process this image, please try processing in an image editor and reloading',
								'isAdminFacing' => true,
							]);
						} elseif (@imagepng($imageResource, $destFullPath, 9)) {
							$result['success'] = true;
						}
					} else {
						$result['message'] = translate([
							'text' => 'Incorrect image type.  Please upload a PNG, GIF, or JPEG',
							'isAdminFacing' => true,
						]);
					}
					if ($result['success'] == true) {
						try {
							chgrp($destFullPath, 'aspen_apache');
							chmod($destFullPath, 0775);
						} catch (Exception $e) {
							//Just ignore errors
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
			$this->reloadCover();
			$result['message'] = translate([
				'text' => 'Your cover has been uploaded successfully',
				'isAdminFacing' => true,
			]);
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function getUploadCoverFormByURL() {
		global $interface;

		$id = $_REQUEST['id'];
		$interface->assign('id', $id);

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
	function uploadCoverByURL() {
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
			$uploadedFile = file_get_contents($url);

			if (isset($uploadedFile["error"]) && $uploadedFile["error"] == 4) {
				$result['message'] = translate([
					'text' => "No Cover file was uploaded",
					'isAdminFacing' => true,
				]);
			} elseif (isset($uploadedFile["error"]) && $uploadedFile["error"] > 0) {
				$result['message'] = translate([
					'text' => "Error in file upload for cover %1%",
					1 => $uploadedFile["error"],
					'isAdminFacing' => true,
				]);
			}

			$id = $_REQUEST['id'];
			global $configArray;
			$destFullPath = $configArray['Site']['coverPath'] . '/original/' . $id . '.png';
			$ext = pathinfo($filename, PATHINFO_EXTENSION);
			if ($ext == "jpg" or $ext == "png" or $ext == "gif" or $ext == "jpeg") {
				$upload = file_put_contents($destFullPath, file_get_contents($url));
				if ($upload) {
					$result['success'] = true;
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
			$this->reloadCover();
			$result['message'] = translate([
				'text' => 'Your cover has been uploaded successfully',
				'isAdminFacing' => true,
			]);
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function getCopyDetails() {
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
		$interface->assign('itemSummaryId', $id);
		$interface->assign('relatedManifestation', $relatedManifestation);

		if ($recordId != $id) {
			$record = $recordDriver->getRelatedRecord($recordId);
			if ($record != null) {
				$summary = $record->getItemSummary();
			} else {
				$summary = null;
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
		$interface->assign('summary', $summary);

		$modalBody = $interface->fetch('GroupedWork/copyDetails.tpl');
		return [
			'title' => translate([
				'text' => "Where is it?",
				'isPublicFacing' => true,
			]),
			'modalBody' => $modalBody,
		];
	}

	/** @noinspection PhpUnused */
	function getGroupWithForm() {
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
	function getGroupWithInfo() {
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
					]) . "</div><div class='col-tn-9'><strong>{$groupedWork->full_title}</strong></div></div>";
				$results['message'] .= "<div class='row'><div class='col-tn-3'>" . translate([
						'text' => 'Author',
						'isAdminFacing' => true,
					]) . "</div><div class='col-tn-9'><strong>{$groupedWork->author}</strong></div></div>";
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
	function processGroupWithForm() {
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
					if ($originalGroupedWork->grouping_category != $workToGroupWith->grouping_category) {
						$results['message'] = translate([
							'text' => "These are different categories of works, cannot group.",
							'isAdminFacing' => true,
						]);
					} else {
						require_once ROOT_DIR . '/sys/Grouping/GroupedWorkAlternateTitle.php';
						$groupedWorkAlternateTitle = new GroupedWorkAlternateTitle();
						$groupedWorkAlternateTitle->permanent_id = $workToGroupWithId;
						$groupedWorkAlternateTitle->alternateAuthor = $originalGroupedWork->author;
						$groupedWorkAlternateTitle->alternateTitle = $originalGroupedWork->full_title;
						$groupedWorkAlternateTitle->addedBy = UserAccount::getActiveUserId();
						$groupedWorkAlternateTitle->dateAdded = time();
						$groupedWorkAlternateTitle->insert();
						$originalGroupedWork->forceReindex(true);
						$results['success'] = true;
						$results['message'] = translate([
							'text' => "Your works have been grouped successfully, the index will update shortly.",
							'isAdminFacing' => true,
						]);
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
	function getGroupWithSearchForm() {
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
							if ($primaryWork->grouping_category == $groupedWork->grouping_category) {
								$availableRecords[$doc['id']] = "$recordIndex) {$primaryWork->full_title} {$primaryWork->author}";
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

	function getStaffView() {
		$result = [
			'success' => false,
			'message' => translate([
				'text' => 'Unknown error loading staff view',
				'isPublicFacing' => true,
			]),
		];
		$id = $_REQUEST['id'];
		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		$recordDriver = new GroupedWorkDriver($id);
		global $interface;
		$interface->assign('recordDriver', $recordDriver);
		$result = [
			'success' => true,
			'staffView' => $interface->fetch($recordDriver->getStaffView()),
		];
		return $result;
	}

	/** @noinspection PhpUnused */
	function deleteAlternateTitle() {
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

	function deleteUngrouping() {
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
	function getDisplayInfoForm() {
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
					'modalButtons' => "<button class='tool btn btn-primary' onclick='AspenDiscovery.GroupedWork.processGroupedWorkDisplayInfoForm(\"{$id}\")'>" . translate([
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
	function processDisplayInfoForm() {
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
	function deleteDisplayInfo() {
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
					$groupedWork->forceReindex(false);
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
	function showSelectDownloadForm() {
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
				'modalButtons' => "<button class='tool btn btn-primary' onclick='$(\"#downloadFile\").submit()'>{$buttonTitle}</button>",
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
	function showSelectFileToViewForm() {
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
				'modalButtons' => "<button class='tool btn btn-primary' onclick='$(\"#viewFile\").submit()'>{$buttonTitle}</button>",
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
	function getPreviewRelatedCover() {
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
			'modalButtons' => "<button class='tool btn btn-primary' onclick='AspenDiscovery.GroupedWork.setRelatedCover(\"{$recordId}\",\"{$groupedWorkId}\",\"{$recordType}\")'>" . translate([
					'text' => "Use Cover",
					'isAdminFacing' => true,
				]) . "</button>",
		];
	}

	/** @noinspection PhpUnused */
	function setRelatedCover() {
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
		}
	}

	/** @noinspection PhpUnused */
	function clearRelatedCover() {
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
	function clearUploadedCover() {
		require_once ROOT_DIR . '/sys/Covers/BookCoverInfo.php';
		$bookcoverInfo = new BookCoverInfo();

		$id = $_REQUEST['id'];
		$bookcoverInfo->recordId = $id;
		if ($bookcoverInfo->find(true)) {
			global $configArray;
			$bookCoverPath = $configArray['Site']['coverPath'];
			$permanentId = $bookcoverInfo->recordId;

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

			$bookcoverInfo->imageSource = 'default';
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
	function getLargeCover() {
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


}