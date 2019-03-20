<?php

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