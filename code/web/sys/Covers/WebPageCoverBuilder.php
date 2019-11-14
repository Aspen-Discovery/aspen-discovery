<?php
require_once ROOT_DIR . '/sys/Utils/StringUtils.php';
require_once ROOT_DIR . '/sys/Covers/CoverImageUtils.php';
require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';

class WebPageCoverBuilder
{
	private $imageWidth = 280; //Pixels
	private $imageHeight = 400; // Pixels

	private $titleFont;

	private $backgroundColor;

	public function __construct()
	{
		global $interface;
		if ($interface == null) {
			//Need to initialize the interface to get access to the themes
			//This is needed because we try to minimize what loads for bookcovers for performance
			$interface = new UInterface();
			$interface->loadDisplayOptions();
		}

		$appliedTheme = $interface->getAppliedTheme();
		if ($appliedTheme != null) {
			$appliedThemes = $appliedTheme->getAllAppliedThemes();
			foreach ($appliedThemes as $theme) {
				if (empty($this->titleFont) && $theme->headingFontDefault == 0 && !empty($theme->headingFont)) {
					$fontFile = ROOT_DIR . '/fonts/' . str_replace(' ', '', $theme->headingFont) . '-Bold.ttf';
					if (file_exists($fontFile)) {
						$this->titleFont = $fontFile;
					}
				}
				if (empty($this->backgroundColor) && !$theme->primaryBackgroundColorDefault) {
					$colors = sscanf($theme->primaryBackgroundColor, "#%02x%02x%02x");
					$this->backgroundColor = [
						'r' => $colors[0],
						'g' => $colors[1],
						'b' => $colors[2]
					];
				}
			}
		}

		if (empty($this->titleFont)) {
			$this->titleFont = ROOT_DIR . '/fonts/JosefinSans-Bold.ttf';
		}
	}

	/**
	 * @param string $title
	 * @param array|null $listTitles
	 * @param string $filename
	 */
	public function getCover($title, $filename)
	{
		//Create the background image
		$imageCanvas = imagecreatetruecolor($this->imageWidth, $this->imageHeight);

		//Define our colors
		$white = imagecolorallocate($imageCanvas, 255, 255, 255);
		$this->setBackgroundColors($title);
		$backgroundColor = imagecolorallocate($imageCanvas, $this->backgroundColor['r'], $this->backgroundColor['g'], $this->backgroundColor['b']);

		//Draw a background for the entire image
		imagefilledrectangle($imageCanvas, 0, 0, $this->imageWidth, $this->imageHeight, $backgroundColor);

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
			//imagefilledrectangle($imageCanvas, $coverLeft, $coverTop, $this->imageWidth - $coverLeft, $this->imageWidth - $coverTop, $white);
			$coverLeft += 10;
			$coverTop += 10;
			imagecopyresampled($imageCanvas, $listEntryImageResource, $coverLeft, $coverTop, 0, 0, $listEntryWidth, $listEntryHeight, $listEntryWidth, $listEntryHeight);
			imagedestroy($listEntryImageResource);
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

	private function setBackgroundColors($title)
	{
		if (isset($this->backgroundColor)) {
			return;
		}
		$base_saturation = 100;
		$base_brightness = 90;
		$color_distance = 100;

		$counts = strlen($title);
		//Get the color seed based on the number of characters in the title and author.
		//We want a number from 10 to 360
		$color_seed = (int)_map(_clip($counts, 2, 80), 2, 80, 10, 360);

		$this->backgroundColor = ColorUtils::colorHSLToRGB(
			($color_seed + $color_distance) % 360,
			$base_saturation,
			$base_brightness
		);
	}

	private function drawText($imageCanvas, $title, $textColor)
	{
		$title_font_size = $this->imageWidth * 0.09;

		$x = 17;
		$y = $this->imageWidth + 5;
		$width = $this->imageWidth - (34);
		//$height = $title_height;

		$title = StringUtils::trimStringToLengthAtWordBoundary($title, 60, true);
		/** @noinspection PhpUnusedLocalVariableInspection */
		list($totalHeight, $lines, $font_size) = wrapTextForDisplay($this->titleFont, $title, $title_font_size, $title_font_size * .15, $width, $this->imageHeight - $this->imageWidth - 20);
		addCenteredWrappedTextToImage($imageCanvas, $this->titleFont, $lines, $font_size, $font_size * .15, $x, $y, $this->imageWidth - 30, $textColor);
	}
}