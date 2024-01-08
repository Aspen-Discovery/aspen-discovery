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

	/** @noinspection PhpUnused */
	function getCheckOutPrompts() {
		$user = UserAccount::getLoggedInUser();
		global $interface;
		$id = $_REQUEST['id'];
		$interface->assign('id', $id);
		$interface->assign('checkoutType', 'book');

		$usersWithPalaceProjectAccess = $this->getPalaceProjectUsers($user);

		if (count($usersWithPalaceProjectAccess) > 1) {
			$promptTitle = translate([
				'text' => 'Palace Project Checkout Options',
				'isPublicFacing' => true,
			]);
			return [
				'promptNeeded' => true,
				'promptTitle' => $promptTitle,
				'prompts' => $interface->fetch('PalaceProject/ajax-checkout-prompt.tpl'),
				'buttons' => '<input class="btn btn-primary" type="submit" name="submit" value="' . translate([
						'text' => 'Checkout Title',
						'inAttribute' => true,
						'isPublicFacing' => true,
					]) . '" onclick="return AspenDiscovery.PalaceProject.processCheckoutPrompts();">',
			];
		} elseif (count($usersWithPalaceProjectAccess) == 1) {
			return [
				'patronId' => reset($usersWithPalaceProjectAccess)->id,
				'promptNeeded' => false,
			];
		} else {
			// No Palace Project Account Found, let the user create one if they want
			return [
				'promptNeeded' => true,
				'promptTitle' => translate([
					'Error',
					'isPublicFacing' => true,
				]),
				'prompts' => translate([
					'Your account is not valid for Palace Project, please contact your local library.',
					'isPublicFacing' => true,
				]),
				'buttons' => '',
			];
		}
	}

	/**
	 * @param User $user
	 * @return User[]
	 */
	private function getPalaceProjectUsers(User $user) {
		global $interface;
		$users = $user->getRelatedEcontentUsers('palace_project');
		$usersWithPalaceProjectAccess = [];
		foreach ($users as $tmpUser) {
			$usersWithPalaceProjectAccess[] = $tmpUser;
		}
		$interface->assign('users', $usersWithPalaceProjectAccess);
		return $usersWithPalaceProjectAccess;
	}

	function checkOutTitle() {
		$user = UserAccount::getLoggedInUser();
		$id = $_REQUEST['id'];
		if ($user) {
			$patronId = $_REQUEST['patronId'];
			$patron = $user->getUserReferredTo($patronId);
			if ($patron) {
				require_once ROOT_DIR . '/Drivers/PalaceProjectDriver.php';
				$driver = new PalaceProjectDriver();
				$result = $driver->checkoutTitle($patron, $id);
				//$logger->log("Checkout result = $result", Logger::LOG_NOTICE);
				if ($result['success']) {
					/** @noinspection HtmlUnknownTarget */
					$result['title'] = translate([
						'text' => "Title Checked Out Successfully",
						'isPublicFacing' => true,
					]);
					$result['buttons'] = '<a class="btn btn-primary" href="/MyAccount/CheckedOut" role="button">' . translate([
							'text' => 'View My Check Outs',
							'isPublicFacing' => true,
						]) . '</a>';
				} else {
					$result['title'] = translate([
						'text' => "Error Checking Out Title",
						'isPublicFacing' => true,
					]);
				}
				return $result;
			} else {
				return [
					'result' => false,
					'title' => translate([
						'text' => "Error Checking Out Title",
						'isPublicFacing' => true,
					]),
					'message' => translate([
						'text' => 'Sorry, it looks like you don\'t have permissions to checkout titles for that user.',
						'isPublicFacing' => true,
					]),
				];
			}
		} else {
			return [
				'result' => false,
				'title' => translate([
					'text' => "Error Checking Out Title",
					'isPublicFacing' => true,
				]),
				'message' => translate([
					'text' => 'You must be logged in to checkout an item.',
					'isPublicFacing' => true,
				]),
			];
		}
	}
}