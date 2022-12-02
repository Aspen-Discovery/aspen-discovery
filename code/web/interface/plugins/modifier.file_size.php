<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */
/**
 * Smarty file_size modifier plugin
 *
 * Type: modifier<br>
 * Name: file_size<br>
 * Purpose: format file size represented in bytes into a human readable string<br>
 * Input:<br>
 * - bytes: input bytes integer
 * @author Rob Ruchte <rob at thirdpartylabs dot com>
 * @param integer
 * @return string
 */
function smarty_modifier_file_size($bytes = 0) {
	$mb = 1024 * 1024;
	if ($bytes > $mb) {
		$output = sprintf("%01.2f", $bytes / $mb) . " MB";
	} elseif ($bytes >= 1024) {
		$output = sprintf("%01.0f", $bytes / 1024) . " Kb";
	} else {
		$output = $bytes . " bytes";
	}
	return $output;
}