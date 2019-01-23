<?php

/**
 * Table definition for loading information about Volumes from the ILS.
 * Data should be stored in the ils_volume_info table
 *
 * @category Pika
 * @author Mark Noble <mark@marmot.org>
 * Date: 11/27/2015
 * Time: 9:31 PM
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';
class IlsVolumeInfo extends DB_DataObject{
	public $__table = 'ils_volume_info';    // table name
	public $id;
	public $recordId;
	public $displayLabel;
	public $relatedItems;
	public $volumeId;
}