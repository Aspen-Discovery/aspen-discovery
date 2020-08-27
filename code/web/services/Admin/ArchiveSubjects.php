<?php

/**
 * Control how subjects are handled when linking to the catalog.
 */
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/ArchiveSubject.php';
class Admin_ArchiveSubjects extends Admin_Admin{

	function launch() {
		global $interface;
		$archiveSubjects = new ArchiveSubject();
		$archiveSubjects->find(true);
		if (isset($_POST['subjectsToIgnore'])){
			$archiveSubjects->subjectsToIgnore = strip_tags($_POST['subjectsToIgnore']);
			$archiveSubjects->subjectsToRestrict = strip_tags($_POST['subjectsToRestrict']);
			if ($archiveSubjects->id){
				$archiveSubjects->update();
			}else{
				$archiveSubjects->insert();
			}
		}
		$interface->assign('subjectsToIgnore', $archiveSubjects->subjectsToIgnore);
		$interface->assign('subjectsToRestrict', $archiveSubjects->subjectsToRestrict);

		$this->display('archiveSubjects.tpl', 'Archive Subjects');
	}

	function getAllowableRoles() {
		return array('opacAdmin', 'archives');
	}

	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#islandora_archive', 'Islandora Archives');
		$breadcrumbs[] = new Breadcrumb('/Admin/ArchiveSubjects', 'Subject Control');
		return $breadcrumbs;
	}

	function getActiveAdminSection()
	{
		return 'islandora_archive';
	}
}