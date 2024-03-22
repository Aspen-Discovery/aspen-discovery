<?php

require_once ROOT_DIR . '/Drivers/AbstractIlsDriver.php';

class MockILSDriver extends AbstractDriver{

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
}