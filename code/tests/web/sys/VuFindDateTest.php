<?php
/**
 * VuFindDate Test Class
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
require_once 'PEAR.php';

/**
 * VuFindDate Test Class
 *
 * @category VuFind
 * @package  Tests
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/unit_tests Wiki
 */
class VuFindDateTest extends PHPUnit_Framework_TestCase
{
    private $_oldConfigArray;

    /**
     * Standard setup method.
     *
     * @return void
     * @access public
     */
    public function setUp()
    {
        global $configArray;

        // Back up the config array since we may manipulate it during the course
        // of testing.
        $this->_oldConfigArray = $configArray;
    }

    /**
     * Test for an appropriate VuFindDate return value.
     *
     * @param string            $expected Expected value
     * @param string|PEAR_Error $actual   Actual value
     *
     * @return void
     * @access private
     */
    private function _checkDate($expected, $actual)
    {
        if (PEAR::isError($actual)) {
            $this->fail($actual->getMessage());
        }
        $this->assertEquals($expected, $actual);
    }

    /**
     * Test citation generation
     *
     * @return void
     * @access public
     */
    public function testDates()
    {
        global $configArray;

        // Clear out config array date settings to ensure we always test defaults
        // in code:
        unset($configArray['Site']['displayDateFormat']);
        unset($configArray['Site']['displayTimeFormat']);

        // Build an object to test with:
        $date = new VuFindDate();

        // Try some conversions:
        $this->_checkDate(
            '11-29-1973', $date->convertToDisplayDate('U', 123456879)
        );
        $this->_checkDate(
            '11-29-1973', $date->convertToDisplayDate('m-d-y', '11-29-73')
        );
        $this->_checkDate(
            '11-29-1973', $date->convertToDisplayDate('m-d-y', '11-29-1973')
        );
        $this->_checkDate(
            '11-29-1973', $date->convertToDisplayDate('m-d-y H:i', '11-29-73 23:01')
        );
        $this->_checkDate(
            '23:01', $date->convertToDisplayTime('m-d-y H:i', '11-29-73 23:01')
        );
        $this->_checkDate(
            '01-02-2001', $date->convertToDisplayDate('m-d-y', '01-02-01')
        );
        $this->_checkDate(
            '01-02-2001', $date->convertToDisplayDate('m-d-y', '01-02-2001')
        );
        $this->_checkDate(
            '01-02-2001', $date->convertToDisplayDate('m-d-y H:i', '01-02-01 05:11')
        );
        $this->_checkDate(
            '05:11', $date->convertToDisplayTime('m-d-y H:i', '01-02-01 05:11')
        );
        $this->_checkDate(
            '01-02-2001', $date->convertToDisplayDate('Y-m-d', '2001-01-02')
        );
        $this->_checkDate(
            '01-02-2001',
            $date->convertToDisplayDate('Y-m-d H:i', '2001-01-02 05:11')
        );
        $this->_checkDate(
            '05:11', $date->convertToDisplayTime('Y-m-d H:i', '2001-01-02 05:11')
        );

        // Check for proper handling of known problems:
        $bad = $date->convertToDisplayDate('U', 'invalid');
        $this->assertTrue(PEAR::isError($bad));
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

        $configArray = $this->_oldConfigArray;
    }
}
?>
