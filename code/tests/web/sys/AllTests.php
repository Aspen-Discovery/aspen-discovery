<?php
/**
 * Suite to run all tests in the current directory.
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
require_once 'PHPUnit/Framework/TestSuite.php';
require_once dirname(__FILE__) . '/authn/AllTests.php';
require_once dirname(__FILE__) . '/SearchObject/AllTests.php';

/**
 * Suite to run all tests in the current directory.
 *
 * @category VuFind
 * @package  Tests
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/unit_tests Wiki
 */
class WebSysAllTests
{
    /**
     * Build the test suite.
     *
     * @return PHPUnit_Framework_TestSuite
     * @access public
     */
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('VuFind - web - sys');

        // Load tests from child directories:
        $suite->addTest(WebSysAuthnAllTests::suite());
        $suite->addTest(WebSysSearchObjectAllTests::suite());

        // Load all tests in the current directory.  This assumes that all PHP files
        // in the directory are unit tests whose class names match their filenames.
        // Obviously, this file (AllTests.php) is a legal exception to the rule.
        $dirName = dirname(__FILE__);
        $dir = opendir($dirName);
        if ($dir) {
            while ($file = readdir($dir)) {
                // We don't want to load ourselves!
                if (substr($file, -4) == '.php' && $file != 'AllTests.php') {
                    include_once $dirName . '/' . $file;
                    $suite->addTestSuite(substr($file, 0, strlen($file) - 4));
                }
            }
        }

        return $suite;
    }
}
?>
