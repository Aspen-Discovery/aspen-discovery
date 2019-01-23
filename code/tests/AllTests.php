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
require_once 'Selenium/AllTests.php';
require_once 'web/AllTests.php';

/**
 * Suite to run all tests in the current directory.
 *
 * @category VuFind
 * @package  Tests
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/unit_tests Wiki
 */
class AllTests extends PHPUnit_Framework_TestSuite
{
    /**
     * Set up the test suite.
     *
     * @return void
     * @access protected
     */
    protected function setUp()
    {
        // Update the code coverage blacklist -- we don't want to analyze all the
        // test code or any external PEAR libraries that we include:
        PHP_CodeCoverage_Filter::getInstance()->addDirectoryToBlacklist(
            '/usr/share/php'
        );
        PHP_CodeCoverage_Filter::getInstance()->addDirectoryToBlacklist(
            '/usr/local/lib'
        );
        PHP_CodeCoverage_Filter::getInstance()->addDirectoryToBlacklist(
            dirname(__FILE__)
        );

        // Clear out Smarty files to ensure testing begins with a clean slate:
        $this->_smartyCleanup();
    }

    /**
     * Build the test suite.
     *
     * @return PHPUnit_Framework_TestSuite
     * @access public
     */
    public static function suite()
    {
        $suite = new AllTests('VuFind');
        $suite->addTest(SeleniumAllTests::suite());
        $suite->addTest(WebAllTests::suite());

        return $suite;
    }

    /**
     * Tear down the test suite.
     *
     * @return void
     * @access protected
     */
    protected function tearDown()
    {
        // Clear any artifacts created by testing:
        $this->_smartyCleanup();
    }

    /**
     * Clean up Smarty files to avoid leaving artifacts after testing and to
     * avoid loading old cache information.
     *
     * @return void
     * @access private
     */
    private function _smartyCleanup()
    {
        $interface = dirname(__FILE__) . '/../web/interface/';
        $this->_emptyDir($interface . 'compile');
        $this->_emptyDir($interface . 'cache');
    }

    /**
     * Empty the contents of a directory.
     *
     * @param string $dir Directory to empty.
     *
     * @return void
     * @access private
     */
    private function _emptyDir($dir)
    {
        $handle = opendir($dir);
        while ($current = readdir($handle)) {
            // Skip abstract directories:
            if ($current != '.' && $current != '..') {
                $current = $dir . '/' . $current;
                if (is_dir($current)) {
                    $this->_emptyDir($current);
                    @rmdir($current);
                } else {
                    @unlink($current);
                }
            }
        }
    }
}
?>
