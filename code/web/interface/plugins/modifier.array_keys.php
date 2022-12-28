<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * Type:     modifier
 * Name:     array_keys
 * Purpose:  Performs the PHP function urlencode
 * -------------------------------------------------------------
 */
function smarty_modifier_array_keys($array) : array {
	return array_keys($array);
}