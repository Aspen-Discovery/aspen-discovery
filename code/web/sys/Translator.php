<?php
/**
 *
 * Copyright (C) Villanova University 2007.
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
 */

/**
 * I18N_Translator
 *
 * The I18N_Translator class handles language translations via an Array that is
 * stored in an INI file. There is 1 ini file per language and upon construction
 * of the class, the appropriate language file is loaded. The class offers
 * functionality to manage the files as well, such as creating new language
 * files and adding/deleting of existing translations. Upon destruction, the
 * file is saved.
 *
 * @author      Andrew S. Nagy <andrew.nagy@villanova.edu>
 * @package     I18N_Translator
 * @category    I18N
 */
class I18N_Translator
{
	/**
	 * Language translation files path
	 *
	 * @var     string
	 * @access  public
	 */
	var $path;

	/**
	 * The specified language.
	 *
	 * @var     string
	 * @access  public
	 */
	var $langCode;

	/**
	 * An array of the translated text
	 *
	 * @var     array
	 * @access  public
	 */
	var $words = array();

	/**
	 * Debugging flag
	 *
	 * @var     boolean
	 * @access  public
	 */
	var $debug = false;

	/**
	 * Constructor
	 *
	 * @param   string $langCode    The ISO 639-1 Language Code
	 * @access  public
	 */
	function __construct($path, $langCode, $debug = false)
	{
		global $timer;
		global $configArray;
		$this->path = $path;
		$this->langCode = preg_replace('/[^\w\-]/', '', $langCode);

		if ($debug) {
			$this->debug = true;
		}

		// Load file in specified path
		if ($dh = opendir($path)) {
			$file = $path . '/' . $this->langCode . '.ini';
			if ($this->langCode != '' && is_file($file)) {
				$this->words = $this->parseLanguageFile($file);
			} else {
				return new PEAR_Error("Unknown language file");
			}
			closedir($dh);
		} else {
			return new PEAR_Error("Cannot open $path for reading");
		}

		//Check for a more specific language file for the site
		global $serverName;
		$serverLangPath = $configArray['Site']['local'] . '/../../sites/' . $serverName . '/lang';
		if ($dh = @opendir($serverLangPath)) {
			$serverFile = $serverLangPath . '/' . $this->langCode . '.ini';
			if (file_exists($serverFile)) {
				$siteWords = $this->parseLanguageFile($serverFile);
				$this->words = array_merge($this->words, $siteWords);
			}
			closedir($dh);
		}

		//Also check the theme specific language file (have to check in reverse order so we can override properly).
		global $interface;
		if ($interface){
			$themes = $interface->getThemes();
			$themeBasePath = $configArray['Site']['local'] . '/interface/themes';
			$themesReversed = array_reverse($themes);
			foreach ($themesReversed as $theme){
				$themeFile = $themeBasePath . '/' . $theme . '/lang/' . $this->langCode . '.ini';
				if (file_exists($themeFile)) {
					$siteWords = $this->parseLanguageFile($themeFile);
					$this->words = array_merge($this->words, $siteWords);
				}
			}
		}

		$timer->logTime('Initialize translator for ' . $langCode);
	}

	/**
	 * Parse a language file.
	 *
	 * @param   string $file        Filename to load
	 * @access  private
	 * @return  array
	 */
	function parseLanguageFile($file)
	{
		/* Old method -- use parse_ini_file; problematic due to reserved words and
		 * increased strictness in PHP 5.3.
		 $words = parse_ini_file($file);
		 return $words;
		 */

		// Manually parse the language file:
		$words = array();
		$contents = file($file);
		if (is_array($contents)) {
			foreach($contents as $current) {
				if (strlen($current) > 0 && substr($current, 0, 1) != ';'){
					$lineContents = str_getcsv($current, '=', '"');
					if (count($lineContents) == 2){
						$key = trim($lineContents[0]);
						$words[$key] = trim($lineContents[1]);
					}
				}


				// Split the string on the equals sign, keeping a max of two chunks:
				/*$lastEqualSign = strrpos($current, '=');
				$key = substr($current, 0, $lastEqualSign);
				$key = trim($key);
				$key = preg_replace('/^\"?(.*?)\"?$/', '$1', $key);
				if (!empty($key) && substr($key, 0, 1) != ';') {
					// Trim outermost double quotes off the value if present:
					$value = trim(substr($current, $lastEqualSign + 1));
					if (isset($value)) {
						$value = preg_replace('/^\"?(.*?)\"?$/', '$1', $value);

						// Store the key/value pair (allow empty values -- sometimes
						// we want to replace a language token with a blank string):
						$words[$key] = $value;
					}
				}*/
			}
		}

		return $words;
	}

	/**
	 * Translate the phrase
	 *
	 * @param   string $phrase      The phrase to translate
	 * @access  public
	 * @note    Can be called statically if 2nd parameter is defined and load
	 *          method is called before
	 * @return  string the translated phrase
	 */
	function translate($phrase)
	{
		if (isset($this->words[$phrase])) {
			$translation = $this->words[$phrase];
		} else {
			if ($this->debug) {
				$translation = "translate_index_not_found($phrase)";
			} else {
				$translation = $phrase;
			}
		}
		//global $timer;
		//$timer->logTime('Translated phrase' . $phrase);
		return $translation;
	}
}