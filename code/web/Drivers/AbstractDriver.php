<?php


abstract class AbstractDriver {
	public abstract function hasNativeReadingHistory(): bool;

	public function performsReadingHistoryUpdatesOfILS() {
		return false;
	}

	public function getReadingHistory(User $patron, $page = 1, $recordsPerPage = -1, $sortOption = "checkedOut") {
		return [
			'historyActive' => false,
			'titles' => [],
			'numTitles' => 0,
		];
	}

	public function doReadingHistoryAction(User $patron, $action, $selectedTitles) {
		return;
	}

	/**
	 * Get Patron Checkouts
	 *
	 * This is responsible for retrieving all checkouts (i.e. checked out items)
	 * by a specific patron.
	 *
	 * @param User $patron The user to load transactions for
	 * @return Checkout[]        Array of the patron's transactions on success
	 * @access public
	 */
	public abstract function getCheckouts(User $patron): array;

	/**
	 * @return boolean true if the driver can renew all titles in a single pass
	 */
	public abstract function hasFastRenewAll(): bool;

	/**
	 * Renew all titles currently checked out to the user
	 *
	 * @param $patron  User
	 * @return mixed
	 */
	public abstract function renewAll(User $patron);

	/**
	 * Renew a single title currently checked out to the user
	 *
	 * @param $patron     User
	 * @param $recordId   string
	 * @param $itemId     string
	 * @param $itemIndex  string
	 * @return mixed
	 */
	abstract function renewCheckout(User $patron, $recordId, $itemId = null, $itemIndex = null);

	public function hasHolds() {
		return true;
	}

	/**
	 * Get Patron Holds
	 *
	 * This is responsible for retrieving all holds for a specific patron.
	 *
	 * @param User $patron The user to load transactions for
	 *
	 * @return array        Array of the patron's holds
	 * @access public
	 */
	public abstract function getHolds(User $patron): array;

	/**
	 * Place Hold
	 *
	 * This is responsible for placing holds
	 *
	 * @param User $patron The User to place a hold for
	 * @param string $recordId The id of the bib record
	 * @param string $pickupBranch The branch where the user wants to pickup the item when available
	 * @param string $cancelDate When the hold should be automatically cancelled
	 * @return  mixed                 True if successful, false if unsuccessful
	 *                                If an error occurs, return a AspenError
	 * @access  public
	 */
	abstract function placeHold(User $patron, $recordId, $pickupBranch = null, $cancelDate = null);

	/**
	 * Cancels a hold for a patron
	 *
	 * @param User $patron The User to cancel the hold for
	 * @param string $recordId The id of the bib record
	 * @param string $cancelId Information about the hold to be cancelled
	 * @param boolean $isIll If the hold is an ILL hold
	 * @return  array
	 */
	abstract function cancelHold(User $patron, $recordId, $cancelId = null, $isIll = false): array;

}