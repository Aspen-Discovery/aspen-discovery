<?php
/**
 * Description goes here
 *
 * @category VuFind-Plus 
 * @author Mark Noble <mark@marmot.org>
 * Date: 12/2/13
 * Time: 11:33 AM
 */

class NovelistData extends DB_DataObject{
	public $id;
	public $groupedRecordPermanentId;
	public $lastUpdate;
	public $groupedRecordHasISBN;
	public $hasNovelistData;
	public $primaryISBN;

	//Series Data
	public $seriesTitle;
	public $seriesNote;
	public $volume;

	//Data calculated at runtime with calls to loadEnrichment
	public $similarTitleCountOwned;
	public $similarTitles;

	public $__table = 'novelist_data';
} 