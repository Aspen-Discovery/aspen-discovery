<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     modifier.removeTrailingPunctuation.php
 * Type:     modifier
 * Name:     removeTrailingPunctuation
 * Purpose:  Removes trailing punctuation from a string
 * -------------------------------------------------------------
 */
function smarty_modifier_removeTrailingPunctuation($str) {
	// We couldn't find the file, return an empty value:
	$str = trim($str);
	$str = preg_replace("~([/:])$~","", $str);
	$str = trim($str);
	return $str;
}