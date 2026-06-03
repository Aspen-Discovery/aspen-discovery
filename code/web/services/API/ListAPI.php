<?php

require_once ROOT_DIR . '/services/API/AbstractAPI.php';
require_once ROOT_DIR . '/sys/API/APIMethodConfiguration.php';
require_once ROOT_DIR . '/sys/Pager.php';
require_once ROOT_DIR . '/sys/UserLists/UserList.php';
require_once ROOT_DIR . '/sys/SearchEntry.php';
require_once ROOT_DIR . '/sys/Authentication/OAuth2/OAuth2Middleware.php';

class ListAPI extends AbstractAPI {
	use APIMethodConfiguration;

	function launch() {
		$method = (isset($_GET['method']) && !is_array($_GET['method'])) ? $_GET['method'] : '';

		$this->setLanguage();

		// Special handling for RSS feed method (XML output)
		if ($method == 'getRSSFeed') {
			header('Content-type: text/xml');
			header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			$xml = '<?xml version="1.0" encoding="UTF-8"?' . ">\n";
			$xml .= $this->$method();
			echo $xml;

			require_once ROOT_DIR . '/sys/SystemLogging/APIUsage.php';
			APIUsage::incrementStat('ListAPI', $method);
			return;
		}

		// Use automatic method discovery - no more manual arrays!
		$this->handleAPIRequestAuto($method, 'list_api');
	}

	/**
	 * Override the API scope prefix for ListAPI
	 */
	protected function getAPIScopePrefix(): string {
		return 'list';
	}


	function getAllListIds() {
		$allListNames = [];
		$publicLists = $this->getPublicLists();
		if ($publicLists['success'] = true) {
			foreach ($publicLists['lists'] as $listInfo) {
				$allListNames[] = $listInfo['id'];
				$allListNames[] = 'list:' . $listInfo['id'];
			}
		}
		$systemLists = $this->getSystemLists();
		if ($systemLists['success'] = true) {
			foreach ($systemLists['lists'] as $listInfo) {
				$allListNames[] = $listInfo['id'];
			}
		}
		return $allListNames;
	}

	/**
	 * Get all public lists
	 * includes id, title, description, and number of titles
	 * @oauth true
	 * @token false
	 * @public true
	 */
	function getPublicLists(): array {
		global $aspen_db;
		$list = new UserList();
		$list->public = 1;
		$list->deleted = 0;
		$list->find();
		$results = [];
		if ($list->getNumResults() > 0) {
			while ($list->fetch()) {
				$query = "SELECT count(id) as numTitles FROM user_list_entry where listId = " . $list->id;
				$stmt = $aspen_db->prepare($query);
				$stmt->setFetchMode(PDO::FETCH_ASSOC);
				$success = $stmt->execute();
				if ($success) {
					$row = $stmt->fetch();
					$numTitles = $row['numTitles'];
				} else {
					$numTitles = -1;
				}

				$results[] = [
					'id' => $list->id,
					'title' => $list->title,
					'description' => $list->description,
					'displayListAuthor' => $list->displayListAuthor,
					'numTitles' => $numTitles,
					'dateUpdated' => $list->dateUpdated,
				];
			}
		}
		return [
			'success' => true,
			'lists' => $results,
		];
	}

	/**
	 * Get all system generated lists that are available.
	 * includes id, title, description, and number of titles
	 */
	function getSystemLists() {
		//System lists are not stored in tables, but are generated based on
		//a variety of factors.
		$systemLists[] = [
			'id' => 'recentlyReviewed',
			'title' => 'Recently Reviewed',
			'description' => 'Titles that have had new reviews added to them.',
			'numTitles' => 30,
		];
		$systemLists[] = [
			'id' => 'highestRated',
			'title' => 'Highly Rated',
			'description' => 'Titles that have the highest ratings within the catalog.',
			'numTitles' => 30,
		];
		$systemLists[] = [
			'id' => 'mostPopular',
			'title' => 'Most Popular Titles',
			'description' => 'Most Popular titles based on checkout history.',
			'numTitles' => 30,
		];
		$systemLists[] = [
			'id' => 'recommendations',
			'title' => 'Recommended For You',
			'description' => 'Titles Recommended for you based off your ratings.',
			'numTitles' => 30,
		];
		return [
			'success' => true,
			'lists' => $systemLists,
		];
	}

	/**
	 * Get all public lists
	 * includes id, title, description, and number of titles
	 */
	function getSearchableLists() {
		$restrictByLibrary = $_REQUEST['restrictByLibrary'] ?? false;
		if ($restrictByLibrary) {
			$titleFilter = $_REQUEST['title'] ?? null;
			global $timer;
			/** @var SearchObject_ListsSearcher $searchObject */
			$searchObject = SearchObjectFactory::initSearchObject('Lists');
			$searchObject->init();
			$searchObject->clearFacets();
			if (!empty($titleFilter)) {
				$searchObject->setSearchTerms([
					'index' => 'title',
					'lookfor' => $titleFilter,
				]);
			}
			$results = $searchObject->processSearch(true, false);
			if ($results == null) {
				return [
					'success' => false,
					'message' => 'The Solr index is offline, please try your search again in a few minutes.',
					'lists' => [],
				];
			} elseif ($results instanceof AspenError || !empty($result['error'])) {
				return [
					'success' => false,
					'message' => 'An error occurred while searching for lists.',
					'lists' => [],
				];
			}

			$timer->logTime('Process Search');
			$searchObject->close();
			if ($searchObject->getResultTotal() == 0) {
				return [
					'success' => true,
					'lists' => [],
				];
			}

			$results = [];
			$recordSet = $searchObject->getResultRecordSet();
			$timer->logTime('load result records');
			foreach ($recordSet as $record) {
				$dateUpdated = null;
				if (isset($record['days_since_updated']) && is_numeric($record['days_since_updated'])) {
					$timestamp = time() - ($record['days_since_updated'] * 24 * 60 * 60);
					$dateUpdated = date('F j, Y g:i A', $timestamp);
				}
				$results[] = [
					'id' => $record['id'],
					'title' => $record['title'],
					'description' => $record['description'],
					'displayListAuthor' => $record['author_display'],
					'numTitles' => $record['num_titles'],
					'dateUpdated' => $dateUpdated,
				];
			}

			return [
				'success' => true,
				'lists' => $results,
			];
		} else {
			global $aspen_db;
			$list = new UserList();
			$list->public = 1;
			$list->searchable = 1;
			$list->deleted = 0;
			$list->find();
			$results = [];
			if ($list->getNumResults() > 0) {
				while ($list->fetch()) {
					$query = "SELECT count(id) as numTitles FROM user_list_entry where listId = " . $list->id;
					$stmt = $aspen_db->prepare($query);
					$stmt->setFetchMode(PDO::FETCH_ASSOC);
					$success = $stmt->execute();
					if ($success) {
						$row = $stmt->fetch();
						$numTitles = $row['numTitles'];
					} else {
						$numTitles = -1;
					}

					$results[] = [
						'id' => $list->id,
						'title' => $list->title,
						'description' => $list->description,
						'displayListAuthor' => $list->displayListAuthor,
						'numTitles' => $numTitles,
						'dateUpdated' => $list->dateUpdated,
					];
				}
			}
			return [
				'success' => true,
				'lists' => $results,
			];
		}
	}

	/**
	 * Get all lists that a particular user has created.
	 * includes id, title, description, number of titles, and whether the list is public
	 * @noinspection PhpUnused
	 */
	function getUserLists() {
		$user = $this->getUserForApiCall();

		if ($user === false) {
			return [
				'success' => false,
				'message' => 'Sorry, we could not find a user with those credentials.',
			];
		}

		$checkIfValid = "true";
		if (isset($_REQUEST['checkIfValid'])) {
			$checkIfValid = $_REQUEST['checkIfValid'];
		}

		$listsPerPage = 20;
		if (isset($_REQUEST['limit'])) {
			$listsPerPage = $_REQUEST['limit'];
		}

		$page = $_REQUEST['page'] ?? 1;

		$includePagination = false;
		if (isset($_REQUEST['includePagination'])) {
			$includePagination = (bool)$_REQUEST['includePagination'];
		}

		global $configArray;
		$userId = $user->id;

		$count = 0;
		$list = new UserList();
		$list->user_id = $userId;
		$list->deleted = 0;
		if ($includePagination) {
			$list->limit(($page - 1) * $listsPerPage, $listsPerPage);
		}
		$listCount = $list->count();
		$list->find();
		$results = [];

		$options = [
			'totalItems' => $listCount,
			'perPage' => $listsPerPage,
		];

		require_once ROOT_DIR . '/sys/Pager.php';
		$pager = new Pager($options);

		if ($list->getNumResults() > 0) {
			while ($list->fetch()) {
				if ($checkIfValid == "true") {
					if ($list->isValidForDisplay()) {
						$count = $count + 1;
						$results[] = [
							'id' => $list->id,
							'title' => $list->title,
							'description' => $list->description,
							'displayListAuthor' => $list->displayListAuthor == 1,
							'numTitles' => $list->numValidListItems(),
							'public' => $list->public == 1,
							'created' => $list->created,
							'dateUpdated' => $list->dateUpdated,
							'cover' => $configArray['Site']['url'] . "/bookcover.php?type=list&id={$list->id}&size=medium",
						];
					}
				} else {
					$count = $count + 1;
					$results[] = [
						'id' => $list->id,
						'title' => $list->title,
						'description' => $list->description,
						'displayListAuthor' => $list->displayListAuthor == 1,
						'numTitles' => $list->numValidListItems(),
						'public' => $list->public == 1,
						'created' => $list->created,
						'dateUpdated' => $list->dateUpdated,
						'cover' => $configArray['Site']['url'] . "/bookcover.php?type=list&id={$list->id}&size=medium",
					];
				}
			}
		}

		$includeSuggestions = $_REQUEST['includeSuggestions'] ?? true;
		if ($includeSuggestions) {
			require_once(ROOT_DIR . '/sys/Suggestions.php');
			$suggestions = Suggestions::getSuggestions($userId);
			if (count($suggestions) > 0) {
				$results[] = [
					'id' => 'recommendations',
					'title' => 'User Recommendations',
					'description' => 'Personalized Recommendations based on ratings.',
					'numTitles' => count($suggestions),
					'public' => false,
				];
			}
		}

		$result = [
			'success' => true,
			'lists' => $results,
			'count' => $count
		];

		if ($includePagination) {
			$result = array_merge($result, [
				'page_current' => (int)$pager->getCurrentPage(),
				'totalResults' => (int)$pager->getTotalItems(),
				'page_total' => (int)$pager->getTotalPages(),
			]);
		}

		return $result;
	}

