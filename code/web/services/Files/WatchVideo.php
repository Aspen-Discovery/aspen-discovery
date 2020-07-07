<?php

require_once ROOT_DIR . '/sys/File/FileUpload.php';
require_once ROOT_DIR . '/sys/ILS/RecordFile.php';
class Files_WatchVideo extends Action
{
	function launch()
	{
		//Get the id of the file to display
		$fileId = $_REQUEST['id'];
		$fileUpload = new FileUpload();
		$fileUpload->id = $fileId;
		if ($fileUpload->find(true)){
			global $interface;
			$title = $fileUpload->title;
			$interface->assign('title', $title);
			$fileSize = filesize($fileUpload->fullPath);
			$interface->assign('fileSize', StringUtils::formatBytes($fileSize));
			global $configArray;
			$interface->assign('videoPath', $configArray['Site']['url'] . '/Files/' . $fileId . '/Contents');
			$this->display('videoViewer.tpl', $title);
		}else{
			$this->display('../Record/invalidRecord.tpl', 'Invalid Record');
		}
	}
}