<?php
/**
 * Creates a default image for a cover based on a default background.
 * Overlays with title and author
 */

require_once ROOT_DIR . '/sys/Utils/StringUtils.php';
class DefaultCoverImageBuilder
{
    private $imageWidth = 280; //Pixels
    private $imageHeight = 400; // Pixels
    private $topMargin = 10;
    private $imagePrintableAreaWidth = 260; //Area printable in Pixels
    private $imagePrintableAreaHeight = 380; //Area printable in Pixels
    private $titleFont;
    private $authorFont;
    private $backgroundColor;
    private $foregroundColor;

    public function __construct()
    {
        $this->titleFont = ROOT_DIR . '/fonts/JosefinSans-Bold.ttf';
        $this->authorFont = ROOT_DIR . '/fonts/JosefinSans-BoldItalic.ttf';
    }

    private function setForegroundAndBackgroundColors($title, $author)
    {
        $base_saturation = 100;
        $base_brightness = 90;
        $color_distance = 100;

        $counts = strlen($title) + strlen($author);
        //Get the color seed based on the number of characters in the title and author.
        //We want a number from 10 to 360
        $color_seed = (int)$this->_map($this->_clip($counts, 2, 80), 2, 80, 10, 360);

        $this->foregroundColor = $this->ColorHSLToRGB($color_seed, $base_saturation, $base_brightness - ($counts % 20) / 100);
        $this->backgroundColor = $this->ColorHSLToRGB(
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
        $this->imageWidth = imagesx($imageCanvas);
        $this->imageHeight = imagesy($imageCanvas);
        $this->imagePrintableAreaWidth = $this->imageWidth - 20; //Add a 10px buffer on both sides
        $this->imagePrintableAreaHeight = $this->imageHeight - 20; //Add a 10px buffer on both sides

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
        //$height = $title_height;

        $title = StringUtils::trimStringToLengthAtWordBoundary($title, 60, true);
        /** @noinspection PhpUnusedLocalVariableInspection */
        list($totalHeight, $lines) = $this->wrapTextForDisplay($this->titleFont, $title, $title_font_size, $title_font_size * .1, $width);
        $this->addWrappedTextToImage($imageCanvas, $this->titleFont, $lines, $title_font_size, $title_font_size * .1, $x, $y, $textColor);

        $author_font_size = $this->imageWidth * 0.055;
        //$author_height = ($this->imageHeight - $this->imageWidth - ($this->imageHeight * $this->topMargin / 100)) * 0.25;

        $width = $this->imageWidth - (2 * $this->imageHeight * $this->topMargin / 100);
        $author = StringUtils::trimStringToLengthAtWordBoundary($author, 40, true);
        list($totalHeight, $lines) = $this->wrapTextForDisplay($this->authorFont, $author, $author_font_size, $author_font_size * .1, $width);
        $y = $this->imageHeight - $artworkHeight - $totalHeight - 5;
        $this->addWrappedTextToImage($imageCanvas, $this->authorFont, $lines, $author_font_size, $author_font_size * .1, $x, $y, $textColor);
        //cover_image.text(author, x, y, width, height, fill, author_font)
    }

    private function wrapTextForDisplay($font, $text, $fontSize, $lineSpacing, $maxWidth){
        //Get the total string length
        $textBox = imageftbbox($fontSize, 0, $font, $text);
        $totalTextWidth = abs($textBox[4] - $textBox[6]);
        //Determine how many lines we will need to break the text into
        $numLines = (float)$totalTextWidth / (float)$maxWidth;
        $charactersPerLine = strlen($text) / $numLines;
        //Wrap based on the number of lines
        $lines = explode("\n", wordwrap($text, $charactersPerLine, "\n"));
        $totalHeight = 0;
        foreach ($lines as $line){
            //Get the width of this line
            $lineBox = imageftbbox($fontSize, 0, $font, $line);
            $lineHeight = abs($lineBox[3] - $lineBox[5]);
            $totalHeight += $lineHeight + $lineSpacing;
        }
        return [$totalHeight, $lines];
    }

	private function addWrappedTextToImage($imageHandle, $font, $lines, $fontSize, $lineSpacing, $startX, $startY, $color){
		foreach ($lines as $line){
			//Get the width of this line
			$lineBox = imageftbbox($fontSize, 0, $font, $line);
			//$lineWidth = abs($lineBox[4] - $lineBox[6]);
			$lineHeight = abs($lineBox[3] - $lineBox[5]);
			//Get the starting position for the text
			$startY += $lineHeight;
			//Write the text to the image
			imagefttext($imageHandle, $fontSize, 0, $startX, $startY, $color, $font, $line);
			$startY += $lineSpacing;
		}
		return $startY;
	}

    function ColorHSLToRGB($h, $s, $l){
        if ($h > 1) $h /= 360;
        if ($s > 1) $s /= 100;
        if ($l > 1) $l /= 100;
        $r = $l;
        $g = $l;
        $b = $l;
        $v = ($l <= 0.5) ? ($l * (1.0 + $s)) : ($l + $s - $l * $s);
        if ($v > 0){
            $m = $l + $l - $v;
            $sv = ($v - $m ) / $v;
            $h *= 6.0;
            $sextant = floor($h);
            $fract = $h - $sextant;
            $vsf = $v * $sv * $fract;
            $mid1 = $m + $vsf;
            $mid2 = $v - $vsf;

            switch ($sextant)
            {
                case 0:
                    $r = $v;
                    $g = $mid1;
                    $b = $m;
                    break;
                case 1:
                    $r = $mid2;
                    $g = $v;
                    $b = $m;
                    break;
                case 2:
                    $r = $m;
                    $g = $v;
                    $b = $mid1;
                    break;
                case 3:
                    $r = $m;
                    $g = $mid2;
                    $b = $v;
                    break;
                case 4:
                    $r = $mid1;
                    $g = $m;
                    $b = $v;
                    break;
                case 5:
                    $r = $v;
                    $g = $m;
                    $b = $mid2;
                    break;
            }
        }
        return array('r' => $r * 255.0, 'g' => $g * 255.0, 'b' => $b * 255.0);
    }

    private function drawArtwork($imageCanvas, $backgroundColor, $foregroundColor, $title)
    {
        $artworkStartX = 0;
        $artworkStartY = $this->imageHeight - $this->imageWidth;

        list($gridCount, $gridTotal, $gridSize) = $this->breakGrid($title);
        $c64_title = $this->c64Convert($title);

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

            if ($y < $this->imageHeight){
                //Draw the artwork background
                imagefilledrectangle($imageCanvas, $x, $y, $x + $gridSize, $y + $gridSize, $backgroundColor);
                $this->drawShape($imageCanvas, $backgroundColor, $foregroundColor, $char, $x, $y, $gridSize);
            }

        }
        return ($gridCount - $rowsToSkip) * $gridSize;
    }

