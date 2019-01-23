<?php
/**
 * Evergreen ILS Driver Test Class
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2011.
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
require_once dirname(__FILE__) . '/../prepend.inc.php';
require_once 'Drivers/Evergreen.php';

/**
 * Evergreen ILS Driver Test Class
 *
 * @category VuFind
 * @package  Tests
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/unit_tests Wiki
 */
class EvergreenTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test the constructor.
     *
     * @return void
     * @access public
     */
    public function testConstructor()
    {
        // Construct object to ensure that everything parses correctly:
        try {
            $driver = new Evergreen();
            $this->assertEquals(is_object($driver), true);
        } catch (PDOException $e) {
            // Couldn't connect to database?  Not configured for testing, so skip!
            $this->markTestSkipped();
        }
    }
}
?>
