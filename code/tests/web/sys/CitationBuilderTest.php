<?php
/**
 * CitationBuilder Test Class
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
require_once dirname(__FILE__) . '/../prepend.inc.php';
require_once 'sys/SearchObject/Factory.php';
require_once 'sys/CitationBuilder.php';
require_once 'sys/Interface.php';
require_once 'sys/ConnectionManager.php';

/**
 * CitationBuilder Test Class
 *
 * @category VuFind
 * @package  Tests
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/unit_tests Wiki
 */
class CitationBuilderTest extends PHPUnit_Framework_TestCase
{
    /** Sample citations -- each element of this array contains three elements --
     * the raw input data and the expected apa/mla output citations.
     *
     * @var    array
     * @access private
     */
    private $_citations = array(
        // @codingStandardsIgnoreStart
        array(
            'raw' => array(
                'authors' => array('Shafer, Kathleen Newton'),
                'title' => 'Medical-surgical nursing',
                'subtitle' => '',
                'edition' => array(),
                'pubPlace' => 'St. Louis',
                'pubName' => 'Mosby',
                'pubDate' => '1958'
            ),
            'apa' => 'Shafer, K. N. (1958). <span style="font-style:italic;">Medical-surgical nursing</span>. St. Louis: Mosby.',
            'mla' => 'Shafer, Kathleen Newton. <span style="font-style: italic;">Medical-surgical Nursing</span>. St. Louis: Mosby, 1958.'
        ),
        array(
            'raw' => array(
                'authors' => array('Lewis, S.M.'),
                'title' => 'Medical-surgical nursing',
                'subtitle' => 'assessment and management of clinical problems.',
                'edition' => array('7th ed. /'),
                'pubPlace' => 'St. Louis, Mo.',
                'pubName' => 'Mosby Elsevier',
                'pubDate' => '2007'
            ),
            'apa' => 'Lewis, S. (2007). <span style="font-style:italic;">Medical-surgical nursing: Assessment and management of clinical problems</span> (7th ed.). St. Louis, Mo: Mosby Elsevier.',
            'mla' => 'Lewis, S.M. <span style="font-style: italic;">Medical-surgical Nursing: Assessment and Management of Clinical Problems</span>. 7th ed. St. Louis, Mo: Mosby Elsevier, 2007.'
        ),
        array(
            'raw' => array(
                'authors' => array('Lewis, S.M.'),
                'title' => 'Medical-surgical nursing',
                'subtitle' => 'assessment and management of clinical problems.',
                'edition' => array('1st ed.'),
                'pubPlace' => 'St. Louis, Mo.',
                'pubName' => 'Mosby Elsevier',
                'pubDate' => '2007'
            ),
            'apa' => 'Lewis, S. (2007). <span style="font-style:italic;">Medical-surgical nursing: Assessment and management of clinical problems</span>. St. Louis, Mo: Mosby Elsevier.',
            'mla' => 'Lewis, S.M. <span style="font-style: italic;">Medical-surgical Nursing: Assessment and Management of Clinical Problems</span>. St. Louis, Mo: Mosby Elsevier, 2007.'
        ),
        array(
            'raw' => array(
                'authors' => array('Lewis, S.M., Weirdlynamed'),
                'title' => 'Medical-surgical nursing',
                'subtitle' => 'why?',
                'edition' => array('7th ed.'),
                'pubPlace' => 'St. Louis, Mo.',
                'pubName' => 'Mosby Elsevier',
                'pubDate' => '2007'
            ),
            'apa' => 'Lewis, S. (2007). <span style="font-style:italic;">Medical-surgical nursing: Why?</span> (7th ed.). St. Louis, Mo: Mosby Elsevier.',
            'mla' => 'Lewis, S.M. <span style="font-style: italic;">Medical-surgical Nursing: Why?</span> 7th ed. St. Louis, Mo: Mosby Elsevier, 2007.'
        ),
        array(
            'raw' => array(
                'authors' => array('Lewis, S.M., IV'),
                'title' => 'Medical-surgical nursing',
                'subtitle' => 'why?',
                'edition' => array('1st ed.'),
                'pubPlace' => 'St. Louis, Mo.',
                'pubName' => 'Mosby Elsevier',
                'pubDate' => '2007'
            ),
            'apa' => 'Lewis, S., IV. (2007). <span style="font-style:italic;">Medical-surgical nursing: Why?</span> St. Louis, Mo: Mosby Elsevier.',
            'mla' => 'Lewis, S.M., IV. <span style="font-style: italic;">Medical-surgical Nursing: Why?</span> St. Louis, Mo: Mosby Elsevier, 2007.'
        ),
        array(
            'raw' => array(
                'authors' => array('Burch, Philip H., Jr.'),
                'title' => 'The New Deal to the Carter administration',
                'subtitle' => '',
                'edition' => array(''),
                'pubPlace' => 'New York :',
                'pubName' => 'Holmes & Meier,',
                'pubDate' => '1980.'
            ),
            'apa' => 'Burch, P. H., Jr. (1980). <span style="font-style:italic;">The New Deal to the Carter administration</span>. New York: Holmes &amp; Meier.',
            'mla' => 'Burch, Philip H., Jr. <span style="font-style: italic;">The New Deal to the Carter Administration</span>. New York: Holmes &amp; Meier, 1980.'
        ),
        array(
            'raw' => array(
                'authors' => array('Burch, Philip H., Jr.', 'Coauthor, Fictional', 'Fakeperson, Third, III'),
                'title' => 'The New Deal to the Carter administration',
                'subtitle' => '',
                'edition' => array(''),
                'pubPlace' => 'New York :',
                'pubName' => 'Holmes & Meier,',
                'pubDate' => '1980.'
            ),
            'apa' => 'Burch, P. H., Jr., Coauthor, F., &amp; Fakeperson, T., III. (1980). <span style="font-style:italic;">The New Deal to the Carter administration</span>. New York: Holmes &amp; Meier.',
            'mla' => 'Burch, Philip H., Jr., Fictional Coauthor, and Third Fakeperson, III. <span style="font-style: italic;">The New Deal to the Carter Administration</span>. New York: Holmes &amp; Meier, 1980.'
        ),
        array(
            'raw' => array(
                'authors' => array('Burch, Philip H., Jr.', 'Coauthor, Fictional', 'Fakeperson, Third, III', 'Mob, Writing', 'Manypeople, Letsmakeup'),
                'title' => 'The New Deal to the Carter administration',
                'subtitle' => '',
                'edition' => array(''),
                'pubPlace' => '',
                'pubName' => '',
                'pubDate' => ''
            ),
            'apa' => 'Burch, P. H., Jr., Coauthor, F., Fakeperson, T., III, Mob, W., &amp; Manypeople, L. <span style="font-style:italic;">The New Deal to the Carter administration</span>.',
            'mla' => 'Burch, Philip H., Jr., et al. <span style="font-style: italic;">The New Deal to the Carter Administration</span>.'
        ),
        array(
            'raw' => array(
                'authors' => array('Burch, Philip H., Jr.', 'Anonymous, 1971-1973', 'Elseperson, Firstnamery, 1971-1973'),
                'title' => 'The New Deal to the Carter administration',
                'subtitle' => '',
                'edition' => array(''),
                'pubPlace' => 'New York',
                'pubName' => 'Holmes & Meier'
            ),
            'apa' => 'Burch, P. H., Jr., Anonymous, &amp; Elseperson, F. <span style="font-style:italic;">The New Deal to the Carter administration</span>. New York: Holmes &amp; Meier.',
            'mla' => 'Burch, Philip H., Jr., Anonymous, and Firstnamery Elseperson. <span style="font-style: italic;">The New Deal to the Carter Administration</span>. New York: Holmes &amp; Meier.'
        )
        // @codingStandardsIgnoreEnd
    );
    private $_oldConfigArray;

