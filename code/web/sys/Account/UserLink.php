<?php

class UserLink extends DataObject{
	public $id;
	public $primaryAccountId;
	public $linkedAccountId;
	public $linkingDisabled;

	public $__table = 'user_link';    // table name

}