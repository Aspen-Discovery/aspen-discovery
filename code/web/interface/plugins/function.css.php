<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.css.php
 * Type:     function
 * Name:     css
 * Purpose:  Loads a CSS file from the appropriate theme
 *           directory.  Supports two parameters:
 *              filename (required) - file to load from
 *                  interface/themes/[theme]/css/ folder.
 *              media (optional) - media attribute to
 *                  pass into <link> tag.
 * -------------------------------------------------------------
 */
function smarty_function_css($params, &$smarty) {
	// Extract details from the config file and parameters so we can find CSS files:
	global $configArray;
	global $interface;
	$local = $configArray['Site']['local'];
	$filename = $params['filename'];

	// Always try to load the main CSS file first
	$css = false;
	global $activeLanguage;

	// If the file exists on the local file system, set $css to the relative
	// path needed to link to it from the web interface.
	if (file_exists("{$local}/interface/themes/responsive/css/{$filename}")) {
		$css = "/interface/themes/responsive/css/{$filename}";
	}

	// If we couldn't find the file, we shouldn't try to link to it:
	if (!$css) {
		return '';
	}

	// We found the file -- build the link tag:
	$media = isset($params['media']) ? " media=\"{$params['media']}\"" : '';
	$version = urlencode($interface->getVariable('aspenVersion')) . '.' . urlencode($interface->getVariable('cssJsCacheCounter'));
	$output = "<link rel=\"stylesheet\" type=\"text/css\"{$media} href=\"{$css}?v={$version}\" />";

	// For RTL languages, also include the RTL CSS file if it exists
	if ($activeLanguage->isRTL()) {
		$rtlFilename = str_replace('.css', '-rtl.css', $filename);
		if (file_exists("{$local}/interface/themes/responsive/css/{$rtlFilename}")) {
			$rtlCss = "/interface/themes/responsive/css/{$rtlFilename}";
			$output .= "\n<link rel=\"stylesheet\" type=\"text/css\"{$media} href=\"{$rtlCss}?v={$version}\" />";
			if (file_exists("{$local}/interface/themes/responsive/css/main-rtl-supplement.css")) {
				$supRtlCss = "/interface/themes/responsive/css/main-rtl-supplement.css";
				$output .= "\n<link rel=\"stylesheet\" type=\"text/css\"{$media} href=\"{$supRtlCss}?v={$version}\" />";
			}
		}
	}

	return $output;
}