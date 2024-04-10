<?php


class WebBuilder_DownloadPDF extends Action {
	function launch() {
		global $interface;
		$id = strip_tags($_REQUEST['id']);
		$interface->assign('id', $id);
		require_once ROOT_DIR . '/sys/File/FileUpload.php';
		$uploadedFile = new FileUpload();
		$uploadedFile->id = $id;
		if (!$uploadedFile->find(true)) {
			global $interface;
			$interface->assign('module', 'Error');
			$interface->assign('action', 'Handle404');
			require_once ROOT_DIR . "/services/Error/Handle404.php";
			$actionClass = new Error_Handle404();
			$actionClass->launch();
			die();
		}

		if (isset($uploadedFile->uploadedFileData)) {
			$size = strlen($uploadedFile->uploadedFileData);
			header('Content-Type: application/octet-stream');
			header('Content-Transfer-Encoding: binary');
			header('Content-Length: ' . $size);
			$fileName = $uploadedFile->getFileName();
			header('Content-Disposition: attachment;filename="' . $fileName . ".pdf");
			echo($uploadedFile->uploadedFileData);
			die();

		} else {
			AspenError::raiseError(new AspenError("File $id does not exist"));
		}

	}

	function getBreadcrumbs(): array {
		return [];
	}
}