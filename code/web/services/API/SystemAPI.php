<?php
require_once ROOT_DIR . '/Action.php';

class SystemAPI extends Action
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
			APIUsage::incrementStat('SystemAPI', $method);
		} else {
			$output = json_encode(array('error' => 'invalid_method'));
		}
		echo $output;
	}

	public function getLibraries() : array
	{
		$return = [
			'success' => true,
			'libraries' => []
		];
		$library = new Library();
		$library->orderBy('isDefault desc');
		$library->orderBy('displayName');
		$library->find();
		while ($library->fetch()){
			$return['libraries'][$library->libraryId] = $library->getApiInfo();
		}
		return $return;
	}

	/** @noinspection PhpUnused */
	public function getLibraryInfo() : array
	{
		if (isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
			$library = new Library();
			$library->libraryId = $_REQUEST['id'];
			if ($library->find(true)){
				return ['success' => true, 'library' => $library->getApiInfo()];
			}else{
				return ['success' => false, 'message' => 'Library not found'];
			}
		}else{
			return ['success' => false, 'message' => 'id not provided'];
		}
	}

	/** @noinspection PhpUnused */
	public function getLocationInfo() : array
	{
		if (isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
			$location = new Location();
			$location->locationId = $_REQUEST['id'];
			if ($location->find(true)){
				return ['success' => true, 'location' => $location->getApiInfo()];
			}else{
				return ['success' => false, 'message' => 'Location not found'];
			}
		}else{
			return ['success' => false, 'message' => 'id not provided'];
		}
	}

	/** @noinspection PhpUnused */
	public function getCurrentVersion() : array {
		global $interface;
		$gitBranch = $interface->getVariable('gitBranchWithCommit');
		return [
			'version' => $gitBranch
		];
	}

	function getBreadcrumbs() : array
	{
		return [];
	}
}