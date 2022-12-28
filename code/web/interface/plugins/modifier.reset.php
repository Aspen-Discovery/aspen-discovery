<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * Type:     modifier
 * Name:     reset
 * Purpose:  Performs the PHP function urlencode
 * -------------------------------------------------------------
 */
function smarty_modifier_reset($array) {
	return reset($array);
}