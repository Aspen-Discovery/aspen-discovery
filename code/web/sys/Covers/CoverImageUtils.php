<?php

function wrapTextForDisplay($font, $text, $fontSize, $lineSpacing, $maxWidth, $maxHeight = 0){
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
        foreach ($lines as $line){
            //Get the width of this line
            $lineBox = imageftbbox($fontSize, 0, $font, $line);
            $lineHeight = abs($lineBox[3] - $lineBox[5]);
            $totalHeight += $lineHeight + $lineSpacing;
        }
        if ($processLines && $totalHeight > $maxHeight){
            $fontSize *= .95;
            $lineSpacing *= 0.95;
        } else {
            break;
        }
    }


    return [$totalHeight, $lines, $fontSize];
}

function addWrappedTextToImage($imageHandle, $font, $lines, $fontSize, $lineSpacing, $startX, $startY, $color){
    foreach ($lines as $line){
        //Get the width of this line
        $lineBox = imageftbbox($fontSize, 0, $font, $line);
        //$lineWidth = abs($lineBox[4] - $lineBox[6]);
        $lineHeight = abs($lineBox[3] - $lineBox[5]);
        //Get the starting position for the text
        $startY += $lineHeight;
        //Write the text to the image
        if (!imagefttext($imageHandle, $fontSize, 0, $startX, $startY, $color, $font, utf8_decode($line))){
            echo("Failed to write text");
        }
        $startY += $lineSpacing;
    }
    return $startY;
}

function addCenteredWrappedTextToImage($imageHandle, $font, $lines, $fontSize, $lineSpacing, $startX, $startY, $width, $color){
    foreach ($lines as $line){
        //Get the width of this line
        $lineBox = imageftbbox($fontSize, 0, $font, $line);
        $lineWidth = abs($lineBox[4] - $lineBox[6]);
        $lineHeight = abs($lineBox[3] - $lineBox[5]);
        //Get the starting position for the text
        $startXOfLine = $startX + ($width - $lineWidth) / 2;
        $startY += $lineHeight;
        //Write the text to the image
        if (!imagefttext($imageHandle, $fontSize, 0, $startXOfLine, $startY, $color, $font, $line)){
            echo("Failed to write text");
        }
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

function _map($value, $iStart, $iStop, $oStart, $oStop){
    return $oStart + ($oStop - $oStart) * (($value - $iStart) / ($iStop - $iStart));
}

function _clip($value, $lower, $upper){
    if ($value < $lower) {
        return $lower;
    } elseif ( $value > $upper) {
        return $upper;
    } else{
        return $value;
    }
}
