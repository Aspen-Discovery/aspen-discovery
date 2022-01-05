<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';
class WebResourceUsage extends DataObject
{
	public $__table = 'web_builder_resource_usage';
	public $id;
	public $instance;
	public $year;
	public $month;
	public $resourceName;
	public $pageViews;
	public $pageViewsByAuthenticatedUsers;
	public $pageViewsInLibrary;

	public function getNumericColumnNames() : array
	{
		return [
			'pageViews',
			'pageViewsByAuthenticatedUsers',
			'pageViewsInLibrary',
		];
	}
}