<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/Covers/BookCoverInfo.php';
require_once ROOT_DIR . '/sys/DB/DataObject.php';

class Greenhouse_CoverManager extends Admin_Admin
{
	function launch(): void
	{
		global $interface;

		// Fetch all distinct image sources from the database.
		$bookCoverInfo = new BookCoverInfo();
		$sources = $bookCoverInfo->getDistinctImageSources();
		$interface->assign('coverSources', $sources);

		$this->display('coverManager.tpl', 'Cover Manager');
	}

	function reloadMultipleCoverSources($sources): array
	{
		$results = [];

		foreach ($sources as $source) {
			$result = $this->reloadCoversBySource($source);
			$results[] = $result;
		}

		return $results;
	}

	function reloadCoversBySource($source): string
	{
		$bookCoverInfo = new BookCoverInfo();
		$tableName = $bookCoverInfo->__table;

		if ($source === 'upload') {
			$query = "UPDATE $tableName SET thumbnailLoaded = 0, mediumLoaded = 0, largeLoaded = 0 WHERE imageSource = 'upload'";
			$bookCoverInfo->query($query);
			$message = 'All uploaded covers have been marked for reload.';
		} else {
			// For all other sources, delete the entries so they'll be regenerated
			$query = "DELETE FROM $tableName WHERE imageSource = " . $bookCoverInfo->escape($source);
			$bookCoverInfo->query($query);

			$message = "All covers from source '$source' have been deleted and will be regenerated.";
		}

		return $message;
	}

	function getBreadcrumbs(): array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/Home', 'Greenhouse Home');
		$breadcrumbs[] = new Breadcrumb('', 'Cover Manager');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string
	{
		return 'greenhouse';
	}

	function canView(): bool
	{
		if (UserAccount::isLoggedIn()) {
			if (UserAccount::getActiveUserObj()->isAspenAdminUser()) {
				return true;
			}
		}
		return false;
	}
}