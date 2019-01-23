<?php
/**
 * Creates a default image for a cover based on a default background.
 * Overlays with title and author
 * Based on work done by Juan Gimenez at Douglas County Libraries
 *
 * @category VuFind-Plus
 * @author Mark Noble <mark@marmot.org>
 * Date: 10/30/13
 * Time: 5:17 PM
 */

class DefaultCoverImageBuilder {
	private $imageWidth = 280; //Pixels
	private $imageHeight = 400; // Pixels
	private $imagePrintableAreaWidth = 260; //Area printable in Pixels
	private $imagePrintableAreaHeight = 400; //Area printable in Pixels
	private $titleFont;
	private $authorFont;
	private $colorText = array("red" => 1, "green" => 1, "blue" => 1);

	public function __construct() {
		$this->titleFont = ROOT_DIR . '/fonts/JosefinSans-Bold.ttf';
		$this->authorFont = ROOT_DIR . '/fonts/JosefinSans-BoldItalic.ttf';
	}

	public function getCover($title, $author, $format, $format_category, $filename) {
		$coverName = strtolower(preg_replace('/\W/', '', $format));
		if (!file_exists(ROOT_DIR . '/images/blankCovers/' . $coverName . '.jpg')) {
			$coverName = strtolower(preg_replace('/\W/', '', $format_category));

			if (!file_exists(ROOT_DIR . '/images/blankCovers/' . $coverName . '.jpg')) {
				$coverName = 'books';
			}
		}

		//Create the background image
		$blankCover = imagecreatefromjpeg(ROOT_DIR . '/images/blankCovers/' . $coverName . '.jpg');
		$this->imageWidth = imagesx($blankCover);
		$this->imageHeight = imagesy($blankCover);
		$this->imagePrintableAreaWidth = $this->imageWidth - 20; //Add a 10px buffer on both sides
		$this->imagePrintableAreaHeight = $this->imageHeight - 20; //Add a 10px buffer on both sides

		$colorText = imagecolorallocate($blankCover, $this->colorText['red'], $this->colorText['green'], $this->colorText['blue']); //#444444

		//Add the title to the background image
		$textYPos = $this->addWrappedTextToImage($blankCover, $this->titleFont, $title, 34, 5, 10, $colorText);
		//Add the author to the background image
		if (strlen($author) > 0){
			$this->addWrappedTextToImage($blankCover, $this->authorFont, $author, 24, 10, $textYPos + 6, $colorText);
		}

		imagepng($blankCover, $filename);
		imagedestroy($blankCover);
	}

	/**
	 * Add text to an image, wrapping based on number of characters.
	 */
	private function addWrappedTextToImage($imageHandle, $font, $text, $fontSize, $lineSpacing, $startY, $color){
		//Get the total string length
		$textBox = imageftbbox($fontSize, 0, $font, $text);
		$totalTextWidth = abs($textBox[4] - $textBox[6]);
		//Determine how many lines we will need to break the text into
		$numLines = (float)$totalTextWidth / (float)$this->imagePrintableAreaWidth;
		$charactersPerLine = strlen($text) / $numLines;
		//Wrap based on the number of lines
		$lines = explode("\n", wordwrap($text, $charactersPerLine, "\n"));
		foreach ($lines as $line){
			//Get the width of this line
			$lineBox = imageftbbox($fontSize, 0, $font, $line);
			$lineWidth = abs($lineBox[4] - $lineBox[6]);
			$lineHeight = abs($lineBox[3] - $lineBox[5]);
			//Get the starting position for the text
			$x = ($this->imageWidth - $lineWidth) / 2;
			$startY += $lineHeight;
			//Write the text to the image
			imagefttext($imageHandle, $fontSize, 0, $x, $startY, $color, $font, $line);
			$startY += $lineSpacing;
		}
		return $startY;
	}

	public function blankCoverExists($format, $format_category) {
		$coverName = strtolower(preg_replace('/\W/', '', $format));
		if (!file_exists(ROOT_DIR . '/images/blankCovers/' . $coverName . '.jpg')) {
			$coverName = strtolower(preg_replace('/\W/', '', $format_category));

			if (!file_exists(ROOT_DIR . '/images/blankCovers/' . $coverName . '.jpg')) {
				return false;
			}
		}
		return true;
	}

}