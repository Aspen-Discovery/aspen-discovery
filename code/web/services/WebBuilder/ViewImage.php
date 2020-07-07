<?php

require_once ROOT_DIR . '/sys/File/ImageUpload.php';
class WebBuilder_ViewImage extends Action{
	function launch()
	{
		global $interface;

		$id = strip_tags($_REQUEST['id']);
		$interface->assign('id', $id);

		require_once ROOT_DIR . '/sys/File/ImageUpload.php';
		$uploadedImage = new ImageUpload();
		$uploadedImage->id = $id;
		if (!$uploadedImage->find(true)){
			$this->display('../Record/invalidPage.tpl', 'Invalid Image');
			die();
		}

		global $serverName;
		$dataPath = '/data/aspen-discovery/' . $serverName . '/uploads/web_builder_image/';
		if (isset($_REQUEST['size'])){
			$size = $_REQUEST['size'];
		}else{
			$size = 'full';
		}
		$dataPath .= $size . '/';
		$fullPath = $dataPath . $uploadedImage->fullSizePath;
		if (file_exists($fullPath)) {
			set_time_limit(300);
			$chunkSize = 2 * (1024 * 1024);

			$size = intval(sprintf("%u", filesize($fullPath)));

			header('Content-Type: image/png');
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
}