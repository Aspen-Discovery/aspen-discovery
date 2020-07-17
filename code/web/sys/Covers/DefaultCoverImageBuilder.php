<?php

require_once ROOT_DIR . '/sys/Utils/ColorUtils.php';
require_once ROOT_DIR . '/sys/Utils/StringUtils.php';
require_once ROOT_DIR . '/sys/Covers/CoverImageUtils.php';

class DefaultCoverImageBuilder
{
	private $imageWidth = 280; //Pixels
	private $imageHeight = 400; // Pixels
	private $topMargin = 10;
	private $titleFont;
	private $authorFont;
	private $backgroundColor;
	private $foregroundColor;

	public function __construct($invertColors = false)
	{
		global $interface;
		if ($interface == null) {
			//Need to initialize the interface to get access to the themes
			//This is needed because we try to minimize what loads for book covers for performance
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
						'b' => $colors[2]
					];
				}
				if (empty($this->foregroundColor) && !$theme->secondaryBackgroundColorDefault) {
					$colors = sscanf($theme->secondaryBackgroundColor, "#%02x%02x%02x");
					$this->foregroundColor = [
						'r' => $colors[0],
						'g' => $colors[1],
						'b' => $colors[2]
					];
				}
				if ($invertColors) {
					$tmpColor = $this->backgroundColor;
					$this->backgroundColor = $this->foregroundColor;
					$this->foregroundColor = $tmpColor;
				}
			}
		}

		if (empty($this->titleFont)) {
			$this->titleFont = ROOT_DIR . '/fonts/JosefinSans-Bold.ttf';
			$this->authorFont = ROOT_DIR . '/fonts/JosefinSans-BoldItalic.ttf';
		}
		$this->titleFont = realpath($this->titleFont);
		$this->authorFont = realpath($this->authorFont);
	}

	private function setForegroundAndBackgroundColors($title, $author)
	{
		if (isset($this->backgroundColor) && isset($this->foregroundColor)) {
			return;
		}
		$base_saturation = 100;
		$base_brightness = 90;
		$color_distance = 100;

		$counts = strlen($title) + strlen($author);
		//Get the color seed based on the number of characters in the title and author.
		//We want a number from 10 to 360
		$color_seed = (int)_map(_clip($counts, 2, 80), 2, 80, 10, 360);

		$this->foregroundColor = ColorUtils::colorHSLToRGB($color_seed, $base_saturation, $base_brightness - ($counts % 20) / 100);
		$this->backgroundColor = ColorUtils::colorHSLToRGB(
			($color_seed + $color_distance) % 360,
			$base_saturation,
			$base_brightness
		);
		if (($counts % 10) == 0) {
			$tmp = $this->foregroundColor;
			$this->foregroundColor = $this->backgroundColor;
			$this->backgroundColor = $tmp;
		}
	}

	public function getCover($title, $author, $filename)
	{
		$this->setForegroundAndBackgroundColors($title, $author);
		//Create the background image
		$imageCanvas = imagecreate($this->imageWidth, $this->imageHeight);
		imagesx($imageCanvas);
		imagesy($imageCanvas);

		//Define our colors
		$white = imagecolorallocate($imageCanvas, 255, 255, 255);
		$backgroundColor = imagecolorallocate($imageCanvas, $this->backgroundColor['r'], $this->backgroundColor['g'], $this->backgroundColor['b']);
		$foregroundColor = imagecolorallocate($imageCanvas, $this->foregroundColor['r'], $this->foregroundColor['g'], $this->foregroundColor['b']);

		//Draw a white background for the entire image
		imagefilledrectangle($imageCanvas, 0, 0, $this->imageWidth, $this->imageHeight, $white);
		//Draw a small margin at the top
		imagefilledrectangle($imageCanvas, 0, 0, $this->imageWidth, $this->topMargin, $backgroundColor);

		$artworkHeight = $this->drawArtwork($imageCanvas, $backgroundColor, $foregroundColor, $title);

		$this->drawText($imageCanvas, $title, $author, $artworkHeight);

		imagepng($imageCanvas, $filename);
		imagedestroy($imageCanvas);
	}

	private function drawText($imageCanvas, $title, $author, $artworkHeight)
	{
		$textColor = imagecolorallocate($imageCanvas, 50, 50, 50);

		$title_font_size = $this->imageWidth * 0.07;
		//$title_height = ($this->imageHeight - $this->imageWidth - ($this->imageHeight * $this->topMargin / 100)) * 0.75;

		$x = 10;
		$y = 15;
		$width = $this->imageWidth - (20);

		$title = StringUtils::trimStringToLengthAtWordBoundary($title, 60, true);
		/** @noinspection PhpUnusedLocalVariableInspection */
		list($totalHeight, $lines) = wrapTextForDisplay($this->titleFont, $title, $title_font_size, $title_font_size * .1, $width);
		addWrappedTextToImage($imageCanvas, $this->titleFont, $lines, $title_font_size, $title_font_size * .1, $x, $y, $textColor);

		$author_font_size = $this->imageWidth * 0.055;

		$width = $this->imageWidth - (2 * $this->imageHeight * $this->topMargin / 100);
		$author = StringUtils::trimStringToLengthAtWordBoundary($author, 40, true);
		list($totalHeight, $lines) = wrapTextForDisplay($this->authorFont, $author, $author_font_size, $author_font_size * .1, $width);
		$y = $this->imageHeight - $artworkHeight - $totalHeight - 5;
		addWrappedTextToImage($imageCanvas, $this->authorFont, $lines, $author_font_size, $author_font_size * .1, $x, $y, $textColor);
	}

	private function drawArtwork($imageCanvas, $backgroundColor, $foregroundColor, $title)
	{
		$artworkStartX = 0;
		$artworkStartY = $this->imageHeight - $this->imageWidth;

		list($gridCount, $gridTotal, $gridSize) = $this->breakGrid($title);
		$c64_title = $this->c64Convert($title);
		$c64_title = str_pad($c64_title, $gridTotal, ' ');

		$rowsToSkip = 0;
		if ($gridCount > 5) {
			$rowsToSkip = 1;
		}
		for ($i = 0; $i < $gridTotal; $i++) {
			$char = $c64_title[$i];
			$grid_x = (int)($i % $gridCount);

			$grid_y = (int)($i / $gridCount) + $rowsToSkip;
			$x = $grid_x * $gridSize + $artworkStartX;
			$y = $grid_y * $gridSize + $artworkStartY;

			if ($y < $this->imageHeight) {
				//Draw the artwork background
				imagefilledrectangle($imageCanvas, $x, $y, $x + $gridSize, $y + $gridSize, $backgroundColor);
				$this->drawShape($imageCanvas, $backgroundColor, $foregroundColor, $char, $x, $y, $gridSize);
			}

		}
		return ($gridCount - $rowsToSkip) * $gridSize;
	}

	private function c64Convert($title)
	{
		$title = strtolower($title);
		$c64_letters = " qwertyuiopasdfghjkl:zxcvbnm,;?<>@[]1234567890.=-+*/";
		$c64_title = "";
		for ($i = 0; $i < strlen($title); $i++) {
			$char = $title[$i];
			if (strpos($c64_letters, $char) !== false) {
				$c64_title .= $char;
			} else {
				$c64_title .= $c64_letters[ord($char) % strlen($c64_letters)];
			}
		}
		return $c64_title;
	}

	//Compute the graphics grid size based on the length of the book title.  We want to show as much of the title as
	//possible without having extra blank space at the end
	private function breakGrid($title)
	{
		$min_title = 2;
		$max_title = 60;
		$length = _clip(strlen($title), $min_title, $max_title);

		$grid_count = _clip(floor(sqrt($length)), 2, 11);
		$grid_total = $grid_count * $grid_count;
		$grid_size = $this->imageWidth / $grid_count;
		return array($grid_count, $grid_total, $grid_size);
	}

	private function drawShape($imageCanvas, $backgroundColor, $foregroundColor, $char, $x, $y, $gridSize)
	{
		$shape_thickness = 10;
		$thick = _clip($gridSize * $shape_thickness / 100, 4, 10);
		imagesetthickness($imageCanvas, $thick);
		if ($char == "q") {
			imagefilledellipse($imageCanvas, $x + $gridSize / 2, $y + $gridSize / 2, $gridSize, $gridSize, $foregroundColor);
		} else if ($char == "w") {
			imagefilledellipse($imageCanvas, $x + $gridSize / 2, $y + $gridSize / 2, $gridSize, $gridSize, $foregroundColor);
			imagefilledellipse($imageCanvas, $x + $gridSize / 2, $y + $gridSize / 2, $gridSize - ($thick * 2), $gridSize - ($thick * 2), $backgroundColor);
		} else if ($char == "e") {
			$this->imageFilledRectangle($imageCanvas, $x, $y + $thick, $gridSize, $thick, $foregroundColor);
		} else if ($char == "r") {
			$this->imageFilledRectangle($imageCanvas, $x, $y + $gridSize - ($thick * 2), $gridSize, $thick, $foregroundColor);
		} else if ($char == "t") {
			$this->imageFilledRectangle($imageCanvas, $x + $thick, $y, $thick, $gridSize, $foregroundColor);
		} else if ($char == "y") {
			$this->imageFilledRectangle($imageCanvas, $x + $gridSize - ($thick * 2), $y, $thick, $gridSize, $foregroundColor);
		} else if ($char == "u") {
			imagearc($imageCanvas, $x + $gridSize, $y + $gridSize, 2 * ($gridSize - $thick), 2 * ($gridSize - $thick), 180, 270, $foregroundColor);
		} else if ($char == "i") {
			imagearc($imageCanvas, $x, $y + $gridSize, 2 * ($gridSize - $thick), 2 * ($gridSize - $thick), 270, 360, $foregroundColor);
		} else if ($char == "o") {
			$this->imageFilledRectangle($imageCanvas, $x, $y, $gridSize, $thick, $foregroundColor);
			$this->imageFilledRectangle($imageCanvas, $x, $y, $thick, $gridSize, $foregroundColor);
		} else if ($char == "p") {
			$this->imageFilledRectangle($imageCanvas, $x, $y, $gridSize, $thick, $foregroundColor);
			$this->imageFilledRectangle($imageCanvas, $x + $gridSize - $thick, $y, $thick, $gridSize, $foregroundColor);
		} else if ($char == "a") {
			imagefilledpolygon($imageCanvas, [$x, $y + $gridSize, $x + ($gridSize / 2), $y, $x + $gridSize, $y + $gridSize], 3, $foregroundColor);
		} else if ($char == "s") {
			imagefilledpolygon($imageCanvas, [$x, $y, $x + ($gridSize / 2), $y + $gridSize, $x + $gridSize, $y], 3, $foregroundColor);
		} else if ($char == "d") {
			$this->imageFilledRectangle($imageCanvas, $x, $y + ($thick * 2), $gridSize, $thick, $foregroundColor);
		} else if ($char == "f") {
			$this->imageFilledRectangle($imageCanvas, $x, $y + $gridSize - ($thick * 3), $gridSize, $thick, $foregroundColor);
		} else if ($char == "g") {
			$this->imageFilledRectangle($imageCanvas, $x + ($thick * 2), $y, $thick, $gridSize, $foregroundColor);
		} else if ($char == "h") {
			$this->imageFilledRectangle($imageCanvas, $x + $gridSize - ($thick * 3), $y, $thick, $gridSize, $foregroundColor);
		} else if ($char == "j") {
			imagearc($imageCanvas, $x + $gridSize, $y, 2 * ($gridSize - $thick), 2 * ($gridSize - $thick), 90, 180, $foregroundColor);
		} else if ($char == "k") {
			imagearc($imageCanvas, $x, $y, 2 * ($gridSize - $thick), 2 * ($gridSize - $thick), 0, 90, $foregroundColor);
		} else if ($char == "l") {
			$this->imageFilledRectangle($imageCanvas, $x, $y, $thick, $gridSize, $foregroundColor);
			$this->imageFilledRectangle($imageCanvas, $x, $y + $gridSize - $thick, $gridSize, $thick, $foregroundColor);
		} else if ($char == ":") {
			$this->imageFilledRectangle($imageCanvas, $x + $gridSize - $thick, $y, $thick, $gridSize, $foregroundColor);
			$this->imageFilledRectangle($imageCanvas, $x, $y + $gridSize - $thick, $gridSize, $thick, $foregroundColor);
		} else if ($char == "z") {
			imagefilledpolygon($imageCanvas, [$x, $y + ($gridSize / 2), $x + ($gridSize / 2), $y, $x + $gridSize, $y + ($gridSize / 2)], 3, $foregroundColor);
			imagefilledpolygon($imageCanvas, [$x, $y + ($gridSize / 2), $x + ($gridSize / 2), $y + $gridSize, $x + $gridSize, $y + ($gridSize / 2)], 3, $foregroundColor);
		} else if ($char == "x") {
			imagefilledellipse($imageCanvas, $x + ($gridSize / 2), $y + ($gridSize / 3), $thick * 2, $thick * 2, $foregroundColor);
			imagefilledellipse($imageCanvas, $x + ($gridSize / 3), $y + $gridSize - ($gridSize / 3), $thick * 2, $thick * 2, $foregroundColor);
			imagefilledellipse($imageCanvas, $x + $gridSize - ($gridSize / 3), $y + $gridSize - ($gridSize / 3), $thick * 2, $thick * 2, $foregroundColor);
		} else if ($char == "c") {
			$this->imageFilledRectangle($imageCanvas, $x, $y + ($thick * 3), $gridSize, $thick, $foregroundColor);
		} else if ($char == "v") {
			imagesetthickness($imageCanvas, 1);
			$this->imageFilledRectangle($imageCanvas, $x, $y, $gridSize, $gridSize, $foregroundColor);
			imagefilledpolygon($imageCanvas, [$x + $thick, $y, $x + ($gridSize / 2), $y + ($gridSize / 2) - $thick, $x + $gridSize - $thick, $y], 3, $backgroundColor);
			imagefilledpolygon($imageCanvas, [$x, $y + $thick, $x + ($gridSize / 2) - $thick, $y + ($gridSize / 2), $x, $y + $gridSize - $thick], 3, $backgroundColor);
			imagefilledpolygon($imageCanvas, [$x + $thick, $y + $gridSize, $x + ($gridSize / 2), $y + ($gridSize / 2) + $thick, $x + $gridSize - $thick, $y + $gridSize], 3, $backgroundColor);
			imagefilledpolygon($imageCanvas, [$x + $gridSize, $y + $thick, $x + $gridSize, $y + $gridSize - $thick, $x + ($gridSize / 2) + $thick, $y + ($gridSize / 2)], 3, $backgroundColor);
			imagesetthickness($imageCanvas, $thick);
		} else if ($char == "b") {
			$this->imageFilledRectangle($imageCanvas, $x + ($thick * 3), $y, $thick, $gridSize, $foregroundColor);
		} else if ($char == "n") {
			imagesetthickness($imageCanvas, 1);
			$this->imageFilledRectangle($imageCanvas, $x, $y, $gridSize, $gridSize, $foregroundColor);
			imagefilledpolygon($imageCanvas, [$x, $y, $x + $gridSize - $thick, $y, $x, $y + $gridSize - $thick], 3, $backgroundColor);
			imagefilledpolygon($imageCanvas, [$x + $thick, $y + $gridSize, $x + $gridSize, $y + $gridSize, $x + $gridSize, $y + $thick], 3, $backgroundColor);
			imagesetthickness($imageCanvas, $thick);
		} else if ($char == "m") {
			imagesetthickness($imageCanvas, 1);
			$this->imageFilledRectangle($imageCanvas, $x, $y, $gridSize, $gridSize, $foregroundColor);
			imagefilledpolygon($imageCanvas, [$x + $thick, $y, $x + $gridSize, $y, $x + $gridSize, $y + $gridSize - $thick], 3, $backgroundColor);
			imagefilledpolygon($imageCanvas, [$x, $y + $thick, $x, $y + $gridSize, $x + $gridSize - $thick, $y + $gridSize], 3, $backgroundColor);
			imagesetthickness($imageCanvas, $thick);
		} else if ($char == ",") {
			$this->imageFilledRectangle($imageCanvas, $x + ($gridSize / 2), $y + ($gridSize / 2), $gridSize / 2, $gridSize / 2, $foregroundColor);
		} else if ($char == ";") {
			$this->imageFilledRectangle($imageCanvas, $x, $y + ($gridSize / 2), $gridSize / 2, $gridSize / 2, $foregroundColor);
		} else if ($char == "?") {
			$this->imageFilledRectangle($imageCanvas, $x, $y, $gridSize / 2, $gridSize / 2, $foregroundColor);
			$this->imageFilledRectangle($imageCanvas, $x + ($gridSize / 2), $y + ($gridSize / 2), $gridSize / 2, $gridSize / 2, $foregroundColor);
		} else if ($char == "<") {
			$this->imageFilledRectangle($imageCanvas, $x + ($gridSize / 2), $y, $gridSize / 2, $gridSize / 2, $foregroundColor);
		} else if ($char == ">") {
			$this->imageFilledRectangle($imageCanvas, $x, $y, $gridSize / 2, $gridSize / 2, $foregroundColor);
		} else if ($char == "@") {
			$this->imageFilledRectangle($imageCanvas, $x, $y + ($gridSize / 2) - ($thick / 2), $gridSize, $thick, $foregroundColor);
		} else if ($char == "[") {
			$this->imageFilledRectangle($imageCanvas, $x + ($gridSize / 2) - ($thick / 2), $y, $thick, $gridSize, $foregroundColor);
		} else if ($char == "]") {
			$this->imageFilledRectangle($imageCanvas, $x, $y + ($gridSize / 2) - ($thick / 2), $gridSize, $thick, $foregroundColor);
			$this->imageFilledRectangle($imageCanvas, $x + ($gridSize / 2) - ($thick / 2), $y, $thick, $gridSize, $foregroundColor);
		} else if ($char == "0") {
			$this->imageFilledRectangle($imageCanvas, $x + ($gridSize / 2) - ($thick / 2), $y + ($gridSize / 2) - ($thick / 2), $thick, $gridSize / 2 + $thick / 2, $foregroundColor);
			$this->imageFilledRectangle($imageCanvas, $x + ($gridSize / 2) - ($thick / 2), $y + ($gridSize / 2) - ($thick / 2), $gridSize / 2 + $thick / 2, $thick, $foregroundColor);
		} else if ($char == "1") {
			$this->imageFilledRectangle($imageCanvas, $x, $y + ($gridSize / 2) - ($thick / 2), $gridSize, $thick, $foregroundColor);
			$this->imageFilledRectangle($imageCanvas, $x + ($gridSize / 2) - ($thick / 2), $y, $thick, $gridSize / 2 + $thick / 2, $foregroundColor);
		} else if ($char == "2") {
			$this->imageFilledRectangle($imageCanvas, $x, $y + ($gridSize / 2) - ($thick / 2), $gridSize, $thick, $foregroundColor);
			$this->imageFilledRectangle($imageCanvas, $x + ($gridSize / 2) - ($thick / 2), $y + ($gridSize / 2) - ($thick / 2), $thick, $gridSize / 2 + $thick / 2, $foregroundColor);
		} else if ($char == "3") {
			$this->imageFilledRectangle($imageCanvas, $x, $y + ($gridSize / 2) - ($thick / 2), $gridSize / 2 + $thick / 2, $thick, $foregroundColor);
			$this->imageFilledRectangle($imageCanvas, $x + ($gridSize / 2) - ($thick / 2), $y, $thick, $gridSize, $foregroundColor);
		} else if ($char == "4") {
			$this->imageFilledRectangle($imageCanvas, $x, $y, $thick * 2, $gridSize, $foregroundColor);
		} else if ($char == "5") {
			$this->imageFilledRectangle($imageCanvas, $x, $y, $thick * 3, $gridSize, $foregroundColor);
		} else if ($char == "6") {
			$this->imageFilledRectangle($imageCanvas, $x + $gridSize - ($thick * 3), $y, $thick * 3, $gridSize, $foregroundColor);
		} else if ($char == "7") {
			$this->imageFilledRectangle($imageCanvas, $x, $y, $gridSize, $thick * 2, $foregroundColor);
		} else if ($char == "8") {
			$this->imageFilledRectangle($imageCanvas, $x, $y, $gridSize, $thick * 3, $foregroundColor);
		} else if ($char == "9") {
			$this->imageFilledRectangle($imageCanvas, $x, $y + $gridSize - ($thick * 3), $gridSize, $thick * 3, $foregroundColor);
		} else if ($char == ".") {
			$this->imageFilledRectangle($imageCanvas, $x + ($gridSize / 2) - ($thick / 2), $y + ($gridSize / 2) - ($thick / 2), $thick, $gridSize / 2 + $thick / 2, $foregroundColor);
			$this->imageFilledRectangle($imageCanvas, $x, $y + ($gridSize / 2) - ($thick / 2), $gridSize / 2 + $thick / 2, $thick, $foregroundColor);
		} else if ($char == "=") {
			$this->imageFilledRectangle($imageCanvas, $x + ($gridSize / 2) - ($thick / 2), $y, $thick, $gridSize / 2 + $thick / 2, $foregroundColor);
			$this->imageFilledRectangle($imageCanvas, $x, $y + ($gridSize / 2) - ($thick / 2), $gridSize / 2, $thick, $foregroundColor);
		} else if ($char == "-") {
			$this->imageFilledRectangle($imageCanvas, $x + ($gridSize / 2) - ($thick / 2), $y, $thick, $gridSize / 2 + $thick / 2, $foregroundColor);
			$this->imageFilledRectangle($imageCanvas, $x + ($gridSize / 2) - ($thick / 2), $y + ($gridSize / 2) - ($thick / 2), $gridSize / 2 + $thick / 2, $thick, $foregroundColor);
		} else if ($char == "+") {
			$this->imageFilledRectangle($imageCanvas, $x + ($gridSize / 2) - ($thick / 2), $y + ($gridSize / 2) - ($thick / 2), $gridSize / 2 + $thick / 2, $thick, $foregroundColor);
			$this->imageFilledRectangle($imageCanvas, $x + ($gridSize / 2) - ($thick / 2), $y, $thick, $gridSize, $foregroundColor);
		} else if ($char == "*") {
			$this->imageFilledRectangle($imageCanvas, $x + $gridSize - ($thick * 2), $y, $thick * 2, $gridSize, $foregroundColor);
		} else if ($char == "/") {
			$this->imageFilledRectangle($imageCanvas, $x, $y + $gridSize - ($thick * 2), $gridSize, $thick * 2, $foregroundColor);
		} else if ($char == " ") {
			$this->imageFilledRectangle($imageCanvas, $x, $y, $gridSize, $gridSize, $backgroundColor);
		}
		imagesetthickness($imageCanvas, 1);
	}

	private function imageFilledRectangle($imageCanvas, $x, $y, $width, $height, $color)
	{
		imagefilledrectangle($imageCanvas, $x, $y, $x + $width, $y + $height, $color);
	}


}