	/**
	 * Get all the list groups that a particular user has created.
	 * @oauth true
	 * @token true
	 * @scopes list:read
	 * @noinspection PhpUnused
	 */
	function getUserListGroups(): array {
		$user = $this->getUserForApiCall();
		if ($user === false) {
			return [
				'success' => false,
				'message' => 'Sorry, we could not find a user with those credentials.',
			];
		}

		require_once ROOT_DIR . '/sys/UserLists/UserListGroup.php';
		$listGroup = new UserListGroup();
		$listGroups = $listGroup->getListGroups($user);
		$unassignedLists = $user->getNumUnassignedLists() ?? 0;

		return [
			'success' => true,
			'groups' => $listGroups,
			'unassigned' => $unassignedLists,
		];
	}

	/**
	 * Get's RSS Feed
	 * @noinspection PhpUnused
	 */
	function getRSSFeed() {
		global $configArray;

		$rssFeed = '<rss version="2.0">';
		$rssFeed .= '<channel>';

		if (!isset($_REQUEST['id'])) {
			$rssFeed .= '<error>No ID Provided</error>';
		} else {
			$listId = $_REQUEST['id'];
			$curDate = date("D M j G:i:s T Y");

			//Grab the title based on the list that id that is passed in
			$titleData = $this->getListTitles($listId);
			$titleCount = count($titleData["titles"]);

			if ($titleCount > 0) {

				$listTitle = $titleData["listName"];
				$listDesc = $titleData["listDescription"];

				$rssFeed .= '<title>' . $listTitle . '</title>';
				$rssFeed .= '<language>en-us</language>';
				$rssFeed .= '<description>' . $listDesc . '</description>';
				$rssFeed .= '<lastBuildDate>' . $curDate . '</lastBuildDate>';
				$rssFeed .= '<pubDate>' . $curDate . '</pubDate>';
				$rssFeed .= '<link>' . htmlspecialchars($configArray['Site']['url'] . '/API/ListAPI?method=getRSSFeed&id=' . $listId) . '</link>';

				foreach ($titleData["titles"] as $title) {
					$titleId = $title["id"];
					$image = $title["image"];
					$bookTitle = $title["title"];
					$bookTitle = rtrim($bookTitle, " /");
					$author = $title["author"];
					$description = $title["description"];
					$length = $title["length"];
					$publisher = $title["publisher"];

					if (isset($title["dateSaved"])) {
						$pubDate = $title["dateSaved"];
					} else {
						$pubDate = "No Date Available";
					}


					$rssFeed .= '<item>';
					$rssFeed .= '<id>' . $titleId . '</id>';
					/** @noinspection HtmlDeprecatedTag */
					$rssFeed .= '<image>' . htmlspecialchars($image) . '</image>';
					$rssFeed .= '<title>' . htmlspecialchars($bookTitle) . '</title>';
					$rssFeed .= '<author>' . htmlspecialchars($author) . '</author>';
					$itemLink = htmlspecialchars($configArray['Site']['url'] . '/Record/' . $titleId);

					$fullDescription = "<a href='{$itemLink}'><img src='{$image}' alt='cover'/></a>$description";
					$rssFeed .= '<description>' . htmlspecialchars($fullDescription) . '</description>';
					$rssFeed .= '<length>' . $length . '</length>';
					$rssFeed .= '<publisher>' . htmlspecialchars($publisher) . '</publisher>';
					$rssFeed .= '<pubDate>' . $pubDate . '</pubDate>';
					$rssFeed .= '<link>' . $itemLink . '</link>';

					$rssFeed .= '</item>';

				}
			} else {
				$rssFeed .= '<error>No Titles Listed</error>';
			}

		}

		$rssFeed .= '</channel>';
		$rssFeed .= '</rss>';


		return $rssFeed;
	}

	/**
	 * Returns information about the titles within a list including:
	 * - Title, Author, Bookcover URL, description, record id
	 *
	 * @param string $listId - The list to show
	 * @param integer $numTitlesToShow - the maximum number of titles that should be shown
	 * @return array
	 * @oauth true
	 * @token true
	 * @scopes list:read
	 */
	function getListTitles($listId = NULL, $numTitlesToShow = 25, $page = 1) {
		global $configArray;
		if (!$listId) {
			if (!isset($_REQUEST['id'])) {
				return [
					'success' => false,
					'message' => 'The id of the list to load must be provided as the id parameter.',
				];
			}
			$listId = $_REQUEST['id'];
		}

		$user = $this->getUserForApiCall();
		if (!$user) {
			$user = UserAccount::getLoggedInUser(); // This is here because the API is called from Discovery when loading Browse Categories
		}

		if (isset($_REQUEST['numTitles'])) {
			$numTitlesToShow = $_REQUEST['numTitles'];
		}

		if (isset($_REQUEST['page'])) {
			$page = $_REQUEST['page'];
		}

		$sort = null;
		if(isset($_REQUEST['sort_by'])) {
			$requestedSort = $_REQUEST['sort_by'];
			$availableSorts = UserList::getSortOptions();
			if (array_key_exists($requestedSort, $availableSorts)) {
				$sort = $requestedSort;
			}
		}

		if (!is_numeric($numTitlesToShow)) {
			$numTitlesToShow = 25;
		}

		if (is_numeric($listId) || preg_match('/list[-:](.*)/', $listId, $listInfo)) {
			if (isset($listInfo)) {
				$listId = $listInfo[1];
			}
			return $this->_getUserListTitles($listId, $numTitlesToShow, $user, $page, $sort);
		} elseif (preg_match('/search:(?<searchID>.*)/', $listId, $searchInfo)) {
			if (is_numeric($searchInfo[1])) {
				$titles = $this->getSavedSearchTitles($searchInfo[1], $numTitlesToShow);
				if ($titles === false) { // Didn't find saved search
					return [
						'success' => false,
						'message' => 'The specified search could not be found.',
					];
				} else { // successful search with or without any results. (javascript can handle no results returned.)
					return [
						'success' => true,
						'listTitle' => $listId,
						'listDescription' => "Search Results",
						'titles' => $titles,
					];
				}
			} else {
				//Do a default search
				$titles = $this->getSystemListTitles($listId, $numTitlesToShow);
				if (count($titles) > 0) {
					return [
						'success' => true,
						'listTitle' => $listId,
						'listDescription' => "System Generated List",
						'titles' => $titles,
					];
				} else {
					return [
						'success' => false,
						'message' => 'The specified list could not be found.',
					];
				}
			}

		} else {
			$systemList = null;
			$systemLists = $this->getSystemLists();
			foreach ($systemLists['lists'] as $curSystemList) {
				if ($curSystemList['id'] == $listId) {
					$systemList = $curSystemList;
					break;
				}
			}
			//The list is a system generated list
			if ($listId == 'recommendations') {
				if (!$user) {
					return [
						'success' => false,
						'message' => 'A valid user must be provided to load recommendations.',
					];
				} else {
					$userId = $user->id;
					require_once(ROOT_DIR . '/sys/Suggestions.php');
					$suggestions = Suggestions::getSuggestions($userId);
					$titles = [];
					foreach ($suggestions as $id => $suggestion) {
						$imageUrl = $configArray['Site']['url'] . "/bookcover.php?id=" . $id;
						if (isset($suggestion['titleInfo']['issn'])) {
							$imageUrl .= "&issn=" . $suggestion['titleInfo']['issn'];
						}
						if (isset($suggestion['titleInfo']['isbn10'])) {
							$imageUrl .= "&isn=" . $suggestion['titleInfo']['isbn10'];
						}
						if (isset($suggestion['titleInfo']['upc'])) {
							$imageUrl .= "&upc=" . is_array($suggestion['titleInfo']['upc']) ? reset($suggestion['titleInfo']['upc']) : $suggestion['titleInfo']['upc'];
						}
						if (isset($suggestion['titleInfo']['format_category'])) {
							$category = explode(
								'#',
								is_array($suggestion['titleInfo']['format_category'])
									? reset($suggestion['titleInfo']['format_category'])
									: $suggestion['titleInfo']['format_category']
							);
							$imageUrl .= "&category=" . end($category);
						}
						$smallImageUrl = $imageUrl . "&size=small";
						$imageUrl .= "&size=medium";
						$titles[] = [
							'id' => $id,
							'image' => $imageUrl,
							'small_image' => $smallImageUrl,
							'title' => $suggestion['titleInfo']['title_display'],
							'author' => $suggestion['titleInfo']['author_display'],
						];
					}
					return [
						'success' => true,
						'listTitle' => $systemList['title'],
						'listDescription' => $systemList['description'],
						'titles' => $titles,
					];
				}
			} else {
				return [
					'success' => false,
					'message' => 'The specified list could not be found.',
				];
			}
		}
	}

