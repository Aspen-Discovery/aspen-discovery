<?php
/**
 * API functionality related to Grouped Works
 *
 * @category VuFind-Plus
 * @author Mark Noble <mark@marmot.org>
 * Date: 2/4/14
 * Time: 9:21 AM
 */

class WorkAPI {
	function launch()
	{
		$method = (isset($_GET['method']) && !is_array($_GET['method'])) ? $_GET['method'] : '';
		if (method_exists($this, $method)) {
			$output = json_encode(array('result'=>$this->$method()));
		} else {
			$output = json_encode(array('error'=>"invalid_method '$method'"));
		}

		echo $output;
	}

	function getRatingData($permanentId = null){
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
		return $ratingData;
	}

	public function getIsbnsForWork($permanentId = null){
		if ($permanentId == null){
			$permanentId = $_REQUEST['id'];
		}

		//Speed this up by not loading the entire grouped work driver since all we need is a list of ISBNs
		//require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		//$groupedWorkDriver = new GroupedWorkDriver($permanentId);
		//return $groupedWorkDriver->getISBNs();

		global $configArray;
		$class = $configArray['Index']['engine'];
		$url = $configArray['Index']['url'];
		/** @var Solr $db */
		$db = new $class($url);

		disableErrorHandler();
		$record = $db->getRecord($permanentId, 'isbn');
		enableErrorHandler();
		if ($record == false || PEAR_Singleton::isError($record)){
			return array();
		}else{
			return $record['isbn'];
		}


	}

	public function generateWorkId(){
		global $configArray;
		$localPath = $configArray['Site']['local'];
		$title = escapeshellarg($_REQUEST['title']);
		$author = escapeshellarg($_REQUEST['author']);
		$format = escapeshellarg($_REQUEST['format']);
		$recordGroupingPath = realpath("$localPath/../record_grouping/");
		$commandToRun = "java -jar $recordGroupingPath/record_grouping.jar generateWorkId $title $author $format";
		$result = shell_exec($commandToRun);
		//TODO: Return normalized title and normalized author as well.
		return json_decode($result);
	}
}