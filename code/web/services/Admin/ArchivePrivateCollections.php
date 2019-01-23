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
require_once ROOT_DIR . '/sys/ArchivePrivateCollection.php';
class Admin_ArchivePrivateCollections extends Admin_Admin{

	function launch() {
		global $interface;
		$privateCollections = new ArchivePrivateCollection();
		$privateCollections->find(true);
		if (isset($_POST['privateCollections'])){
			$privateCollections->privateCollections = strip_tags($_POST['privateCollections']);
			if ($privateCollections->id){
				$privateCollections->update();
			}else{
				$privateCollections->insert();
			}
		}
		$interface->assign('privateCollections', $privateCollections->privateCollections);

		$this->display('archivePrivateCollections.tpl', 'Archive Private Collections');
	}

	function getAllowableRoles() {
		return array('opacAdmin');
	}
}