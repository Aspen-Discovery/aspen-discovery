<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class BlockPatronAccountLink extends DataObject {

	public $__table = 'user_link_blocks';
	public $id;
	public $primaryAccountId;
	public $blockedLinkAccountId; // A specific account primaryAccountId will not be linked to.
	public $blockLinking;         // Indicates primaryAccountId will not be linked to any other accounts.

	// Additional Info Not stored in table
	public $_primaryAccountBarCode;      //  The info the Admin user will see & input
	public $_blockedAccountBarCode;      //  The info the Admin user will see & input

	/**
	 * Override the fetch functionality to fetch Account BarCodes
	 *
	 * @param bool $includeBarCodes short-circuit the fetching of barcodes when not needed.
	 * @return bool
	 * @see DB/DB_DataObject::fetch()
	 */
	function fetch($includeBarCodes = true) {
		$return = parent::fetch();
		if (!is_null($return) & $includeBarCodes) {
			// Default values (clear out any previous values
			$this->_blockedAccountBarCode = null;
			$this->_primaryAccountBarCode = null;

			$barcode = $this->getBarcode();
			$user = new User();
			if ($user->get($this->primaryAccountId)) {
				$this->_primaryAccountBarCode = $user->$barcode;
			}
			if ($this->blockedLinkAccountId) {
				$user = new User();
				if ($user->get($this->blockedLinkAccountId)) {
					$this->_blockedAccountBarCode = $user->$barcode;
				}
			}
		}
		return $return;
	}

	/**
	 * Override the update functionality to store account ids rather than barcodes
	 *
	 * @see DB/DB_DataObject::update()
	 */
	public function update($context = '') {
		$this->getAccountIds();
		if (!$this->primaryAccountId) {
			$this->setLastError("Could not find a user for the blocked barcode that was provided");
			return false;
		}  // require a primary account id
		if (!$this->blockedLinkAccountId && !$this->blockLinking) {
			$this->setLastError("Could not find a user for the non accessible barcode that was provided");
			return false;
		} // require at least one of these
		return parent::update();
	}

	/**
	 * Override the insert functionality to store account ids rather than barcodes
	 *
	 * @see DB/DB_DataObject::insert()
	 */
	public function insert($context = '') {
		$this->getAccountIds();
		if (!$this->primaryAccountId) {
			$this->setLastError("Could not find a user for the blocked barcode that was provided");
			return false;
		}  // require a primary account id
		if (!$this->blockedLinkAccountId && !$this->blockLinking) {
			$this->setLastError("Could not find a user for the non accessible barcode that was provided");
			return false;
		} // require at least one of these
		return parent::insert();
	}

	private function getAccountIds() {
		// Get Account Ids for the barcodes
		$barcode = $this->getBarcode();
		if ($this->_primaryAccountBarCode) {
			$user = new User();
			if ($user->get($barcode, $this->_primaryAccountBarCode)) {
				$this->primaryAccountId = $user->id;
			}
		}
		if ($this->_blockedAccountBarCode) {
			$user = new User();
			if ($user->get($barcode, $this->_blockedAccountBarCode)) {
				$this->blockedLinkAccountId = $user->id;
			}
		}
	}

	private function getBarcode() {
		global $configArray;
		return ($configArray['Catalog']['barcodeProperty'] == 'cat_username') ? 'cat_username' : 'cat_password';
	}

	static function getObjectStructure($context = ''): array {
		return [
			[
				'property' => 'id',
				'type' => 'hidden',
				'label' => 'Id',
				'description' => 'The unique id of the blocking row in the database',
				'storeDb' => true,
				'primaryKey' => true,
			],
			[
				'property' => '_primaryAccountBarCode',
				'type' => 'text',
//				'size' => 36,
//				'maxLength' => 36,
				'label' => 'The following blocked barcode will not have access to the account below.',
				'description' => 'The account the blocking settings will be applied to.',
				'storeDb' => true,
//				'showDescription' => true,
				'required' => true,
			],
			[
				'property' => '_blockedAccountBarCode',
				'type' => 'text',
//				'size' => 36,
//				'maxLength' => 36,
				'label' => 'The following barcode will not be accessible by the blocked barcode above.',
				'description' => '',
//				'showDescription' => true,
				'storeDb' => true,
//				'required' => true,
			],
			[
				'property' => 'blockLinking',
				'type' => 'checkbox',
				'label' => 'Check this box to prevent the blocked barcode from accessing ANY linked accounts.',
				'description' => 'Prevent the blocked barcode from linking to any account.',
//				'showDescription' => true,
				'storeDb' => true,
			],
		];
	}

	public function okToExport(array $selectedFilters): bool {
		return true;
	}
}