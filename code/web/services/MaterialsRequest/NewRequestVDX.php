<?php


require_once ROOT_DIR . "/Action.php";

class MaterialsRequest_NewRequestVDX extends Action
{
	function launch()
	{
		global $interface;
		global $library;

		if (!UserAccount::isLoggedIn()) {
			header('Location: /MyAccount/Home?followupModule=MaterialsRequest&followupAction=NewRequestIls');
			exit;
		} else {
			$user = UserAccount::getActiveUserObj();
			$patronId = empty($_REQUEST['patronId']) ?  $user->id : $_REQUEST['patronId'];
			$patron = $user->getUserReferredTo($patronId);
			$interface->assign('patronId', $patronId);

			require_once ROOT_DIR . '/Drivers/VdxDriver.php';
			require_once ROOT_DIR . '/sys/VDX/VdxSetting.php';
			require_once ROOT_DIR . '/sys/VDX/VdxForm.php';
			$vdxSettings = new VdxSetting();
			$error = false;
			if ($vdxSettings->find(true)){
				if (isset($_REQUEST['submit'])){
					$vdxDriver = new VdxDriver();
					$results = $vdxDriver->submitRequest($vdxSettings, UserAccount::getActiveUserObj(), $_REQUEST, true);
					if ($results['success']){
						header('Location: /MyAccount/Holds#interlibrary_loan');
						exit;
					}else{
						$error = $results['message'];
					}
				}
				$homeLocation = Location::getDefaultLocationForUser();
				if ($homeLocation != null){
					//Get configuration for the form.
					$vdxForm = new VdxForm();
					$vdxForm->id = $homeLocation->vdxFormId;
					if ($vdxForm->find(true)) {
						$interface->assign('vdxForm', $vdxForm);
						$vdxFormFields = $vdxForm->getFormFields(null);
						$interface->assign('structure', $vdxFormFields);
						$interface->assign('saveButtonText', 'Submit Request');
						$fieldsForm = $interface->fetch('DataObjectUtil/objectEditForm.tpl');
						$interface->assign('vdxForm', $fieldsForm);
					}else{
						$error = translate(['text'=>"Unable to find the specified form.", 'isPublicFacing'=>true]);
					}
				}else{
					$error = translate(['text'=>"Unable to determine home library to place request from.", 'isPublicFacing'=>true]);
				}
			}else{
				$error = translate(['text'=>"VDX Settings do not exist, please contact the library to make a request.", 'isPublicFacing'=>true]);
			}

			$interface->assign('error', $error);

			$this->display('vdxRequest.tpl', 'Materials Request');
		}
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'Your Account');
		$breadcrumbs[] = new Breadcrumb('', 'New Materials Request');
		return $breadcrumbs;
	}
}