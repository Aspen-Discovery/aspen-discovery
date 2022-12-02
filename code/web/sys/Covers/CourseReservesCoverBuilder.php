<?php
require_once ROOT_DIR . '/sys/Covers/AbstractCoverBuilder.php';
require_once ROOT_DIR . '/sys/Utils/StringUtils.php';
require_once ROOT_DIR . '/sys/Covers/CoverImageUtils.php';

class CourseReservesCoverBuilder extends AbstractCoverBuilder {
	public function __construct($invertColors = false) {
		parent::__construct(true);
	}

	public function getCover($title, $filename, $props = null) {
		//Create the background image
		$imageCanvas = imagecreatetruecolor($this->imageWidth, $this->imageHeight);

		//Define our colors
		$white = imagecolorallocate($imageCanvas, 255, 255, 255);
		$this->setBackgroundColors($title);
		$backgroundColor = imagecolorallocate($imageCanvas, $this->backgroundColor['r'], $this->backgroundColor['g'], $this->backgroundColor['b']);

		//Draw a background for the entire image
		imagefilledrectangle($imageCanvas, 0, 0, $this->imageWidth, $this->imageHeight, $backgroundColor);

		//Load the cover
		//Add in the icon
		global $configArray;
		$ebscoIconUrl = $configArray['Site']['local'] . '/interface/themes/responsive/images/course_reserves.png';
		if ($ebscoImage = @file_get_contents($ebscoIconUrl, false)) {
			$imageResource = @imagecreatefromstring($ebscoImage);

			$listEntryWidth = imagesx($imageResource);
			$listEntryHeight = imagesy($imageResource);

			//Put a white background beneath the cover
			$coverLeft = 65;
			$coverTop = 20;

			$coverTop += 10;
			imagecopyresampled($imageCanvas, $imageResource, $coverLeft, $coverTop, 0, 0, $listEntryWidth, $listEntryHeight, $listEntryWidth, $listEntryHeight);
			imagedestroy($imageResource);
		}

		//Make sure the borders are preserved
		imagefilledrectangle($imageCanvas, $this->imageWidth - 10, 0, $this->imageWidth, $this->imageHeight, $backgroundColor);
		imagefilledrectangle($imageCanvas, 0, $this->imageWidth, $this->imageWidth - 10, $this->imageHeight, $backgroundColor);

		$textColor = imagecolorallocate($imageCanvas, 50, 50, 50);

		imagefilledrectangle($imageCanvas, 10, 195, $this->imageWidth - 10, $this->imageHeight - 10, $white);
		imagerectangle($imageCanvas, 10, 195, $this->imageWidth - 10, $this->imageHeight - 10, $textColor);
		//Add the title at the bottom of the cover
		$this->drawText($imageCanvas, $title, $textColor, 205, $this->imageHeight - 215, 80);

		imagepng($imageCanvas, $filename);
		imagedestroy($imageCanvas);
	}
}