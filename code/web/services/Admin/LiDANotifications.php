<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/LocalEnrichment/LiDANotification.php';

class Admin_LiDANotifications extends ObjectEditor {

	function getObjectType(): string {
		return 'LiDANotification';
	}

	function getToolName(): string {
		return 'LiDANotifications';
	}

	function getPageTitle(): string {
		return 'LiDA Notifications';
	}

	function canDelete() {
		return UserAccount::userHasPermission([
			'Send Notifications to All Libraries',
			'Send Notifications to All Locations',
			'Send Notifications to Home Library',
			'Send Notifications to Home Location',
			'Send Notifications to Home Library Locations',
		]);
	}

	function getAllObjects($page, $recordsPerPage): array {
		$object = new LiDANotification();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$userHasExistingMessages = true;
		if (!UserAccount::userHasPermission('Send Notifications to All Libraries')) {
			$librarySystemMessage = new LiDANotificationLibrary();
			$library = Library::getPatronHomeLibrary(UserAccount::getActiveUserObj());
			if ($library != null) {
				$librarySystemMessage->libraryId = $library->libraryId;
				$systemMessagesForLibrary = [];
				$librarySystemMessage->find();
				while ($librarySystemMessage->fetch()) {
					$systemMessagesForLibrary[] = $librarySystemMessage->lidaNotificationId;
				}
				if (count($systemMessagesForLibrary) > 0) {
					$object->whereAddIn('id', $systemMessagesForLibrary, false);
				} else {
					$userHasExistingMessages = false;
				}
			}
		}
		$list = [];
		if ($userHasExistingMessages) {
			$object->find();
			while ($object->fetch()) {
				$list[$object->id] = clone $object;
			}
		}
		return $list;
	}

	function getDefaultSort(): string {
		return 'id asc';
	}

	function getObjectStructure(): array {
		return LiDANotification::getObjectStructure();
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getInstructions(): string {
		return '';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#local_enrichment', 'Local Enrichment');
		$breadcrumbs[] = new Breadcrumb('/Admin/LiDANotifications', 'LiDA Notifications');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'local_enrichment';
	}

	function canView(): bool {
		return UserAccount::userHasPermission([
			'Send Notifications to All Libraries',
			'Send Notifications to All Locations',
			'Send Notifications to Home Library',
			'Send Notifications to Home Location',
			'Send Notifications to Home Library Locations',
		]);
	}

	function getInitializationJs(): string {
		return 'AspenDiscovery.Admin.getUrlOptions(); AspenDiscovery.Admin.getDeepLinkFullPath()';
	}

	/*	public function getFilterFields($structure){
			$filterFields = parent::getFilterFields($structure);

			$locationList = Location::getLocationList(!UserAccount::userHasPermission('Send Notifications'));
			$filterFields['locations'] = array('property' => 'locations', 'type' => 'enum', 'label' => 'Location', 'values' => $locationList, 'description' => 'Whether or not closed tickets are shown', 'readOnly'=>true);

			ksort($filterFields);
			return $filterFields;
		}*/
}