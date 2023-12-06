<?php
require_once ROOT_DIR . '/JSON_Action.php';

class PalaceProject_AJAX extends JSON_Action {
	function getStaffView() {
		$result = [
			'success' => false,
			'message' => translate([
				'text' => 'Unknown error loading staff view',
				'isPublicFacing' => true,
			]),
		];
		$id = $_REQUEST['id'];
		require_once ROOT_DIR . '/RecordDrivers/PalaceProjectRecordDriver.php';
		$recordDriver = new PalaceProjectRecordDriver($id);
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
	function getPreview() {
		$result = [
			'success' => false,
			'message' => 'Unknown error loading preview',
		];
		$id = $_REQUEST['id'];
		require_once ROOT_DIR . '/RecordDrivers/PalaceProjectRecordDriver.php';
		$recordDriver = new PalaceProjectRecordDriver($id);
		if ($recordDriver->isValid()) {
			$linkUrl = $recordDriver->getPreviewUrl();
			if ($linkUrl != null) {
				$result['success'] = true;
				$result['title'] = translate([
					'text' => 'Preview',
					'isPublicFacing' => true,
					'isAdminEnteredData' => true,
				]);
				$sampleUrl = $linkUrl;

//				$palaceProjectDriver = new PalaceProjectDriver();
//				$palaceProjectDriver->incrementStat('numPreviews');

				$result['modalBody'] = "<iframe src='{$sampleUrl}' class='previewFrame'></iframe>";
				$result['modalButtons'] = "<a class='tool btn btn-primary' id='viewPreviewFullSize' href='$sampleUrl' target='_blank'>" . translate([
						'text' => "View Full Screen",
						'isPublicFacing' => true,
					]) . "</a>";
			} else {
				$result['message'] = 'No preview found for this title';
			}
		} else {
			$result['message'] = 'The specified Palace Project Product was not valid';
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	function getLargeCover() {
		global $interface;

		$id = $_REQUEST['id'];
		$interface->assign('id', $id);

		return [
			'title' => translate([
				'text' => 'Cover Image',
				'isPublicFacing' => true,
			]),
			'modalBody' => $interface->fetch("PalaceProject/largeCover.tpl"),
			'modalButtons' => "",
		];
	}
}