<?php

require_once ROOT_DIR . '/Action.php';
class DjatokaResolver extends Action{

	function launch() {
		//Pass the request to the Islandora server for processing

		global $configArray;
		$queryString = $_SERVER['QUERY_STRING'];
		$queryString = str_replace('module=AJAX&', '', $queryString);
		$queryString = str_replace('action=DjatokaResolver&', '', $queryString);
		if (substr($queryString, 0, 1) == '&'){
			$queryString = substr($queryString, 1);
		}
		$queryString = str_replace('https', 'http', $queryString);
		$baseRepositoryUrl = $configArray['Islandora']['repositoryUrl'];
		$baseRepositoryUrl = str_replace('https', 'http', $baseRepositoryUrl);
		$requestUrl = $baseRepositoryUrl . '/adore-djatoka/resolver?' . $queryString;

		try{
			$response = @file_get_contents($requestUrl);
			if (!$response){
				$response = json_encode(array(
						'success' => false,
						'message' => 'Could not load from the specified URL ' . $requestUrl
				));
			}
		}catch (Exception $e){
			$response = json_encode(array(
					'success' => false,
					'message' => $e
			));
		}

		echo($response);
	}

	function getBreadcrumbs()
	{
		return [];
	}
}