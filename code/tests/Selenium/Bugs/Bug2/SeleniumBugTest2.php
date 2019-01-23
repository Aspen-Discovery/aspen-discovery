<?php
/**
 * Integration testing of Record module.
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
 * @author   Preetha Rao <vufind-tech@lists.sourceforge.net>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/unit_tests Wiki
 */

require_once dirname(__FILE__) . '/../../lib/SeleniumTestCase.php';
//error_reporting(E_ALL);

/**
 * Integration testing of Record module.
 *
 * @category VuFind
 * @package  Tests
 * @author   Preetha Rao <vufind-tech@lists.sourceforge.net>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/unit_tests Wiki
 */
class SeleniumBugTest2 extends SeleniumTestCase
{
    /**
     * Check that series subfields are displayed in the proper order.
     *
     * @return void
     * @access public
     */
    public function testRecord()
    {
        $this->open($this->baseUrl . '/Record/testbug2');
        $this->waitForPageToLoad($this->timeout);

        $this->assertContains($this->def_rec_tab, $this->getTitle());

        $record_array = array(
            "Main Author" => "Vico, Giambattista, 1668-1744.",
            "Other Authors" => "Pandolfi, Claudia",
            "Series"  => "Vico, Giambattista, 1668-1744. Works. 1982 ; 2, pt. 1.",
            "Tags"    => "No Tags, Be the first to tag this record!"
        );

        // verify the record citation table values
        $this->validateTable("citation", "record", $record_array);
    }
}
?>