    private  function c64Convert($title){
        $title = strtolower($title);
        $c64_letters = " qwertyuiopasdfghjkl:zxcvbnm,;?<>@[]1234567890.=-+*/";
        $c64_title = "";
        for ($i = 0; $i < strlen($title); $i++){
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
        $length = $this->_clip(strlen($title), $min_title, $max_title);

        $grid_count = $this->_clip(floor(sqrt($length)), 2, 11);
        $grid_total = $grid_count * $grid_count;
        $grid_size = $this->imageWidth / $grid_count;
        return array($grid_count, $grid_total, $grid_size);
    }

    private function _map($value, $istart, $istop, $ostart, $ostop){
        return $ostart + ($ostop - $ostart) * (($value - $istart) / ($istop - $istart));
    }



    private function _clip($value, $lower, $upper){
	    if ($value < $lower) {
	        return $lower;
        } elseif ( $value > $upper) {
	        return $upper;
        } else{
	        return $value;
        }
    }

    private function drawShape($imageCanvas, $backgroundColor, $foregroundColor, $char, $x, $y, $gridSize)
    {
        $shape_thickness = 10;
        $thick = $this->_clip($gridSize * $shape_thickness / 100, 4, 10);
        imagesetthickness($imageCanvas, $thick);
        if ($char == "q"){
            imagefilledellipse($imageCanvas, $x + $gridSize /2, $y + $gridSize /2, $gridSize, $gridSize, $foregroundColor);
        }else if ($char == "w"){
            imagefilledellipse($imageCanvas, $x + $gridSize /2, $y + $gridSize /2, $gridSize, $gridSize, $foregroundColor);
            imagefilledellipse($imageCanvas, $x + $gridSize /2, $y + $gridSize /2, $gridSize - ($thick * 2), $gridSize - ($thick * 2), $backgroundColor);
        }else if ($char == "e"){
            $this->imagefilledrectangle($imageCanvas, $x, $y + $thick, $gridSize, $thick, $foregroundColor);
        }else if ($char == "r"){
            $this->imagefilledrectangle($imageCanvas, $x, $y + $gridSize - ($thick * 2), $gridSize, $thick, $foregroundColor);
        }else if ($char == "t"){
            $this->imagefilledrectangle($imageCanvas, $x + $thick, $y, $thick, $gridSize, $foregroundColor);
        }else if ($char == "y"){
            $this->imagefilledrectangle($imageCanvas, $x + $gridSize - ($thick * 2), $y, $thick, $gridSize, $foregroundColor);
        }else if ($char == "u"){
            imagearc($imageCanvas, $x+$gridSize, $y+$gridSize, 2 * ($gridSize - $thick), 2 * ($gridSize - $thick), 180, 270, $foregroundColor);
        }else if ($char == "i"){
            imagearc($imageCanvas, $x, $y+$gridSize, 2 * ($gridSize - $thick), 2 * ($gridSize - $thick), 270, 360, $foregroundColor);
        }else if ($char == "o"){
            $this->imagefilledrectangle($imageCanvas, $x, $y, $gridSize, $thick, $foregroundColor);
            $this->imagefilledrectangle($imageCanvas, $x, $y, $thick, $gridSize, $foregroundColor);
        }else if ($char == "p"){
            $this->imagefilledrectangle($imageCanvas, $x, $y, $gridSize, $thick, $foregroundColor);
            $this->imagefilledrectangle($imageCanvas, $x + $gridSize - $thick, $y, $thick, $gridSize, $foregroundColor);
        }else if ($char == "a"){
            imagefilledpolygon($imageCanvas, [ $x, $y + $gridSize, $x + ($gridSize / 2), $y, $x + $gridSize, $y + $gridSize], 3, $foregroundColor);
        }else if ($char == "s"){
            imagefilledpolygon($imageCanvas, [ $x, $y, $x + ($gridSize / 2), $y + $gridSize, $x + $gridSize, $y], 3, $foregroundColor);
        }else if ($char == "d"){
            $this->imagefilledrectangle($imageCanvas, $x, $y + ($thick * 2), $gridSize, $thick, $foregroundColor);
        }else if ($char == "f"){
            $this->imagefilledrectangle($imageCanvas, $x, $y + $gridSize - ($thick * 3), $gridSize, $thick, $foregroundColor);
        }else if ($char == "g"){
            $this->imagefilledrectangle($imageCanvas, $x + ($thick * 2), $y, $thick, $gridSize, $foregroundColor);
        }else if ($char == "h"){
            $this->imagefilledrectangle($imageCanvas, $x + $gridSize - ($thick * 3), $y, $thick, $gridSize, $foregroundColor);
        }else if ($char == "j"){
            imagearc($imageCanvas, $x + $gridSize, $y, 2 * ($gridSize - $thick), 2 * ($gridSize - $thick), 90, 180, $foregroundColor);
        }else if ($char == "k"){
            imagearc($imageCanvas, $x, $y, 2 * ($gridSize - $thick), 2 * ($gridSize - $thick), 0, 90, $foregroundColor);
        }else if ($char == "l"){
            $this->imagefilledrectangle($imageCanvas, $x, $y, $thick, $gridSize, $foregroundColor);
            $this->imagefilledrectangle($imageCanvas, $x, $y + $gridSize - $thick, $gridSize, $thick, $foregroundColor);
        }else if ($char == ":"){
            $this->imagefilledrectangle($imageCanvas, $x + $gridSize - $thick, $y, $thick, $gridSize, $foregroundColor);
            $this->imagefilledrectangle($imageCanvas, $x, $y + $gridSize - $thick, $gridSize, $thick, $foregroundColor);
        }else if ($char == "z"){
            imagefilledpolygon($imageCanvas, [ $x, $y + ($gridSize / 2), $x + ($gridSize / 2), $y, $x + $gridSize, $y + ($gridSize / 2)], 3, $foregroundColor);
            imagefilledpolygon($imageCanvas, [ $x, $y + ($gridSize / 2), $x + ($gridSize / 2), $y + $gridSize, $x + $gridSize, $y + ($gridSize / 2)], 3, $foregroundColor);
        }else if ($char == "x"){
            imagefilledellipse($imageCanvas, $x + ($gridSize / 2), $y + ($gridSize / 3), $thick * 2, $thick * 2, $foregroundColor);
            imagefilledellipse($imageCanvas, $x + ($gridSize / 3), $y + $gridSize - ($gridSize / 3), $thick * 2, $thick * 2, $foregroundColor);
            imagefilledellipse($imageCanvas, $x + $gridSize - ($gridSize / 3), $y + $gridSize - ($gridSize / 3), $thick * 2, $thick * 2, $foregroundColor);
        }else if ($char == "c"){
            $this->imagefilledrectangle($imageCanvas, $x, $y + ($thick * 3), $gridSize, $thick, $foregroundColor);
        }else if ($char == "v"){
            imagesetthickness($imageCanvas, 1);
            $this->imagefilledrectangle($imageCanvas, $x, $y, $gridSize, $gridSize, $foregroundColor);
            imagefilledpolygon($imageCanvas, [ $x + $thick, $y, $x + ($gridSize / 2), $y + ($gridSize / 2) - $thick, $x + $gridSize - $thick, $y], 3, $backgroundColor);
            imagefilledpolygon($imageCanvas, [ $x, $y + $thick, $x + ($gridSize / 2) - $thick, $y + ($gridSize / 2), $x, $y + $gridSize - $thick], 3, $backgroundColor);
            imagefilledpolygon($imageCanvas, [ $x + $thick, $y + $gridSize, $x + ($gridSize / 2), $y + ($gridSize / 2) + $thick, $x + $gridSize - $thick, $y + $gridSize], 3, $backgroundColor);
            imagefilledpolygon($imageCanvas, [ $x + $gridSize, $y + $thick, $x + $gridSize, $y + $gridSize - $thick, $x + ($gridSize / 2) + $thick, $y + ($gridSize / 2)], 3, $backgroundColor);
            imagesetthickness($imageCanvas, $thick);
        }else if ($char == "b"){
            $this->imagefilledrectangle($imageCanvas, $x + ($thick * 3), $y, $thick, $gridSize, $foregroundColor);
        }else if ($char == "n"){
            imagesetthickness($imageCanvas, 1);
            $this->imagefilledrectangle($imageCanvas, $x, $y, $gridSize, $gridSize, $foregroundColor);
            imagefilledpolygon($imageCanvas, [ $x, $y, $x + $gridSize - $thick, $y, $x, $y + $gridSize - $thick], 3, $backgroundColor);
            imagefilledpolygon($imageCanvas, [ $x + $thick, $y + $gridSize, $x + $gridSize, $y + $gridSize, $x + $gridSize, $y + $thick], 3, $backgroundColor);
            imagesetthickness($imageCanvas, $thick);
        }else if ($char == "m"){
            imagesetthickness($imageCanvas, 1);
            $this->imagefilledrectangle($imageCanvas, $x, $y, $gridSize, $gridSize, $foregroundColor);
            imagefilledpolygon($imageCanvas, [ $x + $thick, $y, $x + $gridSize, $y, $x + $gridSize, $y + $gridSize - $thick], 3, $backgroundColor);
            imagefilledpolygon($imageCanvas, [ $x, $y + $thick, $x, $y + $gridSize, $x + $gridSize - $thick, $y + $gridSize], 3, $backgroundColor);
            imagesetthickness($imageCanvas, $thick);
        }else if ($char == ","){
            $this->imagefilledrectangle($imageCanvas, $x + ($gridSize / 2), $y + ($gridSize / 2), $gridSize / 2, $gridSize / 2, $foregroundColor);
        }else if ($char == ";"){
            $this->imagefilledrectangle($imageCanvas, $x, $y + ($gridSize / 2), $gridSize / 2, $gridSize / 2, $foregroundColor);
        }else if ($char == "?"){
            $this->imagefilledrectangle($imageCanvas, $x, $y, $gridSize / 2, $gridSize / 2, $foregroundColor);
            $this->imagefilledrectangle($imageCanvas, $x + ($gridSize / 2), $y + ($gridSize / 2), $gridSize / 2, $gridSize / 2, $foregroundColor);
        }else if ($char == "<"){
            $this->imagefilledrectangle($imageCanvas, $x + ($gridSize / 2), $y, $gridSize / 2, $gridSize / 2, $foregroundColor);
        }else if ($char == ">"){
            $this->imagefilledrectangle($imageCanvas, $x, $y, $gridSize / 2, $gridSize / 2, $foregroundColor);
        }else if ($char == "@"){
            $this->imagefilledrectangle($imageCanvas, $x, $y + ($gridSize / 2) - ($thick / 2), $gridSize, $thick, $foregroundColor);
        }else if ($char == "["){
            $this->imagefilledrectangle($imageCanvas, $x + ($gridSize / 2) - ($thick / 2), $y, $thick, $gridSize, $foregroundColor);
        }else if ($char == "]"){
            $this->imagefilledrectangle($imageCanvas, $x, $y + ($gridSize / 2) - ($thick / 2), $gridSize, $thick, $foregroundColor);
            $this->imagefilledrectangle($imageCanvas, $x + ($gridSize / 2) - ($thick / 2), $y, $thick, $gridSize, $foregroundColor);
        }else if ($char == "0"){
            $this->imagefilledrectangle($imageCanvas, $x + ($gridSize / 2) - ($thick / 2), $y + ($gridSize / 2) - ($thick / 2), $thick, $gridSize / 2 + $thick / 2, $foregroundColor);
            $this->imagefilledrectangle($imageCanvas, $x + ($gridSize / 2) - ($thick / 2), $y + ($gridSize / 2) - ($thick / 2), $gridSize / 2 + $thick / 2, $thick, $foregroundColor);
        }else if ($char == "1"){
            $this->imagefilledrectangle($imageCanvas, $x, $y + ($gridSize / 2) - ($thick / 2), $gridSize, $thick, $foregroundColor);
            $this->imagefilledrectangle($imageCanvas, $x + ($gridSize / 2) - ($thick / 2), $y, $thick, $gridSize / 2 + $thick / 2, $foregroundColor);
        }else if ($char == "2"){
            $this->imagefilledrectangle($imageCanvas, $x, $y + ($gridSize / 2) - ($thick / 2), $gridSize, $thick, $foregroundColor);
            $this->imagefilledrectangle($imageCanvas, $x + ($gridSize / 2) - ($thick / 2), $y + ($gridSize / 2) - ($thick / 2), $thick, $gridSize / 2 + $thick / 2, $foregroundColor);
        }else if ($char == "3"){
            $this->imagefilledrectangle($imageCanvas, $x, $y + ($gridSize / 2) - ($thick / 2), $gridSize / 2 + $thick / 2, $thick, $foregroundColor);
            $this->imagefilledrectangle($imageCanvas, $x + ($gridSize / 2) - ($thick / 2), $y, $thick, $gridSize, $foregroundColor);
        }else if ($char == "4"){
            $this->imagefilledrectangle($imageCanvas, $x, $y, $thick * 2, $gridSize, $foregroundColor);
        }else if ($char == "5"){
            $this->imagefilledrectangle($imageCanvas, $x, $y, $thick * 3, $gridSize, $foregroundColor);
        }else if ($char == "6"){
            $this->imagefilledrectangle($imageCanvas, $x + $gridSize - ($thick * 3), $y, $thick * 3, $gridSize, $foregroundColor);
        }else if ($char == "7"){
            $this->imagefilledrectangle($imageCanvas, $x, $y, $gridSize, $thick * 2, $foregroundColor);
        }else if ($char == "8"){
            $this->imagefilledrectangle($imageCanvas, $x, $y, $gridSize, $thick * 3, $foregroundColor);
        }else if ($char == "9"){
            $this->imagefilledrectangle($imageCanvas, $x, $y + $gridSize - ($thick * 3), $gridSize, $thick * 3, $foregroundColor);
        }else if ($char == "."){
            $this->imagefilledrectangle($imageCanvas, $x + ($gridSize / 2) - ($thick / 2), $y + ($gridSize / 2) - ($thick / 2), $thick, $gridSize / 2 + $thick / 2, $foregroundColor);
            $this->imagefilledrectangle($imageCanvas, $x, $y + ($gridSize / 2) - ($thick / 2), $gridSize / 2 + $thick / 2, $thick, $foregroundColor);
        }else if ($char == "="){
            $this->imagefilledrectangle($imageCanvas, $x + ($gridSize / 2) - ($thick / 2), $y, $thick, $gridSize / 2 + $thick / 2, $foregroundColor);
            $this->imagefilledrectangle($imageCanvas, $x, $y + ($gridSize / 2) - ($thick / 2), $gridSize / 2, $thick, $foregroundColor);
        }else if ($char == "-"){
            $this->imagefilledrectangle($imageCanvas, $x + ($gridSize / 2) - ($thick / 2), $y, $thick, $gridSize / 2 + $thick / 2, $foregroundColor);
            $this->imagefilledrectangle($imageCanvas, $x + ($gridSize / 2) - ($thick / 2), $y + ($gridSize / 2) - ($thick / 2), $gridSize / 2 + $thick / 2, $thick, $foregroundColor);
        }else if ($char == "+"){
            $this->imagefilledrectangle($imageCanvas, $x + ($gridSize / 2) - ($thick / 2), $y + ($gridSize / 2) - ($thick / 2), $gridSize / 2 + $thick / 2, $thick, $foregroundColor);
            $this->imagefilledrectangle($imageCanvas, $x + ($gridSize / 2) - ($thick / 2), $y, $thick, $gridSize, $foregroundColor);
        }else if ($char == "*"){
            $this->imagefilledrectangle($imageCanvas, $x + $gridSize - ($thick * 2), $y, $thick * 2, $gridSize, $foregroundColor);
        }else if ($char == "/"){
            $this->imagefilledrectangle($imageCanvas, $x, $y + $gridSize - ($thick * 2), $gridSize, $thick * 2, $foregroundColor);
        }else if ($char == " "){
            $this->imagefilledrectangle($imageCanvas, $x, $y, $gridSize, $gridSize, $backgroundColor);
        }
        imagesetthickness($imageCanvas, 1);
    }

    private function imagefilledrectangle($imageCanvas, $x, $y, $width, $height, $color)
    {
        imagefilledrectangle($imageCanvas, $x, $y, $x + $width, $y + $height, $color);
    }


}