	public function _getUserListTitles($listId, $numTitlesToShow, $user, $page, $sort): array {
		global $configArray;
		$listTitles = [];
		//The list is a patron generated list
		$list = new UserList();
		$list->id = $listId;
		if ($list->find(true)) {
			//Make sure the user has access to the list
			if ($list->public == 0) {
				if (!isset($user)) {
					return [
						'success' => false,
						'message' => 'The user was invalid.  A valid user must be provided for private lists.',
					];
				} elseif ($list->user_id != $user->id) {
					return [
						'success' => false,
						'message' => 'The user does not have access to this list.',
					];
				}
			}

			$isLida = $this->checkIfLiDA();
			if ($isLida) {
				$appVersion = $this->getLiDAVersion();
			} else {
				$appVersion = 0;
			}

			//if LiDA we don't want to include events list entries in the list count
			if ($isLida){
				$totalRecords = $list->numValidListItemsForLiDA($appVersion);
			}else {
				$totalRecords = $list->numValidListItems();
			}
			$startRecord = ($page - 1) * $numTitlesToShow;
			if ($startRecord < 0) {
				$startRecord = 0;
			}
			$options = [
				'totalItems' => $totalRecords,
				'perPage' => $numTitlesToShow,
				'append' => false,
			];
			$pager = new Pager($options);

			$titles = $list->getListRecords($startRecord, $numTitlesToShow, false, 'summary', null, $sort, $isLida, $appVersion);

			foreach ($titles as $title) {
				if ($isLida){ //if LiDA don't look at events - filtered out in getListEntries()
					$imageUrl = $configArray['Site']['url'] . "/bookcover.php?id=" . $title['id'];
					$smallImageUrl = $imageUrl . "&size=small";
					$imageUrl .= "&size=medium";
					$recordType = $title['recordType'] ?? $title['recordtype'];

					if($recordType == 'event') {
						$listTitles[] = [
							'id' => $title['id'],
							'image' => $imageUrl,
							'title' => $title['title'],
							'recordType' => $recordType,
							'description' => $title['description'],
							'start_date' => $title['start_date'],
							'end_date' => $title['end_date'],
							'registration_required' => $title['registration_required'],
							'bypass' => $title['bypass'],
							'url' => $title['url'],
							'source' => $title['source']
						];
					} else {
						$listTitles[] = [
							'id' => $title['id'],
							'image' => $imageUrl,
							'small_image' => $smallImageUrl,
							'title' => $title['title'],
							'author' => $title['author'],
							'shortId' => $title['shortId'],
							'recordType' => $recordType,
							'titleURL' => $title['titleURL'],
							'description' => $title['description'],
							'length' => $title['length'],
							'publisher' => $title['publisher'],
							'ratingData' => $title['ratingData'],
							'format' => $title['format'],
							'language' => $title['language'],
							'primary_isbn' => $title['primary_isbn'],
							'primary_upc' => $title['primary_upc'],
						];
					}
				} else if (!$isLida) { //if not LiDA look at all the things
					$imageUrl = "/bookcover.php?id=" . $title['id'];
					$smallImageUrl = $imageUrl . "&size=small";
					$imageUrl .= "&size=medium";

					$listTitles[] = [
						'id' => $title['id'],
						'image' => $imageUrl,
						'small_image' => $smallImageUrl,
						'title' => $title['title'],
						'author' => $title['author'],
						'shortId' => $title['shortId'],
						'recordType' => isset($title['recordType']) ? $title['recordType'] : $title['recordtype'],
						'titleURL' => $title['titleURL'],
						'description' => $title['description'],
						'length' => $title['length'],
						'publisher' => $title['publisher'],
						'ratingData' => $title['ratingData'],
						'format' => $title['format'],
						'language' => $title['language'],
						'primary_isbn' => $title['primary_isbn'],
						'primary_upc' => $title['primary_upc'],
					];
				}
			}
			return [
				'success' => true,
				'listTitle' => $list->title,
				'listDescription' => $list->description,
				'defaultSort' => $list->defaultSort,
				'titles' => $listTitles,
				'totalResults' => $pager->getTotalItems(),
				'page_current' => (int)$pager->getCurrentPage(),
				'page_total' => (int)$pager->getTotalPages(),
			];
		} else {
			return [
				'success' => false,
				'message' => 'The specified list could not be found.',
			];
		}
	}

	/**
	 * Get titles from a saved search - publicly accessible
	 *
	 * @public true
	 */
	function getSavedSearchTitles($searchId = null, $numTitlesToShow = null) {
		if (!$searchId) {
			if (!isset($_REQUEST['searchId'])) {
				return [
					'success' => false,
					'message' => 'The id of the list to load must be provided as the id parameter.',
				];
			} else {
				$searchId = $_REQUEST['searchId'];
			}
		}

		if (!$numTitlesToShow) {
			if (!isset($_REQUEST['numTitles'])) {
				$numTitlesToShow = 30;
			} else {
				$numTitlesToShow = $_REQUEST['numTitles'];
			}
		}

		//return a random selection of 30 titles from the list.
		/** @var SearchObject_AbstractGroupedWorkSearcher|SearchObject_BaseSearcher $searchObj */
		$searchObj = SearchObjectFactory::initSearchObject();
		$searchObj->init();
		$searchObj = $searchObj->restoreSavedSearch($searchId, false, true);
		if ($searchObj) { // check that the saved search was retrieved successfully
			$searchObj->setLimit($numTitlesToShow);
			$searchObj->processSearch(false, false);
			$listTitles = $searchObj->getTitleSummaryInformation();
		} else {
			$listTitles = false;
		}

		return $listTitles;
	}

	function getSystemListTitles($listName, $numTitlesToShow) {
		global $memCache;
		global $configArray;
		$listTitles = $memCache->get('system_list_titles_' . $listName);
		if ($listTitles == false || isset($_REQUEST['reload'])) {
			//return a random selection of 30 titles from the list.
			/** @var SearchObject_AbstractGroupedWorkSearcher $searchObj */
			$searchObj = SearchObjectFactory::initSearchObject();
			$searchObj->init();
			$searchObj->setBasicQuery("*:*");
			if (!preg_match('/^search:/', $listName)) {
				$searchObj->addFilter("system_list:$listName");
			}
			if (isset($_REQUEST['numTitles'])) {
				$searchObj->setLimit($_REQUEST['numTitles']);
			} else {
				$searchObj->setLimit($numTitlesToShow);
			}
			$searchObj->processSearch(false, false);
			$listTitles = $searchObj->getTitleSummaryInformation();

			$memCache->set('system_list_titles_' . $listName, $listTitles, $configArray['Caching']['system_list_titles']);
		}
		return $listTitles;
	}

	/**
	 * Get details about a list including title, description, number of titles, and whether the list is public
	 *
	 * @oauth true
	 * @token true
	 * @scopes list:read
	 * @noinspection PhpUnused
	 */
	function getListDetails(): array {
		global $configArray;
		if (!isset($_REQUEST['id'])) {
			return [
				'success' => false,
				'message' => 'The id of the list to load must be provided as the id parameter.',
			];
		}
		$user = $this->getUserForApiCall();
		if ($user && !($user instanceof AspenError)) {
			$list = new UserList();
			$list->id = $_REQUEST['id'];
			$list->user_id = $user->id;
			if ($list->find(true)) {
				return [
					'success' => true,
					'id' => $list->id,
					'title' => $list->title,
					'description' => $list->description,
					'numTitles' => $list->numValidListItems(),
					'public' => $list->public,
					'created' => $list->created,
					'dateUpdated' => $list->dateUpdated,
					'cover' => $configArray['Site']['url'] . "/bookcover.php?type=list&id={$list->id}&size=medium",
				];
			} else {
				return [
					'success' => false,
					'id' => $list->id,
					'title' => 'Error',
					'message' => "List {$list->title} not found",
				];
			}
		} else {
			return [
				'success' => false,
				'message' => 'Login unsuccessful',
			];
		}

	}

	/**
	 * Loads caching information to determine what the list should be cached as
	 * and whether it is cached for all users and products (general), for a single user,
	 * or for a single product.
	 * @noinspection PhpUnused
	 */
	function getCacheInfoForList() {
		if (!isset($_REQUEST['id'])) {
			return [
				'success' => false,
				'message' => 'The id of the list to load must be provided as the id parameter.',
			];
		}

		$listId = $_REQUEST['id'];
		return $this->getCacheInfoForListId($listId);
	}

