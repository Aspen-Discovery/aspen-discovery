<?php

class DPLA {
	public function getDPLAResults($searchTerm, $numResults = 5)
	{
		$this->setShowCovers();

		$results = array();

		if (empty($searchTerm)){
			return $results;
		}
		require_once ROOT_DIR . '/sys/Enrichment/DPLASetting.php';
		$dplaSetting = new DPLASetting();
		if ($dplaSetting->find(true)){
			$queryUrl = "http://api.dp.la/v2/items?api_key={$dplaSetting->apiKey}&page_size=$numResults&q=" . urlencode($searchTerm);

			$responseRaw = file_get_contents($queryUrl);
			$responseData = json_decode($responseRaw);
			//Uncomment to view full response
			//echo(print_r($responseData, true));

			//Extract, title, author, source, and the thumbnail
			foreach($responseData->docs as $curDoc){
				$curResult = array();

				$curResult['id'] = @$this->getDataForNode($curDoc->id);
				$curResult['link'] = @$this->getDataForNode($curDoc->isShownAt);
				if (isset($curDoc->object)) {
					$curResult['object'] = @$this->getDataForNode($curDoc->object);
					$curResult['image'] = @$this->getDataForNode($curDoc->object);
				}else{
					$curResult['object'] = '';
					$curResult['image'] = '';
					continue;
				}

				$curResult['title'] = @$this->getDataForNode($curDoc->sourceResource->title);
				$curResult['label'] = @$this->getDataForNode($curDoc->sourceResource->title);
				if (isset($curDoc->sourceResource->type)) {
					$curResult['format'] = @$this->getDataForNode($curDoc->sourceResource->type);
				}elseif (isset($curDoc->sourceResource->format)) {
					$curResult['format'] = @$this->getDataForNode($curDoc->sourceResource->format);
				}else{
					$curResult['format'] = 'Unknown';
				}
				if (is_array($curResult['format'])){
					$curResult['format'] = reset($curResult['format']);
				}
				if (isset($curDoc->sourceResource->date->displayDate)){
					$curResult['date'] = @$this->getDataForNode($curDoc->sourceResource->date->displayDate);
				}else{
					$curResult['date'] = 'Unknown';
				}
				$curResult['publisher'] = @$this->getDataForNode($curDoc->provider->name);
				if ($curResult['publisher'] == "" ){
					$curResult['publisher'] = @$this->getDataForNode($curDoc->originalRecord->publisher);
				}
				if (isset($curDoc->sourceResource->description)) {
					if (is_array(@$curDoc->sourceResource->description)) {
						$curResult['description'] = implode("<br>", $curDoc->sourceResource->description);
					} else {
						$curResult['description'] = @$this->getDataForNode($curDoc->sourceResource->description);
					}
				}else{
					$curResult['description'] = "";
				}
				if (is_object($curDoc->dataProvider)){
					$curResult['dataProvider'] = @$this->getDataForNode($curDoc->dataProvider->name);
				}else{
					$curResult['dataProvider'] = @$this->getDataForNode($curDoc->dataProvider);
				}

				$results[] = $curResult;
			}
		}

		return array(
			'firstRecord' => 0,
			'lastRecord' => count($results),
			'resultTotal' => $responseData->count,
			'records' => $results
		);
	}

	public function getDataForNode($node){
		if (empty($node)){
			return "";
		}else if (is_array($node)){
			return $node[0];
		}else{
			return $node;
		}
	}


	public function formatResults($results, $showDescription = true) {
		$formattedResults = "";
		if (!empty($results)){
			global $interface;
			$interface->assign('searchResults', $results);
			$interface->assign('showDplaDescription', $showDescription);
			$formattedResults = $interface->fetch('Search/dplaResults.tpl');
		}
		return $formattedResults;
	}

	public function formatCombinedResults($results, $showDescription = true) {
		$formattedResults = "";
		if (count($results) > 0){
			global $interface;
			$interface->assign('searchResults', $results);
			$interface->assign('showDplaDescription', $showDescription);
			$formattedResults = $interface->fetch('Search/dplaCombinedResults.tpl');
		}
		return $formattedResults;
	}

	function setShowCovers() {
		global $interface;
		// Hide Covers when the user has set that setting on a Search Results Page
		// this is the same setting as used by the MyAccount Pages for now.
		$showCovers = true;
		if (isset($_REQUEST['showCovers'])) {
			$showCovers = ($_REQUEST['showCovers'] == 'on' || $_REQUEST['showCovers'] == 'true');
			if (isset($_SESSION)) $_SESSION['showCovers'] = $showCovers;
		} elseif (isset($_SESSION['showCovers'])) {
			$showCovers = $_SESSION['showCovers'];
		}
		$interface->assign('showCovers', $showCovers);
	}
}