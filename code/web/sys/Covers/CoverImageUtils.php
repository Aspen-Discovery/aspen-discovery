<?php

function wrapTextForDisplay($font, $text, $fontSize, $lineSpacing, $maxWidth, $maxHeight = 0) {
	if (empty($text)) {
		return [
			0,
			[],
			$fontSize,
		];
	}
	//Get the total string length
	$textBox = imageftbbox($fontSize, 0, $font, $text);
	$totalTextWidth = abs($textBox[4] - $textBox[6]);
	//Determine how many lines we will need to break the text into
	$numLines = (float)$totalTextWidth / (float)$maxWidth;
	$charactersPerLine = strlen($text) / $numLines;
	//Wrap based on the number of lines
	$lines = explode("\n", wordwrap($text, $charactersPerLine, "\n"));

	$processLines = true;
	while ($processLines) {
		$processLines = $maxHeight > 0;
		$totalHeight = 0;
		foreach ($lines as $line) {
			//Get the width of this line
			$lineBox = imageftbbox($fontSize, 0, $font, $line);
			$lineHeight = abs($lineBox[3] - $lineBox[5]);
			$totalHeight += $lineHeight + $lineSpacing;
		}
		if ($processLines && $totalHeight > $maxHeight) {
			$fontSize *= .95;
			$lineSpacing *= 0.95;
		} else {
			break;
		}
	}

	return [
		$totalHeight,
		$lines,
		$fontSize,
	];
}

function addWrappedTextToImage($imageHandle, $font, $lines, $fontSize, $lineSpacing, $startX, $startY, $color) {
	foreach ($lines as $line) {
		//Get the width of this line
		$lineBox = imageftbbox($fontSize, 0, $font, $line);
		//$lineWidth = abs($lineBox[4] - $lineBox[6]);
		$lineHeight = abs($lineBox[3] - $lineBox[5]);
		//Get the starting position for the text
		$startY += $lineHeight;

		//Write the text to the image
		if (!imagefttext($imageHandle, $fontSize, 0, $startX, $startY, $color, $font, $line)) {
			echo("Failed to write text");
		}
		$startY += $lineSpacing;
	}
	return $startY;
}

function addCenteredWrappedTextToImage($imageHandle, $font, $lines, $fontSize, $lineSpacing, $startX, $startY, $width, $color) {
	if (!is_array($lines)) {
		$lines = [$lines];
	}
	foreach ($lines as $line) {
		//Get the width of this line
		$lineBox = imageftbbox($fontSize, 0, $font, $line);
		$lineWidth = abs($lineBox[4] - $lineBox[6]);
		$lineHeight = abs($lineBox[3] - $lineBox[5]);
		//Get the starting position for the text
		$startXOfLine = $startX + ($width - $lineWidth) / 2;
		$startY += $lineHeight;
		//Write the text to the image
		if (!imagefttext($imageHandle, $fontSize, 0, $startXOfLine, $startY, $color, $font, $line)) {
			echo("Failed to write text");
		}
		$startY += $lineSpacing;
	}
	return $startY;
}

function _map($value, $iStart, $iStop, $oStart, $oStop) {
	return $oStart + ($oStop - $oStart) * (($value - $iStart) / ($iStop - $iStart));
}

function _clip($value, $lower, $upper) {
	if ($value < $lower) {
		return $lower;
	} elseif ($value > $upper) {
		return $upper;
	} else {
		return $value;
	}
}

function resizeImage($originalPath, $newPath, $maxWidth, $maxHeight) {
	global $logger;
	[
		$width,
		$height,
		$type,
	] = @getimagesize($originalPath);
	if ($image = @file_get_contents($originalPath, false)) {
		if (!$imageResource = @imagecreatefromstring($image)) {
			return false;
		} else {
			if ($width > $maxWidth || $height > $maxHeight) {
				if ($width > $height) {
					$new_width = $maxWidth;
					$new_height = floor($height * ($maxWidth / $width));
				} else {
					$new_height = $maxHeight;
					$new_width = floor($width * ($maxHeight / $height));
				}

				$tmp_img = imagecreatetruecolor($new_width, $new_height);
				imagealphablending($tmp_img, false);
				imagesavealpha($tmp_img, true);
				$transparent = imagecolorallocatealpha($tmp_img, 255, 255, 255, 127);
				imagefilledrectangle($tmp_img, 0, 0, $new_width, $new_height, $transparent);

				if (!imagecopyresampled($tmp_img, $imageResource, 0, 0, 0, 0, $new_width, $new_height, $width, $height)) {
					$logger->log("Could not resize image $originalPath to $newPath", Logger::LOG_ERROR);
					return false;
				}

				// save thumbnail into a file
				if (file_exists($newPath)) {
					$logger->log("File $newPath already exists, deleting", Logger::LOG_DEBUG);
					unlink($newPath);
				}

				if (!@imagepng($tmp_img, $newPath, 9)) {
					$logger->log("Could not save re-sized file $newPath", Logger::LOG_ERROR);
					return false;
				} else {
					return true;
				}
			} else {
				//Just copy the image over
				copy($originalPath, $newPath);
				return true;
			}
		}
	} else {
		return false;
	}
}
