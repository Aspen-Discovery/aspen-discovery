<?php

require_once ROOT_DIR . '/sys/File/ImageUpload.php';

class WebBuilder_ViewImage extends Action {
	private $uploadedImage;

	function launch() {
		global $interface;

		$id = strip_tags($_REQUEST['id']);
		$interface->assign('id', $id);

		require_once ROOT_DIR . '/sys/File/ImageUpload.php';
		$this->uploadedImage = new ImageUpload();
		$this->uploadedImage->id = $id;
		if (!$this->uploadedImage->find(true)) {
			global $interface;
			$interface->assign('module', 'Error');
			$interface->assign('action', 'Handle404');
			require_once ROOT_DIR . "/services/Error/Handle404.php";
			$actionClass = new Error_Handle404();
			$actionClass->launch();
			die();
		}

		$extension = pathinfo($this->uploadedImage->fullSizePath, PATHINFO_EXTENSION);
		if ((isset($_REQUEST['size'])) && $extension != 'svg') {
			$size = $_REQUEST['size'];
		} else {
			$size = 'full';
		}
		$storageKey = 'uploads/web_builder_image/' . $size . '/' . $this->uploadedImage->fullSizePath;

		require_once ROOT_DIR . '/sys/Storage/StorageDriverFactory.php';
		$storage = StorageDriverFactory::getById($this->uploadedImage->storageSettingId);

		$directUrl = $storage->url($storageKey);
		if ($directUrl !== '') {
			header('Location: ' . $directUrl, true, 302);
			die();
		}

			$mimeTypesByExtension = [
				'svg' => 'image/svg+xml',
				'gif' => 'image/gif',
				'jpg' => 'image/jpeg',
				'jpeg' => 'image/jpeg',
				'png' => 'image/png',
			];
			header('Content-Type: ' . ($mimeTypesByExtension[strtolower($extension)] ?? 'application/octet-stream'));
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
				$readResult = readfile($fullPath);
			}

			die();
		} else {
			AspenError::raiseError(new AspenError("Image $id does not exist"));
		}

	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/', 'Home');
		$breadcrumbs[] = new Breadcrumb('', $this->uploadedImage->title, true);
		if (UserAccount::userHasPermission('Administer All Web Content')) {
			$breadcrumbs[] = new Breadcrumb('/WebBuilder/Images?id=' . $this->uploadedImage->id . '&objectAction=edit', 'Edit', true);
		}
		return $breadcrumbs;
	}
}