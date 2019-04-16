<?php

require_once(ROOT_DIR . '/services/Admin/Admin.php');
require_once ROOT_DIR . '/sys/DataObjectUtil.php';
require_once(ROOT_DIR . '/sys/LocalEnrichment/EditorialReview.php');

class EditorialReview_View extends Admin_Admin {

	function launch()
	{
		global $interface;

		$interface->assign('id', $_REQUEST['id']);
		$editorialReview = new EditorialReview();
		$editorialReview->editorialReviewId = $_REQUEST['id'];
		$editorialReview->find();
		if ($editorialReview->N > 0){
			$editorialReview->fetch();
			$interface->assign('editorialReview', $editorialReview);
		}

		$interface->assign('sidebar', 'MyAccount/account-sidebar.tpl');
		$interface->setTemplate('view.tpl');

		$interface->display('layout.tpl');
	}

  function getAllowableRoles(){
    return array('opacAdmin', 'libraryAdmin', 'contentEditor');
  }
}
