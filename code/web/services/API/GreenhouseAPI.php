<?php
require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/sys/Greenhouse/AspenSite.php';

class GreenhouseAPI extends Action
{
	function launch()
	{
		//Make sure the user can access the API based on the IP address
		if (!IPAddress::allowAPIAccessForClientIP()){
			$this->forbidAPIAccess();
		}

		header('Content-type: application/json');
		//header('Content-type: text/html');
		header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past

		$method = (isset($_GET['method']) && !is_array($_GET['method'])) ? $_GET['method'] : '';
		if ($method != 'getCatalogConnection' && $method != 'getUserForApiCall' && method_exists($this, $method)) {
			$result = [
				'result' => $this->$method()
			];
			$output = json_encode($result);
			require_once ROOT_DIR . '/sys/SystemLogging/APIUsage.php';
			APIUsage::incrementStat('GreenhouseAPI', $method);
		} else {
			$output = json_encode(array('error' => 'invalid_method'));
		}
		echo $output;
	}

	public function getLibraries() : array
	{
		$return = [
			'success' => true,
			'libraries' => [],
		];
		$sites = new AspenSite();
		$sites->find();
		while($sites->fetch()) {
			if(($sites->appAccess == 1) || ($sites->appAccess == 3)) {
				$return['libraries'][] = [
					'name' => $sites->name,
					'baseUrl' => $sites->baseUrl,
				];
			}
		}

		return $return;
	}

	function getBreadcrumbs() : array
	{
		return [];
	}
}