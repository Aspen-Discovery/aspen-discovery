<?php
require_once ROOT_DIR . '/sys/Covers/AbstractCoverBuilder.php';
require_once ROOT_DIR . '/sys/Utils/StringUtils.php';
require_once ROOT_DIR . '/sys/Covers/CoverImageUtils.php';

class EventCoverBuilder extends AbstractCoverBuilder {
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

		//Draw the event date

		//Make sure the borders are preserved
		imagefilledrectangle($imageCanvas, $this->imageWidth - 10, 0, $this->imageWidth, $this->imageHeight, $backgroundColor);
		imagefilledrectangle($imageCanvas, 0, $this->imageWidth, $this->imageWidth - 10, $this->imageHeight, $backgroundColor);

		$textColor = imagecolorallocate($imageCanvas, 50, 50, 50);

		imagefilledrectangle($imageCanvas, 10, 10, $this->imageWidth - 10, $this->imageWidth - 10, $white);

		imagefilledrectangle($imageCanvas, 10, $this->imageWidth, $this->imageWidth - 10, $this->imageHeight - 10, $white);
		imagerectangle($imageCanvas, 10, $this->imageWidth, $this->imageWidth - 10, $this->imageHeight - 10, $textColor);

		//Add the title at the bottom of the cover
		$this->drawEventText($imageCanvas, $title, $props['eventDate'], $textColor);

		imagepng($imageCanvas, $filename);
		imagedestroy($imageCanvas);
	}

	/**
	 * @param resource $imageCanvas
	 * @param string $title
	 * @param DateTime $eventDate
	 * @param false|int $textColor
	 */
	protected function drawEventText($imageCanvas, $title, $eventDate, $textColor) {
		$title_font_size = $this->imageWidth * 0.09;

		$x = 17;
		$width = $this->imageWidth - (34);
		//$height = $title_height;

		$dayOfWeek = $eventDate->format('l');
		$y = 17;
		$y = addCenteredWrappedTextToImage($imageCanvas, $this->titleFont, $dayOfWeek, $title_font_size, $title_font_size * .15, $x, $y, $this->imageWidth - 30, $textColor);
		$y += 10;
		$month = $eventDate->format('F');
		$fontMultiplier = 1.75;
		if (strlen($month) > 5) {
			$fontMultiplier = 1.5;
		}
		if (strlen($month) > 7) {
			$fontMultiplier = 1.3;
		}
		$y = addCenteredWrappedTextToImage($imageCanvas, $this->titleFont, $month, $title_font_size * $fontMultiplier, $title_font_size * .15 * 1.65, $x, $y, $this->imageWidth - 30, $textColor);
		$y += 20;
		$dayOfMonth = $eventDate->format('j');
		$y = addCenteredWrappedTextToImage($imageCanvas, $this->titleFont, $dayOfMonth, $title_font_size * 5, $title_font_size * .15 * 5, $x, $y, $this->imageWidth - 30, $textColor);

		$title = StringUtils::trimStringToLengthAtWordBoundary($title, 60, true);
		/** @noinspection PhpUnusedLocalVariableInspection */
		[
			$totalHeight,
			$lines,
			$font_size,
		] = wrapTextForDisplay($this->titleFont, $title, $title_font_size, $title_font_size * .15, $width, $this->imageHeight - $this->imageWidth - 20);
		$y = $this->imageWidth + 5;
		addCenteredWrappedTextToImage($imageCanvas, $this->titleFont, $lines, $font_size, $font_size * .15, $x, $y, $this->imageWidth - 30, $textColor);
	}
}