	function getCacheInfoForListId($listId) {
		if (is_numeric($listId) || preg_match('/list[-:](.*)/', $listId, $listInfo)) {
			if (isset($listInfo)) {
				$listId = $listInfo[1];
			}
			return [
				'cacheType' => 'general',
				'fullListLink' => '/MyAccount/MyList/' . $listId,
			];

		} elseif (preg_match('/review:(.*)/', $listId, $reviewInfo)) {
			return [
				'cacheType' => 'general',
				'fullListLink' => '',
			];
		} elseif ($listId == 'highestRated') {
			return [
				'cacheType' => 'general',
				'fullListLink' => '',
			];
		} elseif ($listId == 'recentlyReviewed') {
			return [
				'cacheType' => 'general',
				'fullListLink' => '',
			];
		} elseif ($listId == 'mostPopular') {
			return [
				'cacheType' => 'general',
				'fullListLink' => '',
			];
		} elseif ($listId == 'recommendations') {
			return [
				'cacheType' => 'user',
				'fullListLink' => '',
			];
		} elseif (preg_match('/^search:(.*)/', $listId, $searchInfo)) {
			if (is_numeric($searchInfo[1])) {
				$searchId = $searchInfo[1];
				return [
					'cacheType' => 'general',
					'fullListLink' => '/Search/Results?saved=' . $searchId,
				];
			} else {
				return [
					'cacheType' => 'general',
					'fullListLink' => '',
				];
			}
		} else {
			return [
				'cacheType' => 'general',
				'fullListLink' => '',
			];
		}
	}

	/**
	 * Get all saved searches for a user.
	 * If checkIfValid is set to true, only returns searches that have a title and are valid for display.
	 * @oauth true
	 * @token true
	 */
	function getSavedSearches($userId = null): array {

		if (!UserAccount::isLoggedIn()) {
			$user = $this->getUserForApiCall();

			if ($user == false) {
				return [
					'success' => false,
					'message' => 'Sorry, we could not find a user with those credentials.',
				];
			}

			$id = $user->id;
		}else{
			if ($userId) {
				$id = $userId;
			} else {
				$id = UserAccount::getActiveUserId();
			}
		}

		$checkIfValid = "true";
		if (isset($_REQUEST['checkIfValid'])) {
			$checkIfValid = $_REQUEST['checkIfValid'];
		}

		$result = [];
		$SearchEntry = new SearchEntry();
		$SearchEntry->user_id = $id;
		$SearchEntry->saved = "1";
		$SearchEntry->whereAdd("searchSource <> 'user_list'");
		$SearchEntry->orderBy('created desc');
		$SearchEntry->find();

		$count = 0;
		$countNewResults = 0;
		while ($SearchEntry->fetch()) {
			if ($checkIfValid == "true") {
				if ($SearchEntry->title && $SearchEntry->isValidForDisplay()) {
					$count = $count + 1;
					$savedSearch = [
						'id' => $SearchEntry->id,
						'title' => $SearchEntry->title,
						'created' => $SearchEntry->created,
						'searchUrl' => $SearchEntry->searchUrl,
						'hasNewResults' => $SearchEntry->hasNewResults,
					];

					if ($SearchEntry->hasNewResults == 1) {
						$countNewResults = $countNewResults + 1;
					}

					$result[] = $savedSearch;
				}
			} else {
				if ($SearchEntry->title) {
					$count = $count + 1;
					$savedSearch = [
						'id' => $SearchEntry->id,
						'title' => $SearchEntry->title,
						'created' => $SearchEntry->created,
						'searchUrl' => $SearchEntry->searchUrl,
						'hasNewResults' => $SearchEntry->hasNewResults,
					];

					if ($SearchEntry->hasNewResults == 1) {
						$countNewResults = $countNewResults + 1;
					}

					$result[] = $savedSearch;
				}
			}
		}

		return [
			'success' => true,
			'searches' => $result,
			'count' => $count,
			'countNewResults' => $countNewResults,
		];
	}

	function getSavedSearchesForLiDA(): array {
		$user = $this->getUserForApiCall();
		if (!$user) {
			$user = UserAccount::getLoggedInUser();
		}
		if ($user && !($user instanceof AspenError)) {
			$checkIfValid = "true";
			if (isset($_REQUEST['checkIfValid'])) {
				$checkIfValid = $_REQUEST['checkIfValid'];
			}

			$result = [];
			$SearchEntry = new SearchEntry();
			$SearchEntry->user_id = $user->id;
			$SearchEntry->saved = "1";
			$SearchEntry->whereAdd("searchSource <> 'user_list'");
			$SearchEntry->orderBy('created desc');
			$SearchEntry->find();
			$count = 0;
			$countNewResults = 0;

			while ($SearchEntry->fetch()) {
				if ($checkIfValid == "true") {
					if ($SearchEntry->title && $SearchEntry->isValidForDisplay()) {
						$count = $count + 1;
						$savedSearch = [
							'id' => $SearchEntry->id,
							'title' => $SearchEntry->title,
							'created' => $SearchEntry->created,
							'searchUrl' => $SearchEntry->searchUrl,
							'hasNewResults' => $SearchEntry->hasNewResults,
						];

						if ($SearchEntry->hasNewResults == 1) {
							$countNewResults = $countNewResults + 1;
						}

						$result[] = $savedSearch;
					}
				} else {
					if ($SearchEntry->title) {
						$count = $count + 1;
						$savedSearch = [
							'id' => $SearchEntry->id,
							'title' => $SearchEntry->title,
							'created' => $SearchEntry->created,
							'searchUrl' => $SearchEntry->searchUrl,
							'hasNewResults' => $SearchEntry->hasNewResults,
						];

						if ($SearchEntry->hasNewResults == 1) {
							$countNewResults = $countNewResults + 1;
						}

						$result[] = $savedSearch;
					}
				}
			}
			return [
				'success' => true,
				'searches' => $result,
				'count' => $count,
				'countNewResults' => $countNewResults,
			];
		} else {
			return [
				'success' => false,
				'message' => 'Login unsuccessful',
			];
		}

	}

	/**
	 * Create a User list for the user.
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin number for the user. </li>
	 * <li>title    - The title of the list to create.</li>
	 * <li>description - A description for the list (optional).</li>
	 * <li>public   - Set to true or 1 if the list should be public.  (optional, defaults to private).</li>
	 * </ul>
	 *
	 * Note: You may also provide the parameters to addTitlesToList and titles will be added to the list
	 * after the list is created.
	 *
	 * Returns:
	 * <ul>
	 * <li>success - true if the account is valid and the list could be created, false if the username or password were incorrect or the list could not be created.</li>
	 * <li>listId - the id of the new list that is created.</li>
	 * </ul>
	 *
	 * Sample Call:
	 * <code>
	 * https://aspenurl/API/ListAPI?method=createList&username=userbarcode&password=userpin&title=Test+List&description=Test&public=0
	 * </code>
	 *
	 * Sample Response:
	 * <code>
	 * {"result":{"success":true,"listId":"1688"}}
	 * </code>
	 * @noinspection PhpUnused
	 */
	function createList(): array {
		if (!isset($_REQUEST['title'])) {
			return [
				'success' => false,
				'message' => 'You must provide the title of the list to be created.',
			];
		}

		$user = $this->getUserForApiCall();
		if ($user && !($user instanceof AspenError)) {
			$list = new UserList();
			$list->title = strip_tags($_REQUEST['title']);
			$list->user_id = $user->id;
			$list->deleted = "0";
			//Check to see if there is already a list with this id and title
			$existingList = false;
			if ($list->find(true)) {
				$existingList = true;
			}

			$list->description = strip_tags($_REQUEST['description'] ?? '');
			$list->public = isset($_REQUEST['public']) ? (($_REQUEST['public'] == true || $_REQUEST['public'] == 1) ? 1 : 0) : 0;
			$list->displayListAuthor = isset($_REQUEST['displayListAuthor']) ? (($_REQUEST['displayListAuthor'] == true || $_REQUEST['displayListAuthor'] == 1) ? 1 : 0) : 0;

			$list->listGroupId = -1;
			if (isset($_REQUEST['addToListGroupOption'])) {
				$addToListGroupOption = $_REQUEST['addToListGroupOption'];
				$addToListGroupNested = $_REQUEST['addToListGroupNested'] ?? 'none';
				if ($addToListGroupOption == 'new') {
					//Create a new list group
					require_once ROOT_DIR . '/sys/UserLists/UserListGroup.php';
					$listGroup = new UserListGroup();
					$listGroup->title = $_REQUEST['addToListGroupNewName'];
					$listGroup->userId = $user->id;
					if ($addToListGroupNested != 'none') {
						$listGroup->parentGroupId = $addToListGroupNested;
					}
					$listGroup->insert();
					$list->listGroupId = $listGroup->id;
				} elseif ($addToListGroupOption == "existing" && is_numeric($addToListGroupNested)) {
					//Add to an existing list group
					$list->listGroupId = intval($addToListGroupNested);
				}
			}

			if ($existingList) {
				$list->update();
				$success = false;
				$title = 'Error creating list';
				$message = 'You already have a list with this title.';
			} else {
				$list->insert();
				$success = true;
				$title = 'Success';
				$message = "List {$list->title} created successfully.";
			}

			if ($user->lastListUsed != $list->id) {
				$user->lastListUsed = $list->id;
				$user->update();
			}

			$list->find();

			if (isset($_REQUEST['recordIds'])) {
				$_REQUEST['listId'] = $list->id;
				return $this->addTitlesToList($existingList);
			} else {
				//There wasn't anything to add so it worked
				return [
					'success' => $success,
					'title' => $title,
					'message' => $message,
					'listId' => $list->id,
				];
			}
		} else {
			return [
				'success' => false,
				'message' => 'Login unsuccessful',
			];
		}
	}

