<?php

class OverDriveAPIProductMetaData extends DataObject {
	public $__table = 'overdrive_api_product_metadata';   // table name

	public $id;
	public $productId;
	public $checksum;
	public $sortTitle;
	public $publisher;
	public $publishDate;
	public $isPublicDomain;
	public $isPublicPerformanceAllowed;
	public $shortDescription;
	public $fullDescription;
	public $starRating;
	public $popularity;
	public $rawData;
	public $thumbnail;
	public $cover;

	private $decodedRawData = null;

	public function getDecodedRawData() {
		if ($this->decodedRawData == null) {
			$this->decodedRawData = json_decode($this->rawData);
		}
		return $this->decodedRawData;
	}

	public function getCompressedColumnNames(): array {
		return ['rawData'];
	}
}