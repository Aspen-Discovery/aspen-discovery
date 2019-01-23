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

require_once ROOT_DIR . '/Action.php';
require_once(ROOT_DIR . '/services/Admin/Admin.php');
require_once(ROOT_DIR . '/sys/LocalEnrichment/EditorialReview.php');
require_once ROOT_DIR . '/sys/DataObjectUtil.php';

class EditorialReview_Edit extends Admin_Admin {

	function launch()
	{
		global $interface;
		global $configArray;

		$isNew = true;
		if (isset($_REQUEST['id']) && strlen($_REQUEST['id']) > 0 ){
			$editorialReview = new EditorialReview();
			$editorialReview->editorialReviewId = $_REQUEST['id'];
			$editorialReview->find();
			if ($editorialReview->N > 0){
				$editorialReview->fetch();
				$interface->assign('object', $editorialReview);
				$interface->setPageTitle('Edit Editorial Review');
				$isNew = false;
			}
		}
		$structure = EditorialReview::getObjectStructure();
		if ($isNew){
			if (isset($_REQUEST['recordId'])){
				$structure['recordId']['default'] = strip_tags($_REQUEST['recordId']);
			}
		}

		if (isset($_REQUEST['submit']) || isset($_REQUEST['submitStay']) || isset($_REQUEST['submitReturnToList']) || isset($_REQUEST['submitAddAnother'])){
			//Save the object
			$results = DataObjectUtil::saveObject($structure, 'EditorialReview');
			$editorialReview = $results['object'];
			//redirect to the view of the competency if we saved ok.
			if (!$results['validatedOk'] || !$results['saveOk']){
				//Display the errors for the user.
				$interface->assign('errors', $results['errors']);
				$interface->assign('object', $editorialReview);

				$_REQUEST['id'] = $editorialReview->editorialReviewId;
			}else{
				//Show the new review
				if (isset($_REQUEST['submitReturnToList'])){
					header('Location:' . $configArray['Site']['path'] . "/GroupedWork/{$editorialReview->recordId}/Home");
				}elseif (isset($_REQUEST['submitAddAnother'])){
					header('Location:' . $configArray['Site']['path'] . "/EditorialReview/Edit?recordId={$editorialReview->recordId}");
				}else{
					header('Location:' . $configArray['Site']['path'] . "/EditorialReview/{$editorialReview->editorialReviewId}/View");
					exit();
				}
			}
		}

		//Manipulate the structure as needed
		if ($isNew){
		}else{
		}

		$interface->assign('isNew', $isNew);
		$interface->assign('submitUrl', $configArray['Site']['path'] . '/EditorialReview/Edit');
		$interface->assign('editForm', DataObjectUtil::getEditForm($structure));

		$interface->assign('sidebar', 'MyAccount/account-sidebar.tpl');
		$interface->setTemplate('edit.tpl');

		$interface->display('layout.tpl');
	}

	function getAllowableRoles(){
		return array('opacAdmin', 'libraryAdmin', 'contentEditor');
	}
}