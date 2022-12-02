<?php

class LDRecordOffer {
	/** @var Grouping_Record */
	private $relatedRecord;

	public function __construct($record) {
		$this->relatedRecord = $record;
	}

	public function getOffers() {
		$offers = [];
		foreach ($this->relatedRecord->getItemSummary() as $itemData) {
			if ($itemData['isLibraryItem'] || $itemData['isEContent']) {
				$offerData = [
					"availability" => $this->getOfferAvailability($itemData),
					'availableDeliveryMethod' => $this->getDeliveryMethod(),
					"itemOffered" => [
						'@type' => 'CreativeWork',
						'@id' => $this->getOfferLinkUrl(),
						//URL to the record
					],
					"offeredBy" => $this->getLibraryUrl(),
					//URL to the library that owns the item
					"price" => '0',
					"inventoryLevel" => $itemData['availableCopies'],
				];
				$locationCode = $itemData['locationCode'];
				$subLocation = $itemData['subLocation'];
				if (strlen($locationCode) > 0) {
					$offerData['availableAtOrFrom'] = $this->getBranchUrl($locationCode, $subLocation);
				}
				$offers[] = $offerData;
			}
		}

		return $offers;
	}

	public function getWorkType() {
		return $this->relatedRecord->getSchemaOrgType();
	}

	function getOfferLinkUrl() {
		global $configArray;
		return $configArray['Site']['url'] . $this->relatedRecord->getUrl();
	}

	function getLibraryUrl() {
		global $configArray;
		$offerBy = [];
		global $library;
		$offerBy[] = [
			"@type" => "Library",
			"@id" => $configArray['Site']['url'] . "/Library/{$library->libraryId}/System",
			"name" => $library->displayName,
		];
		return $offerBy;
	}

	function getOfferAvailability($itemData) {
		if ($itemData['inLibraryUseOnly']) {
			return 'InStoreOnly';
		}
		if ($this->relatedRecord->getStatusInformation()->isAvailableOnline()) {
			return 'OnlineOnly';
		}
		if ($itemData['availableCopies'] > 0) {
			return 'InStock';
		}

		if ($itemData['status'] != '') {
			switch (strtolower($itemData['status'])) {
				case 'on order':
				case 'in processing':
					$availability = 'PreOrder';
					break;
				case 'currently unavailable':
					$availability = 'Discontinued';
					break;
				default:
					$availability = 'InStock';
			}
			return $availability;
		}
		return "";
	}

	function getBranchUrl($locationCode, $subLocation) {
		global $configArray;
		global $library;
		$locations = new Location();
		$locations->libraryId = $library->libraryId;
		$escapedCode = $locations->escape($locationCode);
		$locations->whereAdd("LEFT($escapedCode, LENGTH(code)) = code");
		if ($subLocation) {
			$locations->subLocation = $subLocation;
		}
		$locations->orderBy('isMainBranch DESC, displayName'); // List Main Branches first, then sort by name
		$locations->find();
		$subLocations = [];
		while ($locations->fetch()) {

			$subLocations[] = [
				'@type' => 'Place',
				'name' => $locations->displayName,
				'@id' => $configArray['Site']['url'] . "/Library/{$locations->locationId}/Branch",

			];
		}
		return $subLocations;

	}

	function getDeliveryMethod() {
		if ($this->relatedRecord->isEContent()) {
			return 'DeliveryModeDirectDownload';
		} else {
			return 'DeliveryModePickUp';
		}

	}
}