<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * Type:     modifier
 * Name:     contains
 * Purpose:  Return true if find a occurrence of a string
 * -------------------------------------------------------------
 */
function smarty_modifier_contains($str, $needle  = null) {
    if (strstr($str, $needle) === FALSE)
    {
    	return false;
    }
    else
    {
    	return true;
    }
}