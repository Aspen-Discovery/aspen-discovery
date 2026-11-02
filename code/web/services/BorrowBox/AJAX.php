<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/JSON_Action.php';

class BorrowBox_AJAX extends JSON_Action {

	private function getValidatedPatron(string $loginMessage, string $permissionMessage): array|User {
		if (!UserAccount::isLoggedIn()) {
			return [
				'result' => false,
				'message' => translate(['text' => $loginMessage, 'isPublicFacing' => true]),
			];
		}
		$patron = UserAccount::getLoggedInUser()->getUserReferredTo($_REQUEST['patronId']);
		if (!$patron) {
			return [
				'result' => false,
				'message' => translate(['text' => $permissionMessage, 'isPublicFacing' => true]),
			];
		}
		return $patron;
	}

	private function getDriver(): BorrowBoxDriver {
		require_once ROOT_DIR . '/Drivers/BorrowBoxDriver.php';
		return new BorrowBoxDriver();
	}

	function placeHold(): array {
		$patron = $this->getValidatedPatron('You must be logged in to place a hold.', 'Sorry, it looks like you don\'t have permissions to place holds for that user.');
		if (is_array($patron)) {
			return $patron;
		}
		return $this->getDriver()->placeHold($patron, $_REQUEST['borrowboxId']);
	}

	function checkOutTitle(): array {
		$patron = $this->getValidatedPatron('You must be logged in to checkout an item.', 'Sorry, it looks like you don\'t have permissions to checkout titles for that user.');
		if (is_array($patron)) {
			return $patron;
		}
		$result = $this->getDriver()->checkOutTitle($patron, $_REQUEST['borrowboxId']);
		if ($result['success']) {
			$result['buttons'] = '<a class="btn btn-primary" href="/MyAccount/CheckedOut" role="button">' . translate([
					'text' => 'View My Check Outs',
					'isPublicFacing' => true,
				]) . '</a>';
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function returnCheckout(): array {
		$patron = $this->getValidatedPatron('You must be logged in to return an item.', 'Sorry, it looks like you don\'t have permissions to return titles for that user.');
		if (is_array($patron)) {
			return $patron;
		}
		return $this->getDriver()->returnCheckout($patron, $_REQUEST['borrowboxId']);
	}

	function renewCheckout(): array {
		$patron = $this->getValidatedPatron('You must be logged in to renew titles.', 'Sorry, it looks like you don\'t have permissions to modify checkouts for that user.');
		if (is_array($patron)) {
			return $patron;
		}
		return $this->getDriver()->renewCheckout($patron, $_REQUEST['borrowboxId']);
	}

	function cancelHold(): array {
		$patron = $this->getValidatedPatron('You must be logged in to cancel holds.', 'Sorry, it looks like you don\'t have permissions to cancel holds for that user.');
		if (is_array($patron)) {
			return $patron;
		}
		return $this->getDriver()->cancelHold($patron, $_REQUEST['borrowboxId']);
	}

	/** @noinspection PhpUnused */
	function getHoldPrompts(): array {
		if (!UserAccount::isLoggedIn()) {
			return [
				'success' => false,
				'message' => translate(['text' => 'You must be logged in to place holds, please login again.', 'isPublicFacing' => true]),
			];
		}
		$user = UserAccount::getLoggedInUser();
		global $interface;
		$id = $_REQUEST['id'];

		$interface->assign('borrowboxId', $id);

		$borrowBoxUsers = $user->getRelatedEcontentUsers('borrowbox');
		$interface->assign('borrowBoxUsers', $borrowBoxUsers);
		if (count($borrowBoxUsers) == 1) {
			$interface->assign('patronId', reset($borrowBoxUsers)->id);
		}

		if (count($borrowBoxUsers) == 0) {
			return [
				'success' => false,
				'message' => translate([
					'text' => 'Your account is not valid for BorrowBox, please contact your local library.',
					'isPublicFacing' => true,
				]),
			];
		} elseif (count($borrowBoxUsers) > 1) {
			return [
				'success' => true,
				'promptNeeded' => true,
				'promptTitle' => translate([
					'text' => 'BorrowBox Hold Options',
					'isPublicFacing' => true,
				]),
				'prompts' => $interface->fetch('BorrowBox/ajax-hold-prompt.tpl'),
				'buttons' => '<button class="btn btn-primary" type="submit" name="submit" onclick="return AspenDiscovery.BorrowBox.processBorrowBoxHoldPrompts();">' . translate([
						'text' => 'Place Hold',
						'isPublicFacing' => true,
					]) . '</button>',
			];
		} else {
			return [
				'success' => true,
				'patronId' => reset($borrowBoxUsers)->id,
				'promptNeeded' => false,
			];
		}
	}

	/** @noinspection PhpUnused */
	function getCheckOutPrompts(): array {
		if (!UserAccount::isLoggedIn()) {
			return [
				'promptNeeded' => true,
				'promptTitle' => translate([
					'text' => 'Error.',
					'isPublicFacing' => true,
				]),
				'prompts' => translate([
					'text' => 'Your session has expired. Please login again to checkout this title.',
					'isPublicFacing' => true,
				]),
				'buttons' => '',
			];
		}
		$user = UserAccount::getLoggedInUser();
		global $interface;
		$id = $_REQUEST['id'];
		$interface->assign('borrowboxId', $id);

		$borrowBoxUsers = $user->getRelatedEcontentUsers('borrowbox');
		$interface->assign('borrowBoxUsers', $borrowBoxUsers);

		if (count($borrowBoxUsers) > 1) {
			return [
				'promptNeeded' => true,
				'promptTitle' => 'BorrowBox Checkout Options',
				'prompts' => $interface->fetch('BorrowBox/ajax-checkout-prompt.tpl'),
				'buttons' => '<input class="btn btn-primary" type="submit" name="submit" value="Checkout Title" onclick="return AspenDiscovery.BorrowBox.processBorrowBoxCheckoutPrompts();">',
			];
		} elseif (count($borrowBoxUsers) == 1) {
			return [
				'patronId' => reset($borrowBoxUsers)->id,
				'promptNeeded' => false,
			];
		} else {
			global $logger;
			$logger->log('No valid BorrowBox account was found to check out a BorrowBox title.', Logger::LOG_ERROR);
			return [
				'promptNeeded' => true,
				'promptTitle' => 'Error',
				'prompts' => translate([
					'text' => 'Your account is not valid for BorrowBox, please contact your local library.',
					'isPublicFacing' => true,
				]),
				'buttons' => '',
			];
		}
	}

	function getStaffView(): array {
		$result = [
			'success' => false,
			'message' => 'Unknown error loading staff view',
		];
		$id = $_REQUEST['id'];
		require_once ROOT_DIR . '/RecordDrivers/BorrowBoxRecordDriver.php';
		$recordDriver = new BorrowBoxRecordDriver($id);
		if ($recordDriver->isValid()) {
			global $interface;
			$interface->assign('recordDriver', $recordDriver);
			$result = [
				'success' => true,
				'staffView' => $interface->fetch($recordDriver->getStaffView()),
			];
		} else {
			$result['message'] = 'Could not find that record';
		}
		return $result;
	}
}
