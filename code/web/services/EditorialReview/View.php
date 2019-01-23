<?php
/**
 *
 * Copyright (C) Villanova University 2007.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

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
