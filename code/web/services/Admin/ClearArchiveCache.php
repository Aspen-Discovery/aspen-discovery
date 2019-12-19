<?php

require_once ROOT_DIR . '/services/Admin/Admin.php';
class Admin_ClearArchiveCache extends Admin_Admin{

	function launch() {
		global $interface;

		require_once ROOT_DIR . '/sys/Islandora/IslandoraObjectCache.php';
		if (isset($_REQUEST['submit'])){
			$cache = new IslandoraObjectCache();
			$cache->whereAdd("pid like '%'");
			$cache->delete(true);
		}

		$cache = new IslandoraObjectCache();
		$cache->find();
		$numCachedObjects = $cache->getNumResults();
		$interface->assign('numCachedObjects', $numCachedObjects);
		$this->display('clearArchiveCache.tpl', 'Clear Archive Cache');
	}

	function getAllowableRoles() {
		return array('opacAdmin');
	}
}