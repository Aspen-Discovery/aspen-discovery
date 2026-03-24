<?php

require_once ROOT_DIR . '/sys/Utils/ColorUtils.php';
require_once ROOT_DIR . '/sys/Utils/StringUtils.php';
require_once ROOT_DIR . '/sys/Covers/CoverImageUtils.php';

class DefaultCoverImageBuilder {
	private $imageWidth = 280; //Pixels
	private $imageHeight = 400; // Pixels
	private $topMargin = 10;
	private $titleFont;
	private $authorFont;
	private $backgroundColor;
	private $foregroundColor;
	private $defaultCoverImage;

	public function __construct($invertColors = false) {
		global $interface;
		if ($interface == null) {
			//Need to initialize the interface to get access to the themes
			//This is needed because we try to minimize what loads for book covers for performance
			$interface = new UInterface();
			$interface->loadDisplayOptions(true);
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
				if (empty($this->foregroundColor) && !$theme->secondaryBackgroundColorDefault) {
					$colors = sscanf($theme->secondaryBackgroundColor, "#%02x%02x%02x");
					$this->foregroundColor = [
						'r' => $colors[0],
						'g' => $colors[1],
						'b' => $colors[2],
					];
				}
				if ($invertColors) {
					$tmpColor = $this->backgroundColor;
					$this->backgroundColor = $this->foregroundColor;
					$this->foregroundColor = $tmpColor;
				}
				if (!empty($theme->defaultCover)) {
					$this->defaultCoverImage = ROOT_DIR . '/files/original/' . $theme->defaultCover;
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

	private function setForegroundAndBackgroundColors($title, $author) {
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
		$this->backgroundColor = ColorUtils::colorHSLToRGB(($color_seed + $color_distance) % 360, $base_saturation, $base_brightness);
		if (($counts % 10) == 0) {
			$tmp = $this->foregroundColor;
			$this->foregroundColor = $this->backgroundColor;
			$this->backgroundColor = $tmp;
		}
	}

	public function getCover($title, ?string $author, $filename, $image = null): void {
		$script = $this->detectScript($title . ($author ?? ''));
		$this->selectFontForScript($script);

		$this->setForegroundAndBackgroundColors($title, $author);
		//Create the background image
		$imageCanvas = imagecreatetruecolor($this->imageWidth, $this->imageHeight);

		//Define our colors
		$white = imagecolorallocate($imageCanvas, 255, 255, 255);
		$backgroundColor = imagecolorallocate($imageCanvas, $this->backgroundColor['r'], $this->backgroundColor['g'], $this->backgroundColor['b']);
		$foregroundColor = imagecolorallocate($imageCanvas, $this->foregroundColor['r'], $this->foregroundColor['g'], $this->foregroundColor['b']);

		//Draw a white background for the entire image
		imagefilledrectangle($imageCanvas, 0, 0, $this->imageWidth, $this->imageHeight, $white);
		//Draw a small margin at the top
		imagefilledrectangle($imageCanvas, 0, 0, $this->imageWidth, $this->topMargin, $backgroundColor);

		$artworkHeight = $this->drawArtwork($imageCanvas, $backgroundColor, $foregroundColor, $title);
		if ($script === 'arabic' && $author !== null) {
			$author = $this->transliterateToArabic($author);
		}
		$this->drawText($imageCanvas, $title, $author, $artworkHeight);

		if (!empty($this->defaultCoverImage) || !empty($image)){
			if (!empty($image)){
				$imageInfo = getimagesize($image);
			}else{
				$imageInfo = getimagesize($this->defaultCoverImage);
			}

			$originalHeight = $imageInfo[0];
			$originalWidth = $imageInfo[1];
			$width = $originalWidth;
			$height = $originalHeight;
			$imageRatio = $originalHeight / $originalWidth;
			$rectRatio = $artworkHeight / $this->imageWidth;
			if ($imageRatio < $rectRatio){
				$width = $this->imageWidth;
				$height = ($width * $originalHeight) / $originalWidth;
			}
			if ($rectRatio < $imageRatio) {
				$height = $artworkHeight;
				$width = ($height * $originalWidth) / $originalHeight;
			}

			if (!empty($image)) {
				$uploadedImage = imagecreatefromstring(file_get_contents($image));
			}else {
				$uploadedImage = imagecreatefromstring(file_get_contents($this->defaultCoverImage));
			}

			$uploadedResized = imagescale($uploadedImage, $width, $height);
			$artworkStartY = $this->imageHeight - $artworkHeight;

			imagecopyresampled($imageCanvas, $uploadedResized, 0, $artworkStartY, 0, 0, $this->imageWidth, $artworkHeight, $width, $height);
		}

		imagepng($imageCanvas, $filename);
		imagedestroy($imageCanvas);
		if (!empty($uploadedImage)){
			imagedestroy($uploadedImage);
		}
	}

	private function drawText(GdImage|bool $imageCanvas, string $title, ?string $author, float $artworkHeight): void {
		$textColor = imagecolorallocate($imageCanvas, 50, 50, 50);
		$title_font_size = $this->imageWidth * 0.07;
		$x = 10;
		$y = 15;
		$width = $this->imageWidth - (20);

		$titleTrimmed = StringUtils::trimStringToLengthAtWordBoundary($title, 60, true);
		if (mb_strlen(trim(str_replace('...', '', $titleTrimmed)), 'UTF-8') === 0) {
			// From observation, some characters in certain scripts are large enough to overlap with the artwork,
			// so truncate at 40 characters.
			$titleTrimmed = mb_substr($title, 0, 40, 'UTF-8') . '...';
		}
		$title = $titleTrimmed;
		[, $titleLines,] = wrapTextForDisplay($this->titleFont, $title, $title_font_size, $title_font_size * .1, $width);
		// Draw title and capture the Y position returned (bottom of drawn text).
		$y = addWrappedTextToImage($imageCanvas, $this->titleFont, $titleLines, $title_font_size, $title_font_size * .1, $x, $y, $textColor);

		// Small spacing between title and author.
		$y += 5;

		$author_font_size = $this->imageWidth * 0.055;
		$width = $this->imageWidth - (2 * $this->imageHeight * $this->topMargin / 100);
		$author = $author ? StringUtils::trimStringToLengthAtWordBoundary($author, 40, true) : '';
		[$authorHeight, $authorLines] = wrapTextForDisplay($this->authorFont, $author, $author_font_size, $author_font_size * .1, $width);

		// Ensure author does not overlap artwork section.
		$minYForAuthor = $this->imageHeight - $artworkHeight - $authorHeight - 5;
		if ($y < $minYForAuthor) {
			$y = $minYForAuthor;
		}

		addWrappedTextToImage($imageCanvas, $this->authorFont, $authorLines, $author_font_size, $author_font_size * .1, $x, $y, $textColor);
	}

	private function drawArtwork($imageCanvas, $backgroundColor, $foregroundColor, $title) {
		$artworkStartX = 0;
		$artworkStartY = $this->imageHeight - $this->imageWidth;

		[
			$gridCount,
			$gridTotal,
			$gridSize,
		] = $this->breakGrid($title);
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

			if ($y < $this->imageHeight && empty($this->defaultCoverImage)) {
				//Draw the artwork background
				imagefilledrectangle($imageCanvas, $x, $y, $x + $gridSize, $y + $gridSize, $backgroundColor);
				$this->drawShape($imageCanvas, $backgroundColor, $foregroundColor, $char, $x, $y, $gridSize);
			}
		}
		return ($gridCount - $rowsToSkip) * $gridSize;
	}

	private function c64Convert($title) {
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
	private function breakGrid($title) {
		$min_title = 2;
		$max_title = 60;
		$length = _clip(strlen($title), $min_title, $max_title);

		$grid_count = _clip(floor(sqrt($length)), 2, 11);
		$grid_total = $grid_count * $grid_count;
		$grid_size = $this->imageWidth / $grid_count;
		return [
			$grid_count,
			$grid_total,
			$grid_size,
		];
	}

	private function drawShape($imageCanvas, $backgroundColor, $foregroundColor, $char, $x, $y, $gridSize) {
		$shape_thickness = 10;
		$thick = _clip($gridSize * $shape_thickness / 100, 4, 10);
		imagesetthickness($imageCanvas, $thick);
		if ($char == "q") {
			imagefilledellipse($imageCanvas, $x + $gridSize / 2, $y + $gridSize / 2, $gridSize, $gridSize, $foregroundColor);
		} elseif ($char == "w") {
			imagefilledellipse($imageCanvas, $x + $gridSize / 2, $y + $gridSize / 2, $gridSize, $gridSize, $foregroundColor);
			imagefilledellipse($imageCanvas, $x + $gridSize / 2, $y + $gridSize / 2, $gridSize - ($thick * 2), $gridSize - ($thick * 2), $backgroundColor);
		} elseif ($char == "e") {
			$this->imageFilledRectangle($imageCanvas, $x, $y + $thick, $gridSize, $thick, $foregroundColor);
		} elseif ($char == "r") {
			$this->imageFilledRectangle($imageCanvas, $x, $y + $gridSize - ($thick * 2), $gridSize, $thick, $foregroundColor);
		} elseif ($char == "t") {
			$this->imageFilledRectangle($imageCanvas, $x + $thick, $y, $thick, $gridSize, $foregroundColor);
		} elseif ($char == "y") {
			$this->imageFilledRectangle($imageCanvas, $x + $gridSize - ($thick * 2), $y, $thick, $gridSize, $foregroundColor);
		} elseif ($char == "u") {
			imagearc($imageCanvas, $x + $gridSize, $y + $gridSize, 2 * ($gridSize - $thick), 2 * ($gridSize - $thick), 180, 270, $foregroundColor);
		} elseif ($char == "i") {
			imagearc($imageCanvas, $x, $y + $gridSize, 2 * ($gridSize - $thick), 2 * ($gridSize - $thick), 270, 360, $foregroundColor);
		} elseif ($char == "o") {
			$this->imageFilledRectangle($imageCanvas, $x, $y, $gridSize, $thick, $foregroundColor);
			$this->imageFilledRectangle($imageCanvas, $x, $y, $thick, $gridSize, $foregroundColor);
		} elseif ($char == "p") {
			$this->imageFilledRectangle($imageCanvas, $x, $y, $gridSize, $thick, $foregroundColor);
			$this->imageFilledRectangle($imageCanvas, $x + $gridSize - $thick, $y, $thick, $gridSize, $foregroundColor);
		} elseif ($char == "a") {
			imagefilledpolygon($imageCanvas, [
				$x,
				$y + $gridSize,
				$x + ($gridSize / 2),
				$y,
				$x + $gridSize,
				$y + $gridSize,
			], 3, $foregroundColor);
		} elseif ($char == "s") {
			imagefilledpolygon($imageCanvas, [
				$x,
				$y,
				$x + ($gridSize / 2),
				$y + $gridSize,
				$x + $gridSize,
				$y,
			], 3, $foregroundColor);
		} elseif ($char == "d") {
			$this->imageFilledRectangle($imageCanvas, $x, $y + ($thick * 2), $gridSize, $thick, $foregroundColor);
		} elseif ($char == "f") {
			$this->imageFilledRectangle($imageCanvas, $x, $y + $gridSize - ($thick * 3), $gridSize, $thick, $foregroundColor);
		} elseif ($char == "g") {
			$this->imageFilledRectangle($imageCanvas, $x + ($thick * 2), $y, $thick, $gridSize, $foregroundColor);
		} elseif ($char == "h") {
			$this->imageFilledRectangle($imageCanvas, $x + $gridSize - ($thick * 3), $y, $thick, $gridSize, $foregroundColor);
		} elseif ($char == "j") {
			imagearc($imageCanvas, $x + $gridSize, $y, 2 * ($gridSize - $thick), 2 * ($gridSize - $thick), 90, 180, $foregroundColor);
		} elseif ($char == "k") {
			imagearc($imageCanvas, $x, $y, 2 * ($gridSize - $thick), 2 * ($gridSize - $thick), 0, 90, $foregroundColor);
		} elseif ($char == "l") {
			$this->imageFilledRectangle($imageCanvas, $x, $y, $thick, $gridSize, $foregroundColor);
			$this->imageFilledRectangle($imageCanvas, $x, $y + $gridSize - $thick, $gridSize, $thick, $foregroundColor);
		} elseif ($char == ":") {
			$this->imageFilledRectangle($imageCanvas, $x + $gridSize - $thick, $y, $thick, $gridSize, $foregroundColor);
			$this->imageFilledRectangle($imageCanvas, $x, $y + $gridSize - $thick, $gridSize, $thick, $foregroundColor);
		} elseif ($char == "z") {
			imagefilledpolygon($imageCanvas, [
				$x,
				$y + ($gridSize / 2),
				$x + ($gridSize / 2),
				$y,
				$x + $gridSize,
				$y + ($gridSize / 2),
			], 3, $foregroundColor);
			imagefilledpolygon($imageCanvas, [
				$x,
				$y + ($gridSize / 2),
				$x + ($gridSize / 2),
				$y + $gridSize,
				$x + $gridSize,
				$y + ($gridSize / 2),
			], 3, $foregroundColor);
		} elseif ($char == "x") {
			imagefilledellipse($imageCanvas, $x + ($gridSize / 2), $y + ($gridSize / 3), $thick * 2, $thick * 2, $foregroundColor);
			imagefilledellipse($imageCanvas, $x + ($gridSize / 3), $y + $gridSize - ($gridSize / 3), $thick * 2, $thick * 2, $foregroundColor);
			imagefilledellipse($imageCanvas, $x + $gridSize - ($gridSize / 3), $y + $gridSize - ($gridSize / 3), $thick * 2, $thick * 2, $foregroundColor);
		} elseif ($char == "c") {
			$this->imageFilledRectangle($imageCanvas, $x, $y + ($thick * 3), $gridSize, $thick, $foregroundColor);
		} elseif ($char == "v") {
			imagesetthickness($imageCanvas, 1);
			$this->imageFilledRectangle($imageCanvas, $x, $y, $gridSize, $gridSize, $foregroundColor);
			imagefilledpolygon($imageCanvas, [
				$x + $thick,
				$y,
				$x + ($gridSize / 2),
				$y + ($gridSize / 2) - $thick,
				$x + $gridSize - $thick,
				$y,
			], 3, $backgroundColor);
			imagefilledpolygon($imageCanvas, [
				$x,
				$y + $thick,
				$x + ($gridSize / 2) - $thick,
				$y + ($gridSize / 2),
				$x,
				$y + $gridSize - $thick,
			], 3, $backgroundColor);
			imagefilledpolygon($imageCanvas, [
				$x + $thick,
				$y + $gridSize,
				$x + ($gridSize / 2),
				$y + ($gridSize / 2) + $thick,
				$x + $gridSize - $thick,
				$y + $gridSize,
			], 3, $backgroundColor);
			imagefilledpolygon($imageCanvas, [
				$x + $gridSize,
				$y + $thick,
				$x + $gridSize,
				$y + $gridSize - $thick,
				$x + ($gridSize / 2) + $thick,
				$y + ($gridSize / 2),
			], 3, $backgroundColor);
			imagesetthickness($imageCanvas, $thick);
		} elseif ($char == "b") {
			$this->imageFilledRectangle($imageCanvas, $x + ($thick * 3), $y, $thick, $gridSize, $foregroundColor);
		} elseif ($char == "n") {
			imagesetthickness($imageCanvas, 1);
			$this->imageFilledRectangle($imageCanvas, $x, $y, $gridSize, $gridSize, $foregroundColor);
			imagefilledpolygon($imageCanvas, [
				$x,
				$y,
				$x + $gridSize - $thick,
				$y,
				$x,
				$y + $gridSize - $thick,
			], 3, $backgroundColor);
			imagefilledpolygon($imageCanvas, [
				$x + $thick,
				$y + $gridSize,
				$x + $gridSize,
				$y + $gridSize,
				$x + $gridSize,
				$y + $thick,
			], 3, $backgroundColor);
			imagesetthickness($imageCanvas, $thick);
		} elseif ($char == "m") {
			imagesetthickness($imageCanvas, 1);
			$this->imageFilledRectangle($imageCanvas, $x, $y, $gridSize, $gridSize, $foregroundColor);
			imagefilledpolygon($imageCanvas, [
				$x + $thick,
				$y,
				$x + $gridSize,
				$y,
				$x + $gridSize,
				$y + $gridSize - $thick,
			], 3, $backgroundColor);
			imagefilledpolygon($imageCanvas, [
				$x,
				$y + $thick,
				$x,
				$y + $gridSize,
				$x + $gridSize - $thick,
				$y + $gridSize,
			], 3, $backgroundColor);
			imagesetthickness($imageCanvas, $thick);
		} elseif ($char == ",") {
			$this->imageFilledRectangle($imageCanvas, $x + ($gridSize / 2), $y + ($gridSize / 2), $gridSize / 2, $gridSize / 2, $foregroundColor);
		} elseif ($char == ";") {
			$this->imageFilledRectangle($imageCanvas, $x, $y + ($gridSize / 2), $gridSize / 2, $gridSize / 2, $foregroundColor);
		} elseif ($char == "?") {
			$this->imageFilledRectangle($imageCanvas, $x, $y, $gridSize / 2, $gridSize / 2, $foregroundColor);
			$this->imageFilledRectangle($imageCanvas, $x + ($gridSize / 2), $y + ($gridSize / 2), $gridSize / 2, $gridSize / 2, $foregroundColor);
		} elseif ($char == "<") {
			$this->imageFilledRectangle($imageCanvas, $x + ($gridSize / 2), $y, $gridSize / 2, $gridSize / 2, $foregroundColor);
		} elseif ($char == ">") {
			$this->imageFilledRectangle($imageCanvas, $x, $y, $gridSize / 2, $gridSize / 2, $foregroundColor);
		} elseif ($char == "@") {
			$this->imageFilledRectangle($imageCanvas, $x, $y + ($gridSize / 2) - ($thick / 2), $gridSize, $thick, $foregroundColor);
		} elseif ($char == "[") {
			$this->imageFilledRectangle($imageCanvas, $x + ($gridSize / 2) - ($thick / 2), $y, $thick, $gridSize, $foregroundColor);
		} elseif ($char == "]") {
			$this->imageFilledRectangle($imageCanvas, $x, $y + ($gridSize / 2) - ($thick / 2), $gridSize, $thick, $foregroundColor);
			$this->imageFilledRectangle($imageCanvas, $x + ($gridSize / 2) - ($thick / 2), $y, $thick, $gridSize, $foregroundColor);
		} elseif ($char == "0") {
			$this->imageFilledRectangle($imageCanvas, $x + ($gridSize / 2) - ($thick / 2), $y + ($gridSize / 2) - ($thick / 2), $thick, $gridSize / 2 + $thick / 2, $foregroundColor);
			$this->imageFilledRectangle($imageCanvas, $x + ($gridSize / 2) - ($thick / 2), $y + ($gridSize / 2) - ($thick / 2), $gridSize / 2 + $thick / 2, $thick, $foregroundColor);
		} elseif ($char == "1") {
			$this->imageFilledRectangle($imageCanvas, $x, $y + ($gridSize / 2) - ($thick / 2), $gridSize, $thick, $foregroundColor);
			$this->imageFilledRectangle($imageCanvas, $x + ($gridSize / 2) - ($thick / 2), $y, $thick, $gridSize / 2 + $thick / 2, $foregroundColor);
		} elseif ($char == "2") {
			$this->imageFilledRectangle($imageCanvas, $x, $y + ($gridSize / 2) - ($thick / 2), $gridSize, $thick, $foregroundColor);
			$this->imageFilledRectangle($imageCanvas, $x + ($gridSize / 2) - ($thick / 2), $y + ($gridSize / 2) - ($thick / 2), $thick, $gridSize / 2 + $thick / 2, $foregroundColor);
		} elseif ($char == "3") {
			$this->imageFilledRectangle($imageCanvas, $x, $y + ($gridSize / 2) - ($thick / 2), $gridSize / 2 + $thick / 2, $thick, $foregroundColor);
			$this->imageFilledRectangle($imageCanvas, $x + ($gridSize / 2) - ($thick / 2), $y, $thick, $gridSize, $foregroundColor);
		} elseif ($char == "4") {
			$this->imageFilledRectangle($imageCanvas, $x, $y, $thick * 2, $gridSize, $foregroundColor);
		} elseif ($char == "5") {
			$this->imageFilledRectangle($imageCanvas, $x, $y, $thick * 3, $gridSize, $foregroundColor);
		} elseif ($char == "6") {
			$this->imageFilledRectangle($imageCanvas, $x + $gridSize - ($thick * 3), $y, $thick * 3, $gridSize, $foregroundColor);
		} elseif ($char == "7") {
			$this->imageFilledRectangle($imageCanvas, $x, $y, $gridSize, $thick * 2, $foregroundColor);
		} elseif ($char == "8") {
			$this->imageFilledRectangle($imageCanvas, $x, $y, $gridSize, $thick * 3, $foregroundColor);
		} elseif ($char == "9") {
			$this->imageFilledRectangle($imageCanvas, $x, $y + $gridSize - ($thick * 3), $gridSize, $thick * 3, $foregroundColor);
		} elseif ($char == ".") {
			$this->imageFilledRectangle($imageCanvas, $x + ($gridSize / 2) - ($thick / 2), $y + ($gridSize / 2) - ($thick / 2), $thick, $gridSize / 2 + $thick / 2, $foregroundColor);
			$this->imageFilledRectangle($imageCanvas, $x, $y + ($gridSize / 2) - ($thick / 2), $gridSize / 2 + $thick / 2, $thick, $foregroundColor);
		} elseif ($char == "=") {
			$this->imageFilledRectangle($imageCanvas, $x + ($gridSize / 2) - ($thick / 2), $y, $thick, $gridSize / 2 + $thick / 2, $foregroundColor);
			$this->imageFilledRectangle($imageCanvas, $x, $y + ($gridSize / 2) - ($thick / 2), $gridSize / 2, $thick, $foregroundColor);
		} elseif ($char == "-") {
			$this->imageFilledRectangle($imageCanvas, $x + ($gridSize / 2) - ($thick / 2), $y, $thick, $gridSize / 2 + $thick / 2, $foregroundColor);
			$this->imageFilledRectangle($imageCanvas, $x + ($gridSize / 2) - ($thick / 2), $y + ($gridSize / 2) - ($thick / 2), $gridSize / 2 + $thick / 2, $thick, $foregroundColor);
		} elseif ($char == "+") {
			$this->imageFilledRectangle($imageCanvas, $x + ($gridSize / 2) - ($thick / 2), $y + ($gridSize / 2) - ($thick / 2), $gridSize / 2 + $thick / 2, $thick, $foregroundColor);
			$this->imageFilledRectangle($imageCanvas, $x + ($gridSize / 2) - ($thick / 2), $y, $thick, $gridSize, $foregroundColor);
		} elseif ($char == "*") {
			$this->imageFilledRectangle($imageCanvas, $x + $gridSize - ($thick * 2), $y, $thick * 2, $gridSize, $foregroundColor);
		} elseif ($char == "/") {
			$this->imageFilledRectangle($imageCanvas, $x, $y + $gridSize - ($thick * 2), $gridSize, $thick * 2, $foregroundColor);
		} elseif ($char == " ") {
			$this->imageFilledRectangle($imageCanvas, $x, $y, $gridSize, $gridSize, $backgroundColor);
		}
		imagesetthickness($imageCanvas, 1);
	}

	private function imageFilledRectangle($imageCanvas, $x, $y, $width, $height, $color) {
		imagefilledrectangle($imageCanvas, $x, $y, $x + $width, $y + $height, $color);
	}

	/**
	 * Detect if a string contains any non-ASCII characters.
	 *
	 * @param string $text The text to analyze.
	 * @return bool True if non-ASCII characters are present, otherwise false.
	 */
	private function containsNonAsciiCharacters(string $text): bool {
		return preg_match('/[^\x00-\x7F]/u', $text) === 1;
	}

	/**
	 * SCRIPT_REGEX_MAP
	 * 
	 * Regex mapping for scripts to simplify script selection logic
	 */
	private const SCRIPT_REGEX_MAP = [
		'arabic'     => '/\p{Arabic}/u',
		'armenian'   => '/\p{Armenian}/u',
		'hangul'     => '/\p{Hangul}/u',      // Korean
		'japanese'   => '/[\p{Hiragana}\p{Katakana}]/u', // Kana detection
		'han'		 => '/\p{Han}/u',
		'devanagari' => '/\p{Devanagari}/u',  // Hindi
		'hebrew'     => '/\p{Hebrew}/u',
		'telugu'     => '/\p{Telugu}/u',
		'thai'       => '/\p{Thai}/u',
		'georgian'   => '/\p{Georgian}/u',
		'ethiopic'   => '/\p{Ethiopic}/u',    // Amharic
		'bengali'    => '/\p{Bengali}/u',
		'cyrillic'   => '/[\p{Cyrillic}\p{Greek}]/u',
	];

	/**
	 * Detect the primary script used in the supplied text.
	 *
	 * When adding new scripts, be cautious of the order of detection conditions.
	 * Scripts with overlapping character ranges (e.g., Han used in both Chinese and Japanese)
	 * must be placed appropriately in the regex map to avoid incorrect classification.
	 * 
	 * @param string $text
	 * @return string Language
	 */
	private function detectScript(string $text): string {
		$text = trim($text);
		if ($text === '') {
			return 'latin';
		}

		foreach (self::SCRIPT_REGEX_MAP as $language => $pattern){
			if (preg_match($pattern, $text)) {
				return $language;
			}
		}
		
		return 'latin';
	}

	/**
	 * Select fonts tailored to the detected script if they exist.
	 * Fonts primarily from Google Fonts (https://fonts.google.com/).
	 *
	 * @param string $script
	 */
	private function selectFontForScript(string $script): void {
		$fontMap = [
			'arabic'    =>  ['/fonts/NotoSansArabic-Bold.ttf', '/fonts/NotoSansArabic-Regular.ttf'],
			'armenian'  =>  ['/fonts/NotoSansArmenian-Bold.ttf', '/fonts/NotoSansArmenian-Regular.ttf'],
			'cyrillic'  =>  ['/fonts/NotoSans-Bold.ttf', '/fonts/NotoSans-Regular.ttf'],
			'japanese'  =>  ['/fonts/NotoSansJP-Bold.ttf', '/fonts/NotoSansJP-Regular.ttf'],
			'han'       =>  ['/fonts/NotoSansSC-Bold.ttf', '/fonts/NotoSansSC-Regular.ttf'],
			'hangul'    =>  ['/fonts/NotoSansKR-Bold.ttf', '/fonts/NotoSansKR-Regular.ttf'],
			'devanagari'=>  ['/fonts/NotoSansDevanagari-Bold.ttf', '/fonts/NotoSansDevanagari-Regular.ttf'],
			'hebrew'    =>  ['/fonts/NotoSansHebrew-Bold.ttf', '/fonts/NotoSansHebrew-Regular.ttf'],
			'telugu'    =>  ['/fonts/NotoSansTelugu-Bold.ttf', '/fonts/NotoSansTelugu-Regular.ttf'],
			'thai'      =>  ['/fonts/NotoSansThai-Bold.ttf', '/fonts/NotoSansThai-Regular.ttf'],
			'georgian'  =>  ['/fonts/NotoSansGeorgian-Bold.ttf', '/fonts/NotoSansGeorgian-Regular.ttf'],
			'ethiopic'  =>  ['/fonts/NotoSansEthiopic-Bold.ttf', '/fonts/NotoSansEthiopic-Regular.ttf'],
			'bengali'	=> 	['/fonts/NotoSansBengali-Bold.ttf', '/fonts/NotoSansBengali-Regular.ttf'],
			'latin'     =>  ['/fonts/NotoSans-Bold.ttf', '/fonts/NotoSans-Regular.ttf'],
		];

		if ($script === 'han') {
			$candidateSets = [
				['/fonts/NotoSansSC-Bold.ttf', '/fonts/NotoSansSC-Regular.ttf'], // Simplified Chinese fallback.
				['/fonts/NotoSansJP-Bold.ttf', '/fonts/NotoSansJP-Regular.ttf'], // Preferred for Japanese Kanji.
			];
		} else {
			$candidateSets = [ $fontMap[$script] ];
		}

		foreach ($candidateSets as [$boldRel, $regularRel]) {
			$boldPath = ROOT_DIR . $boldRel;
			$regularPath = ROOT_DIR . $regularRel;
			if (file_exists($boldPath) && file_exists($regularPath)) {
				$this->titleFont = realpath($boldPath);
				$this->authorFont = realpath($regularPath);
				return;
			}
		}

		// If nothing was found, fall back.
		if ($this->containsNonAsciiCharacters($script)) {
			$this->selectUnicodeFallbackFonts();
		}
	}

	/**
	 * Fallback font selection if specific script fonts are missing.
	 *
	 */
	private function selectUnicodeFallbackFonts(): void {
		$fontCandidates = [
			['/fonts/NotoSans-Bold.ttf', '/fonts/NotoSans-BoldItalic.ttf'],
			['/fonts/GothicA1-Bold.ttf', '/fonts/GothicA1-Regular.ttf'],
		];
		foreach ($fontCandidates as [$bold, $regular]) {
			$boldPath = ROOT_DIR . $bold;
			$regularPath = ROOT_DIR . $regular;
			if (file_exists($boldPath) && file_exists($regularPath)) {
				$this->titleFont = realpath($boldPath);
				$this->authorFont = realpath($regularPath);
				return;
			}
		}
	}

	/**
	 * Simplifies extended Latin characters commonly used in Arabic transliteration
	 * (i.e., writing letters of one script using the letters of another script)
	 * by stripping diacritics and converting variant apostrophe-like characters
	 * into a plain ASCII apostrophe.
	 *
	 * This helps ensure clean rendering in Arabic contexts where transliterated
	 * names (e.g., Ṭabbāʻ, ʿUthmān Muṣṭafá) are present, but Arabic-script fonts
	 * do not support the Latin characters with diacritics.
	 *
	 * @param string $text Transliterated Arabic text using extended Latin.
	 * @return string Cleaned text using basic ASCII equivalents.
	 */
	private function transliterateToArabic(string $text): string {
		// Part of PHP intl: https://www.php.net/manual/en/class.normalizer.php
		// Takes decomposed characters and turns them into composed (standard) characters.
		if (class_exists('Normalizer')) {
			$text = Normalizer::normalize($text, Normalizer::FORM_D);
		}
		$text = preg_replace('/\p{Mn}/u', '', $text);
		return strtr($text, [
			'ʻ' => "'",
			'ʿ' => "'",
			'ʾ' => "'",
			'ʼ' => "'",
		]);
	}
}