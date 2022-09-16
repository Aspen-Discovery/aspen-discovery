<?php

require_once ROOT_DIR . '/sys/Account/PType.php';

class LiDANotificationPType extends DataObject
{
	public $__table = 'aspen_lida_notifications_ptype';
	public $id;
	public $lidaNotificationId;
	public $patronTypeId;

	public function getPtypeById($id) {
		$ptype = new PType();
		$ptype->id = $id;
		if($ptype->find(true)) {
			return $ptype->pType;
		}
	}

}
