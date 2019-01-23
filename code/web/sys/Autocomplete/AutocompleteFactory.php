<?php
/**
 * Code for generating Autocomplete objects
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
 * @package  Autocomplete
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/autocomplete Wiki
 */

/**
 * AutocompleteFactory Class
 *
 * This is a factory class to build autocomplete modules for use in searches.
 *
 * @category VuFind
 * @package  Autocomplete
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/autocomplete Wiki
 */
class AutocompleteFactory
{
    /**
     * initRecommendation
     *
     * This constructs an autocomplete plug-in object.
     *
     * @param string $module The name of the autocomplete module to build
     * @param string $params Configuration string to send to the constructor
     *
     * @return mixed         The $module object on success, false otherwise
     * @access public
     */
    public static function initAutocomplete($module, $params)
    {
        global $configArray;
        $path = "{$configArray['Site']['local']}/sys/Autocomplete/{$module}.php";
        if (is_readable($path)) {
            include_once $path;
            if (class_exists($module)) {
                $auto = new $module($params);
                return $auto;
            }
        }

        return false;
    }

    /**
     * getSuggestions
     *
     * This returns an array of suggestions based on current $_REQUEST parameters.
     * This logic is present in the factory class so that it can be easily shared
     * by multiple AJAX handlers.
     *
     * @param string $typeParam  Name of $_REQUEST parameter containing search type
     * @param string $queryParam Name of $_REQUEST parameter containing query string
     *
     * @return array
     * @access public
     */
    public static function getSuggestions($typeParam = 'type', $queryParam = 'q')
    {
        // Process incoming parameters:
        $type = isset($_REQUEST[$typeParam]) ? $_REQUEST[$typeParam] : '';
        $query = isset($_REQUEST[$queryParam]) ? $_REQUEST[$queryParam] : '';

        // Figure out which handler to use:
        $searchSettings = getExtraConfigArray('searches');
        if (!empty($type) && isset($searchSettings['Autocomplete_Types'][$type])) {
            $module = $searchSettings['Autocomplete_Types'][$type];
        } else if (isset($searchSettings['Autocomplete']['default_handler'])) {
            $module = $searchSettings['Autocomplete']['default_handler'];
        } else {
            $module = false;
        }

        // Get suggestions:
        if ($module) {
            @list($name, $params) = explode(':', $module, 2);
            $handler = self::initAutocomplete($name, $params);
        }
        return (isset($handler) && is_object($handler))
            ? $handler->getSuggestions($query) : array();
    }
}
?>