<?php
/**
 * Contains information needed to link to accounts
 *
 * @category VuFind-Plus-2014 
 * @author Mark Noble <mark@marmot.org>
 * Date: 7/21/2015
 * Time: 3:44 PM
 */

class UserLink extends DataObject{
	public $id;
	public $primaryAccountId;
	public $linkedAccountId;

	public $__table = 'user_link';    // table name

}