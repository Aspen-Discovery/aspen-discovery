<?php

class WebBuilder_ViewThumbnail extends Action {
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

		global $serverName;
		$dataPath = '/data/aspen-discovery/' . $serverName . '/uploads/web_builder_image/';
		$extension = pathinfo($this->uploadedImage->fullSizePath, PATHINFO_EXTENSION);
		$fullPath = $uploadedFile->thumbFullPath;

		if ($extension != 'svg') {
			$fullPath = $uploadedFile->thumbFullPath;
		} else {
			$fullPath = $uploadedFile->fullSizePath;
		}

		if ($file = @fopen($fullPath, 'r')) {
			set_time_limit(300);
			$chunkSize = 2 * (1024 * 1024);
			$size = intval(sprintf("%u", filesize($fullPath)));

			if ($extension == 'svg') {
				header('Content-Type: image/svg+xml');
			} else {
				header('Content-Type: image/jpg');
			}
			header('Content-Transfer-Encoding: binary');
			header('Content-Length: ' . $size);

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

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/', 'Home');
		$breadcrumbs[] = new Breadcrumb('', $this->uploadedFile->title, true);
		if (UserAccount::userHasPermission('Administer All Web Content')) {
			$breadcrumbs[] = new Breadcrumb('/WebBuilder/PDFs?id=' . $this->uploadedFile->id . '&objectAction=edit', 'Edit', true);
		}
		return $breadcrumbs;
	}
}