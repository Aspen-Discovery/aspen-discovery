<?php
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/Parsedown/AspenParsedown.php';

class Admin_ReleaseNotes extends Action {
	function launch() : void {
		global $interface;

		//Get a list of all available release notes
		$releaseNotesPath = ROOT_DIR . '/release_notes';
		$releaseNoteFiles = scandir($releaseNotesPath);
		$releaseNotes = [];
		foreach ($releaseNoteFiles as $releaseNoteFile) {
			if (preg_match('/\d{2}\.\d{2}\.\d{2}\.MD/', $releaseNoteFile)) {
				$releaseNoteFile = str_replace('.MD', '', $releaseNoteFile);
				$releaseNotes[$releaseNoteFile] = $releaseNoteFile;
			} elseif (strcasecmp('Supplemental.MD', $releaseNoteFile) === 0) {
				if (filesize($releaseNotesPath . '/' . $releaseNoteFile) > 0) {
					$releaseNoteFile = str_replace('.MD', '', $releaseNoteFile);
					$releaseNotes[$releaseNoteFile] = $releaseNoteFile;
				}
			}
		}

		arsort($releaseNotes);

		if (isset($_REQUEST['release']) && array_key_exists($_REQUEST['release'], $releaseNotes)) {
			$releaseVersion = $_REQUEST['release'];
		}else{
			$releaseVersion = array_values($releaseNotes)[0];
		}
		$interface->assign('releaseVersion', $releaseVersion);

		$parsedown = AspenParsedown::instance();

		//Fetch LiDA release Notes
		require_once ROOT_DIR . '/sys/SystemVariables.php';
		$systemVariables = SystemVariables::getSystemVariables();
		$lidaReleaseNotesFormatted = '';
		if (!empty($systemVariables)) {
			if (!empty($systemVariables->lidaGitHubRepository)) {
				$lidaReleaseNotes = @file_get_contents("$systemVariables->lidaGitHubRepository/raw/refs/heads/$releaseVersion/release-notes/$releaseVersion.MD");
				if (!empty($lidaReleaseNotes)) {
					$lidaReleaseNotesFormatted = $parsedown->parse($lidaReleaseNotes);
				}
			}
		}

		$releaseNotesFormatted = $parsedown->parse(file_get_contents($releaseNotesPath . '/' . $releaseVersion . '.MD'));
		$releaseNotesFormatted = $lidaReleaseNotesFormatted . '<br>' . $releaseNotesFormatted;
		$interface->assign('releaseNotesFormatted', $releaseNotesFormatted);

		if (file_exists($releaseNotesPath . '/' . $releaseVersion . '_action_items.MD')) {
			$actionItemsFormatted = $parsedown->parse(file_get_contents($releaseNotesPath . '/' . $releaseVersion . '_action_items.MD'));
			$interface->assign('actionItemsFormatted', $actionItemsFormatted);
		}
		if (file_exists($releaseNotesPath . '/' . $releaseVersion . '_testing.MD')) {
			$testingSuggestionsFormatted = $parsedown->parse(file_get_contents($releaseNotesPath . '/' . $releaseVersion . '_testing.MD'));
			$interface->assign('testingSuggestionsFormatted', $testingSuggestionsFormatted);
		}

		$interface->assign('releaseNotes', $releaseNotes);
		if (UserAccount::isLoggedIn() && count(UserAccount::getActivePermissions()) > 0) {
			$adminActions = UserAccount::getActiveUserObj()->getAdminActions();
			$interface->assign('adminActions', $adminActions);
			$interface->assign('activeAdminSection', $this->getActiveAdminSection());
			$interface->assign('activeMenuOption', 'admin');
			$sidebar = 'Admin/admin-sidebar.tpl';
		} else {
			$sidebar = '';
		}
		$this->display('releaseNotes.tpl', 'Release Notes', $sidebar);
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		if (UserAccount::isLoggedIn() && count(UserAccount::getActivePermissions()) > 0) {
			$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
			$breadcrumbs[] = new Breadcrumb('/Admin/Home#support', 'Aspen Discovery Support');
		}
		$breadcrumbs[] = new Breadcrumb('', 'Release Notes');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'support';
	}

	function canView(): bool {
		return true;
	}
}