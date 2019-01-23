<?php
/**
 * Solr Authority Autocomplete Module
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
require_once ROOT_DIR . '/sys/Autocomplete/SolrAutocomplete.php';

/**
 * Solr Authority Autocomplete Module
 *
 * This class provides suggestions by using the local Solr authority index.
 *
 * @category VuFind
 * @package  Autocomplete
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/autocomplete Wiki
 */
class SolrAuthAutocomplete extends SolrAutocomplete
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
    public function __construct($params)
    {
        // Use a different default field; otherwise, behave the same as the parent:
        $this->defaultDisplayField = 'heading';
        parent::__construct($params);
    }

    /**
     * initSearchObject
     *
     * Initialize the search object used for finding recommendations.
     *
     * @return void
     * @access protected
     */
    protected function initSearchObject()
    {
        // Build a new search object:
        $this->searchObject = SearchObjectFactory::initSearchObject('SolrAuth');
    }
}

?>