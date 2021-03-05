<?php


class Polaris extends AbstractIlsDriver
{

	public function hasNativeReadingHistory()
	{
		// TODO: Implement hasNativeReadingHistory() method.
	}

	public function getCheckouts(User $user)
	{
		// TODO: Implement getCheckouts() method.
	}

	public function hasFastRenewAll()
	{
		// TODO: Implement hasFastRenewAll() method.
	}

	public function renewAll($patron)
	{
		// TODO: Implement renewAll() method.
	}

	function renewCheckout($patron, $recordId, $itemId = null, $itemIndex = null)
	{
		// TODO: Implement renewCheckout() method.
	}

	public function getHolds($user)
	{
		// TODO: Implement getHolds() method.
	}

	function placeHold($patron, $recordId, $pickupBranch = null, $cancelDate = null)
	{
		// TODO: Implement placeHold() method.
	}

	function cancelHold($patron, $recordId, $cancelId = null)
	{
		// TODO: Implement cancelHold() method.
	}

	public function patronLogin($username, $password, $validatedViaSSO)
	{
		// TODO: Implement patronLogin() method.
	}

	function placeItemHold($patron, $recordId, $itemId, $pickupBranch, $cancelDate = null)
	{
		// TODO: Implement placeItemHold() method.
	}

	function freezeHold($patron, $recordId, $itemToFreezeId, $dateToReactivate)
	{
		// TODO: Implement freezeHold() method.
	}

	function thawHold($patron, $recordId, $itemToThawId)
	{
		// TODO: Implement thawHold() method.
	}

	function changeHoldPickupLocation($patron, $recordId, $itemToUpdateId, $newPickupLocation)
	{
		// TODO: Implement changeHoldPickupLocation() method.
	}

	function updatePatronInfo($patron, $canUpdateContactInfo)
	{
		// TODO: Implement updatePatronInfo() method.
	}

	public function getFines($patron, $includeMessages = false)
	{
		// TODO: Implement getFines() method.
	}
}