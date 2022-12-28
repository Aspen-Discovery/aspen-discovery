<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * Type:     modifier
 * Name:     strstr
 * Purpose:  Performs the PHP function strstr
 * -------------------------------------------------------------
 */
function smarty_modifier_strstr($haystack, $needle) {
	return strstr($haystack, $needle);
}