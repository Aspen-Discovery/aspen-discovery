<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';

class Home extends Admin_Admin
{
	function launch()
	{
		global $configArray;
		global $interface;

		require_once ROOT_DIR . '/services/API/SearchAPI.php';
		$indexStatus = new SearchAPI();
		$aspenStatus = $indexStatus->getIndexStatus();
		$interface->assign('aspenStatus', $aspenStatus['status']);
		$interface->assign('aspenStatusMessages', explode(';', $aspenStatus['message']));

		// Load SOLR Statistics
		$solrStatus = @file_get_contents($configArray['Index']['url'] . '/admin/cores');

		if ($solrStatus) {
			$data = json_decode($solrStatus, true);
			$interface->assign('data', $data['status']);
		}

		$this->display('home.tpl', 'Aspen Discovery Status');
	}

	function getAllowableRoles()
	{
		return array('userAdmin', 'opacAdmin');
	}
}