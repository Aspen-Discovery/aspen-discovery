<?php

require_once ROOT_DIR . '/sys/File/FileUpload.php';

class Files_Contents extends Action {
	function launch() {
		//Get the id of the file to display
		$fileId = $_REQUEST['id'];
		$fileUpload = new FileUpload();
		$fileUpload->id = $fileId;
		if ($fileUpload->find(true)) {
			if (isset($fileUpload->uploadedFileData)) {
				set_time_limit(300);
				$size = strlen($fileUpload->uploadedFileData);

				if ($fileUpload->type == 'RecordPDF' || $fileUpload->type == 'web_builder_pdf') {
					header('Content-Type: application/pdf');
				} elseif ($fileUpload->type == 'web_builder_video') {
					header('Content-Type: video/mp4');
				} else {
					header('Content-Type: image/png');
				}
				header('Content-Transfer-Encoding: binary');
				header('Content-Length: ' . $size);
				echo($fileUpload->uploadedFileData);
				die();
			}
		}
		global $interface;
		$interface->assign('module', 'Error');
		$interface->assign('action', 'Handle404');
		require_once ROOT_DIR . "/services/Error/Handle404.php";
		$actionClass = new Error_Handle404();
		$actionClass->launch();
	}

	function getBreadcrumbs(): array {
		return [];
	}
}