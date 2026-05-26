<?php

require_once ROOT_DIR . '/Drivers/AbstractIlsDriver.php';

class MockILS extends AbstractIlsDriver{

	public function hasNativeReadingHistory(): bool {
		return false;
	}

	public function getCheckouts(User $patron, array $options = []): array {
		return [];
	}

	public function hasFastRenewAll(): bool {
		return false;
	}

	public function renewAll(User $patron) : array {
		return [
			'success' => 'false',
			'message' => 'Renew All not implemented for MockILS'
		];
	}

	function renewCheckout(User $patron, string $recordId, ?string $itemId = null, ?string $itemIndex = null) : array {
		return [
			'success' => 'false',
			'message' => 'Renew Checkout not implemented for MockILS'
		];
	}

	public function getHolds(User $patron): array {
		return [
			'available' => [],
			'unavailable' => []
		];
	}

	function placeHold(User $patron, $recordId, $pickupBranch = null, $cancelDate = null) : array {
		return [
			'success' => 'false',
			'message' => 'Place Hold not implemented for MockILS'
		];
	}

	function cancelHold(User $patron, string $recordId, ?string $cancelId = null, ?bool $isIll = false): array {
		return [
			'success' => 'false',
			'message' => 'Cancel Hold not implemented for MockILS'
		];
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

	function placeItemHold(User $patron, string $recordId, string $itemId, string $pickupBranch, ?string $cancelDate = null, ?string $pickupSublocation = null) : array {
		return [
			'success' => 'false',
			'message' => 'Place Item Hold not implemented for MockILS'
		];
	}

	function freezeHold(User $patron, string $recordId, ?string $itemToFreezeId, ?string $dateToReactivate): array {
		return [
			'success' => 'false',
			'message' => 'Freeze Hold not implemented for MockILS'
		];
	}

	function thawHold(User $patron, string $recordId, string $itemToThawId): array {
		return [
			'success' => 'false',
			'message' => 'Thaw Hold not implemented for MockILS'
		];
	}

	function changeHoldPickupLocation(User $patron, string $holdId, string $newPickupLocation, ?string $newPickupSublocation = null): array {
		return [
			'success' => 'false',
			'message' => 'Change Hold not Pickup Location not implemented for MockILS'
		];
	}

	function updatePatronInfo(User $patron, $canUpdateContactInfo, $fromMasquerade) {
		return [
			'success' => 'false',
			'message' => 'Update Patron Info not implemented for MockILS'
		];
	}

	public function getFines(User $patron, $includeMessages = false): array {
		return [
			'success' => 'false',
			'message' => 'Get Fines not implemented for MockILS'
		];
	}
}
