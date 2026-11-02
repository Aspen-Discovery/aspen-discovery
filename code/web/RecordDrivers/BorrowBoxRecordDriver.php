<?php

require_once ROOT_DIR . '/RecordDrivers/RecordInterface.php';
require_once ROOT_DIR . '/RecordDrivers/GroupedWorkSubDriver.php';

class BorrowBoxRecordDriver extends GroupedWorkSubDriver {
	protected string $id;
	private ?object $borrowBoxProduct = null;
	private ?object $borrowBoxMetaData = null;
	private bool $valid;

	/** @var string[]|null */
	private ?array $isbns = null;

	//$groupedWork and $groupedWorkDriver are defined in GroupedWorkSubDriver

	/**
	 * Constructor.  We build the object using all the data retrieved
	 * from the (Solr) index.  Since we have to
	 * make a search call to find out which record driver to construct,
	 * we will already have this data available, so we might as well
	 * just pass it into the constructor.
	 *
	 * @param string|null $recordId The id of the record within BorrowBox.
	 * @param GroupedWork|null $groupedWork ;
	 * @access  public
	 */
	public function __construct($recordId = null, ?GroupedWork $groupedWork = null) {
		if (is_string($recordId)) {
			$this->id = $recordId;
			require_once ROOT_DIR . '/sys/BorrowBox/BorrowBoxAPIProduct.php';
			$borrowBoxProduct = new BorrowBoxAPIProduct();
			$borrowBoxProduct->borrowboxId = $recordId;
			if ($borrowBoxProduct->find(true)) {
				$this->borrowBoxProduct = $borrowBoxProduct;
				$this->valid = true;
			} else {
				$this->valid = false;
			}
		} else {
			$this->valid = false;
		}
		if ($this->valid) {
			parent::__construct($groupedWork);
		}
	}

	public function getIdWithSource(): string {
		return 'borrowbox:' . $this->id;
	}

	public function getModule(): string {
		return 'BorrowBox';
	}

	public function getRecordType(): string {
		return 'borrowbox';
	}

	/**
	 * Load the grouped work that this record is connected to.
	 */
	public function loadGroupedWork(): void {
		require_once ROOT_DIR . '/sys/Grouping/GroupedWorkPrimaryIdentifier.php';
		require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
		$groupedWork = new GroupedWork();
		$query = "SELECT grouped_work.* FROM grouped_work INNER JOIN grouped_work_primary_identifiers ON grouped_work.id = grouped_work_id WHERE type='borrowbox' AND identifier = '" . $this->getUniqueID() . "'";
		$groupedWork->query($query);

		if ($groupedWork->getNumResults() == 1) {
			$groupedWork->fetch();
			$this->groupedWork = clone $groupedWork;
		}
	}

	public function getPermanentId(): ?string {
		return $this->getGroupedWorkId();
	}

	public function getGroupedWorkId(): ?string {
		if ($this->groupedWork == null) {
			$this->loadGroupedWork();
		}
		if ($this->groupedWork) {
			return $this->groupedWork->permanent_id;
		} else {
			return null;
		}
	}

	public function isValid(): bool {
		return $this->valid;
	}

	function getStatusSummary(): array {
		$availabilityInfo = $this->getAvailabilityInformation();

		// BorrowBox uses status-based availability rather than copy counts.
		// Determine availability from the availabilityStatus field.
		$hasAvailable = false;
		foreach ($availabilityInfo as $availability) {
			if (strtoupper($availability->availabilityStatus ?? '') === 'AVAILABLE') {
				$hasAvailable = true;
			}
		}

		//Load status summary
		$statusSummary = [];
		$statusSummary['recordId'] = $this->id;
		$statusSummary['totalCopies'] = count($availabilityInfo);
		$statusSummary['accessType'] = 'borrowbox';
		$statusSummary['alwaysAvailable'] = false;
		$statusSummary['isBorrowBox'] = true;
		$statusSummary['availableCopies'] = $hasAvailable ? 1 : 0;

		// Map BorrowBox availability statuses
		if ($this->borrowBoxProduct !== null && isset($this->borrowBoxProduct->rawStatus)) {
			$rawStatus = $this->borrowBoxProduct->rawStatus;
			switch ($rawStatus) {
				case 'AVAILABLE':
					$statusSummary['status'] = 'Available from BorrowBox';
					$statusSummary['available'] = true;
					$statusSummary['class'] = 'available';
					break;
				case 'ON_LOAN':
					$statusSummary['status'] = 'Checked Out';
					$statusSummary['available'] = false;
					$statusSummary['class'] = 'checkedOut';
					break;
				case 'NEW':
					$statusSummary['status'] = 'Coming Soon';
					$statusSummary['available'] = false;
					$statusSummary['class'] = 'comingSoon';
					break;
				case 'UNAVAILABLE':
					$statusSummary['status'] = 'Unavailable';
					$statusSummary['available'] = false;
					$statusSummary['class'] = 'unavailable';
					break;
				default:
					if ($hasAvailable) {
						$statusSummary['status'] = 'Available from BorrowBox';
						$statusSummary['available'] = true;
						$statusSummary['class'] = 'available';
					} else {
						$statusSummary['status'] = 'Checked Out';
						$statusSummary['available'] = false;
						$statusSummary['class'] = 'checkedOut';
					}
					break;
			}
		} else {
			if ($hasAvailable) {
				$statusSummary['status'] = 'Available from BorrowBox';
				$statusSummary['available'] = true;
				$statusSummary['class'] = 'available';
			} else {
				$statusSummary['status'] = 'Checked Out';
				$statusSummary['available'] = false;
				$statusSummary['class'] = 'checkedOut';
			}
		}

		//Determine which buttons to show
		$statusSummary['holdQueueLength'] = 0;
		$statusSummary['numHolds'] = 0;
		$statusSummary['showPlaceHold'] = !($statusSummary['available'] ?? false);
		$statusSummary['showCheckout'] = $statusSummary['available'] ?? false;
		$statusSummary['showAddToWishlist'] = false;
		$statusSummary['showAccessOnline'] = false;

		return $statusSummary;
	}

