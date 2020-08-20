<?php

require_once ROOT_DIR . '/sys/File/FileUpload.php';
class Files_Contents extends Action
{
	function launch()
	{
		//Get the id of the file to display
		$fileId = $_REQUEST['id'];
		$fileUpload = new FileUpload();
		$fileUpload->id = $fileId;
		if ($fileUpload->find(true)){
			if (file_exists($fileUpload->fullPath)) {
				set_time_limit(300);

				$size = intval(sprintf("%u", filesize($fileUpload->fullPath)));

				if ($fileUpload->type == 'RecordPDF' || $fileUpload->type == 'web_builder_pdf'){
					header('Content-Type: application/pdf');
				}else{
					header('Content-Type: image/png');
				}
				header('Content-Transfer-Encoding: binary');
				header('Content-Length: ' . $size);

				readfile($fileUpload->fullPath);
				die();
			}
		}
		global $interface;
		$interface->assign('module','Error');
		$interface->assign('action','Handle404');
		require_once ROOT_DIR . "/services/Error/Handle404.php";
		$actionClass = new Error_Handle404();
		$actionClass->launch();
	}

	function getBreadcrumbs()
	{
		return [];
	}
}