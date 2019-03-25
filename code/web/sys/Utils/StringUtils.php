<?php

class StringUtils
{
    public static function trimStringToLengthAtWordBoundary($string, $maxLength, $addEllipsis){
        if (strlen($string) < $maxLength) {
            return $string;
        }else {
            $lastDelimiter = strrpos(substr($string, 0, $maxLength), ' ');
            $string = substr($string, 0, $lastDelimiter);
            if ($addEllipsis) {
                $string .= '...';
            }
            return $string;
        }
    }
}