<?php
require_once ROOT_DIR . '/sys/Utils/StringUtils.php';
require_once ROOT_DIR . '/sys/Covers/CoverImageUtils.php';
require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';

class ListCoverBuilder {
	private $imageWidth = 280; //Pixels
	private $imageHeight = 400; // Pixels

	private $titleFont;
	private $authorFont;

	private $backgroundColor;

	public function __construct() {
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
					$fontFile = ROOT_DIR . '/fonts/' . str_replace(' ', '', $theme->headingFont) . '-BoldItalic.ttf';
					if (file_exists($fontFile)) {
						$this->authorFont = $fontFile;
					} else {
						$fontFile = ROOT_DIR . '/fonts/' . str_replace(' ', '', $theme->headingFont) . '-Regular.ttf';
						if (file_exists($fontFile)) {
							$this->authorFont = $fontFile;
						}
					}
				}
				if (empty($this->backgroundColor) && !$theme->primaryBackgroundColorDefault) {
					$colors = sscanf($theme->primaryBackgroundColor, "#%02x%02x%02x");
					$this->backgroundColor = [
						'r' => $colors[0],
						'g' => $colors[1],
						'b' => $colors[2],
					];
				}
			}
		}

		if (empty($this->titleFont)) {
			$this->titleFont = ROOT_DIR . '/fonts/JosefinSans-Bold.ttf';
			$this->authorFont = ROOT_DIR . '/fonts/JosefinSans-BoldItalic.ttf';
		}
	}

	/**
	 * @param string $title
	 * @param array|null $listTitles
	 * @param string $filename
	 */
	public function getCover($title, $listTitles, $filename) {
		//Create the background image
		$imageCanvas = imagecreatetruecolor($this->imageWidth, $this->imageHeight);

		//Define our colors
		$white = imagecolorallocate($imageCanvas, 255, 255, 255);
		$this->setBackgroundColors($title);
		$backgroundColor = imagecolorallocate($imageCanvas, $this->backgroundColor['r'], $this->backgroundColor['g'], $this->backgroundColor['b']);

		//Draw a background for the entire image
		imagefilledrectangle($imageCanvas, 0, 0, $this->imageWidth, $this->imageHeight, $backgroundColor);

		$isListOfLists = false;
		$validListEntries = [];
		/** @var UserListEntry $curListEntry */
		foreach ($listTitles as $curListEntry) {
			$recordDriver = $curListEntry->getRecordDriver();
			if ($recordDriver != null) {
				if (get_class($recordDriver) === 'ListsRecordDriver') {
					$isListOfLists = true;
				}
				$validListEntries[] = $recordDriver;
			}

			if (count($validListEntries) >= 4) {
				break;
			}
		}

		$maxIndex = $isListOfLists
			? min(count($validListEntries) - 1, 2) // Up to 3 covers for lists-of-lists,
			: min(count($validListEntries) - 1, 3); // Up to 4 covers for standard lists.
		for ($i = $maxIndex; $i >= 0; $i--) {
			$recordDriver = $validListEntries[$i];
			$bookcoverUrl = $recordDriver->getBookcoverUrl('medium', true);
			//Load the cover
			if ($listEntryCoverImage = @file_get_contents($bookcoverUrl, false)) {
				$listEntryImageResource = @imagecreatefromstring($listEntryCoverImage);

				$listEntryWidth = imagesx($listEntryImageResource);
				$listEntryHeight = imagesy($listEntryImageResource);

				if ($isListOfLists) {
					// For lists of lists, use a different placement strategy: make covers larger and more spaced out.
					$coverLeft = 10 + (45 * (2 - $i));
					$coverTop = 10 + (20 * (2 - $i));

					$newWidth = $listEntryWidth * 1.2;
					$newHeight = $listEntryHeight * 1.2;

					if (imagecopyresampled($imageCanvas, $listEntryImageResource, $coverLeft, $coverTop, 0, 0, $newWidth, $newHeight, $listEntryWidth, $listEntryHeight)) {
						$borderColor = imagecolorallocate($imageCanvas, 0, 0, 0);
						imagerectangle($imageCanvas, $coverLeft, $coverTop, $newWidth + $coverLeft, $newHeight + $coverTop, $borderColor);
					} else {
						global $logger;
						$logger->log(sprintf(
							"ListCoverBuilder [getCover()]: imagecopyresampled failed for cover URL '%s' (src %dx%d -> dest %dx%d at %d,%d).",
							$bookcoverUrl, $listEntryWidth, $listEntryHeight, $newWidth, $newHeight, $coverLeft, $coverTop
						), Logger::LOG_ERROR);
					}
				} else {
					$coverLeft = 10 + (40 * (3 - $i));
					$coverTop = 10 + (35 * (3 - $i));
					imagefilledrectangle($imageCanvas, $coverLeft, $coverTop, $listEntryWidth + $coverLeft, $listEntryHeight + $coverTop, $white);
					imagecopyresampled($imageCanvas, $listEntryImageResource, $coverLeft, $coverTop, 0, 0, $listEntryWidth, $listEntryHeight, $listEntryWidth, $listEntryHeight);
				}
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

	private function setBackgroundColors($title) {
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

		require_once ROOT_DIR . '/sys/Utils/ColorUtils.php';
		$this->backgroundColor = ColorUtils::colorHSLToRGB(($color_seed + $color_distance) % 360, $base_saturation, $base_brightness);
	}

	private function drawText($imageCanvas, $title, $textColor) {
		$title_font_size = $this->imageWidth * 0.09;

		$x = 15;
		$y = $this->imageWidth + 5;
		$width = $this->imageWidth - (30);
		//$height = $title_height;

		$title = StringUtils::trimStringToLengthAtWordBoundary($title, 60, true);
		/** @noinspection PhpUnusedLocalVariableInspection */
		[
			$totalHeight,
			$lines,
			$font_size,
		] = wrapTextForDisplay($this->titleFont, $title, $title_font_size, $title_font_size * .15, $width, $this->imageHeight - $this->imageWidth - 20);
		addCenteredWrappedTextToImage($imageCanvas, $this->titleFont, $lines, $font_size, $font_size * .15, $x, $y, $this->imageWidth - 30, $textColor);
	}
}
