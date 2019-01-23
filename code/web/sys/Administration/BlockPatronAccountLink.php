<?php

/**
 * Table Definition for Blocking of Patron Account Linking
 *
 * @category Pika
 * @author: Pascal Brammeier
 * Date: 7/30/2015
 *
 */

require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';

class BlockPatronAccountLink extends DB_DataObject
{

	public $__table = 'user_link_blocks';
	public $id;
	public $primaryAccountId;
	public $blockedLinkAccountId; // A specific account primaryAccountId will not be linked to.
	public $blockLinking;         // Indicates primaryAccountId will not be linked to any other accounts.

	// Additional Info Not stored in table

	public $primaryAccountBarCode;      //  The info the Admin user will see & input
	public $primaryAccountName; // TODO
	public $blockedAccountBarCode;      //  The info the Admin user will see & input
	public $blockedAccountName; // TODO

	/**
	 * Override the fetch functionality to fetch Account BarCodes
	 *
	 * @see DB/DB_DataObject::fetch()
	 * @param bool $includeBarCodes  short-circuit the fetching of barcodes when not needed.
	 * @return bool
	 */
	function fetch($includeBarCodes = true){
		$return = parent::fetch();
		if ($return & $includeBarCodes) {
			// Default values (clear out any previous values
			$this->blockedAccountBarCode = null;
			$this->primaryAccountBarCode = null;

			$barcode = $this->getBarcode();
			$user = new User();
			if($user->get($this->primaryAccountId)) {
				$this->primaryAccountBarCode = $user->$barcode;
			}
			if ($this->blockedLinkAccountId) {
				$user = new User();
				if ($user->get($this->blockedLinkAccountId)) {
					$this->blockedAccountBarCode = $user->$barcode;
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
	public function update($dataObject = false){
		$this->getAccountIds();
		if (!$this->primaryAccountId) return false;  // require a primary account id
		if (!$this->blockedLinkAccountId && !$this->blockLinking) return false; // require at least one of these
		return parent::update($dataObject);
	}

	/**
	 * Override the insert functionality to store account ids rather than barcodes
	 *
	 * @see DB/DB_DataObject::insert()
	 */
	public function insert(){
		$this->getAccountIds();
		if (!$this->primaryAccountId) return false;  // require a primary account id
		if (!$this->blockedLinkAccountId && !$this->blockLinking) return false; // require at least one of these
		return parent::insert();
	}

	private function getAccountIds(){
		// Get Account Ids for the barcodes
		$barcode = $this->getBarcode();
		if ($this->primaryAccountBarCode) {
			$user = new User();
			if ($user->get($barcode, $this->primaryAccountBarCode)) {
				$this->primaryAccountId = $user->id;
			}
		}
		if ($this->blockedAccountBarCode) {
			$user = new User();
			if ($user->get($barcode, $this->blockedAccountBarCode)) {
				$this->blockedLinkAccountId = $user->id;
			}
		}
	}

	private function getBarcode(){
	global $configArray;
	return ($configArray['Catalog']['barcodeProperty'] == 'cat_username') ? 'cat_username' : 'cat_password';
}

	static function getObjectStructure()
	{
		$structure = array(
			array(
				'property' => 'id',
				'type' => 'hidden',
				'label' => 'Id',
				'description' => 'The unique id of the blocking row in the database',
				'storeDb' => true,
				'primaryKey' => true,
			),
			array(
				'property' => 'primaryAccountBarCode',
				'type' => 'text',
//				'size' => 36,
//				'maxLength' => 36,
				'label' => 'The following blocked barcode will not have access to the account below.',
				'description' => 'The account the blocking settings will be applied to.',
				'storeDb' => true,
//				'showDescription' => true,
				'required' => true,
			),
			array(
				'property' => 'blockedAccountBarCode',
				'type' => 'text',
//				'size' => 36,
//				'maxLength' => 36,
				'label' => 'The following barcode will not be accessible by the blocked barcode above.',
				'description' => '',
//				'showDescription' => true,
				'storeDb' => true,
//				'required' => true,
			),
			array(
				'property' => 'blockLinking',
				'type' => 'checkbox',
				'label' => 'Check this box to prevent the blocked barcode from accessing ANY linked accounts.',
				'description' => 'Prevent the blocked barcode from linking to any account.',
//				'showDescription' => true,
				'storeDb' => true,
			),


		);
		return $structure;
	}
}