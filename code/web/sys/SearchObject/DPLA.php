<?php
/**
 * Handles searching DPLA and returning results
 *
 * @category VuFind-Plus-2014
 * @author Mark Noble <mark@marmot.org>
 * Date: 2/9/15
 * Time: 3:09 PM
 */

class DPLA {
	public function getDPLAResults($searchTerm, $numResults = 5){
		global $configArray;
		$results = array();
		if ($configArray['DPLA']['enabled']){
			$queryUrl = "http://api.dp.la/v2/items?api_key={$configArray['DPLA']['apiKey']}&page_size=$numResults&q=" . urlencode($searchTerm);

			$responseRaw = file_get_contents($queryUrl);
			$responseData = json_decode($responseRaw);
			//Uncomment to view full response
			//echo(print_r($responseData, true));

			//Extract, title, author, source, and the thumbnail

			foreach($responseData->docs as $curDoc){
				$curResult = array();

				$curResult['id'] = @$this->getDataForNode($curDoc->id);
				$curResult['link'] = @$this->getDataForNode($curDoc->isShownAt);
				$curResult['object'] = @$this->getDataForNode($curDoc->object);
				$curResult['image'] = @$this->getDataForNode($curDoc->object);
				$curResult['title'] = @$this->getDataForNode($curDoc->sourceResource->title);
				$curResult['label'] = @$this->getDataForNode($curDoc->sourceResource->title);
				$curResult['format'] = @$this->getDataForNode($curDoc->originalRecord->format);
				if ($curResult['format'] == "" ){
					$curResult['format'] = @$this->getDataForNode($curDoc->originalRecord->type);
				}
				if ($curResult['format'] == "" ){
					$curResult['format'] = 'Not Provided';
				}
				$curResult['date'] = @$this->getDataForNode($curDoc->sourceResource->date->displayDate);
				$curResult['publisher'] = @$this->getDataForNode($curDoc->sourceResource->publisher);
				if ($curResult['publisher'] == "" ){
					$curResult['publisher'] = @$this->getDataForNode($curDoc->originalRecord->publisher);
				}
				$curResult['description'] = @$this->getDataForNode($curDoc->sourceResource->description);
				$curResult['dataProvider'] = @$this->getDataForNode($curDoc->dataProvider);
				$results[] = $curResult;
			}
		}

		return array(
				'firstRecord' => 0,
				'lastRecord' => count($results),
				'resultTotal' => $responseData->count,
				'records' => $results);
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
		if (count($results) > 0){
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
}