	/**
	 * Add titles to a user list.
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin number for the user. </li>
	 * <li>listId   - The id of the list to add items to.</li>
	 * <li>recordIds - The id of the record(s) to add to the list.</li>
	 * <li>notes  - descriptive text to apply to the titles.  Can be viewed while on the list.  Notes will apply to all titles being added.  (optional)</li>
	 * </ul>
	 *
	 * Note: You may also provide the parameters to addTitlesToList and titles will be added to the list
	 * after the list is created.
	 *
	 * Returns:
	 * <ul>
	 * <li>success - true if the account is valid and the titles could be added to the list, false if the username or password were incorrect or the list could not be created.</li>
	 * <li>listId - the id of the list that titles were added to.</li>
	 * <li>numAdded - the number of titles that were added to the list.</li>
	 * </ul>
	 *
	 * Sample Call:
	 * <code>
	 * https://aspenurl/API/ListAPI?method=addTitlesToList&username=userbarcode&password=userpin&listId=42&recordIds=53254
	 * </code>
	 *
	 * Sample Response:
	 * <code>
	 * {"result":{"success":true,"listId":"1688","numAdded":"1"}}
	 * </code>
	 */
	function addTitlesToList($existingList = false) {
		if (!isset($_REQUEST['listId'])) {
			return [
				'success' => false,
				'message' => 'You must provide the listId to add titles to.',
			];
		}
		$recordIds = [];
		if (!isset($_REQUEST['recordIds'])) {
			return [
				'success' => false,
				'message' => 'You must provide one or more records to add to the list.',
			];
		} else {
			if (!is_array($_REQUEST['recordIds'])) {
				$recordIds[] = $_REQUEST['recordIds'];
			} else {
				$recordIds = $_REQUEST['recordIds'];
			}
		}

		$source = $_REQUEST['source'] ?? 'GroupedWork';

		$user = $this->getUserForApiCall();
		if ($user && !($user instanceof AspenError)) {
			$list = new UserList();
			$list->id = $_REQUEST['listId'];
			$list->user_id = $user->id;
			if (!$list->find(true)) {
				return [
					'success' => false,
					'message' => 'Unable to find the list to add titles to.',
				];
			} else {
				$numAdded = 0;
				foreach ($recordIds as $id) {
					require_once ROOT_DIR . '/sys/UserLists/UserListEntry.php';
					$userListEntry = new UserListEntry();
					$userListEntry->listId = $list->id;
						$userListEntry->source = $source;
						$userListEntry->sourceId = $id;

						if($source === 'Events') {
							if (preg_match('`^communico`', $id)){
								require_once ROOT_DIR . '/RecordDrivers/CommunicoEventRecordDriver.php';
								$recordDriver = new CommunicoEventRecordDriver($id);
								if ($recordDriver->isValid()) {
									$title = $recordDriver->getTitle();
									$userListEntry->title = substr($title, 0, 50);
								}
							} elseif (preg_match('`^libcal`', $id)){
								require_once ROOT_DIR . '/RecordDrivers/SpringshareLibCalEventRecordDriver.php';
								$recordDriver = new SpringshareLibCalEventRecordDriver($id);
								if ($recordDriver->isValid()) {
									$title = $recordDriver->getTitle();
									$userListEntry->title = substr($title, 0, 50);
								}
							} elseif (preg_match('`^lc_`', $id)){
								require_once ROOT_DIR . '/RecordDrivers/LibraryCalendarEventRecordDriver.php';
								$recordDriver = new LibraryCalendarEventRecordDriver($id);
								if ($recordDriver->isValid()) {
									$title = $recordDriver->getTitle();
									$userListEntry->title = substr($title, 0, 50);
								}
							} elseif (preg_match('`^assabet_`', $id)){
								require_once ROOT_DIR . '/RecordDrivers/AssabetEventRecordDriver.php';
								$recordDriver = new AssabetEventRecordDriver($id);
								if ($recordDriver->isValid()) {
									$title = $recordDriver->getTitle();
									$userListEntry->title = substr($title, 0, 50);
								}
							}
						} else {
							require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
							$groupedWork = new GroupedWork();
							$groupedWork->permanent_id = $id;
							if ($groupedWork->find(true)) {
								$userListEntry->title = substr($groupedWork->full_title, 0, 50);
							}
						}

						$existingEntry = false;
						if ($userListEntry->find(true)) {
							$existingEntry = true;
						}

						if (isset($_REQUEST['notes'])) {
							$notes = $_REQUEST['notes'];
						} else {
							$notes = '';
						}
						$userListEntry->notes = strip_tags($notes);
						$userListEntry->dateAdded = time();
						if ($existingEntry) {
							$userListEntry->update();
						} else {
							$userListEntry->insert();
						}
						if ($user->lastListUsed != $list->id) {
							$user->lastListUsed = $list->id;
							$user->update();
						}
						$numAdded++;

				}

				if ($existingList) {
					$message = $numAdded . " added to list.";
				} else {
					$message = $numAdded . " added to " . $list->title;
				}

				return [
					'success' => true,
					'listId' => $list->id,
					'numAdded' => $numAdded,
					'existingList' => $existingList,
					'message' => $message,
				];
			}


		} else {
			return [
				'success' => false,
				'message' => 'Login unsuccessful',
			];
		}
	}

	/**
	 * Delete a User list for the user.
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin number for the user. </li>
	 * <li>id    - The id of the list to delete.</li>
	 * <li>optOutOfSoftDeletion - Set to true to permanently delete the list instead of soft deletion (optional, defaults to false).</li>
	 * </ul>
	 *
	 * Returns:
	 * <ul>
	 * <li>success - true if the list was found and deleted. false if the list was not found or login unsuccessful</li>
	 * </ul>
	 *
	 * Sample Call:
	 * <code>
	 * https://aspenurl/API/ListAPI?method=deleteList&username=userbarcode&password=userpin&id=42&optOutOfSoftDeletion=true
	 * </code>
	 *
	 * Sample Response:
	 * <code>
	 * {"result":{"success":true}}
	 * </code>
	 * @noinspection PhpUnused
	 */
	function deleteList() {
		if (!isset($_REQUEST['id'])) {
			return [
				'success' => false,
				'message' => 'You must provide the id of the list to be deleted.',
			];
		}
		$user = $this->getUserForApiCall();
		if ($user && !($user instanceof AspenError)) {
			$list = new UserList();
			$list->id = $_REQUEST['id'];
			$list->user_id = $user->id;
			$list->find();
			if ($list->find(true)) {
				$userCanEdit = $user->canEditList($list);
				if ($userCanEdit) {
					$optOutOfSoftDeletion = !empty($_REQUEST['optOutOfSoftDeletion']) && filter_var($_REQUEST['optOutOfSoftDeletion'], FILTER_VALIDATE_BOOLEAN);
					$result = $list->delete(false, $optOutOfSoftDeletion, $user->id);
					if($result === 1) //we successfully modified our list
					{
						return [
							'success' => true,
							'title' => translate(['text' => 'Success', 'isPublicFacing' => true]),
							'message' => translate(['text' => 'List deleted successfully', 'isPublicFacing' => true]),
						];
					}
					else if($result === true) // true is returned from DataObject if no changes were detected
					{
						// this branch shouldn't happen because if we found the list we should have changes to update but better to be safe.
						// in an ideal world we shoiuld only need the first section.
						return [
							'success' => false,
							'title' => translate(['text' => 'Error', 'isPublicFacing' => true]),
							'message' => translate(['text' => 'We attempted to delete your list but it looks like no changes occurred', 'isPublicFacing' => true]),
						];
					} else { // some kind of DB error happened and we almost certainly did not successfully delete the list.
						global $logger;
						$logger->log($list->getLastError(), Logger::LOG_ERROR); //log the error since we don't want to risk exposing it to end users.
						return [
							'success' => false,
							'title' => translate(['text' => 'Error', 'isPublicFacing' => true]),
							'result' => $result,
							'message' => translate(['text' => 'A Database error occurred attempting to delete your list.', 'isPublicFacing' => true]),
						];
					}
				} else {
					return [
						'success' => false,
						'title' => translate(['text' => 'Error', 'isPublicFacing' => true]),
						'message' => translate(["text" => "Sorry you don't have permissions to delete this list.", 'isPublicFacing' => true]),
					];
				}
			} else {
				return [
					'success' => false,
					'title' => translate(['text' => 'Error', 'isPublicFacing' => true]),
					'message' => translate(['text' => 'List not found', 'isPublicFacing' => true]),
					'listId' => $list->id,
					'listTitle' => $list->title,
				];
			}
		} else {
			return [
				'success' => false,
				'message' => 'Login unsuccessful',
			];
		}
	}

