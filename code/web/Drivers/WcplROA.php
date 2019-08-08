<?php

require_once ROOT_DIR . '/Drivers/HorizonROA.php';
class WcplROA extends HorizonROA{
	/**
	 * Get Patron Checkouts
	 *
	 * This is responsible for retrieving all checkouts (i.e. checked out items)
	 * by a specific patron.
	 *
	 * @param User $user The user to load transactions for
	 * @return array        Array of the patron's transactions on success
	 * @access public
	 */
	public function getCheckouts(User $user)
	{
		// TODO: Implement getCheckouts() method.
	}

	/**
	 * Get Patron Holds
	 *
	 * This is responsible for retrieving all holds for a specific patron.
	 *
	 * @param User $user The user to load transactions for
	 *
	 * @return array        Array of the patron's holds
	 * @access public
	 */
	public function getHolds($user)
	{
		// TODO: Implement getHolds() method.
	}

	/**
	 * Renew a single title currently checked out to the user
	 *
	 * @param $patron     User
	 * @param $recordId   string
	 * @param $itemId     string
	 * @param $itemIndex  string
	 * @return mixed
	 */
	function renewCheckout($patron, $recordId, $itemId = null, $itemIndex = null)
	{
		// TODO: Implement renewCheckout() method.
	}

	public function canRenew($itemType)
	{
		if (in_array($itemType, array('BKLCK', 'PBLCK'))) {
			return false;
		}
		return true;
	}
}