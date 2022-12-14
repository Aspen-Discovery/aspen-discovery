<?php

class WorkAPI extends Action {
	/** @var  AbstractIlsDriver */
	protected $catalog;

	public $id;

	/**
	 * @var MarcRecordDriver|IndexRecordDriver
	 * marc record in File_Marc object
	 */
	protected $recordDriver;

	public $record;

	public $isbn;
	public $issn;
	public $upc;

	/** @var  Solr $db */
	public $db;

	function launch() {
		$method = (isset($_GET['method']) && !is_array($_GET['method'])) ? $_GET['method'] : '';

		header('Content-type: application/json');
		header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past

		if (isset($_SERVER['PHP_AUTH_USER'])) {
			if ($this->grantTokenAccess()) {
				if (in_array($method, [
					'getGroupedWork',
					'getRatingData',
					'updateRating'
				])) {
					header('Cache-Control: max-age=10800');
					require_once ROOT_DIR . '/sys/SystemLogging/APIUsage.php';
					APIUsage::incrementStat('WorkAPI', $method);
					$output = json_encode($this->$method());
				} else {
					header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
					$output = json_encode(['error' => 'invalid_method']);
				}
			} else {
				header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
				header('HTTP/1.0 401 Unauthorized');
				$output = json_encode(['error' => 'unauthorized_access']);
			}
			ExternalRequestLogEntry::logRequest('WorkAPI.' . $method, $_SERVER['REQUEST_METHOD'], $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], getallheaders(), '', $_SERVER['REDIRECT_STATUS'], $output, []);
			echo $output;
		} elseif (IPAddress::allowAPIAccessForClientIP()) {
			if ($method != 'getRatingData' && method_exists($this, $method)) {
				if (method_exists($this, $method)) {
					$output = json_encode(['result' => $this->$method()]);
					require_once ROOT_DIR . '/sys/SystemLogging/APIUsage.php';
					APIUsage::incrementStat('WorkAPI', $method);
				} else {
					$output = json_encode(['error' => "invalid_method '$method'"]);
				}

				echo $output;
			}
		} else {
			$this->forbidAPIAccess();
		}
	}

	/** @noinspection PhpUnused */
	function getGroupedWork(): array {

		if (!isset($_REQUEST['id'])) {
			return [
				'success' => false,
				'message' => 'Grouped work id not provided'
			];
		}
		//Load basic information
		$this->id = $_GET['id'];

		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		$groupedWorkDriver = new GroupedWorkDriver($this->id);
		if ($groupedWorkDriver->isValid()) {
			$itemData['success'] = true;
			$itemData['id'] = $this->id;
			$itemData['title'] = $groupedWorkDriver->getShortTitle();
			$itemData['subtitle'] = $groupedWorkDriver->getSubtitle();
			$itemData['author'] = $groupedWorkDriver->getPrimaryAuthor();
			$itemData['description'] = strip_tags($groupedWorkDriver->getDescriptionFast());
			if ($itemData['description'] == '') {
				$itemData['description'] = 'Description Not Provided';
			}
			$language = $groupedWorkDriver->getLanguage();
			$itemData['language'] = $language[0] ?? $language;
			$itemData['cover'] = $groupedWorkDriver->getBookcoverUrl('large', true);

			$itemData['series'] = $groupedWorkDriver->getIndexedSeries();

			$formats = $groupedWorkDriver->getFormatsArray();
			$itemData['formats'] = [];

			foreach ($formats as $format) {
				$itemData['formats'][$format] = [];
				$relatedManifestation = null;
				foreach ($groupedWorkDriver->getRelatedManifestations() as $relatedManifestation) {
					if ($relatedManifestation->format == $format) {
						break;
					}
				}
				$itemData['formats'][$format]['label'] = $format;
				$itemData['formats'][$format]['category'] = $relatedManifestation->formatCategory;
				$itemData['formats'][$format]['actions'] = $relatedManifestation->getActions();
				$itemData['formats'][$format]['isAvailable'] = $relatedManifestation->isAvailable();

			}

			return $itemData;
		}
		return [
			'success' => false,
			'message' => 'Grouped work id not valid'
		];
	}

	function getRatingData($permanentId = null) {
		global $timer;
		if (is_null($permanentId) && isset($_REQUEST['id'])) {
			$permanentId = $_REQUEST['id'];
		}

		//Set default rating data
		$ratingData = [
			'average' => 0,
			'count' => 0,
			'user' => 0,
			'num1star' => 0,
			'num2star' => 0,
			'num3star' => 0,
			'num4star' => 0,
			'num5star' => 0,
		];

		//Somehow we didn't get an id (work no longer exists in the index)
		if (is_null($permanentId)) {
			return $ratingData;
		}

		require_once ROOT_DIR . '/sys/LocalEnrichment/UserWorkReview.php';
		$reviewData = new UserWorkReview();
		$reviewData->groupedRecordPermanentId = $permanentId;
		$reviewData->find();
		$totalRating = 0;
		while ($reviewData->fetch()) {
			if ($reviewData->rating > 0) {
				$totalRating += $reviewData->rating;
				$ratingData['count']++;
				if (UserAccount::isLoggedIn() && $reviewData->userId == UserAccount::getActiveUserId()) {
					$ratingData['user'] = $reviewData->rating;
				}
				if ($reviewData->rating == 1) {
					$ratingData['num1star']++;
				} elseif ($reviewData->rating == 2) {
					$ratingData['num2star']++;
				} elseif ($reviewData->rating == 3) {
					$ratingData['num3star']++;
				} elseif ($reviewData->rating == 4) {
					$ratingData['num4star']++;
				} elseif ($reviewData->rating == 5) {
					$ratingData['num5star']++;
				}
			}
		}
		$reviewData->__destruct();
		$reviewData = null;
		if ($ratingData['count'] > 0) {
			$ratingData['average'] = $totalRating / $ratingData['count'];
			$ratingData['barWidth5Star'] = 100 * $ratingData['num5star'] / $ratingData['count'];
			$ratingData['barWidth4Star'] = 100 * $ratingData['num4star'] / $ratingData['count'];
			$ratingData['barWidth3Star'] = 100 * $ratingData['num3star'] / $ratingData['count'];
			$ratingData['barWidth2Star'] = 100 * $ratingData['num2star'] / $ratingData['count'];
			$ratingData['barWidth1Star'] = 100 * $ratingData['num1star'] / $ratingData['count'];
		} else {
			$ratingData['barWidth5Star'] = 0;
			$ratingData['barWidth4Star'] = 0;
			$ratingData['barWidth3Star'] = 0;
			$ratingData['barWidth2Star'] = 0;
			$ratingData['barWidth1Star'] = 0;
		}
		$timer->logTime("Loaded rating information for $permanentId");
		return $ratingData;
	}

	/** @noinspection PhpUnused */
	public function getIsbnsForWork($permanentId = null) {
		if ($permanentId == null) {
			$permanentId = $_REQUEST['id'];
		}

		//Speed this up by not loading the entire grouped work driver since all we need is a list of ISBNs
		//require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		//$groupedWorkDriver = new GroupedWorkDriver($permanentId);
		//return $groupedWorkDriver->getISBNs();

		global $configArray;
		$url = $configArray['Index']['url'];
		$systemVariables = SystemVariables::getSystemVariables();
		if ($systemVariables->searchVersion == 1) {
			require_once ROOT_DIR . '/sys/SolrConnector/GroupedWorksSolrConnector.php';
			$db = new GroupedWorksSolrConnector($url);
		} else {
			require_once ROOT_DIR . '/sys/SolrConnector/GroupedWorksSolrConnector2.php';
			$db = new GroupedWorksSolrConnector2($url);
		}

		disableErrorHandler();
		$record = $db->getRecord($permanentId, 'isbn');
		enableErrorHandler();
		if ($record == false || ($record instanceof AspenError)) {
			return [];
		} else {
			return $record['isbn'];
		}
	}

	function getBreadcrumbs(): array {
		return [];
	}
}