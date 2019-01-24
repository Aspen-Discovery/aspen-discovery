<?php
/**
 * Table Definition for session
 */
require_once ROOT_DIR . '/sys/DB/DataObject.php';

class Session extends DataObject
{
	###START_AUTOCODE
	/* the code below is auto generated do not remove the above tag */

	public $__table = 'session';                        // table name
	public $id;                              // int(11)  not_null primary_key auto_increment
	public $session_id;                      // string(128)  unique_key
	public $data;                            // blob(65535)  blob
	public $last_used;                       // int(12)  not_null
	public $created;                         // datetime(19)  not_null binary
	public $remember_me;                     // tinyint

	/* the code above is auto generated do not remove the tag below */
	###END_AUTOCODE

 function update($dataObject = false)
 {
	 parent::update($dataObject);
	 global $interface;
	 if (isset($interface)){
		 $interface->assign('session', $this->session_id . ', remember me ' . $this->remember_me);
	 }

 }
}
