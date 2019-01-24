<?php
/**
 * Stores MetaData for a product that has been loaded from OverDrive
 *
 * @category VuFind-Plus 
 * @author Mark Noble <mark@marmot.org>
 * Date: 10/8/13
 * Time: 9:28 AM
 */

class OverDriveAPIProductMetaData extends DataObject {
	public $__table = 'overdrive_api_product_metadata';   // table name

	public $id;
	public $productId;
	public $checksum;
	public $sortTitle;
	public $publisher;
	public $publishDate;
	public $isPublicDomain;
	public $isPublicPerformanceAllowed;
	public $shortDescription;
	public $fullDescription;
	public $starRating;
	public $popularity;
	public $rawData;
	public $thumbnail;
	public $cover;

	private $decodedRawData = null;
	public function getDecodedRawData(){
		if ($this->decodedRawData == null){
			$this->decodedRawData = json_decode($this->rawData);
		}
		return $this->decodedRawData;
	}
}