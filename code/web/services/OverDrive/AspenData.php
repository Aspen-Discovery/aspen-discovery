<?php
require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';

class OverDrive_AspenData extends Admin_Admin
{
	function launch()
	{
		global $interface;
		if (isset($_REQUEST['overDriveId'])){
			$interface->assign('overDriveId', $_REQUEST['overDriveId']);
			require_once ROOT_DIR . '/sys/OverDrive/OverDriveAPIProduct.php';
			$overDriveProduct = new OverDriveAPIProduct();
			$overDriveProduct->overdriveId = $_REQUEST['overDriveId'];
			$errors = '';
			if ($overDriveProduct->find(true)){
				$interface->assign('overDriveProduct', $overDriveProduct);

				require_once ROOT_DIR . '/sys/OverDrive/OverDriveAPIProductMetaData.php';
				$overDriveMetadata = new OverDriveAPIProductMetaData();
				$overDriveMetadata->productId = $overDriveProduct->id;
				if ($overDriveMetadata->find(true)){
					$interface->assign('overDriveMetadata', $overDriveMetadata);
				}else{
					$errors = 'Could not find metadata for the product';
				}

				require_once ROOT_DIR . '/sys/OverDrive/OverDriveAPIProductAvailability.php';
				$overDriveAvailabilities = [];
				$overDriveAvailability = new OverDriveAPIProductAvailability();
				$overDriveAvailability->productId = $overDriveProduct->id;
				$overDriveAvailability->find();
				while ($overDriveAvailability->fetch()){
					$overDriveAvailabilities[] = clone $overDriveAvailability;
				}
				$interface->assign('overDriveAvailabilities', $overDriveAvailabilities);
			}else{
				$errors = 'Could not find a product with that identifier';
			}
			$interface->assign('errors', $errors);
		}else{
			$interface->assign('overDriveId','');
		}

		$this->display('overdriveAspenData.tpl', 'OverDrive Aspen Data');
	}

	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#overdrive', 'OverDrive');
		$breadcrumbs[] = new Breadcrumb('/OverDrive/AspenData', 'Aspen Information');
		return $breadcrumbs;
	}

	function getActiveAdminSection()
	{
		return 'overdrive';
	}

	function canView()
	{
		return UserAccount::userHasPermission('View OverDrive Test Interface');
	}
}