<?php /** @noinspection PhpMissingFieldTypeInspection */

class OmekaTitle extends DataObject {
	public $id;
	public $settingId;
	public $omekaId;
	public $title;
	public $mediaType;
	public $thumbnailUrl;
	public $itemSetIds;
	/** @noinspection PhpUnused */
	public $rawChecksum;
	public $rawResponse;
	/** @noinspection PhpUnused */
	public $rawResponseLength;
	/** @noinspection PhpUnused */
	public $dateFirstDetected;
	/** @noinspection PhpUnused */
	public $lastSeen;
	public $deleted;

	public $__table = 'omeka_title';

	public function getCompressedColumnNames(): array {
		return ['rawResponse'];
	}
}
