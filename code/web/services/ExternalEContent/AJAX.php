<?php

require_once ROOT_DIR . '/JSON_Action.php';
require_once ROOT_DIR . '/RecordDrivers/ExternalEContentDriver.php';

class ExternalEContent_AJAX extends JSON_Action {
	function launch($method = null) : void {
		$method = (isset($_GET['method']) && !is_array($_GET['method'])) ? $_GET['method'] : '';
		if ($method == 'downloadMarc') {
			echo $this->$method();
		}
		parent::launch();
	}


	/** @noinspection PhpUnused */
	function downloadMarc() : void {
		$this->requireLoggedInUser();
		$this->checkRequiredPermission('Download MARC Records');
		$this->checkRequiredParameters(['id']);
		$id = $_REQUEST['id'];
		$marcData = MarcLoader::loadMarcRecordByILSId($id);
		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header("Content-Disposition: attachment; filename=$id.mrc");
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');

		header('Content-Length: ' . strlen($marcData->toRaw()));
		ob_clean();
		flush();
		echo($marcData->toRaw());
	}

	function getStaffView(): array {
		global $interface;
		if (!$interface->getVariable('showStaffView')) {
			$this->failureResult(null, 'Staff View is not available.');
		}
		$this->checkRequiredParameters(['id']);

		$result = [
			'success' => false,
			'message' => 'Unknown error loading staff view',
		];
		$id = $_REQUEST['id'];
		$recordDriver = RecordDriverFactory::initRecordDriverById($id);
		if ($recordDriver->isValid()) {
			global $interface;
			$interface->assign('recordDriver', $recordDriver);
			$result = [
				'success' => true,
				'staffView' => $interface->fetch($recordDriver->getStaffView()),
			];
		} else {
			$result['message'] = translate([
				'text' => 'Could not find that record',
				'isPublicFacing' => true,
			]);
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function showSelectItemToViewForm(): array {
		$this->checkRequiredParameters(['id']);
		global $interface;

		$id = $_REQUEST['id'];
		$recordDriver = $this->loadRecordDriver($id);
		if ($recordDriver->isValid()) {
			if (strpos($id, ':')) {
				[
					,
					$id,
				] = explode(':', $id);
			}
			$interface->assign('id', $id);

			$idWithSource = $recordDriver->getIdWithSource();
			$relatedRecord = $recordDriver->getGroupedWorkDriver()->getRelatedRecord($idWithSource);
			$allItems = $relatedRecord->getItems();
			$interface->assign('items', $allItems);

			$buttonTitle = translate([
				'text' => 'Access Online',
				'isPublicFacing' => true,
			]);
			return [
				'title' => translate([
					'text' => 'Select Link to View',
					'isPublicFacing' => true,
				]),
				'modalBody' => $interface->fetch("ExternalEContent/select-view-item-link-form.tpl"),
				'modalButtons' => "<button class='tool btn btn-primary' onclick='$(\"#viewItem\").submit()'><i class='fas fa-external-link-alt' role='presentation'></i> $buttonTitle</button>",
			];
		} else {
			return [
				'success' => false,
				'title' => translate([
					'text' => 'Error',
					'isPublicFacing' => true,
				]),
				'modalBody' => translate([
					'text' => 'Could not find a record with that id',
					'isPublicFacing' => true,
				]),
				'modalButtons' => "",
			];
		}
	}

	/** @noinspection PhpUnused */
	function viewItem(): array {
		$this->checkRequiredParameters(['id', 'selectedItem']);
		$id = $_REQUEST['id'];
		$itemId = $_REQUEST['selectedItem'];

		$recordDriver = $this->loadRecordDriver($id);
		if ($recordDriver->isValid()) {
			$idWithSource = $recordDriver->getIdWithSource();
			$relatedRecord = $recordDriver->getGroupedWorkDriver()->getRelatedRecord($idWithSource);
			$allItems = $relatedRecord->getItems();
			foreach ($allItems as $item) {
				if ($item->itemId == $itemId) {
					$relatedUrls = $item->getRelatedUrls();
					foreach ($relatedUrls as $relatedUrl) {
						return [
							'success' => true,
							'url' => $relatedUrl['url']
						];
					}
				}
			}
		}
		return [
			'success' => false,
			'title' => translate([
				'text' => 'Error',
				'isPublicFacing' => true,
			]),
			'modalBody' => translate([
				'text' => 'Could not find the url to direct to',
				'isPublicFacing' => true,
			]),
			'modalButtons' => "",
		];

	}

	private function loadRecordDriver($id) : ExternalEContentDriver {
		global $activeRecordProfile;
		$subType = '';
		if (isset($activeRecordProfile)) {
			$subType = $activeRecordProfile;
		} else {
			$indexingProfile = new IndexingProfile();
			$indexingProfile->name = 'ils';
			if ($indexingProfile->find(true)) {
				$subType = $indexingProfile->name;
			} else {
				$indexingProfile = new IndexingProfile();
				$indexingProfile->id = 1;
				if ($indexingProfile->find(true)) {
					$subType = $indexingProfile->name;
				}
			}
		}


		return new ExternalEContentDriver($subType . ':' . $id);
	}

	function getBreadcrumbs(): array {
		return [];
	}
}