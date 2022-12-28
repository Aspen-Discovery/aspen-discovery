<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * Type:     modifier
 * Name:     strpos
 * Purpose:  Performs the PHP function strpos
 * -------------------------------------------------------------
 */
function smarty_modifier_strpos($haystack, $needle) {
	return strpos($haystack, $needle);
}