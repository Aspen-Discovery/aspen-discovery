<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * Type:     modifier
 * Name:     urlencode
 * Purpose:  Performs the PHP function urlencode
 * -------------------------------------------------------------
 */
function smarty_modifier_print_r($variable) : string {
	return print_r($variable, true);
}