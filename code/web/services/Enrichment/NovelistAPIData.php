<?php
require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';

class Enrichment_NovelistAPIData extends Admin_Admin {
	function launch() {
		global $interface;
		global $logger;

		//require_once ROOT_DIR . '/sys/Enrichment/NovelistSetting.php';
		require_once ROOT_DIR . '/sys/Enrichment/Novelist3.php';

		$driver = new Novelist3();

		$contents = '';

		if (!empty($_REQUEST['id'])) {
			$ISBN = $_REQUEST['id'];
			$interface->assign('ISBN', $ISBN);

			if (!empty($_REQUEST['allInfo'])) {
				$allInfo = $_REQUEST['allInfo'];
			}else {
				$allInfo = "off";
			}
			$interface->assign('allInfo', $allInfo);

			$contents .= "<h2>Metadata for ISBN: $ISBN</h2>";
			if ($allInfo == "on"){
				$contents .= "<h3>Data includes info for all records in series</h3>";
			}
			$logger->log("Fetching Novelist Data", Logger::LOG_ERROR);
			$metadata = $driver->getRawNovelistDataISBN($ISBN, $allInfo);
			$logger->log("Fetched Novelist Data", Logger::LOG_ERROR);
			if (!empty($metadata)) {
				$logger->log("Formatting Novelist Data", Logger::LOG_ERROR);
				$contents .= $this->easy_printr("metadata_{$ISBN}", $metadata);
				$logger->log("Finished Formatting Novelist Data", Logger::LOG_ERROR);
			} else {
				$contents .= "No metadata available<br/>";
			}
		}

		$interface->assign('novelistAPIData', $contents);
		$this->display('novelistApiData.tpl', 'Novelist API Data');
	}

	function easy_printr($section, &$var) {
		$contents = "<pre id='{$section}'>";
		$formattedContents = print_r($var, true);
		if ($formattedContents !== false) {
			$contents .= $formattedContents;
		}
		$contents .= '</pre>';
		return $contents;
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#third_party_enrichment', 'Third Party Enrichment');
		$breadcrumbs[] = new Breadcrumb('/Enrichment/NovelistAPIData', 'Novelist API Information');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'third_party_enrichment';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Administer Third Party Enrichment API Keys');
	}
}