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
function smarty_function_css($params, &$smarty)
{
	// Extract details from the config file and parameters so we can find CSS files:
	global $configArray;
	global $interface;
	$local = $configArray['Site']['local'];
	$themes = $interface->getThemes();
	$filename = $params['filename'];

	// Loop through the available themes looking for the requested CSS file:
	$css = false;
	foreach ($themes as $theme) {
		$theme = trim($theme);	

		// If the file exists on the local file system, set $css to the relative
		// path needed to link to it from the web interface.
		//$language = strip_tags(isset($_SESSION['language']));
		$language = strip_tags((isset($_SESSION['language'])) ? $_SESSION['language'] : 'en');
			$mynewLanguage = strip_tags($_REQUEST['myLang']);
		//var_dump($language);
		$userLanguage = UserAccount::getUserInterfaceLanguage();
		//var_dump($userLanguage);
		
		$rtl_langs = array('ar','he');
		
				
		 $smarty->assign("language", $language);
		 $smarty->assign("mynewLanguage", $mynewLanguage);

		 $smarty->assign("userLanguage", $userLanguage);

		 $smarty->assign("rtl_langs", $rtl_langs);

		if (in_array($userLanguage, $rtl_langs) || in_array($mynewLanguage, $rtl_langs) || in_array($language, $rtl_langs))
		{
		  if (file_exists("{$local}/interface/themes/{$theme}/css-rtl/{$filename}")) 
			{
			$css = "/interface/themes/{$theme}/css-rtl/{$filename}";
			break;
			}
		}
		else
		{

			if (file_exists("{$local}/interface/themes/{$theme}/css/{$filename}")) 
			{
			$css = "/interface/themes/{$theme}/css/{$filename}";
			break;
			}

		}
	}


	// If we couldn't find the file, we shouldn't try to link to it:
	if (!$css) {
		return '';
	}

	// We found the file -- build the link tag:
	$media = isset($params['media']) ? " media=\"{$params['media']}\"" : '';
	return "<link rel=\"stylesheet\" type=\"text/css\"{$media} href=\"{$css}?v=" . urlencode($interface->getVariable('gitBranch')) . '.' . urlencode($interface->getVariable('cssJsCacheCounter')) ."\" />";
}