<?php

require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/ArchivePrivateCollection.php';
class Admin_ArchivePrivateCollections extends Admin_Admin{

	function launch() {
		global $interface;
		$privateCollections = new ArchivePrivateCollection();
		$privateCollections->orderBy('id');
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

	function getDefaultSort(){
		return 'id';
	}

	function canSort(){
		return false;
	}

	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#islandora_archive', 'Islandora Archives');
		$breadcrumbs[] = new Breadcrumb('/Admin/ArchivePrivateCollections', 'Private Collections');
		return $breadcrumbs;
	}

	function getActiveAdminSection()
	{
		return 'islandora_archive';
	}

	function canView()
	{
		return UserAccount::userHasPermission('Administer Islandora Archive');
	}
}