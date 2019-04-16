<?php

require_once(ROOT_DIR . '/services/Admin/Admin.php');
require_once(ROOT_DIR . '/sys/LocalEnrichment/EditorialReview.php');
require_once ROOT_DIR . '/sys/DataObjectUtil.php';

class EditorialReview_Delete extends Admin_Admin {

	function launch()
	{
		global $configArray;

		$editorialReview = new EditorialReview();
		$editorialReview->editorialReviewId = $_REQUEST['id'];
		$editorialReview->find();
		if ($editorialReview->N > 0){
			$editorialReview->fetch();
			$editorialReview->delete();
		}

		//Redirect back to the PMDA home page
		header('Location:' . $configArray['Site']['path'] . "/EditorialReview/Search");
		exit();
	}

	function getAllowableRoles(){
		return array('opacAdmin');
	}
}
