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
class SeleniumBugTest1 extends SeleniumTestCase
{
    /**
     * Confirm that a Hebrew author name can be retrieved correctly.
     *
     * @return void
     * @access public
     */
    public function testRecord()
    {
        $this->open(
            $this->baseUrl . "/Author/Home?author=%D7%A4%D7%A8%D7%95%D7%99%D7%A7" .
            "%D7%98%20%D7%9E%D7%95%22%D7%A4%20%D7%A7%D7%93%D7%A1%D7%98%D7%A8%20" .
            "%D7%AA%D7%9C%D7%AA-%D7%9E%D7%9E%D7%93%D7%99"
        );

        // Confirm that author search results were found; this is a very obscure
        // string, so if there is any problem at all we should get nothing.  Looking
        // for the expected publication date should suffice to confirm that the
        // correct result was retrieved.
        $this->assertTitle("Author Search Results");
        $this->verifyTextPresent('Published 2004');
    }
}
?>

