<?php
require_once ROOT_DIR . '/Drivers/SIP2Driver.php';

class Evergreen extends SIP2Driver
{

	public function hasNativeReadingHistory()
	{
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function getCheckouts(User $patron)
	{
		// TODO: Implement getCheckouts() method.
	}

	/**
	 * @inheritDoc
	 */
	public function hasFastRenewAll()
	{
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function renewAll(User $patron)
	{
		// TODO: Implement renewAll() method.
	}

	/**
	 * @inheritDoc
	 */
	function renewCheckout(User $patron, $recordId, $itemId = null, $itemIndex = null)
	{
		// TODO: Implement renewCheckout() method.
	}

	/**
	 * @inheritDoc
	 */
	public function getHolds(User $patron)
	{
		// TODO: Implement getHolds() method.
	}

	/**
	 * @inheritDoc
	 */
	function placeHold(User $patron, $recordId, $pickupBranch = null, $cancelDate = null)
	{
		// TODO: Implement placeHold() method.
	}

	/**
	 * @inheritDoc
	 */
	function cancelHold(User $patron, $recordId, $cancelId = null)
	{
		// TODO: Implement cancelHold() method.
	}

	/**
	 * @inheritDoc
	 */
	function placeItemHold(User $patron, $recordId, $itemId, $pickupBranch, $cancelDate = null)
	{
		// TODO: Implement placeItemHold() method.
	}

	function freezeHold(User $patron, $recordId, $itemToFreezeId, $dateToReactivate)
	{
		// TODO: Implement freezeHold() method.
	}

	function thawHold(User $patron, $recordId, $itemToThawId)
	{
		// TODO: Implement thawHold() method.
	}

	function changeHoldPickupLocation(User $patron, $recordId, $itemToUpdateId, $newPickupLocation)
	{
		// TODO: Implement changeHoldPickupLocation() method.
	}

	function updatePatronInfo(User $patron, $canUpdateContactInfo, $fromMasquerade)
	{
		// TODO: Implement updatePatronInfo() method.
	}

	public function getFines(User $patron, $includeMessages = false)
	{
		// TODO: Implement getFines() method.
	}
}