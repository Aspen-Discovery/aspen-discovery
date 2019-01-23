<?php
/**
 * SearchObject Factory Test Class
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
 * @package  Tests
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/unit_tests Wiki
 */
require_once dirname(__FILE__) . '/../../prepend.inc.php';
require_once 'sys/SearchObject/Factory.php';

/**
 * SearchObject Factory Test Class
 *
 * @category VuFind
 * @package  Tests
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/unit_tests Wiki
 */
class SearchObjectFactoryTest extends PHPUnit_Framework_TestCase
{
    /**
     * Standard setup method.
     *
     * @return void
     * @access public
     */
    public function setUp()
    {
        global $configArray;

        $this->oldConfigArray = $configArray;

        // Load the default configuration:
        $configArray = parse_ini_file(
            dirname(__FILE__) . '/../../conf/config.ini', true
        );
    }

    /**
     * Test initialization of SearchObjects
     *
     * @return void
     * @access public
     */
    public function testInitSearchObject()
    {
        // Test valid options:
        $options = array('Solr', 'SolrAuth', 'Summon', 'WorldCat');
        foreach ($options as $current) {
            $obj = SearchObjectFactory::initSearchObject($current);
            $this->assertTrue(is_object($obj));
            $this->assertEquals('SearchObject_' . $current, get_class($obj));
        }
        
        // Test invalid option:
        $obj = SearchObjectFactory::initSearchObject('IllegalGarbage');
        $this->assertFalse($obj);
    }

    /**
     * Standard teardown method.
     *
     * @return void
     * @access public
     */
    public function tearDown()
    {
        global $configArray;

        $configArray = $this->oldConfigArray;
    }
}
?>
