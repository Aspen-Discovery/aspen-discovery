<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/Covers/BookCoverInfo.php';
require_once ROOT_DIR . '/sys/DB/DataObject.php';

class Greenhouse_CoverManager extends Admin_Admin
{
	function launch(): void
	{
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
		$message = '';

		switch ($source) {
			case 'default':
				// Delete all default covers
				$query = "DELETE FROM $tableName WHERE imageSource = 'default'";
				$bookCoverInfo->query($query);
				$message = 'All default covers have been deleted and will be regenerated.';
				break;

			case 'syndetics':
				// Delete all syndetics covers
				$query = "DELETE FROM $tableName WHERE imageSource = 'syndetics'";
				$bookCoverInfo->query($query);
				$message = 'All Syndetics covers have been deleted and will be regenerated.';
				break;

			case 'marcRecord':
				// Delete all MARC record covers
				$query = "DELETE FROM $tableName WHERE imageSource = 'marcRecord'";
				$bookCoverInfo->query($query);
				$message = 'All MARC Record covers have been deleted and will be regenerated.';
				break;

			case 'omdb_title':
				// Delete all OMDB title covers
				$query = "DELETE FROM $tableName WHERE imageSource = 'omdb_title'";
				$bookCoverInfo->query($query);
				$message = 'All OMDB title covers have been deleted and will be regenerated.';
				break;

			case 'omdb_title_year':
				// Delete all OMDB title+year covers
				$query = "DELETE FROM $tableName WHERE imageSource = 'omdb_title_year'";
				$bookCoverInfo->query($query);
				$message = 'All OMDB title+year covers have been deleted and will be regenerated.';
				break;

			case 'coce_amazon':
				// Delete all COCE Amazon covers
				$query = "DELETE FROM $tableName WHERE imageSource = 'coce_amazon'";
				$bookCoverInfo->query($query);
				$message = 'All COCE Amazon covers have been deleted and will be regenerated.';
				break;

			case 'coce_google_books':
				// Delete all COCE Google Books covers
				$query = "DELETE FROM $tableName WHERE imageSource = 'coce_google_books'";
				$bookCoverInfo->query($query);
				$message = 'All COCE Google Books covers have been deleted and will be regenerated.';
				break;

			case 'coce_open_library':
				// Delete all COCE Open Library covers
				$query = "DELETE FROM $tableName WHERE imageSource = 'coce_open_library'";
				$bookCoverInfo->query($query);
				$message = 'All COCE Open Library covers have been deleted and will be regenerated.';
				break;

			case 'overdrive':
				// Delete all Overdrive covers
				$query = "DELETE FROM $tableName WHERE imageSource = 'overdrive'";
				$bookCoverInfo->query($query);
				$message = 'All OverDrive covers have been deleted and will be regenerated.';
				break;

			case 'upload':
				// Reset uploaded covers to be reloaded (don't delete them)
				$query = "UPDATE $tableName SET thumbnailLoaded = 0, mediumLoaded = 0, largeLoaded = 0 WHERE imageSource = 'upload'";
				$bookCoverInfo->query($query);
				$message = 'All uploaded covers have been marked for reload.';
				break;

			default:
				break;
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