<?php
require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';

class Enrichment_NovelistAPIData extends Admin_Admin {
	function launch() {
		global $interface;

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
			$metadata = $driver->getRawNovelistDataISBN($ISBN, $allInfo);
			if ($metadata) {
				$contents .= $this->easy_printr("metadata_{$ISBN}", $metadata);
			} else {
				$contents .= ("No metadata available<br/>");
			}
		}

		$interface->assign('novelistAPIData', $contents);
		$this->display('novelistApiData.tpl', 'Novelist API Data');
	}

	function easy_printr($section, &$var) {
		$contents = "<pre id='{$section}'>";
		$contents .= print_r($var, true);
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