	/**
	 * Edit an existing User list for the user.
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin number for the user. </li>
	 * <li>id    - The id of the list to modify.</li>
	 * <li>title    - The updated title for the list (optional).</li>
	 * <li>description - A updated description for the list (optional).</li>
	 * <li>public   - The updated public/private status for the list (optional).</li>
	 * </ul>
	 *
	 * Returns:
	 * <ul>
	 * <li>success - true if the list was found and modified. false if the list was not found or login unsuccessful</li>
	 * </ul>
	 *
	 * Sample Call:
	 * <code>
	 * https://aspenurl/API/ListAPI?method=editList&username=userbarcode&password=userpin&id=42
	 * </code>
	 *
	 * Sample Response:
	 * <code>
	 * {"result":{"success":true}}
	 * </code>
	 * @noinspection PhpUnused
	 */
	function editList() {
		if (!isset($_REQUEST['id'])) {
			return [
				'success' => false,
				'message' => 'You must provide the id of the list to be modified.',
			];
		}

		$user = $this->getUserForApiCall();
		if ($user && !($user instanceof AspenError)) {
			$list = new UserList();
			$list->id = $_REQUEST['id'];
			$list->user_id = $user->id;
			if ($list->find(true)) {
				if (isset($_REQUEST['title'])) {
					$list->title = $_REQUEST['title'];
				}
				if (isset($_REQUEST['description'])) {
					$list->description = strip_tags($_REQUEST['description']);
				}
				if (isset($_REQUEST['public'])) {
					if ($_REQUEST['public'] === "false" || $_REQUEST['public'] === false || $_REQUEST['public'] === 0) {
						$list->public = 0;
					} else {
						$list->public = 1;
					}
				}
				if (isset($_REQUEST['displayListAuthor'])) {
					if ($_REQUEST['displayListAuthor'] === "false" || $_REQUEST['displayListAuthor'] === false || $_REQUEST['displayListAuthor'] === 0 || $_REQUEST['displayListAuthor'] === '0') {
						$list->displayListAuthor = 0;
					} else {
						$list->displayListAuthor = 1;
					}
				}
				if (isset($_REQUEST['listGroupId'])) {
					$list->listGroupId = $_REQUEST['listGroupId'];
				}
				$list->update();
				if ($user->lastListUsed != $list->id) {
					$user->lastListUsed = $list->id;
					$user->update();
				}
				return [
					'success' => true,
					'title' => 'Success',
					'message' => "Edited list {$list->title} successfully",
				];
			} else {
				return [
					'success' => false,
					'listId' => $list->id,
					'listTitle' => $list->title,
					'title' => 'Error',
					'message' => "List {$list->title} not found",
				];
			}
		} else {
			return [
				'success' => false,
				'message' => 'Login unsuccessful',
			];
		}
	}

	/**
	 * Remove titles from a user list.
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin number for the user. </li>
	 * <li>listId   - The id of the list to remove items from.</li>
	 * <li>recordIds - The id of the record(s) to remove from the list.</li>
	 * </ul>
	 *
	 * Returns:
	 * <ul>
	 * <li>success - true if the account is valid and the titles could be added to the list, false if the username or password were incorrect or the list could not be created.</li>
	 * <li>listId - the id of the list that titles were added to.</li>
	 * <li>numRemoved - the number of titles that were removed from the list.</li>
	 * </ul>
	 *
	 * Sample Call:
	 * <code>
	 * https://aspenurl/API/ListAPI?method=removeTitlesFromList&username=userbarcode&password=userpin&title=Test+List&listId=42&recordIds=
	 * </code>
	 *
	 * Sample Response:
	 * <code>
	 * {"result":{"success":true,"listId":"1688"}}
	 * </code>
	 */
	function removeTitlesFromList() {
		if (!isset($_REQUEST['listId'])) {
			return [
				'success' => false,
				'message' => 'You must provide the listId to remove titles from.',
			];
		}
		$recordIds = [];
		if (!isset($_REQUEST['recordIds'])) {
			return [
				'success' => false,
				'message' => 'You must provide one or more records to remove from the list.',
			];
		} else {
			if (!is_array($_REQUEST['recordIds'])) {
				$recordIds[] = $_REQUEST['recordIds'];
			} else {
				$recordIds = $_REQUEST['recordIds'];
			}
		}

		$source = $_REQUEST['source'] ?? 'GroupedWork';

		$user = $this->getUserForApiCall();
		if ($user && !($user instanceof AspenError)) {
			$list = new UserList();
			$list->id = $_REQUEST['listId'];
			$list->user_id = $user->id;
			if (!$list->find(true)) {
				return [
					'success' => false,
					'message' => 'Unable to find the list to remove titles from.',
				];
			} else {
				$numRemoved = 0;
				foreach ($recordIds as $id) {
					require_once ROOT_DIR . '/sys/UserLists/UserListEntry.php';
					$userListEntry = new UserListEntry();
					$userListEntry->listId = $list->id;

					$userListEntry->source = $source;
					$userListEntry->sourceId = $id;

					$existingEntry = false;
					if ($userListEntry->find(true)) {
						$userListEntry->delete();
					} else {
						return [
							'success' => false,
							'message' => 'Unable to find record to remove from the list.',
						];
					}

					$numRemoved++;

				}
				if ($user->lastListUsed != $list->id) {
					$user->lastListUsed = $list->id;
					$user->update();
				}
				return [
					'success' => true,
					'listId' => $list->id,
					'numRemoved' => $numRemoved,
				];
			}


		} else {
			return [
				'success' => false,
				'message' => 'Login unsuccessful',
			];
		}
	}

	/**
	 * Clears all titles on a list given a list id
	 *
	 * Parameters:
	 * <ul>
	 * <li>username - The barcode of the user.  Can be truncated to the last 7 or 9 digits.</li>
	 * <li>password - The pin number for the user. </li>
	 * <li>listId   - The id of the list to add items to.</li>
	 * </ul>
	 *
	 * Returns:
	 * <ul>
	 * <li>success - true if the account is valid and the titles could be added to the list, false if the username or password were incorrect or the list could not be created.</li>
	 * </ul>
	 *
	 * Sample Call:
	 * <code>
	 * https://aspenurl/API/ListAPI?method=clearListTitles&username=userbarcode&password=userpin&listId=1234
	 * </code>
	 *
	 * Sample Response:
	 * <code>
	 * {"result":{"success":true}}
	 * </code>
	 * @noinspection PhpUnused
	 */
	function clearListTitles() {
		if (!isset($_REQUEST['listId'])) {
			return [
				'success' => false,
				'message' => 'You must provide the listId to clear titles from.',
			];
		}
		$user = $this->getUserForApiCall();
		if ($user && !($user instanceof AspenError)) {
			$list = new UserList();
			$list->id = $_REQUEST['listId'];
			$list->user_id = $user->id;
			if (!$list->find(true)) {
				return [
					'success' => false,
					'message' => 'Unable to find the list to clear titles from.',
				];
			} else {
				$list->removeAllListEntries();
				return ['success' => true];
			}
		} else {
			return [
				'success' => false,
				'message' => 'Login unsuccessful',
			];
		}
	}

	public function createListGroup(): ?array {
		$user = $this->getUserForApiCall();
		if ($user && !($user instanceof AspenError)) {
			$title = (isset($_REQUEST['title']) && !is_array($_REQUEST['title'])) ? urldecode($_REQUEST['title']) : '';
			if (strlen(trim($title)) == 0) {
				return [
					'success' => false,
					'message' => 'You must provide a title for the list group',
				];
			} else {
				$parentId = $_REQUEST['nestedGroupId'] ?? -1;
				require_once ROOT_DIR . '/sys/UserLists/UserListGroup.php';
				$listGroup = new UserListGroup();
				$listGroup->userId = $user->id;
				$listGroup->title = $title;
				$listGroup->parentGroupId = $parentId;
				if ($listGroup->insert()) {
					// Set the last viewed group to the newly created group
					//$user->lastListGroupViewed = $listGroup->id;
					//$user->update();
					return [
						'success' => true,
						'message' => "List group $listGroup->title created successfully",
						'groupId' => $listGroup->id
					];
				} else {
					return [
						'success' => false,
						'message' => 'Could not create list group',
						'groupId' => null
					];
				}
			}
		} else {
			return [
				'success' => false,
				'title' => translate([
					'text' => 'Error',
					'isPublicFacing' => true
				]),
				'message' => translate([
					'text' => 'Login unsuccessful',
					'isPublicFacing' => true
				])
			];
		}
	}

	public function deleteListGroup() {
		$user = $this->getUserForApiCall();
		if ($user && !($user instanceof AspenError)) {
			$groupId = $_REQUEST['groupId'] ?? null;
			if ($groupId == null) {
				return [
					'success' => false,
					'title' => translate([
						'text' => 'Error',
						'isPublicFacing' => true
					]),
					'message' => translate([
						'text' => 'You must provide the id of the group to delete.',
						'isPublicFacing' => true
					]),
				];
			} else {
				require_once ROOT_DIR . '/sys/UserLists/UserListGroup.php';
				$group = new UserListGroup();
				$group->id = $groupId;
				$group->userId = UserAccount::getActiveUserId();
				if ($group->find(true)) {
					if ($group->delete()) {
						// If the deleted group was the last viewed group, clear that setting
						$user = UserAccount::getLoggedInUser();
						if ($user->lastListGroupViewed == $groupId) {
							$user = UserAccount::getActiveUserObj();
							$user->lastListGroupViewed = -1;
							$user->update();
						}
						// Unassign any lists that were in this group
						require_once ROOT_DIR . '/sys/UserLists/UserList.php';
						$userList = new UserList();
						$userList->listGroupId = $groupId;
						$userList->user_id = UserAccount::getActiveUserId();
						$userList->find();
						while ($userList->fetch()) {
							$userList->listGroupId = -1;
							$userList->update();
						}

						// Unassign any sub-groups that were in this group
						$subGroup = new UserListGroup();
						$subGroup->parentGroupId = $groupId;
						$subGroup->userId = UserAccount::getActiveUserId();
						$subGroup->find();
						while ($subGroup->fetch()) {
							$subGroup->parentGroupId = -1;
							$subGroup->update();
						}

						return [
							'success' => true,
							'title' => translate([
								'text' => 'Success',
								'isPublicFacing' => true
							]),
							'message' => translate([
								'text' => 'The list group was successfully deleted.',
								'isPublicFacing' => true
							]),
						];
					} else {
						return [
							'success' => false,
							'title' => translate([
								'text' => 'Error',
								'isPublicFacing' => true
							]),
							'message' => translate([
								'text' => 'The list group could not be deleted.',
								'isPublicFacing' => true
							]),
						];
					}
				} else {
					return [
						'success' => false,
						'title' => translate([
							'text' => 'Error',
							'isPublicFacing' => true
						]),
						'message' => translate([
							'text' => 'The specified group could not be found.',
							'isPublicFacing' => true
						]),
					];
				}
			}
		} else {
			return [
				'success' => false,
				'title' => translate([
					'text' => 'Error',
					'isPublicFacing' => true
				]),
				'message' => translate([
					'text' => 'Login unsuccessful',
					'isPublicFacing' => true
				])
			];
		}
	}

