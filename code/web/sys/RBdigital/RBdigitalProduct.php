<?php

class RBdigitalProduct extends DataObject {
	public $__table = 'rbdigital_title';

	public $id;
	public $rbdigitalId;
	public $title;
	public $primaryAuthor;
	public $mediaType;
	public /** @noinspection PhpUnused */
		$isFiction;
	public /** @noinspection PhpUnused */
		$audience;
	public $language;
	public $rawChecksum;
	public $rawResponse;
	public /** @noinspection PhpUnused */
		$lastChange;
	public $dateFirstDetected;
	public $deleted;
}