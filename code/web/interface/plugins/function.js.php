<?php
/**
 * js function Smarty plugin
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2010.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind
 * @package  Smarty_Plugins
 * @author   Tuan Nguyen <tuan@yorku.ca>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/building_a_plugin Wiki
 */

/**
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.js.php
 * Type:     function
 * Name:     js
 * Purpose:  Loads a JS file from the appropriate theme
 *           directory.  Supports one parameter:
 *              filename (required) - file to load from
 *                  interface/themes/[theme]/js/ folder.
 * -------------------------------------------------------------
 *
 * @param array $params Incoming parameter array
 * @param object &$smarty Smarty object
 *
 * @return string        <script> tag for including Javascript
 */
function smarty_function_js($params, &$smarty) {
	// @codingStandardsIgnoreEnd
	// Extract details from the config file, Smarty interface and parameters
	// so we can find CSS files:
	global $configArray;

	$local = $configArray['Site']['local'];
	$filename = $params['filename'];

	// Loop through the available themes looking for the requested JS file:
	$js = false;
	// If the file exists on the local file system, set $js to the relative
	// path needed to link to it from the web interface.
	if (file_exists("{$local}/interface/themes/responsive/js/{$filename}")) {
		$js = "/interface/themes/responsive/js/{$filename}";
	}

	// If we couldn't find the file, check the global Javascript area; if that
	// still doesn't help, we shouldn't try to link to it:
	if (!$js) {
		if (file_exists("{$local}/js/{$filename}")) {
			$js = "/js/{$filename}";
		} else {
			return '';
		}
	}

	// We found the file -- build the script tag:
	global $interface;
	return "<script type=\"text/javascript\" src=\"{$js}?v=" . urlencode($interface->getVariable('aspenVersion')) . '.' . urlencode($interface->getVariable('cssJsCacheCounter')) . "\"></script>";
}