<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class WebsitePage extends DataObject
{
	public $__table = 'website_pages';
	public $id;
	public $websiteId;
	public $url;
	public $deleted;

	public function getNumericColumnNames() : array
	{
		return ['id', 'deleted'];
	}
}