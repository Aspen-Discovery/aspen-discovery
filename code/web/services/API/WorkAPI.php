<?php

class WorkAPI extends Action{
	function launch()
	{
		$method = (isset($_GET['method']) && !is_array($_GET['method'])) ? $_GET['method'] : '';
		//Make sure the user can access the API based on the IP address
		if ($method != 'getRatingData' && !IPAddress::allowAPIAccessForClientIP()){
			$this->forbidAPIAccess();
		}

		if (method_exists($this, $method)) {
			$output = json_encode(array('result'=>$this->$method()));
			require_once ROOT_DIR . '/sys/SystemLogging/APIUsage.php';
			APIUsage::incrementStat('WorkAPI', $method);
		} else {
			$output = json_encode(array('error'=>"invalid_method '$method'"));
		}

		echo $output;
	}

	/** @noinspection PhpUnused */
	function getGroupedWork() {
		// placeholder for moving getAppGroupedWork from Item API
	}

	function getRatingData($permanentId = null){
		global $timer;
		if (is_null($permanentId) && isset($_REQUEST['id'])){
			$permanentId = $_REQUEST['id'];
		}

		//Set default rating data
		$ratingData = array(
			'average' => 0,
			'count'   => 0,
			'user'    => 0,
			'num1star' => 0,
			'num2star' => 0,
			'num3star' => 0,
			'num4star' => 0,
			'num5star' => 0,
		);

		//Somehow we didn't get an id (work no longer exists in the index)
		if (is_null($permanentId)){
			return $ratingData;
		}

		require_once ROOT_DIR . '/sys/LocalEnrichment/UserWorkReview.php';
		$reviewData = new UserWorkReview();
		$reviewData->groupedRecordPermanentId = $permanentId;
		$reviewData->find();
		$totalRating = 0;
		while ($reviewData->fetch()){
			if ($reviewData->rating > 0){
				$totalRating += $reviewData->rating;
				$ratingData['count']++;
				if (UserAccount::isLoggedIn() && $reviewData->userId == UserAccount::getActiveUserId()){
					$ratingData['user'] = $reviewData->rating;
				}
				if ($reviewData->rating == 1){
					$ratingData['num1star'] ++;
				}elseif ($reviewData->rating == 2){
					$ratingData['num2star'] ++;
				}elseif ($reviewData->rating == 3){
					$ratingData['num3star'] ++;
				}elseif ($reviewData->rating == 4){
					$ratingData['num4star'] ++;
				}elseif ($reviewData->rating == 5){
					$ratingData['num5star'] ++;
				}
			}
		}
		$reviewData->__destruct();
		$reviewData = null;
		if ($ratingData['count'] > 0){
			$ratingData['average'] = $totalRating / $ratingData['count'];
			$ratingData['barWidth5Star'] = 100 * $ratingData['num5star'] / $ratingData['count'];
			$ratingData['barWidth4Star'] = 100 * $ratingData['num4star'] / $ratingData['count'];
			$ratingData['barWidth3Star'] = 100 * $ratingData['num3star'] / $ratingData['count'];
			$ratingData['barWidth2Star'] = 100 * $ratingData['num2star'] / $ratingData['count'];
			$ratingData['barWidth1Star'] = 100 * $ratingData['num1star'] / $ratingData['count'];
		}else{
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
	public function getIsbnsForWork($permanentId = null){
		if ($permanentId == null){
			$permanentId = $_REQUEST['id'];
		}

		//Speed this up by not loading the entire grouped work driver since all we need is a list of ISBNs
		//require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		//$groupedWorkDriver = new GroupedWorkDriver($permanentId);
		//return $groupedWorkDriver->getISBNs();

		global $configArray;
		$url = $configArray['Index']['url'];
		$systemVariables = SystemVariables::getSystemVariables();
		if ($systemVariables->searchVersion == 1){
			require_once ROOT_DIR . '/sys/SolrConnector/GroupedWorksSolrConnector.php';
			$db = new GroupedWorksSolrConnector($url);
		}else{
			require_once ROOT_DIR . '/sys/SolrConnector/GroupedWorksSolrConnector2.php';
			$db = new GroupedWorksSolrConnector2($url);
		}

		disableErrorHandler();
		$record = $db->getRecord($permanentId, 'isbn');
		enableErrorHandler();
		if ($record == false || ($record instanceof AspenError)){
			return array();
		}else{
			return $record['isbn'];
		}
	}

	function getBreadcrumbs() : array
	{
		return [];
	}
}