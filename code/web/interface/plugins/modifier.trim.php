<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * Type:     modifier
 * Name:     trim
 * Purpose:  Performs the PHP function trim
 * -------------------------------------------------------------
 */
function smarty_modifier_trim($str) : string {
	return trim($str);
}