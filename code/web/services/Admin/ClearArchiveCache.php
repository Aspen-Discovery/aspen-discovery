<?php

/**
 * Control how subjects are handled when linking to the catalog.
 *
 * @category Pika
 * @author Mark Noble <mark@marmot.org>
 * Date: 2/22/2016
 * Time: 7:05 PM
 */
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
		$numCachedObjects = $cache->N;
		$interface->assign('numCachedObjects', $numCachedObjects);
		$this->display('clearArchiveCache.tpl', 'Clear Archive Cache');
	}

	function getAllowableRoles() {
		return array('opacAdmin');
	}
}