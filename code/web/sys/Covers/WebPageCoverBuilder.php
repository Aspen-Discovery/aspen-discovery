<?php
require_once ROOT_DIR . '/sys/Covers/AbstractCoverBuilder.php';
require_once ROOT_DIR . '/sys/Utils/StringUtils.php';
require_once ROOT_DIR . '/sys/Covers/CoverImageUtils.php';

class WebPageCoverBuilder extends AbstractCoverBuilder {
	/**
	 * @param string $title
	 * @param string $filename
	 * @param array|null $props
	 */
	public function getCover($title, $filename, $props = null) {
		//Create the background image
		$imageCanvas = imagecreatetruecolor($this->imageWidth, $this->imageHeight);

		//Define our colors
		$white = imagecolorallocate($imageCanvas, 255, 255, 255);
		$this->setBackgroundColors($title);
		$backgroundColor = imagecolorallocate($imageCanvas, $this->backgroundColor['r'], $this->backgroundColor['g'], $this->backgroundColor['b']);

		//Draw a background for the entire image
		imagefilledrectangle($imageCanvas, 0, 0, $this->imageWidth, $this->imageHeight, $backgroundColor);

		//Get website info for cover image
		require_once ROOT_DIR . '/RecordDrivers/WebsitePageRecordDriver.php';
		$id = $_GET['id'];

		$webPage = new WebsitePageRecordDriver($id);
		$webPage->id = $id;
		if ($webPage->isValid()) {
			$indexingSettingId = $webPage->getSettingId();
		}

		require_once ROOT_DIR . '/sys/WebsiteIndexing/WebsiteIndexSetting.php';
		$setting = new WebsiteIndexSetting;
		$setting->id = $indexingSettingId;
		if($setting->find(true)){
			$defaultCover = $setting->defaultCover;
		}

		if ($defaultCover){
			$cover = ROOT_DIR . '/files/original/' . $defaultCover;

			$coverImage = imagecreatefromstring(file_get_contents($cover));

			$listEntryWidth = imagesx($coverImage);
			$listEntryHeight = imagesy($coverImage);

			imagecopyresampled($imageCanvas, $coverImage, 10, 10, 0, 0, $listEntryWidth, $listEntryHeight, $listEntryWidth, $listEntryHeight);
			imagedestroy($coverImage);
		}else{
			//Draw the globe image

			global $configArray;
			$globeUrl = $configArray['Site']['local'] . '/images/globe.png';
			//Load the cover
			if ($globeImage = @file_get_contents($globeUrl, false)) {
				$listEntryImageResource = @imagecreatefromstring($globeImage);

				$listEntryWidth = imagesx($listEntryImageResource);
				$listEntryHeight = imagesy($listEntryImageResource);

				//Put a white background beneath the cover
				$coverLeft = 20;
				$coverTop = 20;

				$coverLeft += 10;
				$coverTop += 10;
				imagecopyresampled($imageCanvas, $listEntryImageResource, $coverLeft, $coverTop, 0, 0, $listEntryWidth, $listEntryHeight, $listEntryWidth, $listEntryHeight);
				imagedestroy($listEntryImageResource);
			}
		}

		//Make sure the borders are preserved
		imagefilledrectangle($imageCanvas, $this->imageWidth - 10, 0, $this->imageWidth, $this->imageHeight, $backgroundColor);
		imagefilledrectangle($imageCanvas, 0, $this->imageWidth, $this->imageWidth - 10, $this->imageHeight, $backgroundColor);

		$textColor = imagecolorallocate($imageCanvas, 50, 50, 50);

		imagefilledrectangle($imageCanvas, 10, $this->imageWidth, $this->imageWidth - 10, $this->imageHeight - 10, $white);
		imagerectangle($imageCanvas, 10, $this->imageWidth, $this->imageWidth - 10, $this->imageHeight - 10, $textColor);

		//Add the title at the bottom of the cover
		$this->drawText($imageCanvas, $title, $textColor);

		imagepng($imageCanvas, $filename);
		imagedestroy($imageCanvas);
	}
}