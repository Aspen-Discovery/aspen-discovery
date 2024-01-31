<?php

class PalaceProjectTitle extends DataObject {
	public $id;
	public $palaceProjectId;
	public $title;
	public $rawChecksum;
	public $rawResponse;
	public $dateFirstDetected;

	public $__table = 'palace_project_title';

	public function getCompressedColumnNames(): array {
		return ['rawResponse'];
	}

}