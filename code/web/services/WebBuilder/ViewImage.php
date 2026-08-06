<?php

require_once ROOT_DIR . '/sys/File/ImageUpload.php';

class WebBuilder_ViewImage extends Action {
	private $uploadedImage;

	function launch() {
		global $interface, $logger;

		$id = strip_tags($_REQUEST['id']);
		$interface->assign('id', $id);

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
		$mimeTypesByExtension = [
			'svg' => 'image/svg+xml',
			'gif' => 'image/gif',
			'jpg' => 'image/jpeg',
			'jpeg' => 'image/jpeg',
			'png' => 'image/png',
		];
		$contentType = $mimeTypesByExtension[strtolower($extension)] ?? 'application/octet-stream';

		if ((isset($_REQUEST['size'])) && $contentType != 'image/svg+xml') {
			$size = $_REQUEST['size'];
		} else {
			$size = 'full';
		}
		$storageKey = 'uploads/web_builder_image/' . $size . '/' . $this->uploadedImage->fullSizePath;

		require_once ROOT_DIR . '/sys/Storage/StorageDriverFactory.php';
		$storage = StorageDriverFactory::getById($this->uploadedImage->storageSettingId);
		$logger->log("ViewImage: serving image id=$id size=$size key=$storageKey storageSettingId=" . var_export($this->uploadedImage->storageSettingId, true), Logger::LOG_DEBUG);

		$directUrl = $storage->url($storageKey);
		if ($directUrl !== '') {
			$logger->log("ViewImage: redirecting image id=$id to $directUrl", Logger::LOG_DEBUG);
			header('Location: ' . $directUrl, true, 302);
			die();
		}

		$stream = $storage->readStream($storageKey);

		if ($stream !== false) {
			$logger->log("ViewImage: proxied image id=$id key=$storageKey", Logger::LOG_DEBUG);

			header('Content-Type: ' . $contentType);
			header('Content-Transfer-Encoding: binary');

			set_time_limit(300);
			$chunkSize = 2 * (1024 * 1024);
			while (!feof($stream)) {
				set_time_limit(300);
				echo fread($stream, $chunkSize);
				ob_flush();
				flush();
			}
			fclose($stream);
			die();
		} else {
			$logger->log("ViewImage: failed to read image id=$id key=$storageKey from storageSettingId=" . var_export($this->uploadedImage->storageSettingId, true), Logger::LOG_ERROR);
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