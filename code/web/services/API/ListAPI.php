<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/sys/Pager.php';
require_once ROOT_DIR . '/sys/UserLists/UserList.php';

class ListAPI extends Action
{

	function launch()
	{
		$method = (isset($_GET['method']) && !is_array($_GET['method'])) ? $_GET['method'] : '';
		if ($method != 'getRSSFeed' && !IPAddress::allowAPIAccessForClientIP()){
			$this->forbidAPIAccess();
		}

		if (!in_array($method, ['getSavedSearchTitles', 'getCacheInfoForListId', 'getSystemListTitles']) && method_exists($this, $method)) {
			if ($method == 'getRSSFeed') {
				header('Content-type: text/xml');
				header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
				header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
				$xml = '<?xml version="1.0" encoding="UTF-8"?' . ">\n";
				$xml .= $this->$method();

				echo $xml;

			} else {
				header('Content-type: application/json');
				header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
				header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
				$output = json_encode(array('result' => $this->$method()));

				echo $output;
			}
			require_once ROOT_DIR . '/sys/SystemLogging/APIUsage.php';
			APIUsage::incrementStat('ListAPI', $method);
		} else {
			echo json_encode(array('error' => 'invalid_method'));
		}
	}

	function getAllListIds()
	{
		$allListNames = array();
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
	 */
	function getPublicLists()
	{
		global $aspen_db;
		$list = new UserList();
		$list->public = 1;
		$list->deleted = 0;
		$list->find();
		$results = array();
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

				$results[] = array(
					'id' => $list->id,
					'title' => $list->title,
					'description' => $list->description,
					'numTitles' => $numTitles,
					'dateUpdated' => $list->dateUpdated,
				);
			}
		}
		return array('success' => true, 'lists' => $results);
	}

	/**
	 * Get all public lists
	 * includes id, title, description, and number of titles
	 */
	function getSearchableLists()
	{
		global $aspen_db;
		$list = new UserList();
		$list->public = 1;
		$list->searchable = 1;
		$list->deleted = 0;
		$list->find();
		$results = array();
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

				$results[] = array(
					'id' => $list->id,
					'title' => $list->title,
					'description' => $list->description,
					'numTitles' => $numTitles,
					'dateUpdated' => $list->dateUpdated,
				);
			}
		}
		return array('success' => true, 'lists' => $results);
	}

	/**
	 * Get all lists that a particular user has created.
	 * includes id, title, description, number of titles, and whether or not the list is public
	 * @noinspection PhpUnused
	 */
	function getUserLists()
	{
		if (!isset($_REQUEST['username']) || !isset($_REQUEST['password'])) {
			return array('success' => false, 'message' => 'The username and password must be provided to load lists.');
		}

        $username = $_REQUEST['username'];
        $password = $_REQUEST['password'];
        $user = UserAccount::validateAccount($username, $password);

		if ($user == false) {
			return array('success' => false, 'message' => 'Sorry, we could not find a user with those credentials.');
		}

		$userId = $user->id;

		$list = new UserList();
		$list->user_id = $userId;
		$list->find();
		$results = array();
		if ($list->getNumResults() > 0) {
			while ($list->fetch()) {
				$results[] = array(
					'id' => $list->id,
					'title' => $list->title,
					'description' => $list->description,
					'numTitles' => $list->numValidListItems(),
					'public' => $list->public == 1,
				);
			}
		}
		require_once(ROOT_DIR . '/sys/Suggestions.php');
		$suggestions = Suggestions::getSuggestions($userId);
		if (count($suggestions) > 0) {
			$results[] = array(
				'id' => 'recommendations',
				'title' => 'User Recommendations',
				'description' => 'Personalized Recommendations based on ratings.',
				'numTitles' => count($suggestions),
				'public' => false,
			);
		}
		return array('success' => true, 'lists' => $results);
	}

	/**
	 * Get's RSS Feed
	 * @noinspection PhpUnused
	 */
	function getRSSFeed()
	{
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
	 * Get all system generated lists that are available.
	 * includes id, title, description, and number of titles
	 */
	function getSystemLists()
	{
		//System lists are not stored in tables, but are generated based on
		//a variety of factors.
		$systemLists[] = array(
			'id' => 'recentlyReviewed',
			'title' => 'Recently Reviewed',
			'description' => 'Titles that have had new reviews added to them.',
			'numTitles' => 30,
		);
		$systemLists[] = array(
			'id' => 'highestRated',
			'title' => 'Highly Rated',
			'description' => 'Titles that have the highest ratings within the catalog.',
			'numTitles' => 30,
		);
		$systemLists[] = array(
			'id' => 'mostPopular',
			'title' => 'Most Popular Titles',
			'description' => 'Most Popular titles based on checkout history.',
			'numTitles' => 30,
		);
		$systemLists[] = array(
			'id' => 'recommendations',
			'title' => 'Recommended For You',
			'description' => 'Titles Recommended for you based off your ratings.',
			'numTitles' => 30,
		);
		return array('success' => true, 'lists' => $systemLists);
	}

	private function _getUserListTitles($listId, $numTitlesToShow)
	{
		//The list is a patron generated list
		$list = new UserList();
		$list->id = $listId;
		if ($list->find(true)) {
			//Make sure the user has access to the list
			if ($list->public == 0) {
				if (!isset($user)) {
					return array('success' => false, 'message' => 'The user was invalid.  A valid user must be provided for private lists.');
				} elseif ($list->user_id != $user->id) {
					return array('success' => false, 'message' => 'The user does not have access to this list.');
				}
			}

			$titles = $list->getListRecords(0, $numTitlesToShow, false, 'summary');

			return array('success' => true, 'listName' => $list->title, 'listDescription' => $list->description, 'titles' => $titles);
		} else {
			return array('success' => false, 'message' => 'The specified list could not be found.');
		}
	}

	/**
	 * Returns information about the titles within a list including:
	 * - Title, Author, Bookcover URL, description, record id
	 *
	 * @param string $listId - The list to show
	 * @param integer $numTitlesToShow - the maximum number of titles that should be shown
	 * @return array
	 */
	function getListTitles($listId = NULL, $numTitlesToShow = 25)
	{
		if (!$listId) {
			if (!isset($_REQUEST['id'])) {
				return array('success' => false, 'message' => 'The id of the list to load must be provided as the id parameter.');
			}
			$listId = $_REQUEST['id'];
		}

		if (isset($_REQUEST['username']) && isset($_REQUEST['password'])) {
			$username = $_REQUEST['username'];
			$password = $_REQUEST['password'];
			$user = UserAccount::validateAccount($username, $password);
		} else {
			$user = UserAccount::getLoggedInUser();
		}

		if (!is_numeric($numTitlesToShow)) {
			$numTitlesToShow = 25;
		}

		if (is_numeric($listId) || preg_match('/list[-:](.*)/', $listId, $listInfo)) {
			if (isset($listInfo)) {
				$listId = $listInfo[1];
			}
			return $this->_getUserListTitles($listId, $numTitlesToShow);
		} elseif (preg_match('/search:(?<searchID>.*)/', $listId, $searchInfo)) {
			if (is_numeric($searchInfo[1])) {
				$titles = $this->getSavedSearchTitles($searchInfo[1], $numTitlesToShow);
				if ($titles === false) { // Didn't find saved search
					return array('success' => false, 'message' => 'The specified search could not be found.');
				} else { // successful search with or without any results. (javascript can handle no results returned.)
					return array('success' => true, 'listTitle' => $listId, 'listDescription' => "Search Results", 'titles' => $titles);
				}
			} else {
				//Do a default search
				$titles = $this->getSystemListTitles($listId, $numTitlesToShow);
				if (count($titles) > 0) {
					return array('success' => true, 'listTitle' => $listId, 'listDescription' => "System Generated List", 'titles' => $titles);
				} else {
					return array('success' => false, 'message' => 'The specified list could not be found.');
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
					return array('success' => false, 'message' => 'A valid user must be provided to load recommendations.');
				} else {
					$userId = $user->id;
					require_once(ROOT_DIR . '/sys/Suggestions.php');
					$suggestions = Suggestions::getSuggestions($userId);
					$titles = array();
					foreach ($suggestions as $id => $suggestion) {
						$imageUrl = "/bookcover.php?id=" . $id;
						if (isset($suggestion['titleInfo']['issn'])) {
							$imageUrl .= "&issn=" . $suggestion['titleInfo']['issn'];
						}
						if (isset($suggestion['titleInfo']['isbn10'])) {
							$imageUrl .= "&isn=" . $suggestion['titleInfo']['isbn10'];
						}
						if (isset($suggestion['titleInfo']['upc'])) {
							$imageUrl .= "&upc=" . $suggestion['titleInfo']['upc'];
						}
						if (isset($suggestion['titleInfo']['format_category'])) {
							$imageUrl .= "&category=" . $suggestion['titleInfo']['format_category'];
						}
						$smallImageUrl = $imageUrl . "&size=small";
						$imageUrl .= "&size=medium";
						$titles[] = array(
							'id' => $id,
							'image' => $imageUrl,
							'small_image' => $smallImageUrl,
							'title' => $suggestion['titleInfo']['title'],
							'author' => $suggestion['titleInfo']['author'],

						);
					}
					return array('success' => true, 'listTitle' => $systemList['title'], 'listDescription' => $systemList['description'], 'titles' => $titles);
				}
			} else {
				return array('success' => false, 'message' => 'The specified list could not be found.');
			}
		}
	}

	/**
	 * Loads caching information to determine what the list should be cached as
	 * and whether it is cached for all users and products (general), for a single user,
	 * or for a single product.
	 * @noinspection PhpUnused
	 */
	function getCacheInfoForList()
	{
		if (!isset($_REQUEST['id'])) {
			return array('success' => false, 'message' => 'The id of the list to load must be provided as the id parameter.');
		}

		$listId = $_REQUEST['id'];
		return $this->getCacheInfoForListId($listId);
	}

	function getCacheInfoForListId($listId)
	{
		if (is_numeric($listId) || preg_match('/list[-:](.*)/', $listId, $listInfo)) {
			if (isset($listInfo)) {
				$listId = $listInfo[1];
			}
			return array(
				'cacheType' => 'general',
				'fullListLink' => '/MyAccount/MyList/' . $listId,
			);

		} elseif (preg_match('/review:(.*)/', $listId, $reviewInfo)) {
			return array(
				'cacheType' => 'general',
				'fullListLink' => ''
			);
		} elseif ($listId == 'highestRated') {
			return array(
				'cacheType' => 'general',
				'fullListLink' => ''
			);
		} elseif ($listId == 'recentlyReviewed') {
			return array(
				'cacheType' => 'general',
				'fullListLink' => ''
			);
		} elseif ($listId == 'mostPopular') {
			return array(
				'cacheType' => 'general',
				'fullListLink' => ''
			);
		} elseif ($listId == 'recommendations') {
			return array(
				'cacheType' => 'user',
				'fullListLink' => ''
			);
		} elseif (preg_match('/^search:(.*)/', $listId, $searchInfo)) {
			if (is_numeric($searchInfo[1])) {
				$searchId = $searchInfo[1];
				return array(
					'cacheType' => 'general',
					'fullListLink' => '/Search/Results?saved=' . $searchId,
				);
			} else {
				return array(
					'cacheType' => 'general',
					'fullListLink' => ''
				);
			}
		} else {
			return array(
				'cacheType' => 'general',
				'fullListLink' => ''
			);
		}
	}

	function getSavedSearchTitles($searchId, $numTitlesToShow)
	{
		//return a random selection of 30 titles from the list.
		/** @var SearchObject_GroupedWorkSearcher|SearchObject_BaseSearcher $searchObj */
		$searchObj = SearchObjectFactory::initSearchObject();
		$searchObj->init();
		$searchObj = $searchObj->restoreSavedSearch($searchId, false, true);
		if ($searchObj) { // check that the saved search was retrieved successfully
			if (isset($_REQUEST['numTitles'])) {
				$searchObj->setLimit($_REQUEST['numTitles']);
			} else {
				$searchObj->setLimit($numTitlesToShow);
			}
			$searchObj->processSearch(false, false);
			$listTitles = $searchObj->getTitleSummaryInformation();
		}else{
			$listTitles = false;
		}

		return $listTitles;
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
	function createList()
	{
		$username = $_REQUEST['username'];
		$password = $_REQUEST['password'];
		if (!isset($_REQUEST['title'])) {
			return array('success' => false, 'message' => 'You must provide the title of the list to be created.');
		}
		$user = UserAccount::validateAccount($username, $password);
		if ($user && !($user instanceof AspenError)) {
			$list = new UserList();
			$list->title = $_REQUEST['title'];
			$list->description = strip_tags(isset($_REQUEST['description']) ? $_REQUEST['description'] : '');
			$list->public = isset($_REQUEST['public']) ? (($_REQUEST['public'] == true || $_REQUEST['public'] == 1) ? 1 : 0) : 0;
			$list->user_id = $user->id;
			$list->insert();
			$list->find();
			if (isset($_REQUEST['recordIds'])) {
				$_REQUEST['listId'] = $list->id;
				return $this->addTitlesToList();
			}else{
				//There wasn't anything to add so it worked
				return array('success' => true, 'listId' => $list->id);
			}
		} else {
			return array('success' => false, 'message' => 'Login unsuccessful');
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
	 * https://aspenurl/API/ListAPI?method=createList&username=userbarcode&password=userpin&title=Test+List&description=Test&public=0
	 * </code>
	 *
	 * Sample Response:
	 * <code>
	 * {"result":{"success":true,"listId":"1688"}}
	 * </code>
	 */
	function addTitlesToList()
	{
		$username = $_REQUEST['username'];
		$password = $_REQUEST['password'];
		if (!isset($_REQUEST['listId'])) {
			return array('success' => false, 'message' => 'You must provide the listId to add titles to.');
		}
		$recordIds = array();
		if (!isset($_REQUEST['recordIds'])) {
			return array('success' => false, 'message' => 'You must provide one or more records to add to the list.');
		} else if (!is_array($_REQUEST['recordIds'])) {
			$recordIds[] = $_REQUEST['recordIds'];
		} else {
			$recordIds = $_REQUEST['recordIds'];
		}
		$user = UserAccount::validateAccount($username, $password);
		if ($user && !($user instanceof AspenError)) {
			$list = new UserList();
			$list->id = $_REQUEST['listId'];
			$list->user_id = $user->id;
			if (!$list->find(true)) {
				return array('success' => false, 'message' => 'Unable to find the list to add titles to.');
			} else {
				$numAdded = 0;
				foreach ($recordIds as $id) {
					require_once ROOT_DIR . '/sys/UserLists/UserListEntry.php';
					$userListEntry = new UserListEntry();
					$userListEntry->listId = $list->id;
					if (preg_match("/^[A-F0-9]{8}-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{12}|[A-Z0-9_-]+:[A-Z0-9_-]+$/i", $id)) {
						$userListEntry->source = 'GroupedWork';
						$userListEntry->sourceId = $id;

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
						$numAdded++;
					}
				}
				return array('success' => true, 'listId' => $list->id, 'numAdded' => $numAdded);
			}


		} else {
			return array('success' => false, 'message' => 'Login unsuccessful');
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
	function clearListTitles()
	{
		$username = $_REQUEST['username'];
		$password = $_REQUEST['password'];
		if (!isset($_REQUEST['listId'])) {
			return array('success' => false, 'message' => 'You must provide the listId to clear titles from.');
		}
		$user = UserAccount::validateAccount($username, $password);
		if ($user && !($user instanceof AspenError)) {
			$list = new UserList();
			$list->id = $_REQUEST['listId'];
			$list->user_id = $user->id;
			if (!$list->find(true)) {
				return array('success' => false, 'message' => 'Unable to find the list to clear titles from.');
			} else {
				$list->removeAllListEntries();
				return array('success' => true);
			}
		} else {
			return array('success' => false, 'message' => 'Login unsuccessful');
		}
	}

	function getSystemListTitles($listName, $numTitlesToShow)
	{
		global $memCache;
		global $configArray;
		$listTitles = $memCache->get('system_list_titles_' . $listName);
		if ($listTitles == false || isset($_REQUEST['reload'])) {
			//return a random selection of 30 titles from the list.
			/** @var SearchObject_GroupedWorkSearcher $searchObj */
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
	 * Creates or updates a user defined list from information obtained from the New York Times API
	 *
	 * @param string $selectedList machine readable name of the new york times list
	 * @param NYTUpdateLogEntry $nytUpdateLog
	 * @return array
	 * @throws Exception
	 */
	public function createUserListFromNYT($selectedList = null, $nytUpdateLog = null): array
	{
		if ($selectedList == null) {
			$selectedList = $_REQUEST['listToUpdate'];
		}

		require_once ROOT_DIR . '/sys/Enrichment/NewYorkTimesSetting.php';
		$nytSettings = new NewYorkTimesSetting();
		if (!$nytSettings->find(true)) {
			if ($nytUpdateLog != null) {
				$nytUpdateLog->addError("API Key missing");
			}
			return array(
				'success' => false,
				'message' => 'API Key missing'
			);
		}
		$api_key = $nytSettings->booksApiKey;

		//Get the user to attach the list to
		$nytListUser = new User();
		$nytListUser->username = 'nyt_user';
		if (!$nytListUser->find(true)) {
			if ($nytUpdateLog != null) {
				$nytUpdateLog->addError("NY Times user has not been created");
			}
			return array(
				'success' => false,
				'message' => 'NY Times user has not been created'
			);
		}

		//Get the raw response from the API with a list of all the names
		require_once ROOT_DIR . '/sys/NYTApi.php';
		$nyt_api = new NYTApi($api_key);
		$availableListsRaw = $nyt_api->get_list('names');
		//Convert into an object that can be processed
		$availableLists = json_decode($availableListsRaw);

		//Get the human readable title for our selected list
		$selectedListTitle = null;
		$selectedListTitleShort = null;
		$allLists = $availableLists->results;
		//Get the title and description for the selected list
		foreach ($allLists as $listInformation) {
			if ($listInformation->list_name_encoded == $selectedList) {
				$selectedListTitle = 'NYT - ' . $listInformation->display_name;
				$selectedListTitleShort = $listInformation->display_name;
				break;
			}
		}
		if (empty($selectedListTitleShort)) {
			if ($nytUpdateLog != null) {
				$nytUpdateLog->addError("We did not find list '{$selectedList}' in The New York Times API");
			}
			return array(
				'success' => false,
				'message' => "We did not find list '{$selectedList}' in The New York Times API"
			);
		}

		//Get a list of titles from NYT API
		$retry = true;
		$numTries = 0;
		$listTitles = null;
		while ($retry == true) {
			$retry = false;
			$numTries++;
			$listTitles = null;
			$listTitlesRaw = $nyt_api->get_list($selectedList);
			$listTitles = json_decode($listTitlesRaw);
			if (empty($listTitles->status) || $listTitles->status != "OK") {
				if (!empty($listTitles->fault)) {
					if (strpos($listTitles->fault->faultstring, 'quota violation')) {
						$retry = ($numTries <= 3);
						if ($retry){
							sleep(60);
						}else{
							if ($nytUpdateLog != null) {
								$nytUpdateLog->addError("Did not get a good response from the API. {$listTitles->fault->faultstring}");
							}
						}
					} else {
						if ($nytUpdateLog != null) {
							$nytUpdateLog->addError("Did not get a good response from the API. {$listTitles->fault->faultstring}");
						}
					}
				} else {
					if ($nytUpdateLog != null) {
						$nytUpdateLog->addError("Did not get a good response from the API");
					}
				}
			}
		}
		
		if ($listTitles == null){
			return array(
				'success' => false,
				'message' => "Could not get a response from the API"
			);
		}

		$lastModified = date_timestamp_get(new DateTime($listTitles->last_modified));
		$lastModifiedDay = date("M j, Y", $lastModified);

		// Look for selected List
		require_once ROOT_DIR . '/sys/UserLists/UserList.php';
		$nytList = new UserList();
		$nytList->user_id = $nytListUser->id;
		$nytList->title = $selectedListTitle;
		$listExistsInAspen = $nytList->find(1);

		//We didn't find the list in Aspen Discovery, create one
		if (!$listExistsInAspen) {
			$nytList = new UserList();
			$nytList->title = $selectedListTitle;
			$nytList->description = "New York Times - $selectedListTitleShort<br/>{$listTitles->copyright}";
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
				$results = array(
					'success' => true,
					'message' => "Created list <a href='/MyAccount/MyList/{$listID}'>{$selectedListTitle}</a>"
				);
			} else {
				//Update log that this failed
                global $logger;
                $logger->log('Could not create list: ' . $selectedListTitle, Logger::LOG_ERROR);
				if ($nytUpdateLog != null) {
					$nytUpdateLog->addError('Could not create list: ' . $selectedListTitle);
				}
				return array(
					'success' => false,
					'message' => 'Could not create list'
				);
			}

		} else {
			$listID = $nytList->id;
			if ($nytList->nytListModified == $lastModifiedDay){
				if ($nytUpdateLog != null) {
					$nytUpdateLog->numSkipped++;
				}
				//Nothing has changed, no need to update
				return array(
					'success' => true,
					'message' => "List <a href='/MyAccount/MyList/{$listID}'>{$selectedListTitle}</a> has not changed since it was last loaded."
				);
			}
			if ($nytUpdateLog != null) {
				$nytUpdateLog->numUpdated++;
			}
			$nytList->description = "New York Times - $selectedListTitleShort<br/>{$listTitles->copyright}";
			$nytList->nytListModified = $lastModifiedDay;
			$nytList->update();
			$results = array(
				'success' => true,
				'message' => "Updated list <a href='/MyAccount/MyList/{$listID}'>{$selectedListTitle}</a>"
			);
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
		foreach ($listTitles->results as $titleResult) {
			$aspenID = null;
			// go through each list item
			if (!empty($titleResult->isbns)) {
				foreach ($titleResult->isbns as $isbns) {
					$isbn = empty($isbns->isbn13) ? $isbns->isbn10 : $isbns->isbn13;
					if ($isbn) {
						//look the title up by ISBN
						/** @var SearchObject_GroupedWorkSearcher $searchObject */
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
					}
					//break if we found a aspen id for the title
					if ($aspenID != null) {
						break;
					}
				}
			}//Done checking ISBNs
			if ($aspenID != null) {
				$note = "#{$titleResult->rank} on the {$titleResult->display_name} list for {$titleResult->published_date}.";
				if ($titleResult->rank_last_week != 0) {
					$note .= '  Last week it was ranked ' . $titleResult->rank_last_week . '.';
				}
				if ($titleResult->weeks_on_list != 0) {
					$note .= "  It has been on the list for {$titleResult->weeks_on_list} week(s).";
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

	function getBreadcrumbs() : array
	{
		return [];
	}
}
