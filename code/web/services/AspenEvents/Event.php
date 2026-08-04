<?php

require_once ROOT_DIR . '/RecordDrivers/AspenEventRecordDriver.php';

class AspenEvents_Event extends Action {

	private $recordDriver;

	function launch() {
		global $interface;
		$id = urldecode($_REQUEST['id']);

		$this->recordDriver = new AspenEventRecordDriver($id);
		if (!$this->recordDriver->isValid()) {
			global $interface;
			$interface->assign('module', 'Error');
			$interface->assign('action', 'Handle404');
			require_once ROOT_DIR . "/services/Error/Handle404.php";
			$actionClass = new Error_Handle404();
			$actionClass->launch();
			die();
		}
		// Check permissions
		if ($this->recordDriver->isPrivate()) {
			if (!UserAccount::userHasPermission('View Private Events for All Locations')) {
				if (!UserAccount::userHasPermission([
					'View Private Events for Home Library Locations',
					'View Private Events for Home Location'
				])) {
					$this->display('../Admin/noPermission.tpl', 'Access Error');
					exit();
				} else {
					if (!UserAccount::userHasPermission('View Private Events for Home Library Locations')) {
						$user = UserAccount::getLoggedInUser();
						$locations = array_values($user->getAdditionalAdministrationLocations());
						$locations[] = $user->getHomeLocationName();
					} else {
						$locations = array_values(Location::getLocationList(true));
					}
					if (!in_array($this->recordDriver->getBranch(), $locations)) {
						$this->display('../Admin/noPermission.tpl', 'Access Error');
						exit();
					}
				}
			}
		}
		$interface->assign('recordDriver', $this->recordDriver);
		$interface->assign('eventsInLists', true);
		$interface->assign('isStaff', UserAccount::isStaff());
		$interface->assign('upcomingInstanceCount', $this->recordDriver->getEventObject()->getUpcomingInstanceCount() ?? 0);
		$interface->assign('private', $this->recordDriver->isPrivate() ? 'private' : '');
		$this->recordDriver->assignRegistrationTemplateVars();

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