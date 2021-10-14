<?php


class WebBuilder_DownloadPDF extends Action{
	function launch()
	{
		global $interface;

		$id = strip_tags($_REQUEST['id']);
		$interface->assign('id', $id);

		require_once ROOT_DIR . '/sys/File/FileUpload.php';
		$uploadedFile = new FileUpload();
		$uploadedFile->id = $id;
		if (!$uploadedFile->find(true)){
			global $interface;
			$interface->assign('module','Error');
			$interface->assign('action','Handle404');
			require_once ROOT_DIR . "/services/Error/Handle404.php";
			$actionClass = new Error_Handle404();
			$actionClass->launch();
			die();
		}

		global $serverName;
		$dataPath = '/data/aspen-discovery/' . $serverName . '/uploads/web_builder_pdf/';
		if (file_exists($uploadedFile->fullPath)){
			$fullPath = $uploadedFile->fullPath;
		}else{
			$fullPath = $dataPath . $uploadedFile->fullPath;
		}

		if (file_exists($fullPath)) {
			set_time_limit(300);
			$chunkSize = 2 * (1024 * 1024);

			$size = intval(sprintf("%u", filesize($fullPath)));

			header('Content-Type: application/octet-stream');
			header('Content-Transfer-Encoding: binary');
			header('Content-Length: ' . $size);
			$fileName = basename($fullPath);
			header('Content-Disposition: attachment;filename="' . $fileName . '"');

			if ($size > $chunkSize) {
				$handle = fopen($fullPath, 'rb');

				while (!feof($handle)) {
					set_time_limit(300);
					print(@fread($handle, $chunkSize));

					ob_flush();
					flush();
				}

				fclose($handle);
			} else {
				readfile($fullPath);
			}

			die();
		} else {
			AspenError::raiseError(new AspenError("File $id does not exist"));
		}

	}

	function getBreadcrumbs() : array
	{
		return [];
	}
}