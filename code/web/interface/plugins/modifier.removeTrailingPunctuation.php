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
	require_once ROOT_DIR . '/sys/Utils/StringUtils.php';
	return StringUtils::removeTrailingPunctuation($str);
}