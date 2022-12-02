<?php

require_once ROOT_DIR . '/sys/File/FileUpload.php';
require_once ROOT_DIR . '/sys/ILS/RecordFile.php';

class Files_WatchVideo extends Action {
	private $fileUpload;

	function launch() {
		//Get the id of the file to display
		$fileId = $_REQUEST['id'];
		$this->fileUpload = new FileUpload();
		$this->fileUpload->id = $fileId;
		if ($this->fileUpload->find(true)) {
			global $interface;
			$title = $this->fileUpload->title;
			$interface->assign('title', $title);
			$fileSize = filesize($this->fileUpload->fullPath);
			$interface->assign('fileSize', StringUtils::formatBytes($fileSize));
			global $configArray;
			$interface->assign('videoPath', $configArray['Site']['url'] . '/Files/' . $fileId . '/Contents');
			$this->display('videoViewer.tpl', $title);
		} else {
			$this->display('../Record/invalidRecord.tpl', 'Invalid Record');
		}
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/', 'Home');
		$breadcrumbs[] = new Breadcrumb('', $this->fileUpload->title, true);
		if (UserAccount::userHasPermission('Administer All Web Content')) {
			$breadcrumbs[] = new Breadcrumb('/WebBuilder/Videos?id=' . $this->fileUpload->id . '&objectAction=edit', 'Edit', true);
		}
		return $breadcrumbs;
	}
}