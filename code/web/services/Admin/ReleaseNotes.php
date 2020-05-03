<?php
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/Parsedown/AspenParsedown.php';

class Admin_ReleaseNotes extends Admin_Admin
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
		$this->display('releaseNotes.tpl', 'Release Notes');
	}

	function getAllowableRoles(){
		return array('opacAdmin', 'libraryAdmin');
	}
}