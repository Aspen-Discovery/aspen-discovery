<?php
require_once ROOT_DIR . '/sys/CommunityEngagement/Reward.php';

class CommunityEngagement_ViewImage extends Action {
	private $uploadedImage;

	function launch() {
		global $interface;

		$id = strip_tags($_REQUEST['id']);
		$interface->assign('id', $id);

		$this->uploadedImage = new Reward();
		$this->uploadedImage->id = $id;

		//Fetch reward by ID
		if (!$this->uploadedImage->find(true)) {
			global $interface;
			$interface->assign('module', 'Error');
			$interface->assign('action', 'Handle404');
			require_once ROOT_DIR . "/services/Error/Handle404.php";
			$actionClass = new Error_Handle404();
			$actionClass->launch();
			die();
		}
		// Check if reward has an image
		if (empty($this->uploadedImage->badgeImage)) {
			die();
		}

		//Construct the full path to the image using StorageManager
		require_once ROOT_DIR . '/sys/Storage/StorageManager.php';
		$storageManager = StorageManager::getInstance();
		
		$extension = pathinfo($this->uploadedImage->badgeImage, PATHINFO_EXTENSION);

		IF (ISSET($_REQUEST['size']) && $extension != 'svg') {
			$size = $_REQUEST['size'];
		} else {
			$size = 'full';  // Use 'full' for rewards (like web_builder)
		}
		
		$imagePath = $storageManager->getImagePath('reward', null, $size);
		$fullPath = $imagePath . '/' . $this->uploadedImage->badgeImage;

		if ($file = @fopen($fullPath, 'r')) {
			fclose($file);
			set_time_limit(300);
			$chunkSize = 2 * (1024 * 1024);

			$size = intval(sprintf("%u", filesize($fullPath)));

			// Set content type
			if ($extension == 'svg') {
				header('Content-Type: image/svg+xml');
			} else {
				header('Content-Type: image/png');
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
			AspenError::raiseError(new AspenError("Image $id does not exist"));
		}
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/', 'Home');
		$breadcrumbs[] = new Breadcrumb('', $this->uploadedImage->title, true);
		if (UserAccount::userHasPermission('Administer Community Engagement Module')) {
			$breadcrumbs[] = new Breadcrumb('/CommunityEngagement/Images?id=' . $this->uploadedImage->id . '&objectAction=edit', 'Edit', true);
		}
		return $breadcrumbs;
	}
}