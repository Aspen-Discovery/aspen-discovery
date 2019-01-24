<?php

/**
 * Contains information about the Checksum of records loaded from the ils
 *
 * @category Pika
 * @author Mark Noble <mark@marmot.org>
 * Date: 2/8/2016
 * Time: 2:45 PM
 */
class IlsMarcChecksum extends DataObject {
	public $__table = 'ils_marc_checksums';    // table name
	public $id;
	public $ilsId;
	public $checksum;
	public $dateFirstDetected;
	public $source;
}