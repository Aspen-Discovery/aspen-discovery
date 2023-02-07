<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/RecordDrivers/ExternalEContentDriver.php';

global $configArray;

class ExternalEContent_AJAX extends Action {

	function launch() {
		global $timer;
		$method = (isset($_GET['method']) && !is_array($_GET['method'])) ? $_GET['method'] : '';
		$timer->logTime("Starting method $method");
		if (method_exists($this, $method)) {
			// Methods intend to return JSON data
			if ($method == 'downloadMarc') {
				echo $this->$method();
			} else {
				header('Content-type: application/json');
				header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
				header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
				echo json_encode($this->$method());
			}
		} else {
			$output = json_encode(['error' => 'invalid_method']);
			echo $output;
		}
	}


	/** @noinspection PhpUnused */
	function downloadMarc() {
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
				'modalButtons' => "<button class='tool btn btn-primary' onclick='$(\"#view856\").submit()'>$buttonTitle</button>",
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
	function viewItem(): string {
		global $interface;

		$id = $_REQUEST['id'];
		$linkId = $_REQUEST['linkId'];

		$recordDriver = $this->loadRecordDriver($id);
		if ($recordDriver->isValid()) {
			if (strpos($id, ':')) {
				[
					,
					$id,
				] = explode(':', $id);
			}
			$interface->assign('id', $id);

			$validUrls = $recordDriver->getViewable856Links();
			header('Location: ' . $validUrls[$linkId]['url']);
			die();
		} else {
			header('Location: ' . "/Record/$id");
			die();
		}
	}

	function loadRecordDriver($id) : ExternalEContentDriver {
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