	public function editListGroup() {
		$user = $this->getUserForApiCall();
		if ($user && !($user instanceof AspenError)) {
			$groupId = $_REQUEST['groupId'];
			$newName = $_REQUEST['listGroupNameNew'];
			if ($groupId && $newName) {
				require_once ROOT_DIR . '/sys/UserLists/UserListGroup.php';
				$group = new UserListGroup();
				$group->id = $groupId;
				$group->userId = UserAccount::getActiveUserId();
				if ($group->find(true)) {
					$group->title = $newName;
					if ($group->update()) {
						return [
							'success' => true,
							'title' => translate([
								'text' => 'Success',
								'isPublicFacing' => true
							]),
							'message' => translate([
								'text' => 'The list group was successfully updated.',
								'isPublicFacing' => true
							])
						];
					} else {
						return [
							'success' => false,
							'title' => translate([
								'text' => 'Error',
								'isPublicFacing' => true
							]),
							'message' => translate([
								'text' => 'The list group could not be updated.',
								'isPublicFacing' => true
							])
						];
					}
				} else {
					return [
						'success' => false,
						'title' => translate([
							'text' => 'Error',
							'isPublicFacing' => true
						]),
						'message' => translate([
							'text' => 'The specified group could not be found.',
							'isPublicFacing' => true
						])
					];
				}
			} else {
				return [
					'success' => false,
					'title' => translate([
						'text' => 'Error',
						'isPublicFacing' => true
					]),
					'message' => translate([
						'text' => 'You must provide the id of the group to modify and a new title.',
						'isPublicFacing' => true,
					])
				];
			}
		} else {
			return [
				'success' => false,
				'title' => translate([
					'text' => 'Error',
					'isPublicFacing' => true
				]),
				'message' => translate([
					'text' => 'Login unsuccessful',
					'isPublicFacing' => true
				])
			];
		}
	}

	public function editListGroupParent() {
		$user = $this->getUserForApiCall();
		if ($user && !($user instanceof AspenError)) {
			$groupId = $_REQUEST['groupId'];
			$listGroupMoveId = $_REQUEST['listGroupMove'];
			if ($groupId && $listGroupMoveId) {
				require_once ROOT_DIR . '/sys/UserLists/UserListGroup.php';
				$group = new UserListGroup();
				$group->id = $groupId;
				$group->userId = $user->id;
				if ($group->find(true)) {
					$group->parentGroupId = $listGroupMoveId;
					if ($group->update()) {
						return [
							'success' => true,
							'title' => translate([
								'text' => 'Success',
								'isPublicFacing' => true
							]),
							'message' => translate([
								'text' => 'Your list group was successfully moved.',
								'isPublicFacing' => true
							])
						];
					} else {
						return [
							'success' => false,
							'title' => translate([
								'text' => 'Error',
								'isPublicFacing' => true
							]),
							'message' => translate([
								'text' => 'The list group could not be updated.',
								'isPublicFacing' => true
							])
						];
					}
				} else {
					return [
						'success' => false,
						'title' => translate([
							'text' => 'Error',
							'isPublicFacing' => true
						]),
						'message' => translate([
							'text' => 'The specified group could not be found.',
							'isPublicFacing' => true
						])
					];
				}
			} else {
				return [
					'success' => false,
					'title' => translate([
						'text' => 'Error',
						'isPublicFacing' => true
					]),
					'message' => translate([
						'text' => 'You must provide the id of the group to modify and a new parent id.',
						'isPublicFacing' => true,
					])
				];
			}
		} else {
			return [
				'success' => false,
				'title' => translate([
					'text' => 'Error',
					'isPublicFacing' => true
				]),
				'message' => translate([
					'text' => 'Login unsuccessful'
				]),
			];
		}
	}

	public function getListGroupDetails() {
		$user = $this->getUserForApiCall();
		if ($user && !($user instanceof AspenError)) {
			global $configArray;
			if (isset($_REQUEST['groupId'])) {
				// Determine if pagination is to be included to help with supporting different Aspen LiDA versions
				$includePagination = false;
				if (isset($_REQUEST['includePagination'])) {
					$includePagination = (bool)$_REQUEST['includePagination'];
				}

				$listsPerPage = 20;
				if (isset($_REQUEST['limit'])) {
					$listsPerPage = $_REQUEST['limit'];
				}

				$page = $_REQUEST['page'] ?? 1;
				$groupId = $_REQUEST['groupId'];
				$user->lastListGroupViewed = $groupId;
				$user->update();
				$activeListGroup = [];
				if ($groupId == -1) {
					$activeListGroup = $user->getUnassignedListsForListGroups();
					require_once ROOT_DIR . '/sys/UserLists/UserListGroup.php';
					$activeListGroupDetails = new UserListGroup();
					$activeListGroupDetails->title = 'Unassigned Lists';
					$activeListGroupDetails->id = -1;
					$lists = [];
					foreach ($activeListGroup['lists'] as $userList) {
						$lists[] = [
							'id' => $userList->id,
							'title' => $userList->title,
							'description' => $userList->description,
							'displayListAuthor' => $userList->displayListAuthor == 1,
							'numTitles' => $userList->numValidListItems(),
							'public' => $userList->public == 1,
							'created' => $userList->created,
							'dateUpdated' => $userList->dateUpdated,
							'cover' => $configArray['Site']['url'] . "/bookcover.php?type=list&id={$userList->id}&size=medium",
							'listGroupId' => $userList->listGroupId,
						];
					}

					if ($includePagination) {
						return [
							'success' => true,
							'listGroupDetails' => $activeListGroupDetails,
							'listsInGroup' => $lists,
							'page_current' => $activeListGroup['page_current'],
							'totalResults' => $activeListGroup['totalResults'],
							'page_total' => $activeListGroup['page_total'],
						];
					}
					return [
						'success' => true,
						'listGroupDetails' => $activeListGroupDetails,
						'listsInGroup' => $lists,
					];
				} else {
					require_once ROOT_DIR . '/sys/UserLists/UserListGroup.php';
					$listGroup = new UserListGroup();
					$listGroup->id = $groupId;
					$listGroup->userId = $user->id;
					if ($listGroup->find(true)) {
						$listGroupDetails = $listGroup;
						$userList = new UserList();
						$userList->user_id = $user->id;
						$userList->listGroupId = $listGroup->id;
						$totalLists = $userList->count();
						if ($includePagination) {
							$userList->limit(($page - 1) * $listsPerPage, $listsPerPage);
						}
						$userList->find();

						while ($userList->fetch()) {
							$activeListGroup[] = [
								'id' => $userList->id,
								'title' => $userList->title,
								'description' => $userList->description,
								'displayListAuthor' => $userList->displayListAuthor == 1,
								'numTitles' => $userList->numValidListItems(),
								'public' => $userList->public == 1,
								'created' => $userList->created,
								'dateUpdated' => $userList->dateUpdated,
								'cover' => $configArray['Site']['url'] . "/bookcover.php?type=list&id={$userList->id}&size=medium",
								'listGroupId' => $userList->listGroupId,
							];
						}

						if ($includePagination) {
							$options = [
								'totalItems' => $totalLists,
								'perPage' => $listsPerPage,
							];

							require_once ROOT_DIR . '/sys/Pager.php';
							$pager = new Pager($options);

							return [
								'success' => true,
								'listGroupDetails' => $listGroupDetails,
								'listsInGroup' => $activeListGroup,
								'page_current' => (int)$pager->getCurrentPage(),
								'totalResults' => (int)$pager->getTotalItems(),
								'page_total' => (int)$pager->getTotalPages(),
							];
						}

					} else {
						$activeListGroup = UserListGroup::getLastViewedGroupForUser($user);
						$listGroupDetails = UserListGroup::getLastViewedGroupDetailsForUser($user);
					}

					return [
						'success' => true,
						'listGroupDetails' => $listGroupDetails,
						'listsInGroup' => $activeListGroup,
					];
				}
			} else {
				return [
					'success' => false,
					'title' => translate([
						'text' => 'Error',
						'isPublicFacing' => true
					]),
					'message' => translate([
						'text' => 'You must provide the id of the group to get details for.',
						'isPublicFacing' => true
					]),
				];
			}
		} else {
			return [
				'success' => false,
				'title' => translate([
					'text' => 'Error',
					'isPublicFacing' => true
				]),
				'message' => translate([
					'text' => 'Login unsuccessful',
					'isPublicFacing' => true
				])
			];
		}
	}

