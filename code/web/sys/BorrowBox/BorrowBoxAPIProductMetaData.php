<?php /** @noinspection PhpMissingFieldTypeInspection */

class BorrowBoxAPIProductMetaData extends DataObject {
	public $__table = 'borrowbox_api_product_metadata';

	public $id;
	public $productId;
	/** @noinspection PhpUnused */
	public $checksum;
	public $publisher;
	public $releaseDate;
	public $summary;
	public $cover;
	public $rawData;

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
