<?php

require_once ROOT_DIR . '/RecordDrivers/SpringshareLibCalEventRecordDriver.php';

class Springshare_Event extends Action {

	private $recordDriver;

	function launch() {
		global $interface;
		$id = urldecode($_REQUEST['id']);

		$this->recordDriver = new SpringshareLibCalEventRecordDriver($id);
		$interface->assign('recordDriver', $this->recordDriver);

		/* TODO: User Lists for Events! 2022 03 16 James
        //Check to see if there are lists the record is on
		require_once ROOT_DIR . '/sys/UserLists/UserList.php';
		$appearsOnLists = UserList::getUserListsForRecord('EbscoEds', $this->recordDriver->getPermanentId());
		$interface->assign('appearsOnLists', $appearsOnLists);
        */

		// Display Page
		$this->display('event.tpl', $this->recordDriver->getTitle(), null, false);
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		if (!empty($this->lastSearch)) {
			$breadcrumbs[] = new Breadcrumb($this->lastSearch, 'Event Search Results');
		}
		$breadcrumbs[] = new Breadcrumb('', $this->recordDriver->getTitle());
		return $breadcrumbs;
	}
}