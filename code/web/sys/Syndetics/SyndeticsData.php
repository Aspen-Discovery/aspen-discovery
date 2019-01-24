<?php
/**
 * Description goes here
 *
 * @category VuFind-Plus 
 * @author Mark Noble <mark@marmot.org>
 * Date: 12/2/13
 * Time: 9:14 PM
 */

class SyndeticsData extends DataObject{
	public $id;
	public $groupedRecordPermanentId;
	public $lastUpdate;
	public $hasSyndeticsData;
	public $primaryIsbn;
	public $primaryUpc;
	public $description;
	public $tableOfContents;
	public $excerpt;

	public $__table = 'syndetics_data';
} 