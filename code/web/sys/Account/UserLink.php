<?php

class UserLink extends DataObject{
	public $id;
	public $primaryAccountId;
	public $linkedAccountId;

	public $__table = 'user_link';    // table name

}