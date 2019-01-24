<?php
/**
 * Description goes here
 *
 * @category VuFind-Plus 
 * @author Mark Noble <mark@marmot.org>
 * Date: 12/31/13
 * Time: 10:32 AM
 */

class OverDriveAPIProductIdentifiers extends DataObject{
	public $__table = 'overdrive_api_product_identifiers';   // table name

	public $id;
	public $productId;
	public $type;
	public $value;
} 