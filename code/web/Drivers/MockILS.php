<?php

require_once ROOT_DIR . '/Drivers/AbstractIlsDriver.php';

class MockILS extends AbstractIlsDriver{

	public function hasNativeReadingHistory(): bool {
		// TODO: Implement hasNativeReadingHistory() method.
	}

	public function getCheckouts(User $patron): array {
		// TODO: Implement getCheckouts() method.
	}

	public function hasFastRenewAll(): bool {
		// TODO: Implement hasFastRenewAll() method.
	}

	public function renewAll(User $patron) {
		// TODO: Implement renewAll() method.
	}

	function renewCheckout(User $patron, $recordId, $itemId = null, $itemIndex = null) {
		// TODO: Implement renewCheckout() method.
	}

	public function getHolds(User $patron): array {
		// TODO: Implement getHolds() method.
	}

	function placeHold(User $patron, $recordId, $pickupBranch = null, $cancelDate = null) {
		// TODO: Implement placeHold() method.
	}

	function cancelHold(User $patron, $recordId, $cancelId = null, $isIll = false): array {
		// TODO: Implement cancelHold() method.
	}

	public function patronLogin($username, $password, $validatedViaSSO) {
		if ($username == 'test_user' && $password == 'password') {
			$user = new User();
			$user->source = 'ils';
			$user->firstname = 'Test';
			$user->lastname = 'User';
			return $user;
		}
		return null;
	}

	function placeItemHold(User $patron, $recordId, $itemId, $pickupBranch, $cancelDate = null) {
		// TODO: Implement placeItemHold() method.
	}

	function freezeHold(User $patron, $recordId, $itemToFreezeId, $dateToReactivate): array {
		// TODO: Implement freezeHold() method.
	}

	function thawHold(User $patron, $recordId, $itemToThawId): array {
		// TODO: Implement thawHold() method.
	}

	function changeHoldPickupLocation(User $patron, $recordId, $itemToUpdateId, $newPickupLocation): array {
		// TODO: Implement changeHoldPickupLocation() method.
	}

	function updatePatronInfo(User $patron, $canUpdateContactInfo, $fromMasquerade) {
		// TODO: Implement updatePatronInfo() method.
	}

	public function getFines(User $patron, $includeMessages = false): array {
		// TODO: Implement getFines() method.
	}
}