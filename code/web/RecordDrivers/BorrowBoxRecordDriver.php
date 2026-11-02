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
}
