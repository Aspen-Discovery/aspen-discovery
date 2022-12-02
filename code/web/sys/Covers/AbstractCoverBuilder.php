<?php


abstract class AbstractCoverBuilder {
	protected $imageWidth = 280; //Pixels
	protected $imageHeight = 400; // Pixels

	protected $titleFont;

	protected $backgroundColor;

	public function __construct($invertColors = false) {
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
				if ($invertColors) {
					if (empty($this->backgroundColor) && !$theme->secondaryBackgroundColorDefault) {
						$colors = sscanf($theme->secondaryBackgroundColor, "#%02x%02x%02x");
						$this->backgroundColor = [
							'r' => $colors[0],
							'g' => $colors[1],
							'b' => $colors[2],
						];
					}
				} else {
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
		}

		if (empty($this->titleFont)) {
			$this->titleFont = ROOT_DIR . '/fonts/JosefinSans-Bold.ttf';
		}
	}

	/**
	 * @param string $title
	 * @param string $filename
	 * @param string[] $props
	 * @return mixed
	 */
	public abstract function getCover($title, $filename, $props = null);

	protected function setBackgroundColors($title) {
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

	protected function drawText($imageCanvas, $title, $textColor, $y = -1, $textHeight = -1, $maxTextLength = 60) {
		$title_font_size = $this->imageWidth * 0.09;

		$x = 17;
		if ($y == -1) {
			$y = $this->imageWidth + 5;
		}
		$width = $this->imageWidth - (34);
		if ($textHeight == -1) {
			$textHeight = $this->imageHeight - $this->imageWidth - 20;
		}

		$title = StringUtils::trimStringToLengthAtWordBoundary($title, $maxTextLength, true);
		/** @noinspection PhpUnusedLocalVariableInspection */
		[
			$totalHeight,
			$lines,
			$font_size,
		] = wrapTextForDisplay($this->titleFont, $title, $title_font_size, $title_font_size * .15, $width, $textHeight);
		addCenteredWrappedTextToImage($imageCanvas, $this->titleFont, $lines, $font_size, $font_size * .15, $x, $y, $this->imageWidth - 30, $textColor);
	}
}