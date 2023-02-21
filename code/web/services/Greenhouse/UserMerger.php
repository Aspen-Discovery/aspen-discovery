<?php

require_once ROOT_DIR . '/services/Admin/Admin.php';

abstract class UserMerger extends Admin_Admin {
	protected $importPath;
	protected $importDirExists;
	protected $setupErrors;

	function launch() {
		global $serverName;
		$this->importPath = '/data/aspen-discovery/' . $serverName . '/import/';
		$this->importDirExists = false;
		$this->setupErrors = [];
		if (!file_exists($this->importPath)) {
			if (!mkdir($this->importPath, 0774, true)) {
				$this->setupErrors[] = 'Could not create import directory';
			} else {
				chgrp($this->importPath, 'aspen_apache');
				chmod($this->importPath, 0774);
				$this->importDirExists = true;
			}
		} else {
			$this->importDirExists = true;
		}
	}

	function getActiveAdminSection(): string {
		return 'greenhouse';
	}

	function canView(): bool {
		if (UserAccount::isLoggedIn()) {
			if (UserAccount::getActiveUserObj()->source == 'admin' && UserAccount::getActiveUserObj()->cat_username == 'aspen_admin') {
				return true;
			}
		}
		return false;
	}

	function getBlankResult(): array {
		return [
			'numUsersUpdated' => 0,
			'numUsersMerged' => 0,
			'numUnmappedUsers' => 0,
			'numListsMoved' => 0,
			'numReadingHistoryEntriesMoved' => 0,
			'numRolesMoved' => 0,
			'numNotInterestedMoved' => 0,
			'numLinkedPrimaryUsersMoved' => 0,
			'numLinkedUsersMoved' => 0,
			'numSavedSearchesMoved' => 0,
			'numSystemMessageDismissalsMoved' => 0,
			'numPlacardDismissalsMoved' => 0,
			'numMaterialsRequestsMoved' => 0,
			'numMaterialsRequestsAssignmentsMoved' => 0,
			'numUserMessagesMoved' => 0,
			'numUserPaymentsMoved' => 0,
			'numRatingsReviewsMoved' => 0,
			'errors' => [],
		];
	}
}