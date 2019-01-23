<?php
/**
 * Autocomplete Plug-In Interface
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
 * Autocomplete Plug-In Interface
 *
 * This interface class is the definition of the required methods for
 * generating autocomplete suggestions.
 *
 * @category VuFind
 * @package  Autocomplete
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/autocomplete Wiki
 */
interface AutocompleteInterface
{
    /**
     * Constructor
     *
     * Establishes base settings for making autocomplete suggestions.
     *
     * @param string $params Additional settings from searches.ini.
     *
     * @access public
     */
    public function __construct($params);
    
    /**
     * getSuggestions
     *
     * This method returns an array of strings matching the user's query for
     * display in the autocomplete box.
     *
     * @param string $query The user query
     *
     * @return array        The suggestions for the provided query
     * @access public
     */
    public function getSuggestions($query);
}

?>