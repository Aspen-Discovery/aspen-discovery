<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */

/**
 * Smarty escapeCSS modifier plugin
 *
 * Type:    modifier
 * Name:    escapeCSS
 * Purpose: Remove special characters so the string can be used as a css class or id
 *
 * @author  Mark Noble
 *
 * @param string $string The string to escape
 * @return  string
 */
function smarty_modifier_escapeCSS($string) {
	$string = preg_replace('/[^a-zA-Z0-9_-]/', '_', $string);

	return $string;
}