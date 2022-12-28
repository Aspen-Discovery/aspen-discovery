<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * Type:     modifier
 * Name:     urlencode
 * Purpose:  Performs the PHP function urlencode
 * -------------------------------------------------------------
 */
function smarty_modifier_urlencode($str) : string {
	return urlencode($str);
}