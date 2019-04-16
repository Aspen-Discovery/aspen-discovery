<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';

class Home extends Admin_Admin {
	function launch() {
		global $configArray;
		global $interface;

		require_once ROOT_DIR . '/services/API/SearchAPI.php';
		$indexStatus = new SearchAPI();
		$pikaStatus = $indexStatus->getIndexStatus();
		$interface->assign('PikaStatus', $pikaStatus['status']);
		$interface->assign('PikaStatusMessages', explode(';', $pikaStatus['message']));

		// Load SOLR Statistics
        $xml = @file_get_contents($configArray['Index']['url'] . '/admin/cores');

        if ($xml) {
            $data = json_decode($xml, true);
            $interface->assign('data', $data['status']);
        }

        $masterIndexUrl = str_replace(':80', ':81', $configArray['Index']['url']) . '/admin/cores';
        $masterXml = @file_get_contents($masterIndexUrl);

        if ($masterXml) {
            $masterData = json_decode($masterXml, true);
            $interface->assign('master_data', $masterData['status']);
        }

		$this->display('home.tpl', 'Solr Information');
	}

	function getAllowableRoles() {
		return array('userAdmin', 'opacAdmin');
	}
}