    /**
     * Standard setup method.
     *
     * @return void
     * @access public
     */
    public function setUp()
    {
        global $interface;
        global $configArray;

        $this->_oldConfigArray = $configArray;

        // Load the default configuration, but override the base path so that
        // Smarty can find the real templates and cache folders.
        $configArray = parse_ini_file(
            dirname(__FILE__) . '/../conf/config.ini', true
        );
        $configArray['Site']['local'] = dirname(__FILE__) . '/../../../web';
        $interface = new UInterface();
    }

    /**
     * Test citation generation
     *
     * @return void
     * @access public
     */
    public function testCitations()
    {
        global $interface;

        foreach ($this->_citations as $current) {
            $cb = new CitationBuilder($current['raw']);
            $tpl = $cb->getAPA();
            // Normalize whitespace:
            $apa = trim(preg_replace("/\s+/", " ", $interface->fetch($tpl)));
            $this->assertEquals($current['apa'], $apa);
            $tpl = $cb->getMLA();
            // Normalize whitespace:
            $mla = trim(preg_replace("/\s+/", " ", $interface->fetch($tpl)));
            $this->assertEquals($current['mla'], $mla);

            // Repeat tests using newer getCitation method:
            $tpl = $cb->getCitation('APA');
            // Normalize whitespace:
            $apa = trim(preg_replace("/\s+/", " ", $interface->fetch($tpl)));
            $this->assertEquals($current['apa'], $apa);
            $tpl = $cb->getCitation('MLA');
            // Normalize whitespace:
            $mla = trim(preg_replace("/\s+/", " ", $interface->fetch($tpl)));
            $this->assertEquals($current['mla'], $mla);
        }

        // Test a couple of illegal citation formats:
        $this->assertEquals('', $cb->getCitation('Citation'));
        $this->assertEquals('', $cb->getCitation('SupportedCitationFormats'));
        $this->assertEquals('', $cb->getCitation('badgarbage'));
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
