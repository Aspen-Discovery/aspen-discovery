<?php
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/Parsedown/AspenParsedown.php';

class Admin_ReleaseNotes extends Action
{
	function launch()
	{
		global $interface;

		//Get a list of all available release notes
		$releaseNotesPath = ROOT_DIR . '/release_notes';
		$releaseNoteFiles = scandir($releaseNotesPath);
		$releaseNotes = [];
		foreach ($releaseNoteFiles as $releaseNoteFile){
			if (preg_match('/.*\.MD/', $releaseNoteFile)){
				$releaseNoteFile = str_replace('.MD', '', $releaseNoteFile);
				$releaseNotes[$releaseNoteFile] = $releaseNoteFile;
			}
		}

		arsort($releaseNotes);

		$parsedown = AspenParsedown::instance();
		$releaseNotesFormatted = $parsedown->parse(file_get_contents($releaseNotesPath . '/'. reset($releaseNotes) . '.MD'));
		$interface->assign('releaseNotesFormatted', $releaseNotesFormatted);

		$interface->assign('releaseNotes', $releaseNotes);
		if (UserAccount::isLoggedIn() && count(UserAccount::getActivePermissions()) > 0) {
			$adminActions = UserAccount::getActiveUserObj()->getAdminActions();
			$interface->assign('adminActions', $adminActions);
			$interface->assign('activeAdminSection', $this->getActiveAdminSection());
			$interface->assign('activeMenuOption', 'admin');
			$sidebar = 'Admin/admin-sidebar.tpl';
		}else{
			$sidebar = '';
		}
		$this->display('releaseNotes.tpl', 'Release Notes', $sidebar);
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		if (UserAccount::isLoggedIn() && count(UserAccount::getActivePermissions()) > 0) {
			$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
			$breadcrumbs[] = new Breadcrumb('/Admin/Home#aspen_help', 'Aspen Discovery Help');
		}
		$breadcrumbs[] = new Breadcrumb('', 'Release Notes');
		return $breadcrumbs;
	}

	function getActiveAdminSection() : string
	{
		return 'aspen_help';
	}

	function canView() : bool
	{
		return true;
	}
}