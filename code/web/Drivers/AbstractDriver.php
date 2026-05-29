<?php


abstract class AbstractDriver {
	public abstract function hasNativeReadingHistory(): bool;

	public function canLoadReadingHistoryInMasqueradeMode() : bool {
		return true;
	}

	public function performsReadingHistoryUpdatesOfILS() : bool {
		return false;
	}

	public function getReadingHistory(User $patron): array {
		return [
			'historyActive' => false,
			'titles' => [],
			'numTitles' => 0,
		];
	}

	public function doReadingHistoryAction(User $patron, string $action, array $selectedTitles): ?array {
		return null;
	}

	/**
	 * Get Patron Checkouts
	 *
	 * This is responsible for retrieving all checkouts (i.e. checked out items)
	 * by a specific patron.
	 *
	 * @param User $patron       The user to load transactions for
	 * @param array $option      Additional options, currently used for Koha/isNightlyUpdate
	 * @return Checkout[]        Array of the patron's transactions on success
	 * @access public
	 */
	public abstract function getCheckouts(User $patron, array $options = []): array;

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
	public abstract function renewAll(User $patron) : array ;

	/**
	 * Renew a single title currently checked out to the user
	 *
	 * @param $patron     User
	 * @param $recordId   string
	 * @param $itemId     ?string
	 * @param $itemIndex  ?string
	 * @return mixed
	 */
	abstract function renewCheckout(User $patron, string $recordId, ?string $itemId = null, ?string $itemIndex = null) : array ;

	public function isBlockedFromIllRequests(User $user) {
		return false;
	}

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
	 * This is responsible for placing holds.
	 *   For ILS Drivers, this function should always be called through the User object which takes
	 *     care of updating account summary cache etc.
	 *   For eContent, this function is called directly and each driver is reponsible for updating
	 *     the account summary cache
	 *
	 * @param User $patron The User to place a hold for
	 * @param string $recordId The id of the bib record
	 * @param string $pickupBranch The branch where the user wants to pick up the item when available
	 * @param string $cancelDate When the hold should be automatically cancelled
	 * @return  array results of the hold
	 * @access  public
	 */
	abstract function placeHold(User $patron, $recordId, $pickupBranch = null, $cancelDate = null) : array ;

	/**
	 * Cancels a hold for a patron.
	 *   For ILS Drivers, this function should always be called through the User object which passes
	 *     control to catalog connection which takes care of updating account summary cache etc.
	 *   For eContent, this function is called directly and each driver is reponsible for updating
	 *     the account summary cache
	 *
	 * @param User $patron The User to cancel the hold for
	 * @param string $recordId The id of the bib record
	 * @param ?string $cancelId Information about the hold to be cancelled
	 * @param boolean $isIll If the hold is an ILL hold
	 * @return  array
	 */
	abstract function cancelHold(User $patron, string $recordId, ?string $cancelId = null, ?bool $isIll = false): array;

	public function getHoldByCancelId(array $holds, string $recordId, ?string $cancelId = null) : ?Hold {
		foreach ($holds as $holdSection) {
			foreach ($holdSection as $hold) {
				if (is_null($cancelId)) {
					if ($hold->recordId == $recordId) {
						return $hold;
					}
				} else {
					if ($hold->cancelId == $cancelId) {
						return $hold;
					}
				}
			}
		}
		return null;
	}

	public function getHoldBySourceId(array $holds, string $sourceId) : ?Hold {
		/** @var Hold $hold */
		foreach ($holds as $holdSection) {
			foreach ($holdSection as $hold) {
				if ($hold->sourceId == $sourceId) {
					return $hold;
				}elseif ($hold->recordId == $sourceId) {
					return $hold;
				}
			}
		}
		return null;
	}

	public function updateCachesForCancelledHold(User $patron, Hold $hold, string $source) : void {
		$accountProfile = $patron->getCachedAccountSummary($source);
		if ($hold->available) {
			$accountProfile->decrementAvailableHolds();
		}else{
			$accountProfile->decrementUnavailableHolds();
		}
		$hold->delete();
		if ($patron->getHomeLibrary()->showCancelledHolds) {
			$accountProfile->markHoldsStale();
		}
	}

	public function updateCachedHoldsBasedOnActiveHolds(array $cachedHolds, array $activeHolds, AccountSummary $accountSummary) : array {
		//Restructure the arrays so we can loop through them easier
		$allCachedHoldsWithoutSections = array_merge(array_values($cachedHolds['available']), array_values($cachedHolds['unavailable']));
		if (isset($cachedHolds['cancelled'])) {
			$allCachedHoldsWithoutSections = array_merge($allCachedHoldsWithoutSections, array_values($cachedHolds['cancelled']));
		}
		$allActiveHoldsWithoutSections = array_merge(array_values($activeHolds['available']), array_values($activeHolds['unavailable']));
		if (isset($activeHolds['cancelled'])) {
			$allActiveHoldsWithoutSections = array_merge($allActiveHoldsWithoutSections, array_values($activeHolds['cancelled']));
		}

		/** @var Hold $activeHold */
		foreach ($allActiveHoldsWithoutSections as $activeHold) {
			$matchingHold = null;
			$index = -1;
			/** @var Hold $cachedHold */
			foreach ($allCachedHoldsWithoutSections as $index => $cachedHold) {
				if (!empty($cachedHold->cancelId) && !empty($activeHold->cancelId)) {
					if ($cachedHold->cancelId == $activeHold->cancelId) {
						$matchingHold = $cachedHold;
						break;
					}
				}else{
					if ($cachedHold->sourceId == $activeHold->sourceId) {
						$matchingHold = $cachedHold;
						break;
					}
				}
			}

			if ($matchingHold != null) {
				//The hold is already in the cache, update the database
				$activeHold->id = $matchingHold->id;
				$activeHold->update();
				unset($allCachedHoldsWithoutSections[$index]);
			}else{
				//The hold is not in the cache, save it
				if (is_null($activeHold->sourceId)) {
					$activeHold->sourceId = '';
				}
				if (is_null($activeHold->recordId)) {
					$activeHold->recordId = '';
				}
				$activeHold->insert();
			}
		}
		//Delete any cached holds that no longer exist
		foreach ($allCachedHoldsWithoutSections as $cachedHold) {
			$cachedHold->delete();
		}

		$accountSummary->clearHoldsStale();
		return $activeHolds;
	}

	public function updateCachedCheckoutsBasedOnActiveCheckouts(array $cachedCheckouts, array $activeCheckouts, AccountSummary $accountSummary) : array {
		/** @var Checkout $activeCheckout */
		foreach ($activeCheckouts as $activeCheckout) {
			$matchingCheckout = null;
			$index = -1;
			/** @var Checkout $cachedCheckout */
			foreach ($cachedCheckouts as $index => $cachedCheckout) {
				if ($cachedCheckout->sourceId == $activeCheckout->sourceId) {
					$matchingCheckout = $cachedCheckout;
					break;
				}
			}

			if ($matchingCheckout != null) {
				//The checkout is already in the cache, update the database
				$activeCheckout->id = $matchingCheckout->id;
				$activeCheckout->update();
				unset($cachedCheckouts[$index]);
			}else{
				//The checkout is not in the cache, save it
				if (is_null($activeCheckout->sourceId)) {
					$activeCheckout->sourceId = '';
				}
				$activeCheckout->insert();
			}
		}
		//Delete any cached checkout that no longer exist
		foreach ($cachedCheckouts as $cachedCheckout) {
			$cachedCheckout->delete();
		}

		$accountSummary->clearCheckoutsStale();
		return $activeCheckouts;
	}
}
