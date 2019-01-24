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
require_once ROOT_DIR . '/sys/DB/DataObject.php';
class IlsVolumeInfo extends DataObject{
	public $__table = 'ils_volume_info';    // table name
	public $id;
	public $recordId;
	public $displayLabel;
	public $relatedItems;
	public $volumeId;
}