	/** @var BorrowBoxAPIProductAvailability[]|null */
	private ?array $availability = null;

	/**
	 * @return BorrowBoxAPIProductAvailability[]
	 */
	function getAvailabilityInformation(): array {
		if ($this->availability == null) {
			require_once ROOT_DIR . '/sys/BorrowBox/BorrowBoxAPIProductAvailability.php';
			$this->availability = [];
			if ($this->borrowBoxProduct !== null) {
				$availabilityObj = new BorrowBoxAPIProductAvailability();
				$availabilityObj->productId = $this->borrowBoxProduct->id;
				$availabilityObj->find();
				while ($availabilityObj->fetch()) {
					$this->availability[] = clone $availabilityObj;
				}
			}
		}
		return $this->availability;
	}

	/**
	 * Get the items (formats) for this BorrowBox product.
	 * BorrowBox products typically have a single format per product,
	 * so this is simpler than OverDrive.
	 *
	 * @return array
	 */
	public function getItems(): array {
		// BorrowBox products don't have multiple formats per product like OverDrive.
		// The format is determined at the product level.
		$items = [];
		$hasProduct = $this->valid && $this->borrowBoxProduct !== null;
		if ($hasProduct) {
			$items[] = $this->borrowBoxProduct;
		}
		return $items;
	}

	/**
	 * Return the unique identifier of this record within the Solr index;
	 * useful for retrieving additional information (like tags and user
	 * comments) from the external MySQL database.
	 *
	 * @access  public
	 * @return  string              Unique identifier.
	 */
	public function getUniqueID(): string {
		return $this->id;
	}

	public function getGroupedWorkDriver(): ?GroupedWorkDriver {
		$permanentId = $this->getPermanentId();
		if ($permanentId == null) {
			return null;
		} else {
			require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
			if ($this->groupedWorkDriver == null) {
				$this->groupedWorkDriver = new GroupedWorkDriver($this->getPermanentId());
			}
			return $this->groupedWorkDriver;
		}
	}

	protected ?array $_actions = null;

	public function getRecordActions($relatedRecord, $variationId, $isAvailable, $isHoldable, $volumeData = null): array {
		if ($this->_actions === null) {
			if ($relatedRecord == null) {
				$relatedRecord = $this->getRelatedRecord();
			}
			$this->_actions = [];

			if (UserAccount::isLoggedIn()) {
				$activeUser = UserAccount::getActiveUserObj();
				if ($activeUser->isValidForEContentSource('borrowbox')) {
					$this->_actions = array_merge($this->_actions, $activeUser->getCirculatedRecordActionsWithLazyLoading('borrowbox', $this->id));
				}
				$loadDefaultActions = count($this->_actions) == 0;
			} else {
				$activeUser = null;
				$loadDefaultActions = true;
			}

			if ($loadDefaultActions) {
				global $offlineMode;
				global $loginAllowedWhileOffline;
				if (!$offlineMode || $loginAllowedWhileOffline) {
					if ($isAvailable) {
						$this->_actions[] = [
							'title' => translate([
								'text' => 'Borrow with BorrowBox',
								'isPublicFacing' => true,
							]),
							'onclick' => "return AspenDiscovery.BorrowBox.checkOutTitle('$this->id', this);",
							'requireLogin' => false,
							'type' => 'borrowbox_checkout',
						];
					} else {
						$this->_actions[] = [
							'title' => translate([
								'text' => 'Place Hold with BorrowBox',
								'isPublicFacing' => true,
							]),
							'onclick' => "return AspenDiscovery.BorrowBox.placeHold('$this->id', this);",
							'requireLogin' => false,
							'type' => 'borrowbox_hold',
						];
					}
				}
			}
		}
		return $this->_actions;
	}

	function getNumHolds(): int {
		// BorrowBox API does not expose hold queue counts
		return 0;
	}
}