	/**
	 * Creates or updates a user defined list from information obtained from the New York Times API
	 *
	 * @param ?string $selectedList the encoded name of the New York times list
	 * @param ?NYTUpdateLogEntry $nytUpdateLog
	 * @param bool $forceUpdate Set to true to force the list to be updated regardless of the last modified date
	 *
	 * @return array
	 * @throws Exception
	 */
	public function createUserListFromNYT(?string $selectedList = null, ?NYTUpdateLogEntry $nytUpdateLog = null, bool $forceUpdate = false): array {
		if ($selectedList == null) {
			$selectedList = $_REQUEST['listToUpdate'];
		}

		require_once ROOT_DIR . '/sys/Enrichment/NewYorkTimesSetting.php';
		$nytSettings = new NewYorkTimesSetting();
		if (!$nytSettings->find(true)) {
			if ($nytUpdateLog != null) {
				$nytUpdateLog->addError("API Key missing");
			}
			return [
				'success' => false,
				'message' => 'API Key missing',
			];
		}
		$api_key = $nytSettings->booksApiKey;

		//Get the user to attach the list to
		$nytListUser = new User();
		$nytListUser->source = 'admin';
		$nytListUser->username = 'nyt_user';
		if (!$nytListUser->find(true)) {
			if ($nytUpdateLog != null) {
				$nytUpdateLog->addError("NY Times user has not been created");
			}
			return [
				'success' => false,
				'message' => 'NY Times user has not been created',
			];
		}

		//Get the raw response from the API with a list of all the names
		require_once ROOT_DIR . '/sys/NYTApi.php';
		$nyt_api = NYTApi::getNYTApi($api_key);
		$availableLists = $nyt_api->getListsOverview();

		//Get the title for our selected list
		$selectedListTitle = null;
		$selectedListTitleShort = null;
		$selectedListInfo = null;
		//Get the title and description for the selected list
		foreach ($availableLists as $listInformation) {
			if ($listInformation->list_name_encoded == $selectedList) {
				$selectedListTitle = 'NYT - ' . $listInformation->display_name;
				$selectedListTitleShort = $listInformation->display_name;
				$selectedListInfo = $listInformation;
				break;
			}
		}
		if (empty($selectedListTitleShort)) {
			if ($nytUpdateLog != null) {
				$nytUpdateLog->addError("We did not find list '{$selectedList}' in The New York Times API");
			}
			return [
				'success' => false,
				'message' => "We did not find list '{$selectedList}' in The New York Times API",
			];
		}

		//Get a list of titles from NYT API

		$listTitles = $selectedListInfo->books;

		$lastModifiedFromAPI = $nyt_api->getLastUpdateDate();
		if ($lastModifiedFromAPI == null) {
			$lastModifiedDay = '';
		}else{
			$lastModified = date_timestamp_get(new DateTime($lastModifiedFromAPI));
			$lastModifiedDay = date("M j, Y", $lastModified);
		}

		// Look for selected List
		require_once ROOT_DIR . '/sys/UserLists/UserList.php';
		$nytList = new UserList();
		$nytList->user_id = $nytListUser->id;
		$nytList->title = $selectedListTitle;
		$listExistsInAspen = $nytList->find(true);

		//We didn't find the list in Aspen Discovery, create one
		if (!$listExistsInAspen) {
			$nytList = new UserList();
			$nytList->title = $selectedListTitle;
			$nytList->description = "New York Times - $selectedListTitleShort<br/>{$nyt_api->getCopyright()}";
			$nytList->public = 1;
			$nytList->searchable = 1;
			$nytList->defaultSort = 'custom';
			$nytList->user_id = $nytListUser->id;
			$nytList->nytListModified = $lastModifiedDay;
			$success = $nytList->insert();
			$nytList->find(true);

			if ($success) {
				//Update log that we added a list
				$listID = $nytList->id;
				global $logger;
				$logger->log('Created list: ' . $selectedListTitle, Logger::LOG_NOTICE);
				if ($nytUpdateLog != null) {
					$nytUpdateLog->numAdded++;
				}
				$results = [
					'success' => true,
					'message' => "Created list <a href='/MyAccount/MyList/{$listID}'>{$selectedListTitle}</a>",
				];
			} else {
				//Update log that this failed
				global $logger;
				$logger->log('Could not create list: ' . $selectedListTitle, Logger::LOG_ERROR);
				if ($nytUpdateLog != null) {
					$nytUpdateLog->addError('Could not create list: ' . $selectedListTitle);
				}
				return [
					'success' => false,
					'message' => 'Could not create list',
				];
			}

		} else {
			$listID = $nytList->id;
			if ($nytList->nytListModified == $lastModifiedDay && !$forceUpdate) {
				if ($nytUpdateLog != null) {
					$nytUpdateLog->numSkipped++;
				}
				if ($nytList->deleted == 1) {
					$nytList->deleted = 0;
					$nytList->update();
				}
				//Nothing has changed, no need to update
				return [
					'success' => true,
					'message' => "List <a href='/MyAccount/MyList/{$listID}'>{$selectedListTitle}</a> has not changed since it was last loaded.",
				];
			}
			if ($nytUpdateLog != null) {
				$nytUpdateLog->numUpdated++;
			}
			$nytList->description = "New York Times - $selectedListTitleShort<br/>{$nyt_api->getCopyright()}";
			$nytList->nytListModified = $lastModifiedDay;
			if ($nytList->deleted == 1) {
				$nytList->deleted = 0;
			}
			$nytList->update();
			$results = [
				'success' => true,
				'message' => "Updated list <a href='/MyAccount/MyList/{$listID}'>{$selectedListTitle}</a>",
			];
			$nytList->searchable = 1;
			//We already have a list, clear the contents so we don't have titles from last time
			$nytList->removeAllListEntries();
		}

		// We need to add titles to the list //

		// Include Search Engine Class
		require_once ROOT_DIR . '/sys/SolrConnector/GroupedWorksSolrConnector.php';
		// Include UserListEntry Class
		require_once ROOT_DIR . '/sys/UserLists/UserListEntry.php';

		$numTitlesAdded = 0;
		foreach ($listTitles as $titleResult) {
			$aspenID = null;
			// go through each list item
			if (!empty($titleResult->isbns)) {
				foreach ($titleResult->isbns as $isbns) {
					$isbn = empty($isbns->isbn13) ? $isbns->isbn10 : $isbns->isbn13;
					if (!empty($isbn)) {
						$aspenID = $this->_getGroupedWorkIdForISBN($isbn);
					}
					//break if we found an aspen id for the title
					if ($aspenID != null) {
						break;
					}
				}
			} else {
				//No ISBNs provided this may happen if the title only has an ISBN 13
				$isbn = null;
				if (!empty($titleResult->primary_isbn13)) {
					$isbn = $titleResult->primary_isbn13;
				}else if (!empty($titleResult->primary_isbn10)){
					$isbn = $titleResult->primary_isbn10;
				}
				if ($isbn) {
					$aspenID = $this->_getGroupedWorkIdForISBN($isbn);
				}
			}
			if ($aspenID != null) {
				$note = "#{$titleResult->rank} on the {$selectedListTitleShort} list.";
				if ($selectedListInfo->updated == 'WEEKLY') {
					if ($titleResult->rank_last_week != 0) {
						$note .= '  Last week it was ranked ' . $titleResult->rank_last_week . '.';
					}
					if ($titleResult->weeks_on_list != 0) {
						$note .= "  It has been on the list for {$titleResult->weeks_on_list} week(s).";
					}
				}

				$userListEntry = new UserListEntry();
				$userListEntry->listId = $nytList->id;
				$userListEntry->source = 'GroupedWork';
				$userListEntry->sourceId = $aspenID;

				$existingEntry = false;
				if ($userListEntry->find(true)) {
					$existingEntry = true;
				}

				$userListEntry->weight = $titleResult->rank;
				$userListEntry->notes = $note;
				$userListEntry->dateAdded = time();
				if ($existingEntry) {
					if ($userListEntry->update()) {
						$numTitlesAdded++;
					}
				} else {
					if ($userListEntry->insert()) {
						$numTitlesAdded++;
					}
				}
			}
		}

		if ($results['success']) {
			$results['message'] .= "<br/> Added $numTitlesAdded Titles to the list";
			if ($listExistsInAspen) {
				$nytList->update(); // set a new update time on the main list when it already exists
			}
		}

		return $results;
	}

	private function _getGroupedWorkIdForISBN($isbn) : ?string{
		$aspenID = null;
		//look the title up by ISBN
		/** @var SearchObject_AbstractGroupedWorkSearcher $searchObject */
		$searchObject = SearchObjectFactory::initSearchObject(); // QUESTION: Does this need to be done within the Loop??
		$searchObject->init();
		$searchObject->clearFacets();
		$searchObject->clearFilters();
		$searchObject->setBasicQuery($isbn, "ISN");
		$result = $searchObject->processSearch(true, false);
		if ($result && $searchObject->getResultTotal() >= 1) {
			$recordSet = $searchObject->getResultRecordSet();
			foreach ($recordSet as $recordKey => $record) {
				if (!empty($record['id'])) {
					$aspenID = $record['id'];
					break;
				}
			}
		}
		return $aspenID;
	}

	function getBreadcrumbs(): array {
		return [];
	}
}