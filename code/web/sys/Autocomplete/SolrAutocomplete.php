<?php
/**
 * Solr Autocomplete Module
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
require_once ROOT_DIR . '/sys/Autocomplete/Interface.php';

/**
 * Solr Autocomplete Module
 *
 * This class provides suggestions by using the local Solr index.
 *
 * @category VuFind
 * @package  Autocomplete
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/autocomplete Wiki
 */
class SolrAutocomplete implements AutocompleteInterface
{
    protected $handler;
    protected $displayField;
    protected $defaultDisplayField = 'title';
    protected $sortField;
    protected $filters;
    protected $searchObject;

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
        // Save the basic parameters:
        $params = explode(':', $params);
        $this->handler = (isset($params[0]) && !empty($params[0])) ?
            $params[0] : null;
        $this->displayField = (isset($params[1]) && !empty($params[1])) ?
            explode(',', $params[1]) : array($this->defaultDisplayField);
        $this->sortField = (isset($params[2]) && !empty($params[2])) ?
            $params[2] : null;
        $this->filters = array();
        if (count($params > 3)) {
            for ($x = 3; $x < count($params); $x += 2) {
                if (isset($params[$x + 1])) {
                    $this->filters[] = $params[$x] . ':' . $params[$x + 1];
                }
            }
        }

        // Set up the Search Object:
        $this->initSearchObject();
    }

    /**
     * initSearchObject
     *
     * Initialize the search object used for finding recommendations.
     *
     * @return void
     * @access  protected
     */
    protected function initSearchObject()
    {
        // Build a new search object:
        $this->searchObject = SearchObjectFactory::initSearchObject();
        // Not really a browse, but browse searches are similar in that they
        //   have no facets (until added later) and no spellchecking
        $this->searchObject->initBrowseScreen();
    }

    /**
     * mungeQuery
     *
     * Process the user query to make it suitable for a Solr query.
     *
     * @param string $query Incoming user query
     *
     * @return string       Processed query
     * @access protected
     */
    protected function mungeQuery($query)
    {
        // Modify the query so it makes a nice, truncated autocomplete query:
        $forbidden = array(':', '(', ')', '*', '+', '"');
        $query = str_replace($forbidden, " ", $query);
        if (substr($query, -1) != " ") {
            $query .= "*";
        }
        return $query;
    }

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
    public function getSuggestions($query)
    {
        $this->searchObject->disableLogging();
        $this->searchObject->setBasicQuery(
            $this->mungeQuery($query), $this->handler
        );
        $this->searchObject->setSort($this->sortField);
        foreach ($this->filters as $current) {
            $this->searchObject->addFilter($current);
        }

        // Perform the search:
        $result = $this->searchObject->processSearch(true);
        $resultDocs = isset($result['response']['docs']) ?
            $result['response']['docs'] : array();
        $this->searchObject->close();

        // Build the recommendation list:
        $results = array();
        foreach ($resultDocs as $current) {
            foreach ($this->displayField as $field) {
                if (isset($current[$field])) {
                    $results[] = is_array($current[$field]) ?
                        $current[$field][0] : $current[$field];
                    break;
                }
            }
        }

        return array_unique($results);
    }

    /**
     * setDisplayField
     *
     * Set the display field list.  Useful for child classes.
     *
     * @param array $new Display field list.
     *
     * @return void
     * @access protected
     */
    protected function setDisplayField($new)
    {
        $this->displayField = $new;
    }

    /**
     * setSortField
     *
     * Set the sort field list.  Useful for child classes.
     *
     * @param string $new Sort field list.
     *
     * @return void
     * @access protected
     */
    protected function setSortField($new)
    {
        $this->sortField = $new;
    }
}

?>