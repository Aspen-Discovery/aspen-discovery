<?php
require_once ROOT_DIR . '/services/API/AbstractAPI.php';

class CampaignAPI extends AbstractAPI {
	function launch() {
		$method = (isset($_GET['method']) && !is_array($_GET['method'])) ? $_GET['method'] : '';

		header('Content-type: application/json');
		header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past

		global $activeLanguage;
		if (isset($_GET['language'])) {
			$language = new Language();
			$language->code = $_GET['language'];
			if ($language->find(true)) {
				$activeLanguage = $language;
			}
		}

		if (isset($_SERVER['PHP_AUTH_USER'])) {
			if ($this->grantTokenAccess()) {
				if (in_array($method, [
					'getAllCampaigns'
				])) {
					header('Cache-Control: max-age=10800');
					require_once ROOT_DIR . '/sys/SystemLogging/APIUsage.php';
					APIUsage::incrementStat('CampaignAPI', $method);
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
			ExternalRequestLogEntry::logRequest('CampaignAPI.' . $method, $_SERVER['REQUEST_METHOD'], $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], getallheaders(), '', $_SERVER['REDIRECT_STATUS'], $output, []);
			echo $output;
		} elseif (IPAddress::allowAPIAccessForClientIP()) {
			if (method_exists($this, $method)) {
				$output = json_encode(['result' => $this->$method()]);
				require_once ROOT_DIR . '/sys/SystemLogging/APIUsage.php';
				APIUsage::incrementStat('CampaignAPI', $method);
			} else {
				$output = json_encode(['error' => "invalid_method '$method'"]);
			}
			echo $output;
		} else {
			$this->forbidAPIAccess();
		}
	}

	function getAllCampaigns(): array {
		require_once ROOT_DIR . '/sys/CommunityEngagement/Campaign.php';

		$allCampaigns = Campaign::getAllCampaigns();
		return [
			'success' => true,
			'allCampaigns' => $allCampaigns,
		];
	}

	function getBreadcrumbs(): array {
		return [];
	}
}