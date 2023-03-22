<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.img_assign.php
 * Type:     function
 * Name:     css
 * Purpose:  Loads an image source from the appropriate theme
 *           directory. and assigns to a variable Supports two parameters:
 *              filename (required) - file to load from
 *                  interface/themes/[theme]/images/ folder.
 * -------------------------------------------------------------
 */
function smarty_function_img_assign($params, &$smarty) {
	// Extract details from the config file and parameters so we can find CSS files:
	global $configArray;
	global $interface;
	$local = $configArray['Site']['local'];

	$filename = $params['filename'];

	// If the file exists on the local file system, set $css to the relative
	// path needed to link to it from the web interface.
	if (file_exists("{$local}/interface/themes/responsive/images/{$filename}")) {
		$smarty->assign($params['var'], "/interface/themes/responsive/images/{$filename}");
		return;
	}

	//Didn't find a theme specific image, try the images directory
	if (file_exists("{$local}/images/{$filename}")) {
		$smarty->assign($params['var'], "/images/{$filename}");
	}
}